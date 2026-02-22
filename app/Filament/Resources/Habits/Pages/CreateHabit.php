<?php

namespace App\Filament\Resources\Habits\Pages;

use App\Filament\Resources\Habits\HabitResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHabit extends CreateRecord
{
    protected static string $resource = HabitResource::class;
}
