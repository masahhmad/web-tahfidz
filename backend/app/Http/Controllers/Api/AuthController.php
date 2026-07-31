<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request)
    {
        $credential = $request->validate([
            'email' => 'required|email',
            'password'=> 'required'
        ]);

        /** @var \PHPOpenSourceSaver\JWTAuth\JWTGuard $auth */
        $auth = auth('api');

        if (! $accessToken = $auth->attempt($credential)) {
            return response()->json([
                'status'       => 'failed',
                'message' => "Incorrect email or password"
            ]);
        }
        return response()->json([
            'status'       => 'success',
            'access_token' => $accessToken,
            'token_type'   => 'bearer',
            'expires_in'   => $auth->factory()->getTTL() * 60,
            'user'         => $auth->user()
        ]);
    }
}
