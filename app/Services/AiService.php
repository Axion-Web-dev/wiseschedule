<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Exception;

class AiService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->baseUrl = 'https://api.openai.com/v1';
    }

    /**
     * Generate a tweet draft based on post content
     */
    public function generateTweetDraft(Post $post): string
    {
        if (!$this->apiKey) {
            throw new Exception('OpenAI API key not configured');
        }

        // $prompt = "Based on the title: '{$post->title}' and content: '{$post->content}', write a viral tweet under 280 characters. Use [LINK] as a placeholder for the URL.";
$prompt = <<<EOT
You are an elite Twitter copywriter who writes high-converting, viral tweets for builders and entrepreneurs.

GOAL:
Write ONE powerful tweet under 280 characters.

CONTEXT:
Title: {$post->title}
Content: {$post->content}

TARGET AUDIENCE:
Entrepreneurs, Developers, Students

TONE:
Bold. Slightly provocative. Intelligent.
Direct. Sharp. No fluff.

OBJECTIVE:
Extract the strongest insight and turn it into a scroll-stopping tweet that drives clicks.

RULES:
- First sentence MUST create tension or curiosity
- Focus on one core insight only
- Use short, punchy sentences
- Remove filler words
- Maximum 1–2 emojis (only if impactful)
- Strong CTA at the end
- Include {$post->url} exactly once at the end
- Under 280 characters
- No hashtags unless absolutely necessary
- Do NOT repeat the title
- Do NOT invent facts
- No quotation marks
- No explanations

OUTPUT:
Return only the tweet text.

Think internally. Output only the final tweet.
EOT;


        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/chat/completions', [
               'model' => 'gpt-4.1-nano',

                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 100,
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                throw new Exception('OpenAI API request failed: ' . $response->body());
            }

            $data = $response->json();
            $tweet = $data['choices'][0]['message']['content'] ?? '';
           
            // Clean up the tweet - remove extra whitespace and ensure it's under 280 characters
          
            return $tweet;

        } catch (Exception $e) {
            throw new Exception('Failed to generate tweet: ' . $e->getMessage());
        }
    }

    /**
     * Generate an article based on the given prompt
     */
    public function generateArticle(string $prompt): array
    {
        if (!$this->apiKey) {
            throw new Exception('OpenAI API key not configured');
        }

        try {
            // Extract persona bio from additional_instructions if available
            $personaBio = $this->extractPersonaBio($prompt);
            $topic = $this->extractTopic($prompt);
            $keywords = $this->extractKeywords($prompt);
            $wordCount = $this->extractWordCount($prompt);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120) // AI takes time, don't let the job time out
            ->post($this->baseUrl . '/chat/completions', [
                'model' => 'gpt-4.1-nano', // Use existing model for structured output
                'messages' => [
                    // 1. SYSTEM ROLE: Setting the stage
                    [
                        'role' => 'system',
                        'content' => "You are a professional SEO copywriter and expert in high-conversion blog content.
                                      Identity: $personaBio.

                                      JSON Structure (MANDATORY):
                                      {
                                        \"title\": \"Direct Article Headline\",
                                        \"meta_title\": \"SEO Title (50-60 chars)\",
                                        \"meta_description\": \"SEO Description (150-160 chars starting with a power verb)\",
                                        \"content\": \"Clean HTML body content only\"
                                      }

                                      CRITICAL RULES:
                                      - The word 'Introduction' is strictly FORBIDDEN in all fields.
                                      - 'content' MUST ONLY contain semantic HTML (h2, h3, p, ul, li, strong).
                                      - DO NOT include <meta>, <title>, or <body> tags inside the 'content' field.
                                      - 'meta_title' and 'meta_description' must be plain text without HTML tags.
                                      - Ensure 'meta_description' is a complete, compelling call-to-action."
                    ],
                    // 2. ASSISTANT ROLE: Persona Lock
                    [
                        'role' => 'assistant',
                        'content' => "I have adopted the persona of $personaBio. I will provide a valid JSON object with a catchy title, optimized metadata, and clean HTML content, avoiding the word 'Introduction' entirely."
                    ],
                    // 3. USER ROLE: The Task
                    [
                        'role' => 'user',
                        'content' => "Write a professional blog post about: $topic. 
                                      Keywords to include naturally: $keywords. 
                                      Target word count: $wordCount words. 
                                      
                                      Requirements:
                                      - Start the article directly with an engaging hook.
                                      - Use h2 and h3 tags for subheadings.
                                      - Maintain a professional yet accessible tone consistent with my identity."
                    ]
                ],
                'response_format' => ['type' => 'json_object'], // Ensures clean JSON output
                'max_tokens' => 3000,
                'temperature' => 0.7,
            ]);

            if (!$response->successful()) {
                throw new Exception('OpenAI API request failed: ' . $response->body());
            }

            $data = $response->json();
            $result = json_decode($data['choices'][0]['message']['content'], true);
            
            if (!$result || !isset($result['content']) || !isset($result['meta_title']) || !isset($result['meta_description'])) {
                throw new Exception('Invalid JSON response from AI - missing required fields');
            }
            
            // Return the complete result array with all SEO metadata
            return $result;

        } catch (Exception $e) {
            throw new Exception('Failed to generate article: ' . $e->getMessage());
        }
    }

    private function extractPersonaBio(string $prompt): string
    {
        // Try to extract persona from additional_instructions or use default
        if (preg_match('/Additional Instructions:\s*(.+?)(?:\n\n|$)/i', $prompt, $matches)) {
            return trim($matches[1]);
        }
        return "Professional content writer and SEO expert";
    }

    private function extractTopic(string $prompt): string
    {
        if (preg_match('/about [\'"](.+?)[\'"]/', $prompt, $matches)) {
            return trim($matches[1]);
        }
        return "the specified topic";
    }

    private function extractKeywords(string $prompt): string
    {
        if (preg_match('/Keywords to include naturally:\s*(.+?)(?:\.|\n|$)/i', $prompt, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/Keywords?:\s*(.+?)(?:\.|\n|$)/i', $prompt, $matches)) {
            return trim($matches[1]);
        }
        return "relevant keywords";
    }

    private function extractWordCount(string $prompt): int
    {
        if (preg_match('/(\d+)\s*words/', $prompt, $matches)) {
            return (int) $matches[1];
        }
        return 1000;
    }
}
