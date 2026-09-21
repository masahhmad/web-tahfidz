<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->profile($request);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $validated = $request->validate([
            'nama'  => 'required|string|min:3|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            // Role tidak boleh diubah lewat profil sendiri.
            'role'  => 'prohibited',
        ], [
            'email.unique'    => 'Email sudah digunakan.',
            'role.prohibited' => 'Role tidak dapat diubah.',
        ]);

        $user->update([
            'username' => $validated['nama'],
            'email'    => $validated['email'],
        ]);

        return $this->profile($request);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $validated = $request->validate([
            'current_password' => 'required|string|current_password:api',
            'password'         => 'required|string|min:8|confirmed',
        ], [
            'current_password.current_password' => 'Password saat ini salah.',
            'password.confirmed'                => 'Konfirmasi password tidak cocok.',
        ]);

        $user->forceFill([
            'password'            => $validated['password'],
            'password_changed_at' => now(),
        ])->save();

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $auth */
        $auth = auth('api');
        $auth->logout();                 // token lama dicabut
        $token = $auth->login($user);    // token baru supaya sesi ini tidak terputus

        return response()->json([
            'message'      => 'Password berhasil diganti.',
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $auth->factory()->getTTL() * 60,
        ]);
    }

    private function profile(Request $request): JsonResponse
    {
        $user = $request->user('api');

        return response()->json([
            'data' => array_merge((new UserResource($user))->resolve(), [
                'jumlah_siswa' => $user->santri()->count(),
            ]),
        ]);
    }
}
