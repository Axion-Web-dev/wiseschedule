<?php

namespace App\Filament\Resources\WpSites\Pages;

use App\Filament\Resources\WpSites\WpSiteResource;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditWpSite extends EditRecord
{
    protected static string $resource = WpSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            // Basic Site Information Section
            Forms\Components\Section::make('Site Information')
                ->schema([
                    Forms\Components\TextInput::make('site_name')
                        ->label('Site Name')
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('site_url')
                        ->label('Site URL')
                        ->required()
                        ->url()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('wp_username')
                        ->label('WordPress Username')
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('wp_password')
                        ->label('WordPress Password')
                        ->password()
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2),
            
            // WordPress Connection Section
            Forms\Components\Section::make('WordPress Connection')
                ->schema([
                    Forms\Components\Placeholder::make('wordpress_status')
                        ->label('Connection Status')
                        ->content(function ($record) {
                            if ($record->is_connected) {
                                return '<div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3"/>
                                        </svg>
                                        Connected
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        Last synced: ' . ($record->last_synced_at ? $record->last_synced_at->diffForHumans() : 'Never') . '
                                    </span>
                                </div>';
                            } else {
                                return '<div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-gray-400" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3"/>
                                        </svg>
                                        Not Connected
                                    </span>
                                </div>';
                            }
                        }),
                    
                    Forms\Components\Actions::make([
                        Forms\Components\Action::make('test_wordpress_connection')
                            ->label('Test Connection')
                            ->icon('heroicon-o-link')
                            ->color('info')
                            ->visible(fn ($record) => !$record->is_connected)
                            ->action(function ($record) {
                                try {
                                    $wpService = new \App\Services\WordPressService();
                                    $connected = $wpService->testConnection($record);
                                    
                                    if ($connected) {
                                        $record->update([
                                            'is_connected' => true, 
                                            'last_synced_at' => now()
                                        ]);
                                        Notification::make()
                                            ->title('Connected')
                                            ->body('Successfully connected to WordPress site.')
                                            ->success()
                                            ->send();
                                    } else {
                                        Notification::make()
                                            ->title('Connection Failed')
                                            ->body('Failed to connect to WordPress site. Please check your credentials.')
                                            ->danger()
                                            ->send();
                                    }
                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Connection test failed: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                        
                        Forms\Components\Action::make('disconnect_wordpress')
                            ->label('Disconnect')
                            ->icon('heroicon-o-x-mark')
                            ->color('danger')
                            ->visible(fn ($record) => $record->is_connected)
                            ->requiresConfirmation()
                            ->action(function ($record) {
                                $record->update(['is_connected' => false]);
                                Notification::make()
                                    ->title('Disconnected')
                                    ->body('Successfully disconnected from WordPress site.')
                                    ->success()
                                    ->send();
                            }),
                    ]),
                ])
                ->columns(1),
            
            // Twitter Connection Section
            Forms\Components\Section::make('X (Twitter) Connection')
                ->schema([
                    Forms\Components\Placeholder::make('twitter_avatar')
                        ->label('Avatar')
                        ->content(function ($record) {
                            if ($record->twitter_avatar) {
                                return '<img src="' . asset('storage/' . $record->twitter_avatar) . '" alt="' . e($record->twitter_handle) . '" class="h-16 w-16 rounded-full object-cover border-2 border-gray-200">';
                            }
                            return '<div class="h-16 w-16 rounded-full bg-gray-200 flex items-center justify-center">
                                <svg class="h-8 w-8 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </div>';
                        }),
                    
                    Forms\Components\Placeholder::make('twitter_handle')
                        ->label('Handle')
                        ->content(function ($record) {
                            if ($record->twitter_handle) {
                                return '<div>
                                    <p class="text-lg font-semibold text-gray-900">@' . e($record->twitter_handle) . '</p>
                                    <p class="text-sm text-gray-500">Twitter ID: ' . e($record->twitter_id) . '</p>
                                    <p class="text-xs text-gray-400 mt-1">Token expires: ' . ($record->expires_at ? $record->expires_at->diffForHumans() : 'Unknown') . '</p>
                                </div>';
                            }
                            return '<span class="text-gray-500">Not connected</span>';
                        }),
                    
                    Forms\Components\Placeholder::make('twitter_status_badge')
                        ->label('Status')
                        ->content(function ($record) {
                            if ($record->twitter_id) {
                                return '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-green-400" fill="currentColor" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="3"/>
                                    </svg>
                                    Connected
                                </span>';
                            }
                            return '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-gray-400" fill="currentColor" viewBox="0 0 8 8">
                                    <circle cx="4" cy="4" r="3"/>
                                </svg>
                                Not Connected
                            </span>';
                        }),
                    
                    Forms\Components\Actions::make([
                        Forms\Components\Action::make('connect_twitter')
                            ->label('Connect with X')
                            ->icon('heroicon-o-link')
                            ->color('info')
                            ->visible(fn ($record) => !$record->twitter_id)
                            ->action(function ($record) {
                                return redirect()->route('twitter.redirect', ['blog_site_id' => $record->id]);
                            }),
                        
                        Forms\Components\Action::make('disconnect_twitter')
                            ->label('Disconnect X')
                            ->icon('heroicon-o-x-mark')
                            ->color('danger')
                            ->visible(fn ($record) => $record->twitter_id)
                            ->requiresConfirmation()
                            ->modalHeading('Disconnect X (Twitter)')
                            ->modalDescription('This will disconnect your X (Twitter) account from this blog site.')
                            ->action(function ($record) {
                                $record->update([
                                    'twitter_id' => null,
                                    'twitter_handle' => null,
                                    'twitter_avatar' => null,
                                    'access_token' => null,
                                    'refresh_token' => null,
                                    'expires_at' => null,
                                ]);
                                
                                Notification::make()
                                    ->title('Disconnected')
                                    ->body('Successfully disconnected from X (Twitter).')
                                    ->success()
                                    ->send();
                            }),
                    ]),
                ])
                ->columns(2),
        ];
    }
}
