<?php

namespace App\Jobs;

use App\Models\AiArticle;
use App\Services\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefineArticleContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $retryAfter = 60;

    public int $maxExceptions = 3;

    public function __construct(
        public AiArticle $article,
        public string $userInstruction = '',
        public string $refinementType = 'custom'
    ) {}

    public function handle(AiService $aiService): void
    {
        try {

            $this->article->update(['status' => 'refining']);

            $refinedContent = $aiService->refineArticle(
                $this->article,
                $this->userInstruction,
                $this->refinementType
            );

            $this->article->update([
                'title' => $refinedContent['title'],
                'content' => $refinedContent['content'],
                'meta_title' => $refinedContent['meta_title'],
                'meta_description' => $refinedContent['meta_description'],
                'status' => 'ready',
            ]);

            Log::info('Article refined successfully', [
                'article_id' => $this->article->id,
                'refinement_type' => $this->refinementType,
                'user_instruction' => $this->userInstruction
            ]);

        } catch (\Exception $e) {
            Log::error('Article refinement failed', [
                'article_id' => $this->article->id,
                'error' => $e->getMessage(),
                'refinement_type' => $this->refinementType,
                'user_instruction' => $this->userInstruction
            ]);

            $this->article->update(['status' => 'failed']);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Article refinement job permanently failed after all retries', [
            'article_id' => $this->article->id,
            'error' => $exception->getMessage(),
            'refinement_type' => $this->refinementType,
            'user_instruction' => $this->userInstruction,
            'attempts' => $this->attempts()
        ]);

        $this->article->update(['status' => 'failed']);
    }
}