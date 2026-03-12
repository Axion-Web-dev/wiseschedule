<?php

namespace App\Filament\Resources\AiArticleResource\Tables;

use App\Jobs\GenerateAiArticle;
use App\Jobs\PublishToWordPress;
use App\Jobs\GenerateGeminiImageJob;
use App\Jobs\ProcessBulkArticles;
use App\Models\AiArticle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AiArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->poll('5s') // Check for updates every 5 seconds
            ->defaultSort('created_at', 'desc') // Show newest articles first
            ->columns([
                Tables\Columns\TextColumn::make('topic')
                    ->label('Topic')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    }),

                Tables\Columns\TextColumn::make('wpSite.site_name')
                    ->label('Target Site')
                    ->searchable()
                    ->sortable()
                    ->placeholder('No site assigned')
                    ->default('No site assigned')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'generating',
                        'success' => 'ready',
                        'primary' => 'published',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => Str::title($state)),

                Tables\Columns\TextColumn::make('tone')
                    ->label('Tone')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'generating' => 'Generating',
                        'ready' => 'Ready',
                        'published' => 'Published',
                        'failed' => 'Failed',
                    ]),

                Tables\Filters\SelectFilter::make('wp_site_id')
                    ->label('WordPress Site')
                    ->relationship('wpSite', 'site_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('tone')
                    ->options([
                        'professional' => 'Professional',
                        'casual' => 'Casual',
                        'friendly' => 'Friendly',
                        'technical' => 'Technical',
                        'conversational' => 'Conversational',
                        'formal' => 'Formal',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('generate')
                    ->label('Generate')
                    ->icon('heroicon-o-sparkles')
                    ->color('success')
                    ->action(function (AiArticle $record) {
                        // Dispatch the background job
                        GenerateAiArticle::dispatch($record);

                        Notification::make()
                            ->title('AI Generation Started')
                            ->body('The article is being generated in the background. You can continue working while the AI creates your content.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn (AiArticle $record) => in_array($record->status, ['pending', 'failed']))
                    ->requiresConfirmation()
                    ->modalHeading('Generate AI Article')
                    ->modalDescription('This will start the AI generation process for this article. The generation will happen in the background and may take 30-60 seconds.')
                    ->modalSubmitActionLabel('Start Generation'),
                Action::make('publish')
                    ->label('Post to WordPress')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('primary')
                    ->action(function (AiArticle $record) {
                        // Dispatch the publish job
                        PublishToWordPress::dispatch($record);

                        Notification::make()
                            ->title('Publishing to WordPress')
                            ->body('Your article is being published to WordPress. This may take a few moments.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn (AiArticle $record) => $record->status === 'ready')
                    ->requiresConfirmation()
                    ->modalHeading('Post to WordPress')
                    ->modalDescription('This will publish the article to your WordPress site. Make sure your WordPress site credentials are configured correctly.')
                    ->modalSubmitActionLabel('Publish Now'),
                Action::make('generate_image')
                    ->label('Generate Image (Hugging Face)')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->action(function (AiArticle $record) {
                        // Dispatch the image generation job
                        GenerateGeminiImageJob::dispatch($record);

                        Notification::make()
                            ->title('Hugging Face is working...')
                            ->body('AI is generating a featured image for your article using FLUX.1-schnell. This may take 30-60 seconds.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn (AiArticle $record) => in_array($record->status, ['ready', 'failed']))
                    ->requiresConfirmation()
                    ->modalHeading('Generate AI Image')
                    ->modalDescription('This will generate a professional featured image using Hugging Face FLUX.1-schnell based on your article topic and keywords.')
                    ->modalSubmitActionLabel('Generate Image'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    
                    // Bulk Generate Articles
                    Action::make('bulk_generate')
                        ->label('Generate Articles')
                        ->icon('heroicon-o-sparkles')
                        ->color('success')
                        ->action(function ($records) {
                            // Process in chunks to prevent timeouts
                            ProcessBulkArticles::dispatch($records, 'generate');
                            
                            Notification::make()
                                ->title('Bulk Generation Started')
                                ->body(count($records) . ' articles queued for generation. Processing in chunks to prevent timeouts.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Generate Multiple Articles')
                        ->modalDescription('This will queue all selected articles for AI generation in chunks to prevent timeouts.'),
                    
                    // Bulk Publish Articles
                    Action::make('bulk_publish')
                        ->label('Publish to WordPress')
                        ->icon('heroicon-o-cloud-arrow-up')
                        ->color('primary')
                        ->action(function ($records) {
                            // Process in chunks to prevent timeouts
                            ProcessBulkArticles::dispatch($records, 'publish');
                            
                            Notification::make()
                                ->title('Bulk Publishing Started')
                                ->body(count($records) . ' articles queued for publishing. Processing in chunks to prevent timeouts.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Publish Multiple Articles')
                        ->modalDescription('This will queue all selected articles for WordPress publishing in chunks to prevent timeouts.'),
                    
                    // Bulk Generate Images
                    Action::make('bulk_generate_images')
                        ->label('Generate Images')
                        ->icon('heroicon-o-photo')
                        ->color('info')
                        ->action(function ($records) {
                            // Process in chunks to prevent timeouts
                            ProcessBulkArticles::dispatch($records, 'generate_image');
                            
                            Notification::make()
                                ->title('Bulk Image Generation Started')
                                ->body(count($records) . ' images queued for generation. Processing in chunks to prevent timeouts.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation()
                        ->modalHeading('Generate Multiple Images')
                        ->modalDescription('This will queue all selected articles for image generation in chunks to prevent timeouts.'),
                ]),
            ])
            ->emptyStateHeading('No AI articles found')
            ->emptyStateDescription('Create your first AI article to get started.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create AI Article')
                    ->url(fn (): string => route('filament.admin.resources.ai-articles.create')),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                // Eager load relationships to prevent N+1 queries
                $query->with(['wpSite']);
            });
    }
}
