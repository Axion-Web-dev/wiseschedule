<?php

namespace App\Filament\Resources\AiArticleResource\Pages;

use App\Filament\Resources\AiArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateAiArticle extends CreateRecord
{
    protected static string $resource = AiArticleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Show a notification suggesting to generate the article
        Notification::make()
            ->title('Article created!')
            ->body('Use "Generate Article" to start AI content generation.')
            ->info()
            ->send();
    }
}
