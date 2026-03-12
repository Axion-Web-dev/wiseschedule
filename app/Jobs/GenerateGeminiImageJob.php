<?php

namespace App\Jobs;

use App\Models\AiArticle;
use App\Services\HuggingFaceImageService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateGeminiImageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public AiArticle $article) {}

    public function handle(HuggingFaceImageService $huggingFace): void
    {
        try {
            // Validate required fields before image generation
            if (!$this->article->topic) {
                throw new \Exception('Article topic is required for image generation.');
            }

            // Update status to image generation
            $this->article->update(['status' => 'generating_image']);

            // Build a better prompt for the image
            $imagePrompt = $this->buildImagePrompt();

            // 1. Get the Image from Hugging Face (returns binary data)
            $imageBinary = $huggingFace->generateImage($imagePrompt);

            // 2. Validate generated image
            if (empty($imageBinary) || strlen($imageBinary) < 1000) {
                throw new \Exception('Generated image is too small or empty. Please try again.');
            }

            // 3. Save it to your local storage with SEO-optimized filename
            // 1. Create a clean slug from the topic
            // Example: "Stripe Webhooks Tutorial Node.js" -> "stripe-webhooks-tutorial-nodejs"
            $safeName = Str::slug($this->article->topic);
            
            // 2. Add a unique timestamp to prevent overwriting
            $imageName = $safeName . '-' . time() . '.jpg';
            $imagePath = "blog-images/$imageName";
            
            Storage::disk('public')->put($imagePath, $imageBinary);

            // 4. Verify file was saved
            if (!Storage::disk('public')->exists($imagePath)) {
                throw new \Exception('Failed to save generated image to storage.');
            }

            // 5. Update the database with the local path
            $this->article->update([
                'featured_image_url' => Storage::url($imagePath),
                'status' => 'ready',
            ]);

            Log::info('AI Image generated successfully with Hugging Face', [
                'article_id' => $this->article->id,
                'image_path' => $imagePath
            ]);

        } catch (Exception $e) {
            // Handle failure
            $this->article->update(['status' => 'failed']);
            
            Log::error('AI Image generation failed with Hugging Face', [
                'article_id' => $this->article->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function buildImagePrompt(): string
    {
        $prompt = "A professional blog post featured image about: {$this->article->topic}. ";
        
        if (!empty($this->article->keywords)) {
            $prompt .= "Keywords: {$this->article->keywords}. ";
        }
        
        // Add SEO-friendly context for better image generation
        if (!empty($this->article->tone)) {
            $prompt .= "Tone: {$this->article->tone}. ";
        }
        
        $prompt .= "Modern, clean, professional style, suitable for business/tech blog, high quality, detailed, visually appealing for search engines.";
        
        return $prompt;
    }
    
    /**
     * Generate SEO-optimized alt text for the image
     */
    private function generateAltText(): string
    {
        $altText = "Professional blog image about {$this->article->topic}";
        
        if (!empty($this->article->keywords)) {
            $altText .= " featuring {$this->article->keywords}";
        }
        
        return $altText;
    }
}
