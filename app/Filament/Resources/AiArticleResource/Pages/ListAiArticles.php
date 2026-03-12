<?php

namespace App\Filament\Resources\AiArticleResource\Pages;

use App\Filament\Resources\AiArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class ListAiArticles extends ListRecords
{
    protected static string $resource = AiArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('generate-article')
                ->label('Generate Article')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->action(function (array $data) {
                    // For now, just show a success notification
                    // In the future, this will trigger AI generation
                    Notification::make()
                        ->title('AI Generation Started')
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Generate AI Article')
                ->modalDescription('This will start the AI generation process for the selected article.')
                ->modalSubmitActionLabel('Start Generation'),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
}
