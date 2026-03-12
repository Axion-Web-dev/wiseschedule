<?php

namespace App\Jobs;

use App\Models\AiArticle;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ProcessBulkArticles implements ShouldQueue
{
    use Batchable, Queueable;

    public function __construct(
        public Collection $articles,
        public string $action
    ) {}

    public function handle(): void
    {
        try {
            // Process articles in chunks to prevent memory issues
            $this->articles->chunk(50, function ($chunk) {
                foreach ($chunk as $article) {
                    match($this->action) {
                        'generate' => $this->generateArticle($article),
                        'publish' => $this->publishArticle($article),
                        'generate_image' => $this->generateImage($article),
                        default => Log::warning("Unknown bulk action: {$this->action}")
                    };
                }
            });

            Log::info("Bulk processing completed", [
                'action' => $this->action,
                'total_articles' => $this->articles->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Bulk processing failed", [
                'action' => $this->action,
                'error' => $e->getMessage(),
                'articles_count' => $this->articles->count()
            ]);
            throw $e;
        }
    }

    private function generateArticle(AiArticle $article): void
    {
        if ($article->canGenerate()) {
            GenerateAiArticle::dispatch($article);
        }
    }

    private function publishArticle(AiArticle $article): void
    {
        if ($article->isReady()) {
            PublishToWordPress::dispatch($article);
        }
    }

    private function generateImage(AiArticle $article): void
    {
        if (in_array($article->status, ['ready', 'failed'])) {
            GenerateGeminiImageJob::dispatch($article);
        }
    }
}
