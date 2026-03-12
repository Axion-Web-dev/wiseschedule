<?php

namespace App\Filament\Resources\Posts;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\Posts\Schemas\PostForm;
use App\Filament\Resources\Posts\Tables\PostsTable;
use App\Models\Post;
use App\Models\WpSite;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Actions as NotificationActions;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'WordPress Posts';

    protected static ?string $modelLabel = 'Post';

    protected static ?string $pluralModelLabel = 'WordPress Posts';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PostForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Featured Image')
                    ->schema([
                        ImageEntry::make('featured_image_url')
                            ->label('Featured Image')
                            ->height(400)
                            ->width('100%')
                            ->defaultImageUrl(url('/placeholder-image.jpg'))
                            ->visible(fn ($record): bool => !empty($record->featured_image_url)),
                    ])
                    ->collapsible(),
                
                Section::make('Post Information')
                    ->schema([
                        TextEntry::make('wpSite.site_name')
                            ->label('Source Site'),
                        TextEntry::make('title')
                            ->label('Title'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'publish' => 'success',
                                'pending' => 'warning',
                                'draft' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('url')
                            ->label('URL')
                            ->url(fn ($record) => $record->url)
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2),
                
                Section::make('AI Generated Tweet')
                    ->schema([
                        TextEntry::make('ai_tweet_body')
                            ->label('AI Generated Tweet')
                            ->placeholder('No tweet generated yet')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->headerActions([
                        Action::make('publish_to_x')
                            ->label('Publish to X')
                            ->icon('heroicon-o-paper-airplane')
                            ->color('success')
                            ->requiresConfirmation()
                            ->modalHeading('Publish to X (Twitter)')
                            ->modalDescription('This will post the tweet with featured image to X. Are you sure?')
                            ->modalSubmitActionLabel('Publish to X')
                            ->action(function ($record) {
                                $blogSite = $record->wpSite;
                                
                                if (!$blogSite || !$blogSite->access_token) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('Twitter Not Connected')
                                        ->body('Please connect Twitter account for this blog site first.')
                                        ->send();
                                    return;
                                }
                                
                                if (!$record->ai_tweet_body) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('No Tweet Content')
                                        ->body('Please generate AI tweet content first.')
                                        ->send();
                                    return;
                                }
                                
                                try {
                                    // Step 1: Check and refresh token if needed
                                    $accessToken = $blogSite->access_token;
                                    
                                    if ($blogSite->expires_at && now()->gte($blogSite->expires_at)) {
                                        // Refresh the token with Basic Auth
                                        $refreshResponse = \Http::asForm()
                                            ->withBasicAuth(
                                                config('services.twitter.client_id'),
                                                config('services.twitter.client_secret')
                                            )
                                            ->post('https://api.twitter.com/2/oauth2/token', [
                                                'grant_type' => 'refresh_token',
                                                'refresh_token' => $blogSite->refresh_token,
                                            ]);
                                        
                                        if (!$refreshResponse->successful()) {
                                            throw new \Exception('Failed to refresh access token: ' . $refreshResponse->body());
                                        }
                                        
                                        $tokenData = $refreshResponse->json();
                                        $accessToken = $tokenData['access_token'];
                                        
                                        // Update the blog site with new token
                                        $blogSite->update([
                                            'access_token' => $accessToken,
                                            'refresh_token' => $tokenData['refresh_token'] ?? $blogSite->refresh_token,
                                            'expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 7200),
                                        ]);
                                    }
                                    
                                    // Step 2: Upload Image (if exists) using v2 API
                                    $mediaId = null;
                                    if ($record->featured_image_url) {
                                        $imageContent = file_get_contents($record->featured_image_url);
                                        
                                        $mediaResponse = \Http::withHeaders([
                                            'Authorization' => 'Bearer ' . $accessToken,
                                        ])->asMultipart()
                                          ->post('https://api.twitter.com/2/media/upload', [
                                              'file' => $imageContent,
                                              'media_category' => 'tweet_image', // Required for v2
                                          ]);

                                        if ($mediaResponse->successful()) {
                                            $mediaId = $mediaResponse->json()['data']['id'];
                                        } else {
                                            \Log::warning('Media upload failed: ' . $mediaResponse->body());
                                        }
                                    }

                                    // Step 3: Create the tweet with the Media ID
                                    $tweetData = [
                                        'text' => $record->ai_tweet_body,
                                    ];

                                    if ($mediaId) {
                                        $tweetData['media'] = [
                                            'media_ids' => [(string) $mediaId]
                                        ];
                                    }
                                    
                                    $tweetResponse = \Http::withHeaders([
                                        'Authorization' => 'Bearer ' . $accessToken,
                                        'Content-Type' => 'application/json',
                                    ])->post('https://api.twitter.com/2/tweets', $tweetData);
                                    
                                    if (!$tweetResponse->successful()) {
                                        throw new \Exception('Failed to post tweet: ' . $tweetResponse->body());
                                    }
                                    
                                    $tweetResult = $tweetResponse->json();
                                    $tweetId = $tweetResult['data']['id'];
                                    
                                    // Step 4: Update record status
                                    $record->update(['status' => 'published']);
                                    
                                    \Filament\Notifications\Notification::make()
                                        ->success()
                                        ->title('Successfully Published to X!')
                                        ->body("Tweet posted successfully. Tweet ID: {$tweetId}")
                                        ->actions([
                                            NotificationActions\Action::make('view')
                                                ->label('View on X')
                                                ->url("https://twitter.com/i/web/status/{$tweetId}")
                                                ->openUrlInNewTab(),
                                        ])
                                        ->send();
                                        
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->danger()
                                        ->title('Publishing Failed')
                                        ->body('Error: ' . $e->getMessage())
                                        ->send();
                                }
                            })
                            ->visible(fn ($record) => 
                                $record->ai_tweet_body && 
                                $record->wpSite && 
                                $record->wpSite->access_token &&
                                $record->status !== 'published'
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return PostsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        // Filter by wp_site_id if provided in URL
        if (request()->has('wp_site_id')) {
            $query->where('wp_site_id', request('wp_site_id'));
        }
        
        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPosts::route('/'),
            'create' => CreatePost::route('/create'),
            'view' => \App\Filament\Resources\Posts\Pages\ViewPost::route('/{record}'),
            'edit' => EditPost::route('/{record}/edit'),
        ];
    }
}
