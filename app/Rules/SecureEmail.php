<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;

class SecureEmail implements ValidationRule
{
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $config = Config::get('security.auth.email');
        $domain = strtolower(substr(strrchr($value, '@'), 1));

        if ($config['block_temp_domains']) {
            $tempDomains = Config::get('security.auth.temp_email_domains', []);
            
            foreach ($tempDomains as $tempDomain) {
                if (str_contains($domain, strtolower($tempDomain))) {
                    $fail('Temporary email addresses are not allowed. Please use a permanent email address.');
                    return;
                }
            }
        }

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail('Please provide a valid email address.');
            return;
        }

        if ($this->hasSuspiciousPattern($value)) {
            $fail('This email address appears to be suspicious. Please use a different email address.');
        }

        if (!$this->isValidDomain($domain)) {
            $fail('The email domain appears to be invalid.');
        }
    }

    private function hasSuspiciousPattern(string $email): bool
    {
        $localPart = strtolower(substr($email, 0, strrpos($email, '@')));

        $suspiciousPatterns = [
            '/^[0-9]+$/', // Only numbers
            '/^test.*$/i', // Starts with 'test'
            '/^fake.*$/i', // Starts with 'fake'
            '/^spam.*$/i', // Starts with 'spam'
            '/.*\.{2,}.*/', // Multiple consecutive dots
            '/.*@.*@.*/', // Multiple @ symbols
            '/.*\+\d+@.*/', // Plus with numbers (common in temp emails)
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $email)) {
                return true;
            }
        }
        
        return false;
    }

    private function isValidDomain(string $domain): bool
    {

        if (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $domain)) {
            return false;
        }

        if (str_contains($domain, '..')) {
            return false;
        }

        if (str_starts_with($domain, '-') || str_starts_with($domain, '.') ||
            str_ends_with($domain, '-') || str_ends_with($domain, '.')) {
            return false;
        }

        if (strlen($domain) > 253) {
            return false;
        }
        
        return true;
    }
}