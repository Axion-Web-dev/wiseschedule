<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Schemas\Schema;
use Filament\Auth\Pages\Register as FilamentRegister;
use Illuminate\Validation\Rules\Password;

class Register extends FilamentRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique('users', 'email', ignoreRecord: false)
                    ->validationMessages([
                        'unique' => 'This email address is already registered. Please use a different email or try logging in.',
                        'email' => 'Please enter a valid email address.',
                    ]),
                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rules([
                        'required',
                        'min:8',
                        'regex:/[a-z]/', // at least one lowercase
                        'regex:/[A-Z]/', // at least one uppercase  
                        'regex:/[0-9]/', // at least one number
                        'regex:/[@$!%*#?&]/', // at least one symbol
                    ])
                    ->validationMessages([
                        'min' => 'Password must be at least 8 characters long.',
                        'regex' => 'Password must contain at least one lowercase letter, one uppercase letter, one number, and one special character (@$!%*#?&).',
                    ])
                    ->helperText(function ($state) {

                        $password = $state ?? '';

                        if (strlen($password) > 0) {
                            $hasUppercase = preg_match('/[A-Z]/', $password);
                            $hasLowercase = preg_match('/[a-z]/', $password);
                            $hasNumbers = preg_match('/[0-9]/', $password);
                            $hasSymbols = preg_match('/[@$!%*#?&]/', $password);

                            if (!$hasUppercase || !$hasLowercase || !$hasNumbers || !$hasSymbols || strlen($password) < 8) {
                                return null;
                            }
                        }
                        
                        return 'Must be at least 8 characters with uppercase, lowercase, numbers, and symbols (@$!%*#?&)';
                    })
                    ->dehydrateStateUsing(fn ($state) => bcrypt($state))
                    ->dehydrated(true),
                TextInput::make('password_confirmation')
                    ->label('Confirm password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password')
                    ->dehydrated(false),
                Checkbox::make('terms')
                    ->label('I agree to the Terms of Service and Privacy Policy')
                    ->required()
                    ->accepted(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('register')
                ->label('Sign up')
                ->submit('register'),
        ];
    }
}