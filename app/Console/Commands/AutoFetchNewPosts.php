<?php

namespace App\Console\Commands;

use App\Models\WpSite;
use App\Models\Post;
use App\Services\WordPressService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutoFetchNewPosts extends Command
{
    protected $signature = 'app:auto-fetch-new-posts';
    protected $description = 'Auto-fetch only new posts from WordPress sites (checks latest post only)';
    protected $wordPressService;

    public function __construct(WordPressService $wordPressService)
    {
        parent::__construct();
        $this->wordPressService = $wordPressService;
    }

    public function handle()
    {
        $this->info('🔍 Starting auto-fetch for new WordPress posts...');

        $sites = WpSite::where('is_connected', true)
                       ->where('is_auto_sync_enabled', true)
                       ->get();
        
        $totalNewPosts = 0;
        $totalSites = $sites->count();

        if ($totalSites === 0) {
            $this->info('ℹ️  No sites with auto-sync enabled found.');
            return Command::SUCCESS;
        }

        $this->info("Checking {$totalSites} sites with auto-sync enabled for new posts...");

        foreach ($sites as $site) {
            try {
                $this->line("🔍 Checking site: {$site->site_name} ({$site->site_url})");

                $newPost = $this->getLatestPostIfNew($site);
                
                if ($newPost) {
                    $totalNewPosts++;
                    $this->info("✅ Found new post: '{$newPost['title']}' for {$site->site_name}");

                } else {
                    $this->line("ℹ️  No new posts found for {$site->site_name}");
                }
                
            } catch (\Exception $e) {
                Log::error("Failed to auto-fetch posts for site {$site->id}", [
                    'site_id' => $site->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                $this->error("❌ Error checking {$site->site_name}: " . $e->getMessage());
            }
        }

        $this->info("🎉 Auto-fetch completed! Found {$totalNewPosts} new posts across {$totalSites} sites.");
        return Command::SUCCESS;
    }

    private function getLatestPostIfNew(WpSite $site): ?array
    {

        $url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/posts?per_page=1&_embed=1';
        $credentials = [
            'username' => $site->wp_username,
            'password' => $site->wp_password
        ];

        try {
            $response = Http::timeout(10)
                ->withBasicAuth($credentials['username'], $credentials['password'])
                ->get($url);

            if (!$response->successful()) {
                Log::warning('Failed to fetch latest post', [
                    'site_id' => $site->id,
                    'status' => $response->status()
                ]);
                return null;
            }

            $posts = $response->json();

            if (empty($posts) || !is_array($posts)) {
                Log::info('No posts found during auto-fetch', [
                    'site_id' => $site->id,
                    'site_url' => $site->site_url,
                    'message' => 'Site has zero published posts'
                ]);
                return null;
            }

            $latestWpPost = $posts[0] ?? null;

            if (!$latestWpPost) {
                Log::info('No valid post data found', [
                    'site_id' => $site->id,
                    'message' => 'Posts array was empty or invalid'
                ]);
                return null;
            }

            $exists = Post::where('wp_site_id', $site->id)
                          ->where('wp_post_id', $latestWpPost['id'])
                          ->exists();

            if ($exists) {
                return null; // Post already exists
            }

            $featuredImageUrl = null;
            if (isset($latestWpPost['_embedded']['wp:featuredmedia'][0]['source_url'])) {
                $featuredImageUrl = $latestWpPost['_embedded']['wp:featuredmedia'][0]['source_url'];
            }

            $newPost = Post::create([
                'wp_site_id' => $site->id,
                'wp_post_id' => $latestWpPost['id'],
                'title' => $latestWpPost['title']['rendered'] ?? '',
                'content' => strip_tags($latestWpPost['content']['rendered'] ?? ''),
                'url' => $latestWpPost['link'] ?? '',
                'status' => $latestWpPost['status'] ?? 'pending',
                'featured_image_url' => $featuredImageUrl,
                'user_id' => $site->user_id,
            ]);

            Log::info('Auto-fetched new post', [
                'site_id' => $site->id,
                'wp_post_id' => $latestWpPost['id'],
                'title' => $latestWpPost['title']['rendered'] ?? 'Untitled'
            ]);

            return [
                'id' => $newPost->id,
                'wp_post_id' => $latestWpPost['id'],
                'title' => $latestWpPost['title']['rendered'] ?? 'Untitled',
                'url' => $latestWpPost['link'] ?? '',
                'content' => strip_tags($latestWpPost['content']['rendered'] ?? ''),
            ];

        } catch (\Exception $e) {
            Log::error('Exception in getLatestPostIfNew', [
                'site_id' => $site->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}