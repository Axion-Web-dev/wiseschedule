<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Illuminate\Support\Facades\Log;

class GuzzleLoggingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(Client::class, function ($app) {
            $stack = HandlerStack::create();
            
            // Add request logging middleware
            $stack->push(Middleware::request(function ($request, $options) {
                if ($this->isTwitterRequest($request)) {
                    Log::info('Guzzle HTTP Request', [
                        'method' => $request->getMethod(),
                        'uri' => (string) $request->getUri(),
                        'headers' => $this->sanitizeHeaders($request->getHeaders()),
                        'body' => $this->sanitizeRequestBody($request->getBody()->getContents()),
                        'timestamp' => now()->toISOString()
                    ]);
                }
                return $request;
            }));
            
            // Add response logging middleware
            $stack->push(Middleware::response(function ($response) {
                $request = $response->getRequest();
                if ($this->isTwitterRequest($request)) {
                    Log::info('Guzzle HTTP Response', [
                        'method' => $request->getMethod(),
                        'uri' => (string) $request->getUri(),
                        'status_code' => $response->getStatusCode(),
                        'headers' => $response->getHeaders(),
                        'body' => $this->sanitizeResponseBody($response->getBody()->getContents()),
                        'timestamp' => now()->toISOString()
                    ]);
                }
                return $response;
            }));
            
            return new Client([
                'handler' => $stack,
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);
        });
    }
    
    private function isTwitterRequest($request): bool
    {
        $uri = (string) $request->getUri();
        return str_contains($uri, 'api.twitter.com') || 
               str_contains($uri, 'twitter.com/i/oauth2');
    }
    
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];
        
        return array_map(function($values) use ($sensitiveHeaders) {
            $headerName = strtolower(array_key_first($values));
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
            $sensitiveFields = ['client_secret', 'code_verifier', 'access_token', 'refresh_token'];
            
            foreach ($sensitiveFields as $field) {
                if (isset($data[$field])) {
                    $data[$field] = '[REDACTED]';
                }
            }
            
            return $data;
        }

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
