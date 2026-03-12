<?php

namespace App\Services;

use App\Models\Post;
use App\Models\WpSite;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WordPressService
{
    private const USER_FRIENDLY_ERROR = 'Connection failed. The website is taking too long to respond. Please check your internet connection or try again later.';
    private const TIMEOUT_SECONDS = 15;
    /**
     * Make an HTTP request with proper error handling
     */
    private function makeRequest(string $method, string $url, array $credentials, array $data = []): mixed
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withBasicAuth($credentials['username'], $credentials['password'])
                ->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }

            $this->handleHttpError($response->status(), $url);
            
        } catch (ConnectionException $e) {
            $this->logError('Connection error', $e, ['url' => $url]);
            throw new Exception(self::USER_FRIENDLY_ERROR);
        } catch (RequestException $e) {
            $this->handleRequestException($e, $url);
        } catch (Exception $e) {
            $this->logError('Unexpected error', $e, ['url' => $url]);
            throw new Exception(self::USER_FRIENDLY_ERROR);
        }
    }

    /**
     * Handle HTTP request exceptions
     */
    private function handleRequestException(RequestException $e, string $url): void
    {
        $errorMessage = $e->getMessage();
        
        if (str_contains($errorMessage, 'cURL error 28') || 
            str_contains($errorMessage, 'SSL connection timeout') ||
            str_contains($errorMessage, 'Connection timed out')) {
            $this->logError('Connection timeout', $e, ['url' => $url]);
            throw new Exception(self::USER_FRIENDLY_ERROR);
        }

        $this->logError('Request failed', $e, ['url' => $url]);
        throw new Exception('An error occurred while communicating with the server. Please try again later.');
    }

    /**
     * Log errors with context
     */
    private function logError(string $message, Exception $e, array $context = []): void
    {
        Log::error("$message: " . $e->getMessage(), array_merge([
            'exception' => get_class($e),
            'trace' => $e->getTraceAsString(),
        ], $context));
    }

    /**
     * Handle HTTP status code errors
     */
    private function handleHttpError(int $statusCode, string $url): void
    {
        $message = match ($statusCode) {
            401, 403 => 'Authentication failed. Please check your credentials.',
            404 => 'The requested resource was not found.',
            500 => 'The server encountered an error. Please try again later.',
            default => 'An unexpected error occurred.',
        };

        Log::error("HTTP Error $statusCode: $message", ['url' => $url]);
        throw new Exception($message);
    }

    /**
     * Test connection to WordPress site
     */
    public function testConnection(WpSite $site): bool
    {
        try {
            // Test with a more restrictive endpoint that requires authentication
            $url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/users/me';
            $credentials = [
                'username' => $site->wp_username,
                'password' => $site->wp_password
            ];

            Log::info('Testing connection', [
                'url' => $url,
                'username' => $credentials['username'],
                'password_length' => strlen($credentials['password'])
            ]);

            $response = $this->makeRequest('get', $url, $credentials);
            
            Log::info('Connection test response', [
                'response' => $response,
                'user_email' => $response['email'] ?? 'N/A'
            ]);
            
            $site->update(['is_connected' => true]);
            return true;
            
        } catch (Exception $e) {
            Log::error('Connection test failed', [
                'error' => $e->getMessage(),
                'site_url' => $site->site_url
            ]);
            
            $site->update(['is_connected' => false]);
            throw $e;
        }
    }

    /**
     * Fetch posts from WordPress REST API
     */
    public function fetchPosts(WpSite $site): array
    {
        $url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/posts';
        $credentials = [
            'username' => $site->wp_username,
            'password' => $site->wp_password
        ];

        $response = $this->makeRequest('get', $url, $credentials, [
            'per_page' => 10,
            '_embed' => 1,
        ]);

        // Ensure we always return an array, even if response is null
        return $response ?? [];
    }

    /**
     * Get featured image URL from media ID
     */
    private function getFeaturedImageUrl(WpSite $site, int $mediaId): ?string
    {
        try {
            $url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/media/' . $mediaId;
            $credentials = [
                'username' => $site->wp_username,
                'password' => $site->wp_password
            ];

            $media = $this->makeRequest('get', $url, $credentials);
            
            return $media['source_url'] ?? null;
        } catch (Exception $e) {
            Log::warning('Failed to fetch featured image', [
                'media_id' => $mediaId,
                'site_id' => $site->id,
                'exception' => $e
            ]);
            return null;
        }
    }

    /**
     * Sync posts from WordPress to local database
     *
     * @param WpSite $site
     * @return array
     * @throws Exception
     */
    public function syncPosts(WpSite $site): array
    {
        try {
            $posts = $this->fetchPosts($site);
            $syncedCount = 0;
            $errors = [];

            foreach ($posts as $wpPost) {
                try {
                    // Extract featured image URL from _embedded data
                    $featuredImageUrl = null;
                    if (isset($wpPost['featured_media']) && $wpPost['featured_media'] > 0) {
                        // If we have featured_media ID, we need to fetch the media details
                        $featuredImageUrl = $this->getFeaturedImageUrl($site, $wpPost['featured_media']);
                    } elseif (isset($wpPost['_embedded']['wp:featuredmedia'][0]['source_url'])) {
                        // If featured media is embedded in the response
                        $featuredImageUrl = $wpPost['_embedded']['wp:featuredmedia'][0]['source_url'];
                    }

                    Post::updateOrCreate(
                        [
                            'wp_site_id' => $site->id,
                            'wp_post_id' => $wpPost['id'],
                        ],
                        [
                            'title' => $wpPost['title']['rendered'] ?? '',
                            'content' => strip_tags($wpPost['content']['rendered'] ?? ''),
                            'url' => $wpPost['link'] ?? '',
                            'status' => $wpPost['status'] ?? 'pending',
                            'featured_image_url' => $featuredImageUrl,
                        ]
                    );
                    
                    $syncedCount++;
                } catch (Exception $e) {
                    $errorMessage = "Failed to sync post {$wpPost['id']}";
                    $errors[] = $errorMessage;
                    Log::error($errorMessage, ['exception' => $e]);
                }
            }

            return [
                'synced_count' => $syncedCount,
                'errors' => $errors,
            ];
        } catch (Exception $e) {
            Log::error('Failed to fetch posts from WordPress', [
                'site_id' => $site->id,
                'exception' => $e
            ]);
            throw new Exception(self::USER_FRIENDLY_ERROR);
        }
    }

    public function updatePost(Post $post): bool
    {
        try {
            $site = $post->wpSite;
            $url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/posts/' . $post->wp_post_id;
            $credentials = [
                'username' => $site->wp_username,
                'password' => $site->wp_password
            ];

            $this->makeRequest('put', $url, $credentials, [
                'title' => $post->title,
                'content' => $post->content,
                'status' => $post->status,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to update WordPress post', [
                'post_id' => $post->id,
                'exception' => $e
            ]);
            return false;
        }
    }

    public function deletePost(Post $post): bool
    {
        try {
            $site = $post->wpSite;
            $url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/posts/' . $post->wp_post_id;
            $credentials = [
                'username' => $site->wp_username,
                'password' => $site->wp_password
            ];

            $this->makeRequest('delete', $url, $credentials);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete WordPress post', [
                'post_id' => $post->id,
                'exception' => $e
            ]);
            return false;
        }
    }

    /**
     * Fetch site logo from WordPress site
     */
    public function fetchSiteLogo(WpSite $site): ?string
    {
        try {
            $credentials = [
                'username' => $site->wp_username,
                'password' => $site->wp_password,
            ];

            // Get site info from WordPress REST API
            $url = rtrim($site->site_url, '/') . '/wp-json/wp/v2/settings';
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->withBasicAuth($credentials['username'], $credentials['password'])
                ->get($url);

            if ($response->successful()) {
                $settings = $response->json();
                
                // Try to get site logo from different possible fields
                if (!empty($settings['site_logo'])) {
                    // Get logo details
                    $logoUrl = rtrim($site->site_url, '/') . '/wp-json/wp/v2/media/' . $settings['site_logo'];
                    $logoResponse = Http::timeout(self::TIMEOUT_SECONDS)
                        ->withBasicAuth($credentials['username'], $credentials['password'])
                        ->get($logoUrl);
                    
                    if ($logoResponse->successful()) {
                        $logoData = $logoResponse->json();
                        return $logoData['source_url'] ?? null;
                    }
                }
                
                // Fallback: try to get site icon
                if (!empty($settings['site_icon'])) {
                    $iconUrl = rtrim($site->site_url, '/') . '/wp-json/wp/v2/media/' . $settings['site_icon'];
                    $iconResponse = Http::timeout(self::TIMEOUT_SECONDS)
                        ->withBasicAuth($credentials['username'], $credentials['password'])
                        ->get($iconUrl);
                    
                    if ($iconResponse->successful()) {
                        $iconData = $iconResponse->json();
                        return $iconData['source_url'] ?? null;
                    }
                }
            }

            return null;
        } catch (Exception $e) {
            Log::error('Failed to fetch WordPress site logo', [
                'site_id' => $site->id,
                'site_url' => $site->site_url,
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }
}
