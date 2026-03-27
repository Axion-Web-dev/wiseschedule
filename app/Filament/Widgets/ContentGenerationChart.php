<?php

namespace App\Filament\Widgets;

use App\Models\AiArticle;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ContentGenerationChart extends ChartWidget
{
    protected static ?int $sort = 3;
    
    protected ?string $heading = 'Content Generation & Publishing Trend';
    
    protected ?string $description = 'Daily content creation and publishing activity (Last 7 days)';
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getData(): array
    {

        $days = collect(range(6, 0))->map(function ($day) {
            return now()->subDays($day)->format('Y-m-d');
        });
        
        $labels = $days->map(function ($day) {
            return \Carbon\Carbon::parse($day)->format('M j');
        })->toArray();

        $generatedData = $days->map(function ($day) {
            return AiArticle::whereDate('created_at', $day)->count();
        })->toArray();

        $publishedData = $days->map(function ($day) {
            return AiArticle::whereDate('updated_at', $day)
                ->whereNotNull('wp_post_id')
                ->count();
        })->toArray();
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Articles Generated',
                    'data' => $generatedData,
                    'backgroundColor' => 'rgba(195, 192, 255, 0.2)', // Landing page purple
                    'borderColor' => 'rgba(195, 192, 255, 1)', // Landing page purple
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Articles Published',
                    'data' => $publishedData,
                    'backgroundColor' => 'rgba(79, 70, 229, 0.2)', // Primary container purple
                    'borderColor' => 'rgba(79, 70, 229, 1)', // Primary container purple
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.1)',
                    ],
                ],
                'x' => [
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.1)',
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'elements' => [
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
        ];
    }
}