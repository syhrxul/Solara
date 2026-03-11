<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SystemCommand;
use Illuminate\Http\Request;

class SystemCommandController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/system-actions/execute
     * Enqueue a new command to be executed by Mac
     */
    public function execute(Request $request)
    {
        $validated = $request->validate([
            'action_type' => 'required|string',
            'action_payload' => 'nullable|array',
        ]);

        $command = SystemCommand::create([
            'user_id' => $request->user()->id,
            'action_type' => $validated['action_type'],
            'action_payload' => $validated['action_payload'] ?? [],
            'status' => 'pending',
        ]);

        return $this->success($command, 'Command queued for execution', 201);
    }

    /**
     * GET /api/system-actions/pending
     * Called by Swift app to get commands waiting to be executed
     */
    public function pending(Request $request)
    {
        $command = SystemCommand::where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->orderBy('id', 'asc')
            ->first();

        // Swift app only needs the first pending command to execute one by one
        return $this->success($command); // Returns null data if no pending commands
    }

    /**
     * PATCH /api/system-actions/{id}
     * Called by Swift app to update status of a command
     */
    public function update(Request $request, $id)
    {
        $command = SystemCommand::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'status' => 'required|in:completed,failed',
            'error_message' => 'nullable|string',
        ]);

        $command->update([
            'status' => $validated['status'],
            'error_message' => $validated['error_message'],
        ]);

        return $this->success($command, 'Command status updated');
    }
}
