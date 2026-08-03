<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $data = Kelas::all();
        return response()->json([
            'status'  => 'success',
            'data'    => $data
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kelas'=>'required',
            'kategori'=> ['required', Rule::in(['ikh', 'akh'])],
        ]);

        $kelas = Kelas::create($validated);

        return response()->json([
            'status' => 'success',
            'kelas' => $kelas
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $kelas = Kelas::findOrFail($id);

        if (!$kelas) {
            return response()->json([
                'status' => 'failed',
                'message' => 'User not found'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Success get kelas',
            'kelas' => $kelas
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kelas $kelas)
    {
        $validated = $request->validate([
            'kategori'=> ['required', Rule::in(['ikh', 'akh'])],
        ]);

        $kelas->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data kelas berhasil diperbarui',
            'data'    => $kelas
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kelas $kelas)
    {
        $kelas->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data kelas berhasil dihapus'
        ]);
    }
}
