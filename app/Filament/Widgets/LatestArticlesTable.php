<?php

namespace App\Filament\Widgets;

use App\Models\AiArticle;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class LatestArticlesTable extends TableWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => AiArticle::query()->latest()->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('topic')
                    ->label('Article Topic')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        return $column->getState();
                    }),
                    
                Tables\Columns\TextColumn::make('wpSite.site_name')
                    ->label('WordPress Site')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => ['pending', 'refining'],
                        'info' => ['generating', 'generating_image'],
                        'success' => ['ready', 'published'],
                        'primary' => 'scheduled',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state): string => Str::title($state)),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('wp_post_url')
                    ->label('WordPress URL')
                    ->url(fn ($record) => $record->wp_post_url)
                    ->openUrlInNewTab()
                    ->placeholder('Not published')
                    ->color('success'),
            ])
            ->heading('Latest Articles')
            ->description('Most recent 5 articles')
            ->paginated([5])
            ->striped();
    }
}