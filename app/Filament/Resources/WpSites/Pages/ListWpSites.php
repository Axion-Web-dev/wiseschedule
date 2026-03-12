<?php

namespace App\Filament\Resources\WpSites\Pages;

use App\Filament\Resources\WpSites\WpSiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWpSites extends ListRecords
{
    protected static string $resource = WpSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
