<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogTwitterApiRequests
{
    public function handle(Request $request, Closure $next)
    {
        // Only log Twitter API requests
        if (!$this->isTwitterApiRequest($request)) {
            return $next($request);
        }

        $startTime = microtime(true);
        
        Log::info('Twitter API Request Started', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'query_params' => $request->query(),
            'request_body' => $this->sanitizeRequestBody($request->getContent()),
            'timestamp' => now()->toISOString()
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);

        Log::info('Twitter API Request Completed', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'response_headers' => $response->headers,
            'response_body' => $this->sanitizeResponseBody($response->getContent()),
            'duration_ms' => $duration,
            'timestamp' => now()->toISOString()
        ]);

        return $response;
    }

    private function isTwitterApiRequest(Request $request): bool
    {
        $url = $request->fullUrl();
        return str_contains($url, 'api.twitter.com') || 
               str_contains($url, 'twitter.com/i/oauth2');
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        
        return array_map(function($values) use ($sensitiveHeaders) {
            $headerName = strtolower($values[0] ?? '');
            if (in_array($headerName, $sensitiveHeaders)) {
                return ['[REDACTED]'];
            }
            return $values;
        }, $headers);
    }

    private function sanitizeRequestBody(string $body): array|string
    {
        if (empty($body)) {
            return '[EMPTY]';
        }

        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Redact sensitive fields
            $sensitiveFields = ['client_secret', 'code_verifier', 'access_token', 'refresh_token'];
            
            foreach ($sensitiveFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = '[REDACTED]';
                }
            }
            
            return $data;
        }

        // For non-JSON bodies, check for common sensitive patterns
        if (str_contains($body, 'client_secret') || str_contains($body, 'code_verifier')) {
            return '[CONTAINS_SENSITIVE_DATA]';
        }

        return $body;
    }

    private function sanitizeResponseBody(string $body): array|string
    {
        if (empty($body)) {
            return '[EMPTY]';
        }

        $data = json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Redact sensitive fields from response
            $sensitiveFields = ['access_token', 'refresh_token'];
            
            foreach ($sensitiveFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = '[REDACTED]';
                }
            }
            
            return $data;
        }

        return $body;
    }
}
