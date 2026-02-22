<?php

namespace App\Filament\Resources\ClassSchedules\Tables;

use App\Imports\ClassScheduleImport;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ClassSchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('hari')
                    ->label('Hari')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'Senin'  => 'info',
                        'Selasa' => 'success',
                        'Rabu'   => 'warning',
                        'Kamis'  => 'danger',
                        'Jumat'  => 'gray',
                        default  => 'info',
                    })
                    ->sortable(query: function ($query, $direction) {
                        $order = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7];
                        // Sort by day order
                        return $query->orderByRaw('FIELD(hari, "Senin","Selasa","Rabu","Kamis","Jumat","Sabtu","Minggu") ' . $direction);
                    }),

                TextColumn::make('waktu_mulai')
                    ->label('Waktu')
                    ->formatStateUsing(fn ($record) => substr($record->waktu_mulai, 0, 5) . ($record->waktu_selesai ? ' – ' . substr($record->waktu_selesai, 0, 5) : ''))
                    ->icon('heroicon-o-clock'),

                TextColumn::make('mata_kuliah')
                    ->label('Mata Kuliah')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->dosen),

                TextColumn::make('kelas')
                    ->label('Kelas')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('sks')
                    ->label('SKS')
                    ->suffix(' sks')
                    ->alignCenter(),

                TextColumn::make('media_pembelajaran')
                    ->label('Media')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'Offline' => 'success',
                        'Zoom', 'Gmeet', 'Teams' => 'info',
                        'Hybrid' => 'warning',
                        default  => 'gray',
                    }),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('hari')
            ->filters([
                SelectFilter::make('hari')
                    ->label('Hari')
                    ->options([
                        'Senin'  => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu'   => 'Rabu',
                        'Kamis'  => 'Kamis',
                        'Jumat'  => 'Jumat',
                        'Sabtu'  => 'Sabtu',
                        'Minggu' => 'Minggu',
                    ]),
                TernaryFilter::make('is_active')->label('Status Aktif'),
            ])
            ->headerActions([
                Action::make('import')
                    ->label('📥 Import dari Excel')
                    ->color('success')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('file')
                            ->label('File Excel (.xlsx / .csv)')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                            ])
                            ->disk('local')
                            ->directory('imports')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $fileValue = $data['file'];

                        if (is_array($fileValue)) {
                            $fileValue = reset($fileValue);
                        }

                        // Livewire 4: bisa berupa TemporaryUploadedFile object
                        if ($fileValue instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                            $path = $fileValue->getRealPath();
                        } else {
                            // Stored path string
                            $path = Storage::disk('local')->path($fileValue);
                        }

                        Excel::import(
                            new ClassScheduleImport(auth()->id()),
                            $path
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('✅ Import Berhasil!')
                            ->body('Jadwal kuliah berhasil diimport dari Excel.')
                            ->success()
                            ->send();
                    }),
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
}
