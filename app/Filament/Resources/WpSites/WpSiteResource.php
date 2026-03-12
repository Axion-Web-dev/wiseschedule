<?php

namespace App\Filament\Resources\WpSites;

use App\Filament\Resources\WpSites\Pages\CreateWpSite;
use App\Filament\Resources\WpSites\Pages\EditWpSite;
use App\Filament\Resources\WpSites\Pages\ListWpSites;
use App\Filament\Resources\WpSites\Schemas\WpSiteForm;
use App\Filament\Resources\WpSites\Tables\WpSitesTable;
use App\Models\WpSite;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WpSiteResource extends Resource
{
    protected static ?string $model = WpSite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'site_url';

    protected static ?string $navigationLabel = 'Blog Sites';

    protected static ?string $modelLabel = 'Blog Site';

    protected static ?string $pluralModelLabel = 'Blog Sites';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return WpSiteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WpSitesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\WpSites\RelationManagers\PostsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWpSites::route('/'),
            'create' => CreateWpSite::route('/create'),
            'edit' => EditWpSite::route('/{record}/edit'),
        ];
    }

    public static function getActions(): array
    {
        return [
            // Actions moved to EditWpSite form for better UX
        ];
    }
}
