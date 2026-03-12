<?php

namespace App\Filament\Resources\WpSites\Schemas;

use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class WpSiteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('WordPress Connection')
                    ->schema([
                        Forms\Components\TextInput::make('site_name')
                            ->label('Site Name')
                            ->required()
                            ->rules(['required', 'string', 'max:255'])
                            ->placeholder('My WordPress Site'),
                        Forms\Components\TextInput::make('site_url')
                            ->label('Site URL')
                            ->url()
                            ->required()
                            ->rules(['required', 'url'])
                            ->placeholder('https://example.com'),
                        Forms\Components\TextInput::make('wp_username')
                            ->label('WordPress Username')
                            ->required()
                            ->rules(['required', 'string'])
                            ->placeholder('admin'),
                        Forms\Components\TextInput::make('wp_password')
                            ->label(' Application Password')
                            ->password()
                            ->required()
                            ->revealable()
                            ->rules(['required', 'string'])
                            ->placeholder('Enter application password'),
                    ])
                    ->columns(2),
                
                Section::make('X (Twitter) Connection')
                    ->schema([
                        // FIXED: Using asset() to resolve the 404/path issue
                        Forms\Components\Placeholder::make('twitter_avatar')
                            ->label('Profile Picture')
                            ->visible(fn ($record) => $record && $record->twitter_avatar)
                            ->content(function ($record) {
                                // asset() forces the path to start from http://127.0.0.1:8000/
                                $url = asset('storage/' . $record->twitter_avatar);
                                
                                return new HtmlString("
                                 <div style='display: flex; align-items: center;'>
                <img src='{$url}' 
                     style='
                        width: 80px; 
                        height: 80px; 
                        border-radius: 50%; 
                        object-fit: cover; 
                        /* This border works on any background */
                        border: 2px solid rgba(213, 44, 44, 0.2); 
                        /* This shadow adds depth on light mode */
                        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                        
                     '
                     alt='Avatar'>
            </div>
                                ");
                            }),

                       Forms\Components\Placeholder::make('twitter_status')
    ->label('Connection Status')
    ->content(function ($record) {
        if ($record && $record->twitter_id) {
            return new \Illuminate\Support\HtmlString("
                <div style='display: flex; align-items: center;'>
                    <span style='
                        background-color: #dcfce7; 
                        color: #166534; 
                        padding: 4px 12px; 
                        border-radius: 9999px; 
                        font-size: 14px; 
                        font-weight: 600;
                        display: inline-flex;
                        align-items: center;
                    '>
                        <span style='width: 8px; height: 8px; background-color: #22c55e; border-radius: 50%; margin-right: 8px;'></span>
                        Connected as @{$record->twitter_handle}
                    </span>
                </div>
            ");
        }
        
        return new \Illuminate\Support\HtmlString("
            <span style='color: #6b7280; font-style: italic;'>Not connected</span>
        ");
    }),
                        
                        Forms\Components\Placeholder::make('twitter_connect_action')
                            ->label('Actions')
                            ->content(function ($record) {
                                if (!$record || !$record->twitter_id) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<a href="' . route('twitter.redirect', ['blog_site_id' => $record?->id]) . '" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium">
                                            Connect with X
                                        </a>'
                                    );
                                }
                                return '';
                            })
                            ->visible(fn ($record) => !$record || !$record->twitter_id),
                    ])
                    ->collapsible(),
            ]);
    }
}