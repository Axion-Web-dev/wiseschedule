<?php

namespace App\Services;

use App\Models\AiArticle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimizedQueryService
{
    /**
     * Get articles with cursor pagination for large datasets
     */
    public static function getArticlesCursor($limit = 50, $status = null)
    {
        $query = AiArticle::with(['wpSite'])
            ->orderBy('created_at', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        return $query->cursorPaginate($limit);
    }

    /**
     * Process articles using chunk() for memory efficiency
     */
    public static function processArticlesInChunks($chunkSize = 100, $callback)
    {
        AiArticle::query()
            ->chunk($chunkSize, function ($articles) use ($callback) {
                $callback($articles);
                
                // Free memory after each chunk
                unset($articles);
                gc_collect_cycles();
            });
    }

    /**
     * Get statistics with caching
     */
    public static function getArticleStats()
    {
        return Cache::remember('article.stats', 300, function () {
            return [
                'total' => AiArticle::count(),
                'pending' => AiArticle::where('status', 'pending')->count(),
                'generating' => AiArticle::where('status', 'generating')->count(),
                'ready' => AiArticle::where('status', 'ready')->count(),
                'published' => AiArticle::where('status', 'published')->count(),
                'failed' => AiArticle::where('status', 'failed')->count(),
            ];
        });
    }

    /**
     * Bulk update with single query for performance
     */
    public static function bulkUpdateStatus(array $articleIds, string $status)
    {
        return DB::table('ai_articles')
            ->whereIn('id', $articleIds)
            ->update(['status' => $status, 'updated_at' => now()]);
    }

    /**
     * Get articles for processing with cursor to avoid timeouts
     */
    public static function getArticlesForProcessing($status = 'pending', $limit = 10)
    {
        return AiArticle::where('status', $status)
            ->with(['wpSite'])
            ->orderBy('created_at', 'asc')
            ->cursor()
            ->take($limit);
    }

    /**
     * Clear all article-related caches
     */
    public static function clearArticleCache()
    {
        Cache::forget('article.stats');
        Cache::forget('articles.recent');
        
        // Clear all cached article lists
        for ($i = 10; $i <= 100; $i += 10) {
            Cache::forget("articles.recent.{$i}");
        }
        
        // Clear status-based caches
        $statuses = ['pending', 'generating', 'ready', 'published', 'failed'];
        foreach ($statuses as $status) {
            Cache::forget("articles.status.{$status}");
        }
    }
}
