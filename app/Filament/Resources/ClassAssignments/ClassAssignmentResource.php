<?php

namespace App\Filament\Resources\ClassAssignments;

use App\Filament\Resources\ClassAssignments\Pages\CreateClassAssignment;
use App\Filament\Resources\ClassAssignments\Pages\EditClassAssignment;
use App\Filament\Resources\ClassAssignments\Pages\ListClassAssignments;
use App\Models\ClassAssignment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ClassAssignmentResource extends Resource
{
    protected static ?string $model = ClassAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Tugas Kuliah';

    protected static string|UnitEnum|null $navigationGroup = 'Akademik';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Tugas';

    protected static ?string $pluralModelLabel = 'Tugas Kuliah';

    public static function getNavigationBadge(): ?string
    {
        $count = ClassAssignment::where('user_id', auth()->id())
            ->where('status', 'belum')
            ->whereNotNull('deadline')
            ->where('deadline', '>=', today())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Detail Tugas')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    TextInput::make('title')
                        ->label('Judul Tugas')
                        ->placeholder('Contoh: Tugas 1 - Entity Relationship Diagram')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Select::make('class_schedule_id')
                            ->label('Mata Kuliah')
                            ->relationship(
                                'classSchedule',
                                'mata_kuliah',
                                fn ($query) => $query->where('user_id', auth()->id())
                            )
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Select::make('type')
                            ->label('Jenis')
                            ->options([
                                'tugas'       => '📝 Tugas',
                                'kuis'        => '📋 Kuis',
                                'ujian'       => '📖 Ujian',
                                'presentasi'  => '🎤 Presentasi',
                                'praktikum'   => '🔬 Praktikum',
                                'lainnya'     => '📌 Lainnya',
                            ])
                            ->default('tugas')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'belum'      => '⏳ Belum Dikerjakan',
                                'dikerjakan' => '🔄 Sedang Dikerjakan',
                                'selesai'    => '✅ Selesai',
                            ])
                            ->default('belum')
                            ->required(),

                        DatePicker::make('deadline')
                            ->label('Deadline')
                            ->native(false)
                            ->displayFormat('d M Y'),
                    ]),

                    TextInput::make('nilai')
                        ->label('Nilai')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100')
                        ->nullable(),

                    Textarea::make('description')
                        ->label('Keterangan')
                        ->placeholder('Detail tugas, requirements, dsb...')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deadline')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : 'gray'),

                TextColumn::make('classSchedule.mata_kuliah')
                    ->label('Mata Kuliah')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Tugas')
                    ->searchable()
                    ->weight('medium'),

                BadgeColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'tugas'      => '📝 Tugas',
                        'kuis'       => '📋 Kuis',
                        'ujian'      => '📖 Ujian',
                        'presentasi' => '🎤 Presentasi',
                        'praktikum'  => '🔬 Praktikum',
                        default      => '📌 ' . ucfirst($state),
                    }),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'belum',
                        'info'    => 'dikerjakan',
                        'success' => 'selesai',
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'belum'      => '⏳ Belum',
                        'dikerjakan' => '🔄 Dikerjakan',
                        'selesai'    => '✅ Selesai',
                        default      => $state,
                    }),

                TextColumn::make('nilai')
                    ->label('Nilai')
                    ->suffix('/100')
                    ->color(fn ($record) => match (true) {
                        $record->nilai >= 85 => 'success',
                        $record->nilai >= 70 => 'warning',
                        $record->nilai > 0   => 'danger',
                        default              => 'gray',
                    }),
            ])
            ->defaultSort('deadline', 'asc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'belum'      => 'Belum',
                        'dikerjakan' => 'Dikerjakan',
                        'selesai'    => 'Selesai',
                    ]),
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'tugas'      => 'Tugas',
                        'kuis'       => 'Kuis',
                        'ujian'      => 'Ujian',
                        'presentasi' => 'Presentasi',
                        'praktikum'  => 'Praktikum',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListClassAssignments::route('/'),
            'create' => CreateClassAssignment::route('/create'),
            'edit'   => EditClassAssignment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canViewAny(): bool
    {
        $settings = auth()->user()->settings ?? [];
        return $settings['module_academic'] ?? true;
    }
}
