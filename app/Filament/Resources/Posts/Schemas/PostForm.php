<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->readOnly(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull()
                    ->readOnly(),
                TextInput::make('url')
                    ->url()
                    ->required()
                    ->readOnly(),
                TextInput::make('status')
                    ->required()
                    ->readOnly(),
            ]);
    }
}
