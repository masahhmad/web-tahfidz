<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use function PHPSTORM_META\map;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $data = User::all();
        return response()->json([
            'status'  => 'success',
            'data'    => UserResource::collection($data)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => 'required|min:3',
            'email' => 'required|unique:users,email|email',
            'password' => 'required|between:6, 12',
            'category' => ['required', Rule::in(['ikh', 'akh'])],
            'role' => ['required', Rule::in(['admin', 'guru_halaqah'])]
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
                'status' => 'failed',
                'message' => 'User not found'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Success get user',
            'user' => new UserResource($user)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                Rule::unique('users', 'username')->ignore($user->id) // Unik, abaikan ID sendiri
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id) // Unik, abaikan ID sendiri
            ],
            // Nullable agar password tidak wajib diisi jika pengguna tidak mau ganti password
            'password' => 'nullable|between:6,12',
            'category' => ['required', Rule::in(['ikh', 'akh'])],
            'role'     => ['required', Rule::in(['admin', 'guru_halaqah'])]
        ]);

        $user->update($validated);

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
