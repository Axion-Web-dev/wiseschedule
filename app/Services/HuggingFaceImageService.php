<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class HuggingFaceImageService
{
    public function generateImage(string $prompt)
    {
        $token = config('services.huggingface.token');
        
        if (!$token) {
            throw new Exception('Hugging Face token not configured');
        }

        $maxRetries = 3;
        $retryDelay = 2000; // 2 seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $response = Http::withToken($token)
                    ->timeout(120)
                    ->post('https://router.huggingface.co/hf-inference/models/black-forest-labs/FLUX.1-schnell', [
                        'inputs' => $prompt,
                    ]);

                if ($response->successful()) {
                    return $response->body();
                }

                // If it's the last attempt, throw exception
                if ($attempt === $maxRetries) {
                    throw new Exception('Hugging Face API request failed after ' . $maxRetries . ' attempts: ' . $response->body());
                }

                // Log the retry attempt
                Log::warning('Hugging Face API attempt ' . $attempt . ' failed, retrying...', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'attempt' => $attempt . '/' . $maxRetries
                ]);

            } catch (\Exception $e) {
                // If it's a timeout or network error and not the last attempt, retry
                if ($attempt < $maxRetries && (
                    str_contains($e->getMessage(), 'timeout') || 
                    str_contains($e->getMessage(), 'Resolving timed out') ||
                    str_contains($e->getMessage(), 'cURL error')
                )) {
                    Log::warning('Hugging Face API timeout on attempt ' . $attempt . ', retrying...', [
                        'error' => $e->getMessage(),
                        'attempt' => $attempt . '/' . $maxRetries
                    ]);
                    
                    // Wait before retrying
                    usleep($retryDelay * 1000); // Convert to microseconds
                    $retryDelay *= 2; // Exponential backoff
                    continue;
                }
                
                // Re-throw the exception if it's not retryable or it's the last attempt
                throw new Exception('Hugging Face API failed after ' . $maxRetries . ' attempts: ' . $e->getMessage());
            }
        }

        throw new Exception('Hugging Face API failed after ' . $maxRetries . ' attempts');
    }
}
