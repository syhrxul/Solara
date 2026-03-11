<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemMonitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'cpu_usage', 'cpu_cores', 'cpu_model', 'cpu_user', 'cpu_system', 'cpu_idle',
        'mem_total', 'mem_used', 'mem_free', 'mem_usage_percent', 'swap_total', 'swap_used',
        'disk_total', 'disk_used', 'disk_free', 'disk_usage_percent',
        'net_bytes_in', 'net_bytes_out', 'net_speed_in', 'net_speed_out',
        'uptime_seconds', 'uptime_formatted',
    ];

    protected $casts = [
        'cpu_usage' => 'decimal:2',
        'cpu_user' => 'decimal:2',
        'cpu_system' => 'decimal:2',
        'cpu_idle' => 'decimal:2',
        'mem_usage_percent' => 'decimal:2',
        'disk_usage_percent' => 'decimal:2',
        'net_speed_in' => 'decimal:2',
        'net_speed_out' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
