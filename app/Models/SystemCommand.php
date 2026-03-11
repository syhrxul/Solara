<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action_type',
        'action_payload',
        'status',
        'error_message',
    ];

    protected $casts = [
        'action_payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
