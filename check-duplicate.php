<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking for existing post ID 6...\n";

$existing = \App\Models\Post::where('wp_post_id', 6)->first();

if ($existing) {
    echo "✅ Found existing post ID 6:\n";
    echo "  Local ID: {$existing->id}\n";
    echo "  Site ID: {$existing->wp_site_id}\n";
    echo "  Site Name: " . \App\Models\WpSite::find($existing->wp_site_id)->site_name . "\n";
    echo "  Title: {$existing->title}\n";
    echo "  Created: {$existing->created_at}\n";
} else {
    echo "❌ No existing post with ID 6 found\n";
}

echo "\nAll posts with ID 6 across all sites:\n";
$allPosts = \App\Models\Post::where('wp_post_id', 6)->get();
foreach ($allPosts as $post) {
    echo "- Site {$post->wp_site_id}: {$post->title}\n";
}