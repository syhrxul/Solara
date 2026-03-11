<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemMonitorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cpu' => [
                'usage' => (float) $this->cpu_usage,
                'cores' => $this->cpu_cores,
                'model' => $this->cpu_model,
                'userUsage' => (float) $this->cpu_user,
                'systemUsage' => (float) $this->cpu_system,
                'idleUsage' => (float) $this->cpu_idle,
            ],
            'memory' => [
                'total' => $this->mem_total,
                'used' => $this->mem_used,
                'free' => $this->mem_free,
                'usagePercent' => (float) $this->mem_usage_percent,
                'swapTotal' => $this->swap_total,
                'swapUsed' => $this->swap_used,
            ],
            'disk' => [
                'total' => $this->disk_total,
                'used' => $this->disk_used,
                'free' => $this->disk_free,
                'usagePercent' => (float) $this->disk_usage_percent,
            ],
            'network' => [
                'bytesIn' => $this->net_bytes_in,
                'bytesOut' => $this->net_bytes_out,
                'speedIn' => (float) $this->net_speed_in,
                'speedOut' => (float) $this->net_speed_out,
            ],
            'uptime' => [
                'seconds' => $this->uptime_seconds,
                'formatted' => $this->uptime_formatted,
            ],
            'recorded_at' => $this->created_at->toISOString(),
        ];
    }
}
