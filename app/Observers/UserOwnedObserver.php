<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;

class UserOwnedObserver
{
    public function creating(Model $model): void
    {
        if (auth()->check() && ! isset($model->getAttributes()['user_id'])) {
            $model->user_id = auth()->id();
        }
    }
}
