<?php

namespace App\Filament\Resources\WpSites\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Tables\Actions;

class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';
    protected static ?string $recordTitleAttribute = 'title';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('WordPress Status')
                    ->colors([
                        'success' => 'publish',
                        'warning' => 'pending',
                        'danger' => 'draft',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Synced At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'published' => 'Published',
                        'draft' => 'Draft',
                    ]),
            ])
            ->headerActions([
                // No create action - posts are synced from WordPress
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record): string => \App\Filament\Resources\Posts\PostResource::getUrl('view', ['record' => $record])),
                
               
                
                \Filament\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                // No bulk actions for security
            ]);
    }
}
