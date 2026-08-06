<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PresensiSiswa;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class PresensiSiswaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $data = PresensiSiswa::all();

            if ($data->isEmpty()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Data presensi siswa masih kosong',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil semua data presensi siswa',
                'data'    => $data
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data presensi siswa: ' . $e->getMessage()
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
                'status'     => ['required', Rule::in(['hadir', 'sakit', 'izin', 'tidur', 'alpha'])],
                'keterangan' => 'nullable|string|max:255',
                'santri_id'  => 'required|integer|exists:santris,id',
            ], [
                'status.required'   => 'Status presensi wajib diisi!',
                'status.in'         => 'Status hanya boleh berisi: hadir, sakit, izin, tidur, atau alpha.',
                'santri_id.required' => 'Santri wajib dipilih!',
                'santri_id.integer'  => 'Format santri ID tidak valid!',
                'santri_id.exists'   => 'Data santri tidak ditemukan di sistem.',
            ]);


            $presensi = PresensiSiswa::create($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Presensi siswa berhasil dicatat',
                'data'    => $presensi
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mencatat presensi siswa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $presensi = PresensiSiswa::findOrFail($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil detail presensi siswa',
                'data'    => $presensi
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi siswa tidak ditemukan'
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
            $presensi = PresensiSiswa::findOrFail($id);

            $validated = $request->validate([
                'status'     => ['required', Rule::in(['hadir', 'sakit', 'izin', 'tidur', 'alpha'])],
                'keterangan' => 'nullable|string|max:255',
                'santri_id'  => 'nullable|integer|exists:santris,id',
            ], [
                'status.required'  => 'Status presensi wajib diisi!',
                'status.in'        => 'Status hanya boleh berisi: hadir, sakit, izin, tidur, atau alpha.',
                'santri_id.exists' => 'Data santri tidak ditemukan di sistem.',
            ]);

            $presensi->update($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data presensi siswa berhasil diperbarui',
                'data'    => $presensi->fresh()
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi siswa tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui data presensi siswa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $presensi = PresensiSiswa::findOrFail($id);
            $presensi->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data presensi siswa berhasil dihapus'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi siswa yang akan dihapus tidak ditemukan'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus data presensi siswa: ' . $e->getMessage()
            ], 500);
        }
    }
}
