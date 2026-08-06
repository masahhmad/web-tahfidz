<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KenaikanJuz;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class KenaikanJuzController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $data = KenaikanJuz::all();

            if ($data->isEmpty()) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Data kenaikan juz masih kosong',
                    'data'    => []
                ], 200);
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil semua data kenaikan juz',
                'data'    => $data
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data kenaikan juz: ' . $e->getMessage()
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
                'juz'            => 'required|integer|between:1,30',
                'nilai_setoran'  => 'required|integer|between:0,100',
                'nilai_soal'     => 'required|integer|between:0,100',
                'nilai_tambahan' => 'nullable|integer|between:0,100',
                'santri_id'      => 'required|integer|exists:santris,id',
            ], [
                'juz.required'           => 'Juz wajib diisi!',
                'juz.between'            => 'Juz harus di antara 1 sampai 30.',
                'nilai_setoran.required' => 'Nilai setoran wajib diisi!',
                'nilai_setoran.between'  => 'Nilai setoran harus bernilai 0 - 100.',
                'nilai_soal.required'    => 'Nilai soal wajib diisi!',
                'nilai_soal.between'     => 'Nilai soal harus bernilai 0 - 100.',
                'nilai_tambahan.between' => 'Nilai tambahan harus bernilai 0 - 100.',
                'santri_id.required'     => 'Santri wajib dipilih!',
                'santri_id.exists'       => 'Data santri tidak ditemukan di sistem.',
            ]);

            $kenaikanJuz = KenaikanJuz::create($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data kenaikan juz berhasil ditambahkan',
                'data'    => $kenaikanJuz
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menambahkan data kenaikan juz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $kenaikanJuz = KenaikanJuz::findOrFail($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Berhasil mengambil detail kenaikan juz',
                'data'    => $kenaikanJuz
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data kenaikan juz tidak ditemukan'
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
            $kenaikanJuz = KenaikanJuz::findOrFail($id);

            $validated = $request->validate([
                'juz'            => 'required|integer|between:1,30',
                'nilai_setoran'  => 'required|integer|between:0,100',
                'nilai_soal'     => 'required|integer|between:0,100',
                'nilai_tambahan' => 'nullable|integer|between:0,100',
                'santri_id'      => 'required|integer|exists:santris,id',
            ], [
                'juz.required'           => 'Juz wajib diisi!',
                'juz.between'            => 'Juz harus di antara 1 sampai 30.',
                'nilai_setoran.required' => 'Nilai setoran wajib diisi!',
                'nilai_setoran.between'  => 'Nilai setoran harus bernilai 0 - 100.',
                'nilai_soal.required'    => 'Nilai soal wajib diisi!',
                'nilai_soal.between'     => 'Nilai soal harus bernilai 0 - 100.',
                'nilai_tambahan.between' => 'Nilai tambahan harus bernilai 0 - 100.',
                'santri_id.required'     => 'Santri wajib dipilih!',
                'santri_id.exists'       => 'Data santri tidak ditemukan di sistem.',
            ]);

            $kenaikanJuz->update($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Data kenaikan juz berhasil diperbarui',
                'data'    => $kenaikanJuz->fresh()
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data kenaikan juz tidak ditemukan'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memperbarui data kenaikan juz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $kenaikanJuz = KenaikanJuz::findOrFail($id);
            $kenaikanJuz->delete();

            return response()->json([
                'status'  => 'success',
                'message' => 'Data kenaikan juz berhasil dihapus'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Data kenaikan juz yang akan dihapus tidak ditemukan'
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal menghapus data kenaikan juz: ' . $e->getMessage()
            ], 500);
        }
    }
}