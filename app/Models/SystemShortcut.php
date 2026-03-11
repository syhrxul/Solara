<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemShortcut extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'icon',
        'action_type',
        'action_payload',
        'sort_order',
    ];

    protected $casts = [
        'action_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
