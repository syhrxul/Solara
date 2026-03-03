<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/auth/register
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('solara-api')->plainTextToken;

        return $this->success([
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ], 'Registrasi berhasil', 201);
    }

    /**
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->error('Email atau password salah', 401);
        }

        $user = Auth::user();
        $token = $user->createToken('solara-api')->plainTextToken;

        return $this->success([
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ], 'Login berhasil');
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logout berhasil');
    }

    /**
     * GET /api/auth/profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return $this->success([
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'settings'        => $user->settings,
            'telegram_chat_id' => $user->telegram_chat_id,
            'created_at'      => $user->created_at,
        ]);
    }

    /**
     * PUT /api/auth/profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['sometimes', 'confirmed', Password::defaults()],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return $this->success([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ], 'Profil berhasil diperbarui');
    }
}
