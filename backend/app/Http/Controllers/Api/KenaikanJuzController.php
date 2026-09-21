<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KenaikanJuzResource;
use App\Models\KenaikanJuz;
use App\Models\Santri;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Ujian Kenaikan Juz.
 *  - baca: admin & super_admin semua baris; guru_halaqah baris yang ia ampu/uji.
 *  - buat baris: admin (semua siswa) & guru_halaqah (siswanya); hapus: admin.
 *  - nilai: `nilai_hafalan` oleh pengampu siswa, `nilai_soal` oleh penguji baris tsb.
 */
class KenaikanJuzController extends Controller
{
    private const WITH = ['santri.kelas', 'santri.pengampu', 'penguji'];

    public function index(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $request->validate(['juz' => 'nullable|integer|between:1,30']);

        $ujian = KenaikanJuz::query()
            ->with(self::WITH)
            ->when($user->role === 'guru_halaqah', fn ($q) => $q->where(function ($q) use ($user) {
                $q->where('penguji_id', $user->id)
                  ->orWhereHas('santri', fn ($q) => $q->where('guru_id', $user->id));
            }))
            ->when($request->query('juz'), fn ($q, $juz) => $q->where('juz', $juz))
            ->when($request->query('kelas_id'), fn ($q, $id) => $q->whereHas('santri', fn ($q) => $q->where('kelas_id', $id)))
            ->when($request->query('search'), fn ($q, $term) => $q->whereHas('santri', fn ($q) => $q->where('nama', 'like', $this->like($term))))
            ->orderBy('juz')
            ->orderBy('id')
            ->paginate($this->perPage($request));

        return $this->paginated($ujian, KenaikanJuzResource::class);
    }

    /** Tombol "Tambah Siswa": baris ujian dibuat dengan nilai kosong. */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $validated = $request->validate([
            'santri_id'  => ['required', 'integer', Rule::exists('santris', 'id')->whereNull('deleted_at')],
            'juz'        => 'required|integer|between:1,30',
            'penguji_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'guru_halaqah')->where('is_active', true)],
        ], [
            'penguji_id.exists' => 'Penguji harus guru halaqah yang aktif.',
        ]);

        $santri = Santri::findOrFail($validated['santri_id']);

        if ($user->role === 'guru_halaqah' && ! $santri->isTaughtBy($user)) {
            return $this->forbidden('Anda hanya dapat menambahkan ujian untuk siswa yang Anda ampu.');
        }

        try {
            $ujian = KenaikanJuz::create([
                'santri_id'  => $santri->id,
                'juz'        => $validated['juz'],
                'penguji_id' => $validated['penguji_id'] ?? null,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Ujian ulang dilakukan dengan mengubah nilai baris yang sama (PATCH), bukan membuat baris baru.
            return $this->message('Ujian juz ini sudah ada untuk siswa tersebut.', 409);
        }

        return $this->item(new KenaikanJuzResource($ujian->load(self::WITH)), 201);
    }

    public function nilai(Request $request, KenaikanJuz $kenaikanJuz): JsonResponse
    {
        $user = $request->user('api');

        $validated = $request->validate([
            'nilai_hafalan' => 'required_without:nilai_soal|nullable|integer|between:0,100',
            'nilai_soal'    => 'required_without:nilai_hafalan|nullable|integer|between:0,100',
        ]);

        $isPengampu = $kenaikanJuz->santri->isTaughtBy($user);
        $isPenguji = $kenaikanJuz->penguji_id !== null && (int) $kenaikanJuz->penguji_id === (int) $user->id;

        // Field yang bukan hak pemanggil ditolak (bukan diabaikan diam-diam).
        if (array_key_exists('nilai_hafalan', $validated) && ! $isPengampu) {
            return $this->forbidden('Nilai hafalan hanya dapat diisi oleh pengampu siswa.');
        }
        if (array_key_exists('nilai_soal', $validated) && ! $isPenguji) {
            return $this->forbidden('Nilai soal hanya dapat diisi oleh penguji yang ditunjuk.');
        }

        $data = [];
        if (array_key_exists('nilai_hafalan', $validated)) {
            $data['nilai_setoran'] = $validated['nilai_hafalan']; // kolom DB: nilai_setoran
        }
        if (array_key_exists('nilai_soal', $validated)) {
            $data['nilai_soal'] = $validated['nilai_soal'];
        }
        $kenaikanJuz->update($data);

        return $this->item(new KenaikanJuzResource($kenaikanJuz->load(self::WITH)));
    }

    public function destroy(KenaikanJuz $kenaikanJuz): JsonResponse|Response
    {
        $kenaikanJuz->delete();

        return response()->noContent();
    }
}
