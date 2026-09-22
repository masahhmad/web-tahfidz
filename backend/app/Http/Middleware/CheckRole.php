<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  ...$roles  Array role yang diizinkan mengakses route ini
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        // Toleran terhadap spasi: `role:admin, guru_halaqah` dipecah Laravel menjadi ['admin', ' guru_halaqah'].
        $allowed = array_map('trim', $roles);

        if (! in_array($user->role, $allowed, true)) {
            return response()->json([
                'message' => 'Akses ditolak, anda tidak memiliki akses untuk fitur ini.',
            ], 403);
        }

        return $next($request);
    }
}
