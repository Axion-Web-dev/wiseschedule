<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AiArticleResource\Pages;
use App\Filament\Resources\AiArticleResource\Pages\CreateAiArticle;
use App\Filament\Resources\AiArticleResource\Pages\EditAiArticle;
use App\Filament\Resources\AiArticleResource\Pages\ListAiArticles;
use App\Filament\Resources\AiArticleResource\Schemas\AiArticleForm;
use App\Filament\Resources\AiArticleResource\Tables\AiArticlesTable;
use App\Models\AiArticle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AiArticleResource extends Resource
{
    protected static ?string $model = AiArticle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

protected static string|UnitEnum|null $navigationGroup = 'AI Section';

protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'topic';

    protected static ?string $navigationLabel = 'AI Articles';

    protected static ?string $modelLabel = 'AI Article';

    protected static ?string $pluralModelLabel = 'AI Articles';

    public static function form(Schema $schema): Schema
    {
        return AiArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AiArticlesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiArticles::route('/'),
            'create' => CreateAiArticle::route('/create'),
            'edit' => EditAiArticle::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
