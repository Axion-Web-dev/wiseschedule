<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WpSite;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$site = WpSite::find(9);

if (!$site) {
    echo "Site ID 9 not found!\n";
    exit(1);
}

echo "Testing site: {$site->site_name}\n";
echo "URL: {$site->site_url}\n";
echo "Connected: " . ($site->is_connected ? 'Yes' : 'No') . "\n";
echo "Auto-sync: " . ($site->is_auto_sync_enabled ? 'Yes' : 'No') . "\n\n";

$url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/posts?per_page=3&_embed=1';
echo "Fetching: $url\n";

try {
    $response = Http::timeout(10)
        ->withBasicAuth($site->wp_username, $site->wp_password)
        ->get($url);

    echo "Status: {$response->status()}\n";
    
    if (!$response->successful()) {
        echo "Failed to fetch posts. Response: {$response->body()}\n";
        exit(1);
    }

    $posts = $response->json();
    echo "Posts found: " . count($posts) . "\n\n";

    if (empty($posts)) {
        echo "No posts returned from WordPress API!\n";
        exit(0);
    }

    foreach ($posts as $index => $post) {
        echo "Post " . ($index + 1) . ":\n";
        echo "  ID: {$post['id']}\n";
        echo "  Title: {$post['title']['rendered']}\n";
        echo "  Status: {$post['status']}\n";
        echo "  Date: {$post['date']}\n\n";
    }

    $latestPost = $posts[0];
    $exists = \App\Models\Post::where('wp_site_id', $site->id)
                              ->where('wp_post_id', $latestPost['id'])
                              ->exists();

    echo "Latest post (ID: {$latestPost['id']}) already in database: " . ($exists ? 'Yes' : 'No') . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    Log::error('WordPress API test failed', ['error' => $e->getMessage()]);
}