<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;

class StrongPassword implements ValidationRule
{
    
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $config = Config::get('security.auth.password');

        if (strlen($value) < $config['min_length']) {
            $fail("Password must be at least {$config['min_length']} characters long.");
            return;
        }

        if ($config['prevent_common_passwords']) {
            $commonPasswords = Config::get('security.auth.common_passwords', []);
            
            if (in_array(strtolower($value), array_map('strtolower', $commonPasswords))) {
                $fail('This is a very weak password. Please choose a more secure password with a mix of letters, numbers, and symbols.');
                return;
            }
        }

        if ($this->hasSequentialChars($value)) {
            $fail('Password contains sequential characters (like "123" or "abc"). Please use a more complex password.');
            return;
        }

        if ($this->hasRepeatedChars($value)) {
            $fail('Password contains repeated characters. Please use a more varied password.');
            return;
        }

        if (ctype_digit($value)) {
            $fail('Password cannot be only numbers. Please add letters and symbols for better security.');
            return;
        }

        if (ctype_alpha($value)) {
            $fail('Password cannot be only letters. Please add numbers and symbols for better security.');
            return;
        }

        $missingRequirements = [];
        
        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $value)) {
            $missingRequirements[] = 'uppercase letter';
        }
        
        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $value)) {
            $missingRequirements[] = 'lowercase letter';
        }
        
        if ($config['require_numbers'] && !preg_match('/[0-9]/', $value)) {
            $missingRequirements[] = 'number';
        }
        
        if ($config['require_symbols'] && !preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $value)) {
            $missingRequirements[] = 'special character';
        }
        
        if (!empty($missingRequirements)) {
            $requirements = implode(', ', $missingRequirements);
            $fail("Password is weak. Please add at least one: {$requirements}.");
            return;
        }
    }

    private function hasSequentialChars(string $password): bool
    {
        $length = strlen($password);
        
        for ($i = 0; $i < $length - 2; $i++) {
            $char1 = strtolower($password[$i]);
            $char2 = strtolower($password[$i + 1]);
            $char3 = strtolower($password[$i + 2]);

            if (is_numeric($char1) && is_numeric($char2) && is_numeric($char3)) {
                if (($char2 - $char1 == 1) && ($char3 - $char2 == 1)) {
                    return true;
                }
            }

            if (ctype_alpha($char1) && ctype_alpha($char2) && ctype_alpha($char3)) {
                if ((ord($char2) - ord($char1) == 1) && (ord($char3) - ord($char2) == 1)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    private function hasRepeatedChars(string $password): bool
    {
        $length = strlen($password);
        
        for ($i = 0; $i < $length - 2; $i++) {
            if ($password[$i] === $password[$i + 1] && $password[$i] === $password[$i + 2]) {
                return true;
            }
        }
        
        return false;
    }
}