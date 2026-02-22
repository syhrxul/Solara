<?php

namespace App\Filament\Resources\Habits\Pages;

use App\Filament\Resources\Habits\HabitResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHabit extends EditRecord
{
    protected static string $resource = HabitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
