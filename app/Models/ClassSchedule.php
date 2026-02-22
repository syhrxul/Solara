<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'mata_kuliah',
        'kelas',
        'dosen',
        'media_pembelajaran',
        'sks',
        'sesi',
        'hari',
        'waktu_mulai',
        'waktu_selesai',
        'ruangan',
        'is_active',
        'semester',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sks'       => 'integer',
        'sesi'      => 'integer',
    ];

    const HARI_ORDER = [
        'Senin'  => 1,
        'Selasa' => 2,
        'Rabu'   => 3,
        'Kamis'  => 4,
        'Jumat'  => 5,
        'Sabtu'  => 6,
        'Minggu' => 7,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ClassAssignment::class);
    }

    public function scopeToday($query)
    {
        $today = Carbon::now()->locale('id')->isoFormat('dddd');
        // Map English day name to Indonesian
        $dayMap = [
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu',
            'Sunday'    => 'Minggu',
        ];
        $todayId = $dayMap[now()->format('l')] ?? now()->format('l');

        return $query->where('hari', $todayId)->where('is_active', true);
    }

    public function getWaktuLengkapAttribute(): string
    {
        $selesai = $this->waktu_selesai ? ' - ' . substr($this->waktu_selesai, 0, 5) : '';

        return substr($this->waktu_mulai, 0, 5) . $selesai;
    }
}
