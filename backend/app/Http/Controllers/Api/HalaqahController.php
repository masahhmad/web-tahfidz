<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HalaqahResource;
use App\Http\Resources\HalaqahSiswaResource;
use App\Models\Halaqah;
use App\Models\Santri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Halaqah — super_admin & admin membaca, admin menulis (diatur di routes/api.php).
 * Pengampu tetap `santris.guru_id`; halaqah adalah kelompok terpisah (`santris.halaqah_id`).
 */
class HalaqahController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $halaqah = Halaqah::query()
            ->withCount('santri')
            ->when($request->query('search'), fn ($q, $term) => $q->where('nama', 'like', $this->like($term)))
            ->orderBy('nama')
            ->paginate($this->perPage($request));

        return $this->paginated($halaqah, HalaqahResource::class);
    }

    public function show(Halaqah $halaqah): JsonResponse
    {
        return $this->item(new HalaqahResource($halaqah->loadCount('santri')));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:100|unique:halaqahs,nama',
        ], ['nama.unique' => 'Nama halaqah sudah digunakan.']);

        $halaqah = Halaqah::create($validated);

        return $this->item(new HalaqahResource($halaqah->loadCount('santri')), 201);
    }

    public function update(Request $request, Halaqah $halaqah): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('halaqahs', 'nama')->ignore($halaqah->id)],
        ], ['nama.unique' => 'Nama halaqah sudah digunakan.']);

        $halaqah->update($validated);

        return $this->item(new HalaqahResource($halaqah->loadCount('santri')));
    }

    public function destroy(Halaqah $halaqah): JsonResponse|Response
    {
        if ($halaqah->santri()->exists()) {
            return $this->message('Halaqah tidak dapat dihapus karena masih memiliki siswa.', 422);
        }

        $halaqah->delete();

        return response()->noContent();
    }

    /** Tabel halaman Halaqah: siswa beserta pengampu & halaqahnya. */
    public function siswa(Request $request): JsonResponse
    {
        $santri = Santri::query()
            ->with(['kelas', 'pengampu', 'halaqah'])
            ->when($request->query('guru_id'), fn ($q, $id) => $q->where('guru_id', $id))
            ->when($request->query('kelas_id'), fn ($q, $id) => $q->where('kelas_id', $id))
            ->when($request->query('halaqah_id'), fn ($q, $id) => $q->where('halaqah_id', $id))
            ->when($request->query('search'), fn ($q, $term) => $q->where(function ($q) use ($term) {
                $q->where('nama', 'like', $this->like($term))
                  ->orWhere('nisn', 'like', $this->like($term));
            }))
            ->orderBy('nama')
            ->orderBy('id')
            ->paginate($this->perPage($request));

        return $this->paginated($santri, HalaqahSiswaResource::class);
    }

    /** Mode "Atur Halaqah": tetapkan pengampu + halaqah untuk siswa terpilih, atomik. */
    public function assign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'santri_ids'   => 'required|array|min:1',
            'santri_ids.*' => ['integer', 'distinct', Rule::exists('santris', 'id')->whereNull('deleted_at')],
            'guru_id'      => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'guru_halaqah')->where('is_active', true)],
            'halaqah_id'   => 'required|integer|exists:halaqahs,id',
        ], [
            'guru_id.exists' => 'Pengampu harus guru halaqah yang aktif.',
        ]);

        $diperbarui = DB::transaction(fn () => Santri::whereIn('id', $validated['santri_ids'])->update([
            'guru_id'    => $validated['guru_id'],
            'halaqah_id' => $validated['halaqah_id'],
        ]));

        return response()->json(['data' => ['diperbarui' => $diperbarui]]);
    }
}
