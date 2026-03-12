<?php

namespace App\Filament\Resources\Posts\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions as TablesActions;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wpSite.site_name')
                    ->label('Source Site')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(100),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('WordPress Status')
                    ->colors([
                        'success' => 'publish',
                        'warning' => 'pending',
                        'danger' => 'draft',
                    ]),
                Tables\Columns\TextColumn::make('ai_tweet_body')
                    ->label('AI Tweet')
                    ->searchable()
                    ->limit(50)
                    ->placeholder('No tweet')
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'published' => 'Published',
                        'draft' => 'Draft',
                    ]),
            ])
            ->actions([
                Action::make('view')
                    ->label('View Post')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record): string => \App\Filament\Resources\Posts\PostResource::getUrl('view', ['record' => $record])),
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->requiresConfirmation()
                    ->modalHeading('Delete')
                    ->modalDescription('Are you sure you want to delete this post? This will also delete it from WordPress.')
                    ->modalSubmitActionLabel('Delete')
                    ->action(function ($record) {
                        try {
                            // Delete post from WordPress first
                            $wpService = new \App\Services\WordPressService();
                            \Log::info('Attempting to delete WordPress post: ' . $record->wp_post_id . ' from site: ' . $record->wpSite->site_url);
                            
                            $wpSuccess = $wpService->deletePost($record);
                            
                            if ($wpSuccess) {
                                \Log::info('Successfully deleted from WordPress, now deleting from local database');
                                // Delete from local database
                                $record->delete();
                                
                                \Filament\Notifications\Notification::make()
                                    ->title('Post Deleted')
                                    ->body('Post has been deleted from WordPress successfully.')
                                    ->success()
                                    ->send();
                            } else {
                                \Log::error('Failed to delete from WordPress, aborting local delete');
                                \Filament\Notifications\Notification::make()
                                    ->title('Delete Failed')
                                    ->body('Failed to delete post from WordPress. Local post was not deleted.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            \Log::error('Exception during delete: ' . $e->getMessage());
                            \Filament\Notifications\Notification::make()
                                ->title('Delete Error')
                                ->body('An error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
