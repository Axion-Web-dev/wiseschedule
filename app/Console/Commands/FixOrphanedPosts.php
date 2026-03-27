<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\WpSite;
use Illuminate\Console\Command;

class FixOrphanedPosts extends Command
{
    protected $signature = 'posts:fix-orphaned {user_id}';
    protected $description = 'Fix orphaned posts by assigning them to a specific user';

    public function handle()
    {
        $targetUserId = $this->argument('user_id');
        
        $this->info("Fixing orphaned posts for user ID: {$targetUserId}");

        $orphanedPosts = Post::whereNull('user_id')->get();
        $fixedCount = 0;
        
        foreach ($orphanedPosts as $post) {
            $post->update(['user_id' => $targetUserId]);
            $fixedCount++;
            $this->line("Fixed post ID {$post->id}: '{$post->title}' -> User ID {$targetUserId}");
        }
        
        $this->info("Successfully fixed {$fixedCount} orphaned posts!");

        $totalPosts = Post::count();
        $postsWithUser = Post::whereNotNull('user_id')->count();
        
        $this->info("Summary: {$postsWithUser}/{$totalPosts} posts now have user_id assigned");
        
        return Command::SUCCESS;
    }
}