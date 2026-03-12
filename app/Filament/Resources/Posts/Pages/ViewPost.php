<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Posts\PostResource;
use App\Services\AiService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;

class ViewPost extends ViewRecord
{
    protected static string $resource = PostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_ai_tweet')
                ->label('Generate AI Tweet')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->action(function () {
                    try {
                        $aiService = new AiService();
                        $tweet = $aiService->generateTweetDraft($this->record);
                        
                        $this->record->update(['ai_tweet_body' => $tweet]);
                        
                        // Refresh the record to get the updated data
                        $this->record->refresh();
                        
                        Notification::make()
                            ->title('Tweet Generated Successfully')
                            ->body('AI tweet has been generated and saved.')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Generation Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
