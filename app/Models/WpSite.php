<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WpSite extends Model
{
    protected $table = 'wp_sites';

    protected $fillable = [
        'site_name',
        'site_url',
        'site_logo',
        'wp_username',
        'wp_password',
        'is_connected',
        'last_synced_at',
        'twitter_id',
        'twitter_handle',
        'twitter_avatar',
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'wp_password' => 'encrypted',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'is_connected' => 'boolean',
        'last_synced_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
