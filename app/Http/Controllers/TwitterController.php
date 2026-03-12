<?php

namespace App\Http\Controllers;

use App\Models\WpSite;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class TwitterController extends Controller
{
    /**
     * Redirect to Twitter OAuth
     */
    // public function redirect(Request $request)
    // {
    //     $blogSiteId = $request->query('blog_site_id');
        
    //     if (!$blogSiteId) {
    //         return redirect()->back()->with('error', 'Blog site ID is required.');
    //     }
        
    //     // Verify the blog site exists
    //     $blogSite = WpSite::find($blogSiteId);
    //     if (!$blogSite) {
    //         return redirect()->back()->with('error', 'Blog site not found.');
    //     }
        
    //     // Store the blog site ID in session to retrieve in callback
    //     session(['twitter_blog_site_id' => $blogSiteId]);
        
    //     return Socialite::driver('twitter')
    //         ->redirect();
    // }
    
public function redirect(Request $request)
{
    \Log::info('Twitter OAuth redirect started', [
        'request_data' => $request->all(),
        'blog_site_id' => $request->query('blog_site_id'),
        'timestamp' => now()->toISOString(),
        'user_agent' => $request->userAgent(),
        'ip_address' => $request->ip()
    ]);

    try {
        // Get the ID of the blog site we are connecting
        $blogSiteId = $request->query('blog_site_id');

        if (!$blogSiteId) {
            \Log::error('Twitter OAuth redirect: Blog site ID is missing', [
                'request_data' => $request->all(),
                'query_params' => $request->query(),
                'headers' => $request->headers->all()
            ]);
            return redirect()->back()->with('error', 'Blog site ID is required.');
        }

        // Validate blog site exists
        $blogSite = \App\Models\WpSite::find($blogSiteId);
        if (!$blogSite) {
            \Log::error('Twitter OAuth redirect: Blog site not found', [
                'blog_site_id' => $blogSiteId,
                'request_data' => $request->all()
            ]);
            return redirect()->back()->with('error', 'Blog site not found.');
        }

        // Store blog site ID in session
        session(['twitter_oauth_blog_site_id' => $blogSiteId]);

        \Log::info('Twitter OAuth redirect: Stored blog site ID in session', [
            'blog_site_id' => $blogSiteId,
            'blog_site_name' => $blogSite->name ?? null,
            'redirect_uri' => config('services.twitter-oauth2.redirect')
        ]);

        // Generate authorization URL
        $socialite = Socialite::driver('twitter-oauth2');
        $authorizationUrl = $socialite
            ->scopes(['tweet.read', 'tweet.write', 'users.read', 'offline.access'])
            ->redirect();

        \Log::info('Twitter OAuth redirect: Successfully generated authorization URL', [
            'blog_site_id' => $blogSiteId,
            'authorization_url_generated' => true
        ]);

        return $authorizationUrl;

    } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
        \Log::error('Twitter OAuth redirect: Invalid state exception', [
            'exception_message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'stack_trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
            'blog_site_id' => $request->query('blog_site_id')
        ]);
        
        return redirect()->back()->with('error', 'Invalid OAuth state. Please try again.');
        
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $response = $e->getResponse();
        \Log::error('Twitter OAuth redirect: HTTP client exception', [
            'exception_message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'stack_trace' => $e->getTraceAsString(),
            'status_code' => $response?->getStatusCode(),
            'response_body' => $response?->getBody()->getContents(),
            'request_data' => $request->all(),
            'blog_site_id' => $request->query('blog_site_id')
        ]);
        
        return redirect()->back()->with('error', 'Twitter API error: ' . ($response?->getStatusCode() ?? 'Unknown'));
        
    } catch (\Exception $e) {
        \Log::error('Twitter OAuth redirect: General exception occurred', [
            'exception_message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'stack_trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
            'blog_site_id' => $request->query('blog_site_id'),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        return redirect()->back()->with('error', 'Failed to redirect to Twitter: ' . $e->getMessage());
    }
}

    /**
     * Handle Twitter OAuth callback
     */
    public function callback(Request $request)
{
    \Log::info('Twitter OAuth callback started - DEBUG', [
        'request_method' => $request->method(),
        'full_url' => $request->fullUrl(),
        'all_request_data' => $request->all(),
        'headers' => $request->headers->all(),
        'session_data' => session()->all(),
        'state' => $request->input('state'),
        'code' => $request->has('code') ? 'present' : 'missing',
        'error' => $request->input('error'),
        'error_description' => $request->input('error_description'),
        'timestamp' => now()->toISOString(),
        'user_agent' => $request->userAgent(),
        'ip_address' => $request->ip()
    ]);

    try {
        // Check for OAuth errors from Twitter
        if ($request->has('error')) {
            \Log::error('Twitter OAuth callback: OAuth error returned from Twitter', [
                'error' => $request->input('error'),
                'error_description' => $request->input('error_description'),
                'state' => $request->input('state'),
                'full_request_data' => $request->all()
            ]);
            
            $errorMessage = $request->input('error_description') ?? $request->input('error');
            return redirect()->route('filament.admin.resources.wp-sites.index')
                ->with('error', 'Twitter OAuth error: ' . $errorMessage);
        }

        // 1. Try to get blog site ID from session
        $blogSiteId = session('twitter_oauth_blog_site_id');

        // 2. If session failed, use fallback
        if (!$blogSiteId) {
            \Log::warning('Session lost! Using fallback mechanism.', [
                'state' => $request->input('state'),
                'available_session_keys' => array_keys(session()->all())
            ]);
            
            // Try to get most recent site as fallback
            $blogSiteId = \App\Models\WpSite::latest()->first()?->id;
            \Log::info('Using fallback: most recent site ID', ['blog_site_id' => $blogSiteId]);
        }

        if (!$blogSiteId) {
            \Log::error('Twitter OAuth callback: Blog site ID missing from session', [
                'state' => $request->input('state'),
                'session_data' => session()->all()
            ]);
            return redirect()->route('filament.admin.resources.wp-sites.index')
                ->with('error', 'Invalid session state. Please try connecting again.');
        }

        // Validate authorization code
        $authorizationCode = $request->input('code');
        if (!$authorizationCode) {
            \Log::error('Twitter OAuth callback: Authorization code is missing', [
                'blog_site_id' => $blogSiteId,
                'request_data' => $request->all()
            ]);
            return redirect()->route('filament.admin.resources.wp-sites.index')
                ->with('error', 'Authorization code not received from Twitter.');
        }

        \Log::info('Twitter OAuth callback: Attempting to get user from Twitter', [
            'blog_site_id' => $blogSiteId,
            'driver' => 'twitter-oauth2',
            'has_authorization_code' => !empty($authorizationCode),
            'code_length' => strlen($authorizationCode)
        ]);
            
        // Get the user from Twitter
        $user = Socialite::driver('twitter-oauth2')->user();
        
        \Log::info('Twitter OAuth callback: Successfully got user from Twitter', [
            'blog_site_id' => $blogSiteId,
            'user_id' => $user->getId(),
            'nickname' => $user->getNickname(),
            'name' => $user->getName(),
            'token_present' => !empty($user->token),
            'refresh_token_present' => !empty($user->refreshToken),
            'expires_in' => $user->expiresIn,
            'raw_user_data' => $user->getRaw()
        ]);
            
        // Find the blog site
        $blogSite = \App\Models\WpSite::find($blogSiteId);
        if (!$blogSite) {
            \Log::error('Twitter OAuth callback: Blog site not found', [
                'blog_site_id' => $blogSiteId,
                'twitter_user_id' => $user->getId(),
                'twitter_handle' => $user->getNickname()
            ]);
            
            // Clear session data
            session()->forget('twitter_oauth_blog_site_id');
            
            return redirect()->route('filament.admin.resources.wp-sites.index')
                ->with('error', 'Blog site not found for the given OAuth state.');
        }

        \Log::info('Twitter OAuth callback: Updating blog site with Twitter data', [
            'blog_site_id' => $blogSiteId,
            'blog_site_name' => $blogSite->name ?? null,
            'twitter_id' => $user->getId(),
            'twitter_handle' => $user->getNickname(),
            'has_existing_twitter' => !empty($blogSite->twitter_id)
        ]);
        
        // Download and store Twitter avatar locally
        $avatarUrl = $user->getAvatar();
        $storedAvatarPath = null;
        
        if ($avatarUrl) {
            try {
                // Get higher quality image (remove _normal suffix)
                $highQualityAvatarUrl = str_replace('_normal', '', $avatarUrl);
                
                // Download the avatar
                $response = Http::timeout(10)->get($highQualityAvatarUrl);
                
                if ($response->successful()) {
                    // Create directory if it doesn't exist
                    $filename = 'twitter_avatars/' . $blogSiteId . '.jpg';
                    
                    // Store the avatar locally
                    Storage::disk('public')->put($filename, $response->body());
                    $storedAvatarPath = $filename;
                    
                    \Log::info('Twitter avatar downloaded and stored locally', [
                        'blog_site_id' => $blogSiteId,
                        'original_url' => $avatarUrl,
                        'high_quality_url' => $highQualityAvatarUrl,
                        'stored_path' => $storedAvatarPath,
                        'file_size' => strlen($response->body())
                    ]);
                } else {
                    \Log::warning('Failed to download Twitter avatar', [
                        'blog_site_id' => $blogSiteId,
                        'avatar_url' => $highQualityAvatarUrl,
                        'status' => $response->status()
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Exception while downloading Twitter avatar', [
                    'blog_site_id' => $blogSiteId,
                    'avatar_url' => $avatarUrl,
                    'error' => $e->getMessage()
                ]);
            }
        }
            
        // Save Twitter OAuth data to the blog site
        $blogSite->update([
            'twitter_id' => $user->getId(),
            'twitter_handle' => $user->getNickname(),
            'twitter_avatar' => $storedAvatarPath,
            'access_token' => $user->token,
            'refresh_token' => $user->refreshToken,
            'expires_at' => now()->addSeconds($user->expiresIn),
            'is_connected' => true,
        ]);

        \Log::info('Twitter OAuth callback: Successfully connected Twitter account', [
            'blog_site_id' => $blogSiteId,
            'blog_site_name' => $blogSite->name ?? null,
            'twitter_handle' => $user->getNickname(),
            'twitter_id' => $user->getId(),
            'token_expires_at' => now()->addSeconds($user->expiresIn)->toISOString(),
            'is_connected' => true,
        ]);

        // Clear session data after successful connection
        session()->forget('twitter_oauth_blog_site_id');
            
        return redirect()->route('filament.admin.resources.wp-sites.edit', ['record' => $blogSiteId])
            ->with('success', 'X (Twitter) account connected successfully!');

    } catch (\Laravel\Socialite\Two\InvalidStateException $e) {
        \Log::error('Twitter OAuth callback: Invalid state exception', [
            'exception_message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'stack_trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
            'blog_site_id' => session('twitter_oauth_blog_site_id')
        ]);
        
        // Clear session data
        session()->forget('twitter_oauth_blog_site_id');
        
        return redirect()->route('filament.admin.resources.wp-sites.index')
            ->with('error', 'Invalid OAuth state. Please try connecting again.');
            
    } catch (\Laravel\Socialite\Two\InvalidResponseException $e) {
        \Log::error('Twitter OAuth callback: Invalid response exception', [
            'exception_message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'stack_trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
            'blog_site_id' => session('twitter_oauth_blog_site_id')
        ]);
        
        // Clear session data
        session()->forget('twitter_oauth_blog_site_id');
        
        return redirect()->route('filament.admin.resources.wp-sites.index')
            ->with('error', 'Invalid response from Twitter. Please try again.');
            
    } catch (\GuzzleHttp\Exception\ClientException $e) {
        $response = $e->getResponse();
        \Log::error('Twitter OAuth callback: HTTP client exception', [
            'exception_message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'stack_trace' => $e->getTraceAsString(),
            'status_code' => $response?->getStatusCode(),
            'response_body' => $response?->getBody()->getContents(),
            'request_data' => $request->all(),
            'blog_site_id' => session('twitter_oauth_blog_site_id')
        ]);
        
        // Clear session data
        session()->forget('twitter_oauth_blog_site_id');
        
        $statusCode = $response?->getStatusCode();
        $errorMessage = 'Twitter API error';
        
        if ($statusCode === 400) {
            $errorMessage = 'Invalid request to Twitter API';
        } elseif ($statusCode === 401) {
            $errorMessage = 'Authentication failed with Twitter';
        } elseif ($statusCode === 403) {
            $errorMessage = 'Access forbidden by Twitter API';
        }
        
        return redirect()->route('filament.admin.resources.wp-sites.index')
            ->with('error', $errorMessage . ' (HTTP ' . $statusCode . ')');
            
    } catch (\Exception $e) {
        \Log::error('Twitter OAuth callback: General exception occurred', [
            'exception_message' => $e->getMessage(),
            'exception_class' => get_class($e),
            'stack_trace' => $e->getTraceAsString(),
            'request_data' => $request->all(),
            'blog_site_id' => session('twitter_oauth_blog_site_id'),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        
        // Clear session data
        session()->forget('twitter_oauth_blog_site_id');
        
        return redirect()->route('filament.admin.resources.wp-sites.index')
            ->with('error', 'Failed to connect X (Twitter) account: ' . $e->getMessage());
    }
}
    
    /**
     * Disconnect Twitter account
     */
    public function disconnect(Request $request)
{
    \Log::info('Twitter OAuth disconnect started', [
        'request_data' => $request->all(),
        'blog_site_id' => $request->query('blog_site_id')
    ]);

    $blogSiteId = $request->query('blog_site_id');
        
    if (!$blogSiteId) {
        \Log::error('Twitter OAuth disconnect: Blog site ID is missing');
        return redirect()->back()->with('error', 'Blog site ID is required.');
    }
        
    $blogSite = WpSite::find($blogSiteId);
    if (!$blogSite) {
        \Log::error('Twitter OAuth disconnect: Blog site not found', [
            'blog_site_id' => $blogSiteId
        ]);
        return redirect()->back()->with('error', 'Blog site not found.');
    }

    \Log::info('Twitter OAuth disconnect: Clearing Twitter data from blog site', [
        'blog_site_id' => $blogSiteId,
        'current_twitter_handle' => $blogSite->twitter_handle
    ]);
        
    // Clear Twitter OAuth data
    $blogSite->update([
        'twitter_id' => null,
        'twitter_handle' => null,
        'twitter_avatar' => null,
        'access_token' => null,
        'refresh_token' => null,
        'expires_at' => null,
        'is_connected' => false,
    ]);

    \Log::info('Twitter OAuth disconnect: Successfully disconnected Twitter account', [
        'blog_site_id' => $blogSiteId
    ]);
        
    return redirect()->back()->with('success', 'X (Twitter) account disconnected successfully.');
}

/**
 * Get valid access token, refreshing if necessary
 */
public function getValidToken($blogSite)
{
    // Check if token is expired (with a 5-minute buffer)
    if (now()->addMinutes(5)->gte($blogSite->expires_at)) {
        
        \Log::info('Twitter OAuth: Token expired, refreshing', [
            'blog_site_id' => $blogSite->id,
            'expires_at' => $blogSite->expires_at,
            'now' => now()
        ]);
        
        $provider = Socialite::driver('twitter-oauth2');
        
        // Call the refresh method we just created
        $response = $provider->refreshAccessToken($blogSite->refresh_token);

        \Log::info('Twitter OAuth: Token refreshed successfully', [
            'blog_site_id' => $blogSite->id,
            'has_new_access_token' => !empty($response['access_token']),
            'has_new_refresh_token' => !empty($response['refresh_token']),
            'expires_in' => $response['expires_in'] ?? null
        ]);

        // Update database with NEW tokens
        $blogSite->update([
            'access_token'  => $response['access_token'],
            'refresh_token' => $response['refresh_token'], // Twitter rotates this!
            'expires_at'    => now()->addSeconds($response['expires_in']),
        ]);
        
        return $response['access_token'];
    }

    return $blogSite->access_token;
}
}
