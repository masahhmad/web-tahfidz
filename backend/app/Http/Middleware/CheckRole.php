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
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if(!auth('api')->check()){
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthenticated, Silakan login terlebih dahulu.'
            ]);
        }

        $user = auth('api')->user();

        if (! in_array($user->role, $roles)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Akses ditolak, anda tidak memiliki akses untuk fitur ini.'
            ]);
        }

        return $next($request);
    }
}
