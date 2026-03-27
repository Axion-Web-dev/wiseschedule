<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WpSite;
use Illuminate\Support\Facades\Http;

$sites = WpSite::where('is_connected', true)
               ->where('is_auto_sync_enabled', true)
               ->get();

echo "Checking " . $sites->count() . " sites with auto-sync enabled...\n\n";

foreach ($sites as $site) {
    echo "=== Site: {$site->site_name} ===\n";
    echo "URL: {$site->site_url}\n";
    
    try {
        $url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/posts?per_page=1&_embed=1';
        $response = Http::timeout(10)
            ->withBasicAuth($site->wp_username, $site->wp_password)
            ->get($url);

        if (!$response->successful()) {
            echo "❌ API Error: {$response->status()}\n\n";
            continue;
        }

        $posts = $response->json();
        
        if (empty($posts)) {
            echo "ℹ️ No posts found on this site\n\n";
            continue;
        }

        $latestPost = $posts[0];
        echo "Latest WP Post ID: {$latestPost['id']}\n";
        echo "Title: {$latestPost['title']['rendered']}\n";
        
        $exists = \App\Models\Post::where('wp_site_id', $site->id)
                                  ->where('wp_post_id', $latestPost['id'])
                                  ->exists();
        
        echo "Already synced: " . ($exists ? '✅ Yes' : '❌ No') . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "=== Summary ===\n";
echo "Total posts in database: " . \App\Models\Post::count() . "\n";
echo "Sites checked: " . $sites->count() . "\n";