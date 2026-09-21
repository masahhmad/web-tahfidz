<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SetoranResource;
use App\Models\Santri;
use App\Models\Setoran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\Response;

/**
 * guru_halaqah: CRUD untuk siswa yang ia ampu. admin: CRUD semua. super_admin: hanya baca.
 */
class SetoranController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $request->validate([
            'tanggal' => 'nullable|date_format:Y-m-d',
            'juz'     => 'nullable|integer|between:1,30',
        ]);

        $setoran = Setoran::query()
            ->with('santri.kelas')
            ->whereHas('santri', fn ($q) => $q->visibleTo($user))
            ->when($request->query('santri_id'), fn ($q, $id) => $q->where('santri_id', $id))
            ->when($request->query('juz'), fn ($q, $juz) => $q->where('juz', $juz))
            ->when($request->query('tanggal'), fn ($q, $tanggal) => $q->whereDate('tanggal', $tanggal))
            ->when($request->query('search'), fn ($q, $term) => $q->whereHas('santri', fn ($q) => $q->where('nama', 'like', $this->like($term))))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate($this->perPage($request));

        return $this->paginated($setoran, SetoranResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        if (! $this->canManage($request, Santri::findOrFail($validated['santri_id']))) {
            return $this->forbidden();
        }

        $setoran = Setoran::create([
            'santri_id' => $validated['santri_id'],
            'juz'       => $validated['juz'],
            'baris'     => $validated['baris'],
            'tanggal'   => $validated['tanggal'] ?? now()->toDateString(),
            'guru_id'   => $request->user('api')->id, // pencatat dari token
        ]);

        return $this->item(new SetoranResource($setoran->load('santri.kelas')), 201);
    }

    public function update(Request $request, Setoran $setoran): JsonResponse
    {
        $validated = $request->validate($this->rules());

        // Harus berhak atas data yang lama sekaligus siswa tujuan.
        if (! $this->canManage($request, $setoran->santri) || ! $this->canManage($request, Santri::findOrFail($validated['santri_id']))) {
            return $this->forbidden();
        }

        $setoran->update([
            'santri_id' => $validated['santri_id'],
            'juz'       => $validated['juz'],
            'baris'     => $validated['baris'],
            'tanggal'   => $validated['tanggal'] ?? $setoran->tanggal,
        ]);

        return $this->item(new SetoranResource($setoran->load('santri.kelas')));
    }

    public function destroy(Request $request, Setoran $setoran): JsonResponse|Response
    {
        if (! $this->canManage($request, $setoran->santri)) {
            return $this->forbidden();
        }

        $setoran->delete();

        return response()->noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'santri_id' => ['required', 'integer', Rule::exists('santris', 'id')->whereNull('deleted_at')],
            'juz'       => 'required|integer|between:1,30',
            'baris'     => 'required|integer|min:1',
            'tanggal'   => 'nullable|date_format:Y-m-d',
        ];
    }

    /** Admin boleh semua siswa; guru_halaqah hanya siswa yang ia ampu. */
    private function canManage(Request $request, ?Santri $santri): bool
    {
        $user = $request->user('api');

        return $user->role !== 'guru_halaqah' || ($santri !== null && $santri->isTaughtBy($user));
    }
}
