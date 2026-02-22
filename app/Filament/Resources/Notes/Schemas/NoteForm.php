<?php

namespace App\Filament\Resources\Notes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Catatan')
                        ->placeholder('Judul...')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    RichEditor::make('content')
                        ->label('Isi Catatan')
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsDirectory('notes')
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship(
                                'category',
                                'name',
                                fn ($query) => $query->where('type', 'note')
                                    ->orWhere('type', 'general')
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Tambah tag...')
                            ->separator(','),

                        ColorPicker::make('color')
                            ->label('Warna Catatan'),
                    ]),

                    Grid::make(2)->schema([
                        Toggle::make('is_pinned')
                            ->label('📌 Sematkan'),

                        Toggle::make('is_favorite')
                            ->label('⭐ Favorit'),
                    ]),
                ]),
        ]);
    }
}
