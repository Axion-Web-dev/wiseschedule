<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $fillable = [
        'wp_site_id',
        'wp_post_id',
        'title',
        'content',
        'url',
        'status',
        'ai_tweet_body',
        'featured_image_url',
    ];

    public function wpSite(): BelongsTo
    {
        return $this->belongsTo(WpSite::class);
    }
}
