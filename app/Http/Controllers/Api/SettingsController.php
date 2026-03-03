<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/settings
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $settings = $user->settings ?? [];

        return $this->success([
            'settings'         => $settings,
            'telegram_chat_id' => $user->telegram_chat_id,
        ]);
    }

    /**
     * PUT /api/settings
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        $currentSettings = $user->settings ?? [];
        $merged = array_merge($currentSettings, $validated['settings']);

        $user->update(['settings' => $merged]);

        return $this->success(['settings' => $merged], 'Pengaturan berhasil diperbarui');
    }

    /**
     * PUT /api/settings/telegram
     */
    public function updateTelegram(Request $request)
    {
        $validated = $request->validate([
            'telegram_chat_id' => 'nullable|string',
        ]);

        $request->user()->update($validated);

        return $this->success(null, 'Telegram chat ID berhasil diperbarui');
    }

    /**
     * PUT /api/settings/location
     */
    public function updateLocation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'lat'  => 'required|numeric',
            'lng'  => 'required|numeric',
        ]);

        $user = $request->user();
        $settings = $user->settings ?? [];
        $settings['jadwal_location'] = $validated;

        // Also save to saved_locations
        $savedLocations = $settings['saved_locations'] ?? [];
        $savedLocations[$validated['name']] = $validated;
        $settings['saved_locations'] = $savedLocations;

        $user->update(['settings' => $settings]);

        return $this->success(['location' => $validated], 'Lokasi berhasil diperbarui');
    }
}
