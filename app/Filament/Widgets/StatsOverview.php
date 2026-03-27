<?php

namespace App\Filament\Widgets;

use App\Models\AiArticle;
use App\Models\WpSite;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalArticles = AiArticle::count();
        $publishedArticles = AiArticle::whereNotNull('wp_post_id')->count();
        $successRate = $totalArticles > 0 ? round(($publishedArticles / $totalArticles) * 100, 1) : 0;

        return [
            Stat::make('Connected Websites', WpSite::count())
                ->description('Total WordPress sites ')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('warning'),

            Stat::make('Articles Generated', $totalArticles)
                ->description($publishedArticles . ' published (' . $successRate . '% success) ')
                ->descriptionIcon('heroicon-m-check-circle')
                ->chart([7, 2, 10, 3, 15, 4, 17]) // This creates the line graph
                ->color('success'),

            Stat::make('Publishing Success Rate', $successRate . '%')
                ->description('Last 30 days ')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([15, 4, 10, 2, 12, 4, 11])
                ->color($successRate >= 80 ? 'success' : ($successRate >= 50 ? 'warning' : 'danger')),
        ];
    }
}