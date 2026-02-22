<?php

namespace App\Filament\Resources\ClassAssignments\Pages;

use App\Filament\Resources\ClassAssignments\ClassAssignmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClassAssignments extends ListRecords
{
    protected static string $resource = ClassAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(' Tambah Tugas')
                ->icon('heroicon-o-plus'),
        ];
    }
}
