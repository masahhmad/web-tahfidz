<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Dicek di setiap request (bukan hanya saat login) supaya token yang sudah terbit
     * ikut mati ketika akun dinonaktifkan atau password-nya diganti.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        if (! $user) {
            return response()->json(['message' => 'Silakan login terlebih dahulu.'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Akun Anda dinonaktifkan.'], 403);
        }

        // Token yang terbit sebelum password terakhir diganti tidak berlaku lagi.
        if ($user->password_changed_at && $guard->payload()->get('iat') < $user->password_changed_at->getTimestamp()) {
            return response()->json(['message' => 'Password telah diganti, silakan login kembali.'], 401);
        }

        return $next($request);
    }
}
