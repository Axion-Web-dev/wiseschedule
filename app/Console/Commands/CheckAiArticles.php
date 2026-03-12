<?php

namespace App\Console\Commands;

use App\Models\AiArticle;
use Illuminate\Console\Command;

class CheckAiArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-ai-articles';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check AI articles and their status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $articles = AiArticle::all();
        
        $this->info("Total AI Articles: " . $articles->count());
        
        foreach ($articles as $article) {
            $this->line("ID: {$article->id}");
            $this->line("  Topic: {$article->topic}");
            $this->line("  Status: {$article->status}");
            $this->line("  Title: " . ($article->title ?? 'NULL'));
            $this->line("  Content: " . (strlen($article->content ?? '') > 0 ? 'EXISTS (' . strlen($article->content) . ' chars)' : 'NULL'));
            $this->line("  Image: " . ($article->featured_image_url ?? 'NULL'));
            $this->line("  Reading Time: " . ($article->reading_time ?? 'NULL'));
            $this->line("  WP Post ID: " . ($article->wp_post_id ?? 'NULL'));
            $this->line("  WP Post URL: " . ($article->wp_post_url ?? 'NULL'));
            $this->line("  ---");
        }
        
        return Command::SUCCESS;
    }
}
