<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SantriController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // Super Admin: mengambil semua data santri
        $data = Santri::all();

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil mengambil semua data santri',
            'data'    => $data
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama'           => 'required|string|max:255',
            'nim'            => 'required|integer|unique:santris,nim',
            'kategori'       => ['required', Rule::in(['ikh', 'akh'])],
            'jumlah_hafalan' => 'required|integer|min:0',
            'kelas_id'       => 'nullable|integer',
            'guru_id'        => 'nullable|integer',
        ]);

        $santri = Santri::create($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data santri berhasil ditambahkan',
            'data'    => $santri
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $santri = Santri::findOrFail($id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berhasil mengambil detail santri',
            'data'    => $santri
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $santri = Santri::findOrFail($id);

        $validated = $request->validate([
            'nama'           => 'required|string|max:255',
            'nim'            => [
                'required',
                'integer',
                Rule::unique('santris', 'nim')->ignore($santri->id)
            ],
            'kategori'       => ['required', Rule::in(['ikh', 'akh'])],
            'jumlah_hafalan' => 'required|integer|min:0',
            'kelas_id'       => 'nullable|integer',
            'guru_id'        => 'nullable|integer',
        ]);

        $santri->update($validated);

        return response()->json([
            'status'  => 'success',
            'message' => 'Data santri berhasil diperbarui',
            'data'    => $santri->fresh()
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $santri = Santri::findOrFail($id);
        $santri->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Data santri berhasil dihapus'
        ], 200);
    }
}