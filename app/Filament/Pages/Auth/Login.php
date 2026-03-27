<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Auth\Pages\Login as FilamentLogin;

class Login extends FilamentLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                $this->getEmailFormComponent()
                    ->label('Email Address')
                    ->email()
                    ->required()
                    ->autocomplete('email')
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),
                $this->getPasswordFormComponent()
                    ->label('Password')
                    ->password()
                    ->required()
                    ->autocomplete('current-password')
                    ->extraInputAttributes(['tabindex' => 2]),
                $this->getRememberFormComponent()
                    ->label('Remember me')
                    ->extraInputAttributes(['tabindex' => 3]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('authenticate')
                ->label('Sign in')
                ->submit('authenticate'),
        ];
    }

    protected function getEmailFormComponent(): TextInput
    {
        return TextInput::make('email')
            ->label('Email Address')
            ->email()
            ->required()
            ->autocomplete('email')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): TextInput
    {
        return TextInput::make('password')
            ->label('Password')
            ->password()
            ->revealable()
            ->required()
            ->autocomplete('current-password')
            ->extraInputAttributes(['tabindex' => 2]);
    }

    protected function getRememberFormComponent(): \Filament\Forms\Components\Checkbox
    {
        return \Filament\Forms\Components\Checkbox::make('remember')
            ->label('Remember me')
            ->extraInputAttributes(['tabindex' => 3]);
    }
}