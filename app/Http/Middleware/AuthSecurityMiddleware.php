<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class AuthSecurityMiddleware
{
    
    public function handle(Request $request, Closure $next): Response
    {
        $ipAddress = $request->ip();
        $email = $request->input('email', '');
        $fingerprint = $this->generateFingerprint($request);

        $rateLimits = [
            'login' => [
                'attempts' => 3,  // Reduced from 5
                'decay' => 60,    // 1 minute instead of 15
                'penalty' => 300, // 5 minutes instead of 1 hour
            ],
            'register' => [
                'attempts' => 3,
                'decay' => 60,    // 1 minute instead of 30
                'penalty' => 300, // 5 minutes instead of 2 hours
            ],
            'password_reset' => [
                'attempts' => 3,
                'decay' => 120,   // 2 minutes
                'penalty' => 300, // 5 minutes instead of 4 hours
            ]
        ];
        
        $routeName = $request->route()->getName();
        $authType = $this->getAuthType($routeName);
        
        if ($authType && isset($rateLimits[$authType])) {
            $config = $rateLimits[$authType];

            if ($this->isIpBlacklisted($ipAddress)) {
                Log::warning('Blacklisted IP attempted access', [
                    'ip' => $ipAddress,
                    'route' => $routeName,
                    'email' => $email
                ]);
                
                return $this->securityResponse('Access temporarily blocked due to suspicious activity.');
            }

            $rateLimitKey = "auth:{$authType}:{$ipAddress}:{$fingerprint}";
            $penaltyKey = "auth:penalty:{$authType}:{$ipAddress}";

            if (Cache::has($penaltyKey)) {
                $remainingTime = Cache::get($penaltyKey);
                Log::warning('Rate limit penalty active', [
                    'ip' => $ipAddress,
                    'auth_type' => $authType,
                    'remaining_time' => $remainingTime
                ]);
                
                return $this->securityResponse("Too many attempts. Please try again in {$remainingTime} seconds.");
            }

            $executed = RateLimiter::attempt(
                $rateLimitKey,
                $config['attempts'],
                function () use ($request, $next) {
                    return $next($request);
                },
                $config['decay']
            );
            
            if (!$executed) {

                Cache::put($penaltyKey, $config['penalty'], $config['penalty']);
                
                Log::warning('Rate limit exceeded - penalty applied', [
                    'ip' => $ipAddress,
                    'auth_type' => $authType,
                    'email' => $email,
                    'penalty_duration' => $config['penalty']
                ]);

                $this->trackSuspiciousActivity($ipAddress, $email, $authType, 'rate_limit_exceeded');
                
                return $this->securityResponse("Too many attempts. Access blocked for {$config['penalty']} seconds.");
            }

            if ($this->hasSuspiciousPattern($request, $authType)) {
                Log::warning('Suspicious pattern detected', [
                    'ip' => $ipAddress,
                    'auth_type' => $authType,
                    'email' => $email,
                    'user_agent' => $request->userAgent()
                ]);
                
                $this->trackSuspiciousActivity($ipAddress, $email, $authType, 'suspicious_pattern');

                Cache::put($penaltyKey, 60, 60); // 1 minute instead of 5
                
                return $this->securityResponse('Suspicious activity detected. Please wait 1 minute before trying again.');
            }
        }

        $response = $next($request);
        $this->addSecurityHeaders($response);
        
        return $response;
    }

    private function generateFingerprint(Request $request): string
    {
        return md5(implode('|', [
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept'),
        ]));
    }

    private function getAuthType(?string $routeName): ?string
    {
        if (!$routeName) return null;
        
        if (str_contains($routeName, 'login')) {
            return 'login';
        }
        
        if (str_contains($routeName, 'register')) {
            return 'register';
        }
        
        if (str_contains($routeName, 'password')) {
            return 'password_reset';
        }
        
        return null;
    }

    private function isIpBlacklisted(string $ip): bool
    {
        return Cache::has("blacklist:ip:{$ip}");
    }

    private function trackSuspiciousActivity(string $ip, string $email, string $authType, string $reason): void
    {
        $key = "suspicious:{$ip}";
        $activities = Cache::get($key, []);
        
        $activities[] = [
            'timestamp' => now(),
            'email' => $email,
            'auth_type' => $authType,
            'reason' => $reason,
        ];

        $activities = array_slice($activities, -10);
        
        Cache::put($key, $activities, 86400); // 24 hours

        if (count($activities) >= 5) {
            Cache::put("blacklist:ip:{$ip}", true, 86400); // 24 hours
            Log::alert('IP auto-blacklisted due to repeated suspicious activity', [
                'ip' => $ip,
                'activities_count' => count($activities)
            ]);
        }
    }

    private function hasSuspiciousPattern(Request $request, string $authType): bool
    {
        $email = $request->input('email', '');
        $password = $request->input('password', '');
        $userAgent = $request->userAgent();

        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python', 'java', 'perl', 'ruby', 'php'
        ];
        
        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        if ($authType === 'register') {

            $tempEmailDomains = [
                '10minutemail', 'tempmail', 'guerrillamail', 'mailinator',
                'yopmail', 'maildrop', 'temp-mail', 'throwaway'
            ];
            
            foreach ($tempEmailDomains as $domain) {
                if (stripos($email, $domain) !== false) {
                    return true;
                }
            }
        }

        if ($authType === 'login') {
            $commonPasswords = ['password', '123456', 'admin', 'qwerty', 'login'];
            
            foreach ($commonPasswords as $common) {
                if (strtolower($password) === $common) {
                    return true;
                }
            }
        }

        $lastRequestKey = "last_request:{$request->ip()}:{$authType}";
        $lastRequest = Cache::get($lastRequestKey);
        
        if ($lastRequest && now()->diffInSeconds($lastRequest) < 1) {
            return true;
        }
        
        Cache::put($lastRequestKey, now(), 60);
        
        return false;
    }

    private function addSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    private function securityResponse(string $message): Response
    {
        return response()->view('errors.security', [
            'message' => $message
        ], 429);
    }
}