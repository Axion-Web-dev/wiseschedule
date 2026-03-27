<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WpSite;
use App\Models\Post;
use App\Models\AiArticle;
use Illuminate\Console\Command;

class ShowAllUserData extends Command
{
    protected $signature = 'users:show-all {email}';
    protected $description = 'Show all data (WordPress sites, AI articles, posts) for a user';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("=== COMPLETE DATA OVERVIEW FOR: {$email} ===");

        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email {$email} not found!");
            return Command::FAILURE;
        }
        
        $this->info("User ID: {$user->id}");
        $this->info("User Name: {$user->name}");
        $this->info("User Email: {$user->email}");
        $this->info("User Created: {$user->created_at}");
        $this->info(str_repeat('=', 60));

        $wpSites = WpSite::where('user_id', $user->id)->get();
        $this->info(" WORDPRESS SITES ({$wpSites->count()})");
        $this->info(str_repeat('-', 60));
        
        foreach ($wpSites as $index => $site) {
            $status = $site->is_connected ? '✅ Connected' : '❌ Not Connected';
            $syncStatus = $site->last_synced_at ? "Last sync: {$site->last_synced_at->diffForHumans()}" : 'Never synced';
            
            $this->line("  " . ($index + 1) . ". {$site->site_name}");
            $this->line("     URL: {$site->site_url}");
            $this->line("     Status: {$status}");
            $this->line("     {$syncStatus}");
            
            if ($site->twitter_handle) {
                $this->line("     Twitter: @{$site->twitter_handle}");
            }
            $this->line("     Created: {$site->created_at}");
            $this->line('');
        }
        
        $this->info(str_repeat('=', 60));

        $aiArticles = AiArticle::where('user_id', $user->id)->get();
        $this->info("🤖 AI ARTICLES ({$aiArticles->count()})");
        $this->info(str_repeat('-', 60));
        
        foreach ($aiArticles as $index => $article) {
            $statusIcon = match($article->status) {
                'ready' => '🟢',
                'published' => '🟢', 
                'pending' => '🟡',
                'failed' => '🔴',
                'draft' => '🔴',
                default => '⚪',
            };
            
            $this->line("  " . ($index + 1) . ". {$article->topic}");
            $this->line("     Status: {$statusIcon} {$article->status}");
            $this->line("     Word Count: {$article->word_count_target}");
            $this->line("     Created: {$article->created_at}");
            
            if ($article->wp_site_id) {
                $wpSite = WpSite::find($article->wp_site_id);
                if ($wpSite) {
                    $this->line("     WordPress Site: {$wpSite->site_name}");
                }
            }
            
            if ($article->featured_image_url) {
                $this->line("     Image: ✅ Has featured image");
            } else {
                $this->line("     Image: ❌ No featured image");
            }
            
            $this->line("     Reading Time: {$article->reading_time} min");
            $this->line('');
        }
        
        $this->info(str_repeat('=', 60));

        $posts = Post::withoutGlobalScope('user_filter')->where('user_id', $user->id)->get();
        $this->info("📝 WORDPRESS POSTS ({$posts->count()})");
        $this->info(str_repeat('-', 60));
        
        foreach ($posts as $index => $post) {
            $statusIcon = match($post->status) {
                'publish' => '🟢',
                'pending' => '🟡',
                'draft' => '🔴',
                default => '⚪',
            };
            
            $this->line("  " . ($index + 1) . ". {$post->title}");
            $this->line("     Status: {$statusIcon} {$post->status}");
            $this->line("     URL: {$post->url}");
            $this->line("     Created: {$post->created_at}");
            
            if ($post->wp_site_id) {
                $wpSite = WpSite::find($post->wp_site_id);
                if ($wpSite) {
                    $this->line("     WordPress Site: {$wpSite->site_name}");
                }
            }
            
            if ($post->ai_tweet_body) {
                $this->line("     Tweet: ✅ AI tweet generated");
            } else {
                $this->line("     Tweet: ❌ No AI tweet");
            }
            
            if ($post->featured_image_url) {
                $this->line("     Image: ✅ Has featured image");
            } else {
                $this->line("     Image: ❌ No featured image");
            }
            
            $this->line('');
        }
        
        $this->info(str_repeat('=', 60));
        $this->info("📊 SUMMARY");
        $this->info("Total WordPress Sites: {$wpSites->count()}");
        $this->info("Total AI Articles: {$aiArticles->count()}");
        $this->info("Total WordPress Posts: {$posts->count()}");
        $this->info("Total Data Items: " . ($wpSites->count() + $aiArticles->count() + $posts->count()));
        
        return Command::SUCCESS;
    }
}