<?php

namespace App\Filament\Resources\AiArticleResource\Pages;

use App\Filament\Resources\AiArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EditAiArticle extends EditRecord
{
    protected static string $resource = AiArticleResource::class;

    protected function mountRecord(int|string $record): Model
    {
        try {
            return parent::mountRecord($record);
        } catch (ModelNotFoundException $e) {
            Notification::make()
                ->title('Record Not Found')
                ->body('The AI article you are trying to edit does not exist.')
                ->danger()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
            throw $e;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            // Secure "View Live Post" button - only shows when article is published
            Actions\Action::make('viewLivePost')
                ->label('View Live Post')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn () => $this->record->wp_post_url)
                ->openUrlInNewTab()
                ->visible(fn () => $this->record && $this->record->status === 'published' && $this->record->wp_post_url)
                ->tooltip('View the published article on WordPress'),
            
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
