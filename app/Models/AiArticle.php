<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class AiArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'wp_site_id',
        'topic',
        'keywords',
        'tone',
        'word_count_target',
        'content_type',
        'additional_instructions',
        'title',
        'meta_title',
        'meta_description',
        'content',
        'featured_image_url',
        'reading_time',
        'status',
        'wp_post_id',
        'wp_post_url',
    ];

    protected $casts = [
        'content' => 'string',
        'featured_image_url' => 'string',
        'status' => 'string',
    ];

    /**
     * Get the WordPress site that owns the AI article.
     */
    public function wpSite(): BelongsTo
    {
        return $this->belongsTo(WpSite::class);
    }

    /**
     * Get cached articles with eager loading
     */
    public static function getCachedArticles($limit = 50)
    {
        return Cache::remember('articles.recent.' . $limit, 300, function () use ($limit) {
            return static::with(['wpSite'])
                ->latest()
                ->take($limit)
                ->get();
        });
    }

    /**
     * Get articles by status with caching
     */
    public static function getByStatus($status)
    {
        return Cache::remember("articles.status.{$status}", 180, function () use ($status) {
            return static::where('status', $status)
                ->with(['wpSite'])
                ->get();
        });
    }

    /**
     * Clear article cache when updated
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function () {
            Cache::forget('articles.recent');
            Cache::forget('articles.recent.10');
            Cache::forget('articles.recent.25');
            Cache::forget('articles.recent.50');
            Cache::forget('articles.recent.100');
        });
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'generating' => 'info',
            'ready' => 'success',
            'published' => 'primary',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Check if the article is ready for publishing.
     */
    public function isReady(): bool
    {
        return $this->status === 'ready' && !empty($this->title) && !empty($this->content);
    }

    /**
     * Check if the article can be generated.
     */
    public function canGenerate(): bool
    {
        return $this->status === 'pending' && !empty($this->topic);
    }
}
