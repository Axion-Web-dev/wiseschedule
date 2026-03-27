<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Config;

class SecurePasswordInput extends TextInput
{
    protected string $view = 'filament.forms.components.secure-password-input';
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->password()
            ->revealable()
            ->required()
            ->rules([
                'required',
                'string',
                'min:' . Config::get('security.auth.password.min_length', 8),
                new \App\Rules\StrongPassword(),
            ])
            ->helperText($this->getPasswordHelperText())
            ->label('Password');
    }

    protected function getPasswordHelperText(): string
    {
        $config = Config::get('security.auth.password');
        $requirements = [];
        
        if ($config['min_length']) {
            $requirements[] = "At least {$config['min_length']} characters";
        }
        
        if ($config['require_uppercase']) {
            $requirements[] = "One uppercase letter";
        }
        
        if ($config['require_lowercase']) {
            $requirements[] = "One lowercase letter";
        }
        
        if ($config['require_numbers']) {
            $requirements[] = "One number";
        }
        
        if ($config['require_symbols']) {
            $requirements[] = "One special character";
        }
        
        if ($config['prevent_common_passwords']) {
            $requirements[] = "Not a common password";
        }
        
        return 'Password must contain: ' . implode(', ', $requirements) . '.';
    }

    public function confirm(): static
    {
        $this->dehydrateStateUsing(function ($state) {
            return $state;
        });
        
        return $this;
    }
}