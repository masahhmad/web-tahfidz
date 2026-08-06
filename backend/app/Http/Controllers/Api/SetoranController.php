<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setoran;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SetoranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $data = Setoran::all();

            if ($data->isEmpty()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Data setoran masih kosong',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil semua data setoran',
                'data'    => $data
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data setoran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'baris'     => 'required|integer|min:1',
                'santri_id' => 'required|integer|exists:santris,id',
            ], [
                'baris.required'     => 'Jumlah baris setoran wajib diisi!',
                'baris.integer'      => 'Baris harus berupa angka!',
                'baris.min'          => 'Jumlah baris setoran minimal 1!',
                'santri_id.required' => 'Santri wajib dipilih!',
                'santri_id.integer'  => 'Format ID Santri tidak valid!',
                'santri_id.exists'   => 'Data santri tidak ditemukan di sistem.',
            ]);

            $setoran = Setoran::create($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data setoran berhasil ditambahkan',
                'data'    => $setoran
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menambahkan data setoran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $setoran = Setoran::findOrFail($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil detail setoran',
                'data'    => $setoran
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data setoran tidak ditemukan'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $setoran = Setoran::findOrFail($id);

            $validated = $request->validate([
                'baris'     => 'required|integer|min:1',
                'santri_id' => 'required|integer|exists:santris,id',
            ], [
                'baris.required'     => 'Jumlah baris setoran wajib diisi!',
                'baris.integer'      => 'Baris harus berupa angka!',
                'baris.min'          => 'Jumlah baris setoran minimal 1!',
                'santri_id.required' => 'Santri wajib dipilih!',
                'santri_id.integer'  => 'Format ID Santri tidak valid!',
                'santri_id.exists'   => 'Data santri tidak ditemukan di sistem.',
            ]);

            $setoran->update($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data setoran berhasil diperbarui',
                'data'    => $setoran->fresh()
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data setoran tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui data setoran: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $setoran = Setoran::findOrFail($id);
            $setoran->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data setoran berhasil dihapus'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data setoran yang akan dihapus tidak ditemukan'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus data setoran: ' . $e->getMessage()
            ], 500);
        }
    }
}