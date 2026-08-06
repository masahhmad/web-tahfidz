<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PresensiGuru;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class PresensiGuruController extends Controller
{
    /**
     * Mengambil semua data presensi (Super Admin / Rekap).
     */
    public function getAllAtendance(): JsonResponse
    {
        try {
            $data = PresensiGuru::all();

            if ($data->isEmpty()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Data presensi masih kosong',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil semua data presensi guru',
                'data'    => $data
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data presensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mengambil data presensi khusus guru yang sedang login.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $data   = PresensiGuru::where('user_id', $userId)->get();

            if ($data->isEmpty()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Anda belum memiliki riwayat presensi',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil riwayat presensi guru',
                'data'    => $data
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil riwayat presensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyimpan presensi baru.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status'     => ['required', Rule::in(['hadir', 'izin', 'sakit'])],
                'keterangan' => 'nullable|string|max:255',
                'lokasi'     => 'nullable|string|max:255',
                'user_id'    => 'required|integer|exists:users,id',
            ], [
                'status.required' => 'Status presensi wajib diisi!',
                'status.in'       => 'Status hanya boleh berisi hadir, izin, atau sakit.',
                'user_id.required' => 'User ID wajib diisi!',
                'user_id.exists'  => 'User/Guru tidak ditemukan di sistem.',
            ]);

            $presensi = PresensiGuru::create($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Presensi berhasil dicatat',
                'data'    => $presensi
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Throw kembali agar penanganan error validasi bawaan Laravel (422) tetap berjalan
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mencatat presensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail 1 presensi berdasarkan ID.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $presensi = PresensiGuru::findOrFail($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil detail presensi',
                'data'    => $presensi
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memperbarui data presensi.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $presensi = PresensiGuru::findOrFail($id);

            $validated = $request->validate([
                'status'     => ['required', Rule::in(['hadir', 'izin', 'sakit'])],
                'keterangan' => 'nullable|string|max:255',
                'lokasi'     => 'nullable|string|max:255',
                'user_id'    => 'required|integer|exists:users,id',
            ]);

            $presensi->update($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data presensi berhasil diperbarui',
                'data'    => $presensi->fresh()
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui data presensi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus data presensi.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $presensi = PresensiGuru::findOrFail($id);
            $presensi->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data presensi berhasil dihapus'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data presensi yang akan dihapus tidak ditemukan'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus data presensi: ' . $e->getMessage()
            ], 500);
        }
    }
}
