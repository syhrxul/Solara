<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class PushNotificationWidget extends Widget
{
    protected string $view = 'filament.widgets.push-notification';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function getVapidPublicKey(): string
    {
        return config('webpush.vapid.public_key', '');
    }
}
