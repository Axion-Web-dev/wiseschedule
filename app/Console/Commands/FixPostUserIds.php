<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Models\WpSite;
use Illuminate\Console\Command;

class FixPostUserIds extends Command
{
    protected $signature = 'posts:fix-user-ids';
    protected $description = 'Fix user_id for existing posts by assigning them from their WordPress sites';

    public function handle()
    {
        $this->info('Fixing user_id for existing posts...');
        
        $postsWithoutUser = Post::whereNull('user_id')->get();
        $fixedCount = 0;
        
        foreach ($postsWithoutUser as $post) {
            if ($post->wpSite && $post->wpSite->user_id) {
                $post->update(['user_id' => $post->wpSite->user_id]);
                $fixedCount++;
                $this->line("Fixed post ID {$post->id}: '{$post->title}' -> User ID {$post->wpSite->user_id}");
            } else {
                $this->warn("Skipping post ID {$post->id}: No associated site or site has no user_id");
            }
        }
        
        $this->info("Successfully fixed {$fixedCount} posts!");

        $totalPosts = Post::count();
        $postsWithUser = Post::whereNotNull('user_id')->count();
        
        $this->info("Summary: {$postsWithUser}/{$totalPosts} posts now have user_id assigned");
        
        return Command::SUCCESS;
    }
}