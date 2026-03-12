<?php

namespace App\Jobs;
use Exception;
use App\Models\AiArticle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PublishToWordPress implements ShouldQueue
{
    use Queueable;

    public function __construct(public AiArticle $article) {}

    public function handle(): void
    {
        try {
            $site = $this->article->wpSite; // Assuming relationship exists

            if (!$site) {
                throw new \Exception('No WordPress site associated with this article');
            }

            // Update status to publishing
            $this->article->update(['status' => 'publishing']);

            // Validate article before publishing
            if (!$this->article->title) {
                throw new \Exception('Article title is empty. Cannot publish to WordPress.');
            }
            if (!$this->article->content) {
                throw new \Exception('Article content is empty. Cannot publish to WordPress.');
            }
            
            // Clean the content before publishing
            $cleanContent = $this->cleanContent($this->article->content);

            // Upload featured image to WordPress if exists
            $featuredImageId = null;
            if ($this->article->featured_image_url) {
                $featuredImageId = $this->uploadFeaturedImage($site, $this->article->featured_image_url);
            }

            // First, get existing tags or create them
            $tagIds = $this->getOrCreateTags($site, $this->article->keywords);

            // Prepare post data with explicit Rank Math / SureRank meta fields
            $postData = [
                'title'   => $this->article->title,
                'content' => $cleanContent,
                'status'  => 'publish',
                
                // ADD THIS LINE: This fills the "Post Excerpt" variable in SureRank
                'excerpt' => $this->article->meta_description, 

                'categories' => [],
                'tags' => $tagIds,
                
                // SureRank specific keys - covers every possible way SureRank looks for data
                'meta' => [
                    // 1. The main SureRank UI keys (Public)
                    'surerank_seo_title'        => $this->article->meta_title,
                    'surerank_seo_description'  => $this->article->meta_description,

                    // 2. The "Hidden" keys (These are usually what website actually displays)
                    '_surerank_seo_title'       => $this->article->meta_title,
                    '_surerank_seo_description' => $this->article->meta_description,

                    // 3. The "Legacy" keys (In case SureRank is looking for old SEO data)
                    '_yoast_wpseo_title'        => $this->article->meta_title,
                    'seo_title'                 => $this->article->meta_title,
                ],
            ];

            // Add featured image if uploaded successfully
            if ($featuredImageId) {
                $postData['featured_media'] = $featuredImageId;
            }

            // WordPress uses Basic Auth for Application Passwords: username:app_password
            Log::info('=== WORDPRESS REQUEST DEBUG ===', [
                'article_id' => $this->article->id,
                'wp_url' => "{$site->site_url}/wp-json/wp/v2/posts",
                'title' => $this->article->meta_title,
                'excerpt' => $this->article->meta_description,
                'meta_title' => $this->article->meta_title,
                'meta_description' => $this->article->meta_description,
                'keywords' => $this->article->keywords,
                'meta_keys_being_sent' => $postData['meta'] ?? [],
                'tag_ids' => $tagIds,
                'featured_image_id' => $featuredImageId,
                'full_post_data' => $postData
            ]);
            
            $response = Http::withBasicAuth($site->wp_username, $site->wp_password)
                ->timeout(120) // Increased from 60 to 120 seconds
                ->post("{$site->site_url}/wp-json/wp/v2/posts", $postData);

            Log::info('=== WORDPRESS RESPONSE DEBUG ===', [
                'article_id' => $this->article->id,
                'response_status' => $response->status(),
                'response_successful' => $response->successful(),
                'response_body' => $response->body(),
                'response_headers' => $response->headers(),
                'wp_post_id' => $response->json('id'),
                'wp_post_url' => $response->json('link')
            ]);

            if ($response->successful()) {
                $wpPostId = $response->json('id');
                $wpPostUrl = $response->json('link');
                
                Log::info('=== SURERANK META VERIFICATION ===', [
                    'article_id' => $this->article->id,
                    'wp_post_id' => $wpPostId,
                    'checking_meta_fields' => true
                ]);
                
                // Check if SureRank stored our meta data
                $metaCheckResponse = Http::withBasicAuth($site->wp_username, $site->wp_password)
                    ->timeout(60) // Increased timeout for meta verification
                    ->get("{$site->site_url}/wp-json/wp/v2/posts/{$wpPostId}");
                
                if ($metaCheckResponse->successful()) {
                    $postMeta = $metaCheckResponse->json('meta', []);
                    Log::info('=== SURERANK META RESPONSE ===', [
                        'article_id' => $this->article->id,
                        'wp_post_id' => $wpPostId,
                        'all_meta_fields' => $postMeta,
                        'surerank_seo_title_found' => $postMeta['surerank_seo_title'] ?? 'NOT_FOUND',
                        'surerank_seo_description_found' => $postMeta['surerank_seo_description'] ?? 'NOT_FOUND',
                        '_surerank_seo_title_found' => $postMeta['_surerank_seo_title'] ?? 'NOT_FOUND',
                        '_surerank_seo_description_found' => $postMeta['_surerank_seo_description'] ?? 'NOT_FOUND',
                        '_yoast_wpseo_title_found' => $postMeta['_yoast_wpseo_title'] ?? 'NOT_FOUND',
                        'seo_title_found' => $postMeta['seo_title'] ?? 'NOT_FOUND'
                    ]);
                } else {
                    Log::error('=== SURERANK META CHECK FAILED ===', [
                        'article_id' => $this->article->id,
                        'wp_post_id' => $wpPostId,
                        'error' => $metaCheckResponse->body()
                    ]);
                }
                
                // Update article with WordPress post info
                $this->article->update([
                    'status' => 'published',
                    'wp_post_id' => $wpPostId,
                    'wp_post_url' => $wpPostUrl,
                ]);

                Log::info('Article published to WordPress successfully', [
                    'article_id' => $this->article->id,
                    'wp_post_id' => $wpPostId,
                    'wp_url' => $wpPostUrl
                ]);

            } else {
                Log::error('=== WORDPRESS REQUEST FAILED ===', [
                    'article_id' => $this->article->id,
                    'error_status' => $response->status(),
                    'error_body' => $response->body(),
                    'error_headers' => $response->headers()
                ]);
                throw new Exception('WordPress API request failed: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('WordPress publish failed', [
                'article_id' => $this->article->id,
                'error' => $e->getMessage()
            ]);
            
            $this->article->update(['status' => 'failed']);
        }
    }

    /**
     * Get existing tag IDs or create new tags from keywords
     */
    private function getOrCreateTags($site, string $keywords): array
    {
        $tagIds = [];
        $keywordArray = array_map('trim', explode(',', $keywords));
        
        foreach ($keywordArray as $keyword) {
            if (empty($keyword)) continue;
            
            // Try to find existing tag
            $response = Http::withBasicAuth($site->wp_username, $site->wp_password)
                ->get("{$site->site_url}/wp-json/wp/v2/tags", [
                    'search' => $keyword,
                ]);

            if ($response->successful()) {
                $tags = $response->json();
                $existingTag = collect($tags)->firstWhere('name', $keyword);
                
                if ($existingTag) {
                    $tagIds[] = $existingTag['id'];
                } else {
                    // Create new tag
                    $createResponse = Http::withBasicAuth($site->wp_username, $site->wp_password)
                        ->post("{$site->site_url}/wp-json/wp/v2/tags", [
                            'name' => $keyword,
                            'slug' => strtolower(str_replace(' ', '-', $keyword)),
                        ]);
                    
                    if ($createResponse->successful()) {
                        $tagIds[] = $createResponse->json('id');
                    }
                }
            }
        }
        
        return $tagIds;
    }

    /**
     * Clean content before publishing to WordPress
     */
    private function cleanContent(string $content): string
    {
        // 1. Remove the first H2 heading to avoid duplicate title
        $content = preg_replace('/<h2>.*?<\/h2>/', '', $content, 1);
        
        // 2. Remove non-printable characters and AI garbage text
        $content = preg_replace('/[^\x20-\x7E\n\r\t<>\/\-=\[\]{}()&;:"\',.?`~!@#$%^&*]/', '', $content);
        
        // 3. Remove lines that are just random characters (likely AI garbage)
        $lines = explode("\n", $content);
        $cleanLines = [];
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            // Skip lines that are just random characters without spaces
            if (strlen($trimmed) > 0 && !preg_match('/^[^\s]{20,}$/', $trimmed)) {
                $cleanLines[] = $line;
            }
        }
        
        return implode("\n", $cleanLines);
    }

    /**
     * Upload featured image to WordPress Media Library
     */
    private function uploadFeaturedImage($site, string $imagePath): ?int
    {
        try {
            // The database stores /storage/blog-images/image.jpg
            // For Storage facade with public disk, we need: blog-images/image.jpg
            $fullPath = str_replace('/storage/', '', $imagePath);
            
            // Debug: Log what we're looking for
            Log::info('Looking for image', [
                'original_path' => $imagePath,
                'storage_path' => $fullPath,
                'file_exists' => Storage::disk('public')->exists($fullPath)
            ]);
            
            if (!Storage::disk('public')->exists($fullPath)) {
                Log::warning('Image file not found', [
                    'original_path' => $imagePath,
                    'storage_path' => $fullPath,
                    'all_files' => Storage::disk('public')->files('blog-images')
                ]);
                return null;
            }

            // Get image content
            $imageContent = Storage::disk('public')->get($fullPath);
            $imageName = basename($imagePath);
            
            // Upload to WordPress Media Library with SEO-optimized metadata
            $response = Http::withBasicAuth($site->wp_username, $site->wp_password)
                ->timeout(60)
                ->attach('file', $imageContent, $imageName)
                ->post("{$site->site_url}/wp-json/wp/v2/media", [
                    'title' => 'Featured Image - ' . $this->article->topic,
                    'alt_text' => $this->generateSeoAltText(),
                    'caption' => $this->generateImageCaption(),
                ]);

            if ($response->successful()) {
                $mediaId = $response->json('id');
                Log::info('Image uploaded to WordPress', [
                    'article_id' => $this->article->id,
                    'media_id' => $mediaId
                ]);
                return $mediaId;
            } else {
                Log::error('Failed to upload image to WordPress', [
                    'article_id' => $this->article->id,
                    'error' => $response->body()
                ]);
                return null;
            }

        } catch (\Exception $e) {
            Log::error('Image upload exception', [
                'article_id' => $this->article->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Generate SEO-optimized alt text for the image
     */
    private function generateSeoAltText(): string
    {
        $altText = "Professional blog image about {$this->article->topic}";
        
        if (!empty($this->article->keywords)) {
            $altText .= " featuring {$this->article->keywords}";
        }
        
        return $altText;
    }

    /**
     * Generate SEO-optimized image caption
     */
    private function generateImageCaption(): string
    {
        $caption = "Featured image for blog post: {$this->article->topic}";
        
        if (!empty($this->article->meta_description)) {
            $caption .= " - " . substr($this->article->meta_description, 0, 100) . "...";
        }
        
        return $caption;
    }
}
