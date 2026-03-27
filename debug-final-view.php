<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WpSite;
use Illuminate\Support\Facades\Http;

$site = WpSite::where('site_name', 'final view')->first();

if (!$site) {
    echo "Site 'final view' not found!\n";
    exit(1);
}

echo "Testing site: {$site->site_name} (ID: {$site->id})\n";
echo "URL: {$site->site_url}\n";
echo "Username: {$site->wp_username}\n";
echo "Password: " . (empty($site->wp_password) ? 'EMPTY' : 'SET') . "\n\n";

$url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/posts?per_page=1&_embed=1';
echo "Fetching: $url\n";

try {
    $response = Http::timeout(10)
        ->withBasicAuth($site->wp_username, $site->wp_password)
        ->get($url);

    echo "Status: {$response->status()}\n";
    
    if (!$response->successful()) {
        echo "❌ Failed to fetch posts. Response: {$response->body()}\n";

        echo "\nTrying without authentication...\n";
        $publicResponse = Http::timeout(10)->get($url);
        echo "Public Status: {$publicResponse->status()}\n";
        if ($publicResponse->successful()) {
            echo "✅ Site works publicly, but credentials are wrong\n";
        } else {
            echo "❌ Site doesn't work even publicly\n";
        }
        exit(1);
    }

    $posts = $response->json();
    echo "✅ Posts found: " . count($posts) . "\n\n";

    if (empty($posts)) {
        echo "No posts returned from WordPress API!\n";
        exit(0);
    }

    $latestPost = $posts[0];
    echo "Latest post details:\n";
    echo "  ID: {$latestPost['id']}\n";
    echo "  Title: {$latestPost['title']['rendered']}\n";
    echo "  Status: {$latestPost['status']}\n";
    echo "  Date: {$latestPost['date']}\n\n";

    $exists = \App\Models\Post::where('wp_site_id', $site->id)
                              ->where('wp_post_id', $latestPost['id'])
                              ->exists();

    echo "Post already in database: " . ($exists ? '✅ Yes' : '❌ No') . "\n";

    if (!$exists) {
        echo "🎉 This post SHOULD be imported!\n";

        $featuredImageUrl = null;
        if (isset($latestPost['_embedded']['wp:featuredmedia'][0]['source_url'])) {
            $featuredImageUrl = $latestPost['_embedded']['wp:featuredmedia'][0]['source_url'];
        }

        $newPost = \App\Models\Post::create([
            'wp_site_id' => $site->id,
            'wp_post_id' => $latestPost['id'],
            'title' => $latestPost['title']['rendered'] ?? '',
            'content' => strip_tags($latestPost['content']['rendered'] ?? ''),
            'url' => $latestPost['link'] ?? '',
            'status' => $latestPost['status'] ?? 'pending',
            'featured_image_url' => $featuredImageUrl,
            'user_id' => $site->user_id,
        ]);

        echo "✅ Manually imported post with local ID: {$newPost->id}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}