<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SystemShortcut;
use Illuminate\Http\Request;

class SystemShortcutController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $shortcuts = SystemShortcut::where('user_id', $request->user()->id)
            ->orderBy('sort_order', 'asc')
            ->get();
            
        return $this->success($shortcuts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'action_type' => 'required|string',
            'action_payload' => 'nullable|array',
            'sort_order' => 'integer',
        ]);

        $shortcut = SystemShortcut::create([
            ...$validated,
            'user_id' => $request->user()->id,
            'action_payload' => $validated['action_payload'] ?? [],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return $this->success($shortcut, 'Shortcut created', 201);
    }

    public function update(Request $request, $id)
    {
        $shortcut = SystemShortcut::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string',
            'action_type' => 'sometimes|string',
            'action_payload' => 'nullable|array',
            'sort_order' => 'integer',
        ]);

        $shortcut->update($validated);
        return $this->success($shortcut, 'Shortcut updated');
    }

    public function destroy(Request $request, $id)
    {
        $shortcut = SystemShortcut::where('id', $id)->where('user_id', $request->user()->id)->firstOrFail();
        $shortcut->delete();
        return $this->success(null, 'Shortcut deleted');
    }
}
