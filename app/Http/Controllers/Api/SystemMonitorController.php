<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SystemMonitorResource;
use App\Http\Traits\ApiResponse;
use App\Models\SystemMonitor;
use Illuminate\Http\Request;

class SystemMonitorController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/system-monitor
     * Ambil riwayat data monitor (terbaru dulu)
     */
    public function index(Request $request)
    {
        $query = SystemMonitor::where('user_id', $request->user()->id)
            ->when($request->from, fn ($q, $d) => $q->where('created_at', '>=', $d))
            ->when($request->to, fn ($q, $d) => $q->where('created_at', '<=', $d))
            ->latest();

        return SystemMonitorResource::collection($query->paginate($request->per_page ?? 30))
            ->additional(['success' => true]);
    }

    /**
     * GET /api/system-monitor/latest
     * Ambil data monitor terbaru
     */
    public function latest(Request $request)
    {
        $latest = SystemMonitor::where('user_id', $request->user()->id)
            ->latest()
            ->first();

        if (!$latest) {
            return $this->error('Belum ada data monitor', 404);
        }

        return $this->success(new SystemMonitorResource($latest));
    }

    /**
     * POST /api/system-monitor
     * Terima data dari Swift app
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cpu.usage' => 'required|numeric',
            'cpu.cores' => 'required|integer',
            'cpu.model' => 'required|string',
            'cpu.userUsage' => 'required|numeric',
            'cpu.systemUsage' => 'required|numeric',
            'cpu.idleUsage' => 'required|numeric',

            'memory.total' => 'required|integer',
            'memory.used' => 'required|integer',
            'memory.free' => 'required|integer',
            'memory.usagePercent' => 'required|numeric',
            'memory.swapTotal' => 'required|integer',
            'memory.swapUsed' => 'required|integer',

            'disk.total' => 'required|integer',
            'disk.used' => 'required|integer',
            'disk.free' => 'required|integer',
            'disk.usagePercent' => 'required|numeric',

            'network.bytesIn' => 'required|integer',
            'network.bytesOut' => 'required|integer',
            'network.speedIn' => 'required|numeric',
            'network.speedOut' => 'required|numeric',

            'uptime.seconds' => 'required|integer',
            'uptime.formatted' => 'required|string',

            'apps' => 'nullable|array',
        ]);

        $monitor = SystemMonitor::create([
            'user_id' => $request->user()->id,

            'cpu_usage' => $validated['cpu']['usage'],
            'cpu_cores' => $validated['cpu']['cores'],
            'cpu_model' => $validated['cpu']['model'],
            'cpu_user' => $validated['cpu']['userUsage'],
            'cpu_system' => $validated['cpu']['systemUsage'],
            'cpu_idle' => $validated['cpu']['idleUsage'],

            'mem_total' => $validated['memory']['total'],
            'mem_used' => $validated['memory']['used'],
            'mem_free' => $validated['memory']['free'],
            'mem_usage_percent' => $validated['memory']['usagePercent'],
            'swap_total' => $validated['memory']['swapTotal'],
            'swap_used' => $validated['memory']['swapUsed'],

            'disk_total' => $validated['disk']['total'],
            'disk_used' => $validated['disk']['used'],
            'disk_free' => $validated['disk']['free'],
            'disk_usage_percent' => $validated['disk']['usagePercent'],

            'net_bytes_in' => $validated['network']['bytesIn'],
            'net_bytes_out' => $validated['network']['bytesOut'],
            'net_speed_in' => $validated['network']['speedIn'],
            'net_speed_out' => $validated['network']['speedOut'],

            'uptime_seconds' => $validated['uptime']['seconds'],
            'uptime_formatted' => $validated['uptime']['formatted'],

            'running_apps' => $validated['apps'] ?? [],
        ]);

        return $this->success(new SystemMonitorResource($monitor), 'Data monitor berhasil disimpan', 201);
    }

    /**
     * DELETE /api/system-monitor/cleanup
     * Hapus data lama (default: lebih dari 7 hari)
     */
    public function cleanup(Request $request)
    {
        $days = $request->days ?? 7;

        $deleted = SystemMonitor::where('user_id', $request->user()->id)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        return $this->success(['deleted' => $deleted], "Berhasil menghapus {$deleted} data lama");
    }
}
