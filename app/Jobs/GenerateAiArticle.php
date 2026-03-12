<?php

namespace App\Jobs;

use App\Models\AiArticle;
use App\Services\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateAiArticle implements ShouldQueue
{
    use Queueable;

    public function __construct(public AiArticle $article) {}

    public function handle(AiService $aiService): void
    {
        try {
            // Validate required fields before generation
            if (!$this->article->topic) {
                throw new \Exception('Article topic is required for content generation.');
            }
            if (!$this->article->wp_site_id) {
                throw new \Exception('WordPress site is required for content generation.');
            }

            // 1. Update status to 'generating'
            $this->article->update(['status' => 'generating']);

            // 2. Build advanced SEO prompt
            $prompt = $this->buildSeoPrompt();

            // 3. Generate content using the existing AiService with timeout
            $aiResult = $aiService->generateArticle($prompt);

            // 4. Extract data from AI result
            $title = $aiResult['title'] ?? '';
            $content = $aiResult['content'] ?? '';
            $metaTitle = $aiResult['meta_title'] ?? $title;
            $metaDescription = $aiResult['meta_description'] ?? '';

            // 5. Clean up any markdown code blocks if the AI accidentally includes them
            $cleanContent = str_replace(['```html', '```'], '', $content);
            $cleanContent = trim($cleanContent);

            // 6. Validate generated content
            if (empty($cleanContent) || strlen($cleanContent) < 100) {
                throw new \Exception('Generated content is too short or empty. Please try again.');
            }

            // 7. Validate extracted title
            if (empty($title)) {
                throw new \Exception('Could not extract title from generated content.');
            }

            // 8. Calculate reading time
            $readingTime = $this->calculateReadingTime($cleanContent);

            // 9. Save content and meta data, set status to 'ready'
            $this->article->update([
                'title' => $title,
                'meta_title' => $metaTitle,
                'meta_description' => $metaDescription,
                'content' => $cleanContent,
                'reading_time' => $readingTime,
                'status' => 'ready'
            ]);

            Log::info('AI Article generated successfully', ['article_id' => $this->article->id]);

        } catch (\Exception $e) {
            // 7. Handle failure
            $this->article->update(['status' => 'failed']);
            
            Log::error('AI Article generation failed', [
                'article_id' => $this->article->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function buildSeoPrompt(): string
    {
        $contentTypeMap = [
            'blog_post' => 'SEO-optimized blog post',
            'tutorial' => 'step-by-step tutorial',
            'listicle' => 'engaging listicle',
            'how_to' => 'how-to guide',
            'opinion' => 'opinion piece',
            'news' => 'news article'
        ];

        $contentType = $contentTypeMap[$this->article->content_type] ?? 'SEO-optimized blog post';
        $wordCount = $this->article->word_count_target ?? 1000;
        
        $prompt = "Write a comprehensive {$contentType} about '{$this->article->topic}'. ";
        $prompt .= "Target Word Count: {$wordCount} words. ";
        $prompt .= "Tone: {$this->article->tone}. ";
        $prompt .= "Keywords to include naturally: {$this->article->keywords}. ";
        
        if (!empty($this->article->additional_instructions)) {
            $prompt .= "Additional Instructions: {$this->article->additional_instructions}. ";
        }
        
        $prompt .= "\n\nRequirements:\n";
        $prompt .= "- Use HTML tags only (<h2>, <h3>, <p>, <ul>, <li>, <strong>, <em>, <blockquote>)\n";
        $prompt .= "- Do not include <html>, <body>, or <head> tags\n";
        $prompt .= "- Start directly with an engaging introduction (no H1 title needed)\n";
        $prompt .= "- Use subheadings (H2, H3) to break up the text\n";
        $prompt .= "- Include bullet points or numbered lists where appropriate\n";
        $prompt .= "- Use <strong> for important points and <em> for emphasis\n";
        $prompt .= "- Include a conclusion with a call to action\n";
        $prompt .= "- Ensure content is SEO-friendly with natural keyword placement\n";
        $prompt .= "- Write in a conversational yet professional tone\n";
        $prompt .= "- Make it valuable and informative for readers";
        
        return $prompt;
    }

    private function extractTitle(string $content): string
    {
        // Look for H2 headings in the content
        if (preg_match('/<h2>(.*?)<\/h2>/', $content, $matches)) {
            $title = strip_tags($matches[1]);
            $title = trim($title);
            
            // Limit title length to prevent database errors
            if (strlen($title) > 200) {
                $title = substr($title, 0, 197) . '...';
            }
            
            return $title;
        }
        
        // If no H2 found, try to extract from first line
        $lines = explode("\n", trim($content));
        $firstLine = $lines[0] ?? '';
        
        // Remove HTML tags and clean up
        $title = strip_tags($firstLine);
        $title = trim($title);
        
        // If the first line is too long, use the topic instead
        if (strlen($title) > 100) {
            return $this->article->topic;
        }
        
        // If the first line is too short or empty, use the topic as title
        if (strlen($title) < 10) {
            return $this->article->topic;
        }
        
        return $title;
    }

    private function calculateReadingTime(string $content): string
    {
        $wordCount = str_word_count(strip_tags($content));
        $readingTime = max(1, ceil($wordCount / 200)); // Average reading speed: 200 words per minute
        return "{$readingTime} min read";
    }
}
