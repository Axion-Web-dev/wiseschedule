<?php

namespace App\Filament\Resources\WpSites\Tables;

use App\Models\WpSite;
use App\Services\WordPressService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;

class WpSitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->paginationPageOptions([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->striped()
            ->deferLoading()
            ->poll('30s')
            ->columns([
                Tables\Columns\ImageColumn::make('site_logo')
                    ->label('Logo')
                    ->size(40)
                    ->circular()
                    ->defaultImageUrl(fn (WpSite $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->site_name) . '&color=7F9CF5&background=EBF4FF')
                    ->placeholder(fn (WpSite $record): string => 'https://ui-avatars.com/api/?name=' . urlencode($record->site_name) . '&color=7F9CF5&background=EBF4FF'),
                Tables\Columns\TextColumn::make('site_name')
                    ->label('Site Name')
                    ->searchable()
                    ->copyable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('site_url')
                    ->label('Site URL')
                    ->searchable()
                    ->copyable()
                    ->limit(30)
                    ->url(fn (WpSite $record): string => $record->site_url)
                    ->openUrlInNewTab(),
                Tables\Columns\BadgeColumn::make('is_connected')
                    ->label('Status')
                    ->getStateUsing(fn (WpSite $record): string => $record->is_connected ? 'Connected' : 'Not Connected')
                    ->color(fn (WpSite $record): string => $record->is_connected ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable()
                    ->description(fn (WpSite $record) => $record->last_synced_at ? $record->last_synced_at->diffForHumans() : 'Never')
                    ->color('gray'),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->label('View')
                    ->icon('heroicon-s-eye') 

                    ->color('green'),
                Action::make('sync_posts')
                    ->label('Sync Posts')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->visible(fn (WpSite $record): bool => (bool) $record->is_connected)
                    ->action(function (WpSite $record, array $arguments) {
                        try {
                            $wpService = new WordPressService();
                            $result = $wpService->syncPosts($record);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Sync Complete')
                                ->body("Successfully synced {$result['synced_count']} posts.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Sync Posts')
                    ->modalDescription('This will fetch posts from WordPress and sync them to your local database.')
                    ->modalSubmitActionLabel('Sync Now')
                    ->extraAttributes(function (WpSite $record) {
                        return [
                            'x-on:click' => '$wire.loading = true',
                            'x-on:click-after' => '$wire.loading = false',
                        ];
                    }),
                Action::make('toggle_connection')
                    ->label(fn (WpSite $record): string => $record->is_connected ? 'Disconnect' : 'Connect')
                    ->icon(fn (WpSite $record): string => $record->is_connected ? 'heroicon-o-x-mark' : 'heroicon-o-link')
                    ->color(fn (WpSite $record): string => $record->is_connected ? 'danger' : 'success')
                    ->action(function (WpSite $record) {
                        try {
                            if ($record->is_connected) {
                                // Disconnect
                                $record->update(['is_connected' => false]);
                                \Filament\Notifications\Notification::make()
                                    ->title('Disconnected')
                                    ->body('Successfully disconnected from blog site.')
                                    ->success()
                                    ->send();
                            } else {
                                // Connect
                                $wpService = new WordPressService();
                                $connected = $wpService->testConnection($record);
                                
                                if ($connected) {
                                    // Fetch and save site logo
                                    $logoUrl = $wpService->fetchSiteLogo($record);
                                    if ($logoUrl) {
                                        $record->update(['site_logo' => $logoUrl]);
                                        \Filament\Notifications\Notification::make()
                                            ->title('Connection Successful')
                                            ->body('Successfully connected to blog site and fetched site logo.')
                                            ->success()
                                            ->send();
                                    } else {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Connection Successful')
                                            ->body('Successfully connected to blog site. No logo found.')
                                            ->success()
                                            ->send();
                                    }
                                } else {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Connection Failed')
                                        ->body('Unable to connect to blog site. Please check your credentials.')
                                        ->danger()
                                        ->send();
                                }
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Connection Error')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation(fn (WpSite $record): bool => !$record->is_connected)
                    ->modalHeading(fn (WpSite $record): string => $record->is_connected ? 'Disconnect from Blog Site' : 'Connect to Blog Site')
                    ->modalDescription(fn (WpSite $record): string => $record->is_connected 
                        ? 'This will disconnect from your blog site.' 
                        : 'This will test the connection to your blog site using the provided credentials.')
                    ->modalSubmitActionLabel(fn (WpSite $record): string => $record->is_connected ? 'Disconnect Now' : 'Connect Now'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
