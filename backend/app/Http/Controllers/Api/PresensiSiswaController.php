<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PresensiSiswaResource;
use App\Models\PresensiSiswa;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * guru_halaqah hanya menyentuh siswa yang ia ampu; admin & super_admin melihat semua.
 * Membuat (bulk): admin & guru_halaqah. Koreksi/hapus: admin & super_admin.
 */
class PresensiSiswaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $request->validate([
            'tanggal' => 'nullable|date_format:Y-m-d',
            'sesi'    => ['nullable', Rule::in(['pagi', 'siang', 'sore'])],
        ]);

        $presensi = PresensiSiswa::query()
            ->with('santri.halaqah')
            ->when($user->role === 'guru_halaqah', fn ($q) => $q->whereHas('santri', fn ($q) => $q->visibleTo($user)))
            ->when($request->query('tanggal'), fn ($q, $tanggal) => $q->whereDate('tanggal', $tanggal))
            ->when($request->query('sesi'), fn ($q, $sesi) => $q->where('sesi', $sesi))
            ->when($request->query('santri_id'), fn ($q, $id) => $q->where('santri_id', $id))
            ->when($request->query('halaqah_id'), fn ($q, $id) => $q->whereHas('santri', fn ($q) => $q->where('halaqah_id', $id)))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->paginated($presensi, PresensiSiswaResource::class);
    }

    /**
     * Simpan satu sesi untuk banyak siswa. Mengirim ulang sesi yang sama = upsert
     * (guru boleh memperbaiki), bukan 409.
     */
    public function bulk(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $validated = $request->validate([
            'tanggal'            => 'required|date_format:Y-m-d',
            'sesi'               => ['required', Rule::in(['pagi', 'siang', 'sore'])],
            'halaqah_id'         => 'required|integer|exists:halaqahs,id',
            'items'              => 'required|array|min:1',
            'items.*.santri_id'  => ['required', 'integer', 'distinct', Rule::exists('santris', 'id')->whereNull('deleted_at')],
            'items.*.status'     => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'items.*.keterangan' => 'nullable|string|max:255',
        ]);

        $santri = Santri::whereIn('id', collect($validated['items'])->pluck('santri_id'))->get()->keyBy('id');

        // Row-level: guru hanya boleh mengisi siswa yang ia ampu.
        if ($user->role === 'guru_halaqah' && $santri->contains(fn (Santri $s) => ! $s->isTaughtBy($user))) {
            return $this->forbidden('Anda hanya dapat mengisi presensi siswa yang Anda ampu.');
        }

        foreach ($validated['items'] as $i => $item) {
            if ((int) $santri[$item['santri_id']]->halaqah_id !== (int) $validated['halaqah_id']) {
                throw ValidationException::withMessages([
                    "items.$i.santri_id" => 'Siswa ini bukan anggota halaqah yang dipilih.',
                ]);
            }
        }

        $hasil = DB::transaction(function () use ($validated, $user) {
            return collect($validated['items'])->map(fn ($item) => PresensiSiswa::updateOrCreate(
                [
                    'santri_id' => $item['santri_id'],
                    'tanggal'   => $validated['tanggal'],
                    'sesi'      => $validated['sesi'],
                ],
                [
                    'status'     => $item['status'],
                    'keterangan' => $item['keterangan'] ?? null,
                    'guru_id'    => $user->id,
                ],
            ));
        });

        $hasil = (new EloquentCollection($hasil->all()))->load('santri.halaqah');

        return response()->json([
            'data' => PresensiSiswaResource::collection($hasil)->resolve(),
        ]);
    }

    public function update(Request $request, PresensiSiswa $presensiSiswa): JsonResponse
    {
        $validated = $request->validate([
            'tanggal'    => 'sometimes|required|date_format:Y-m-d',
            'sesi'       => ['sometimes', 'required', Rule::in(['pagi', 'siang', 'sore'])],
            'status'     => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'keterangan' => 'nullable|string|max:255',
        ]);

        try {
            $presensiSiswa->update($validated + ['guru_id' => $request->user('api')->id]);
        } catch (UniqueConstraintViolationException) {
            return $this->message('Presensi siswa ini pada tanggal dan sesi tersebut sudah ada.', 409);
        }

        return $this->item(new PresensiSiswaResource($presensiSiswa->load('santri.halaqah')));
    }

    public function destroy(PresensiSiswa $presensiSiswa): JsonResponse|Response
    {
        $presensiSiswa->delete();

        return response()->noContent();
    }
}
