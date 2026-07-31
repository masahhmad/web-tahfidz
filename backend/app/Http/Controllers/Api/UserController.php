<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $data = User::all();
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|between:6, 12',
            'category' => 'required',
            'role' => 'required'
        ]);

        $user = User::create($validated);

        return response()->json([
            'status' => 'success',
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = User::findOrFail($id);

        if (!$user) {
            return response()->json([
                'status'=> 'failed',
                'message'=> 'User not found'
            ]);
        }

        return response()->json([
            'status'=> 'success',
            'message'=> 'Success get user',
            'user'=> new UserResource($user)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $user->update($request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Data user berhasil diperbarui',
            'data'    => new UserResource($user)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data user berhasil dihapus'
        ]);
    }
}
