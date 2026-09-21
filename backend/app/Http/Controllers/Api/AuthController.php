<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $auth */
        $auth = auth('api');

        if (! $token = $auth->attempt($credentials)) {
            return $this->message('Email atau password salah.', 401);
        }

        // Password sudah benar, tapi akun dinonaktifkan: token yang baru terbit dicabut lagi.
        if (! $auth->user()->is_active) {
            $auth->logout();

            return $this->message('Akun Anda dinonaktifkan.', 403);
        }

        return $this->tokenResponse($token, $auth->user());
    }

    public function refresh(): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $auth */
        $auth = auth('api');

        return $this->tokenResponse($auth->refresh(), $auth->user());
    }

    public function logout(): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $auth */
        $auth = auth('api');
        $auth->logout();

        return $this->message('Berhasil keluar.');
    }

    private function tokenResponse(string $token, User $user): JsonResponse
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $auth */
        $auth = auth('api');

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => $auth->factory()->getTTL() * 60,
            'user'         => (new UserResource($user))->resolve(),
        ]);
    }
}
