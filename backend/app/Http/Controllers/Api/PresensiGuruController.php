<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PresensiGuruResource;
use App\Models\PresensiGuru;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * guru_halaqah hanya membaca presensi miliknya; admin & super_admin membaca semua.
 * Membuat presensi: admin & guru_halaqah (untuk diri sendiri). Koreksi/hapus: super_admin.
 */
class PresensiGuruController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $request->validate([
            'tanggal' => 'nullable|date_format:Y-m-d',
            'sesi'    => ['nullable', Rule::in(['pagi', 'siang', 'sore'])],
        ]);

        $presensi = PresensiGuru::query()
            ->with('user')
            ->when($user->role === 'guru_halaqah',
                fn ($q) => $q->where('user_id', $user->id),
                fn ($q) => $q->when($request->query('user_id'), fn ($q, $id) => $q->where('user_id', $id)))
            ->when($request->query('tanggal'), fn ($q, $tanggal) => $q->whereDate('tanggal', $tanggal))
            ->when($request->query('sesi'), fn ($q, $sesi) => $q->where('sesi', $sesi))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->paginated($presensi, PresensiGuruResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        try {
            // user_id selalu dari token, bukan dari body.
            $presensi = PresensiGuru::create($validated + ['user_id' => $request->user('api')->id]);
        } catch (UniqueConstraintViolationException) {
            return $this->sudahAda();
        }

        return $this->item(new PresensiGuruResource($presensi->load('user')), 201);
    }

    public function update(Request $request, PresensiGuru $presensiGuru): JsonResponse
    {
        $validated = $request->validate($this->rules());

        try {
            $presensiGuru->update($validated);
        } catch (UniqueConstraintViolationException) {
            return $this->sudahAda();
        }

        return $this->item(new PresensiGuruResource($presensiGuru->load('user')));
    }

    public function destroy(PresensiGuru $presensiGuru): JsonResponse|Response
    {
        $presensiGuru->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'tanggal'    => 'required|date_format:Y-m-d',
            'sesi'       => ['required', Rule::in(['pagi', 'siang', 'sore'])],
            'status'     => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'keterangan' => 'required_unless:status,hadir|nullable|string|max:255',
            'lokasi'     => 'nullable|string|max:100',
        ];
    }

    private function sudahAda(): JsonResponse
    {
        return $this->message('Presensi untuk tanggal dan sesi ini sudah ada.', 409);
    }
}
