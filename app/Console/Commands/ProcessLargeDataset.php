<?php

namespace App\Console\Commands;

use App\Models\AiArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessLargeDataset extends Command
{
    protected $signature = 'articles:process-large {--action=generate} {--chunk=100}';
    protected $description = 'Process large datasets using chunking to prevent timeouts';

    public function handle()
    {
        $action = $this->option('action');
        $chunkSize = $this->option('chunk');

        $this->info("Starting bulk processing: {$action} with chunk size: {$chunkSize}");

        try {
            // Use chunk() for memory efficiency
            AiArticle::where('status', 'pending')
                ->chunk($chunkSize, function ($articles) use ($action) {
                    $this->line("Processing chunk of " . $articles->count() . " articles");

                    foreach ($articles as $article) {
                        match($action) {
                            'generate' => $this->generateArticle($article),
                            'publish' => $this->publishArticle($article),
                            default => $this->error("Unknown action: {$action}")
                        };
                    }

                    // Free memory
                    unset($articles);
                    gc_collect_cycles();
                });

            $this->info("Bulk processing completed successfully!");

        } catch (\Exception $e) {
            $this->error("Processing failed: " . $e->getMessage());
            Log::error("Bulk processing failed", [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function generateArticle($article)
    {
        if ($article->canGenerate()) {
            $this->line("Generating article: {$article->id}");
            // Dispatch job instead of processing synchronously
            \App\Jobs\GenerateAiArticle::dispatch($article);
        }
    }

    private function publishArticle($article)
    {
        if ($article->isReady()) {
            $this->line("Publishing article: {$article->id}");
            // Dispatch job instead of processing synchronously
            \App\Jobs\PublishToWordPress::dispatch($article);
        }
    }
}
