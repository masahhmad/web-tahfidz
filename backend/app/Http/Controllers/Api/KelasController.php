<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KelasResource;
use App\Models\Kelas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Kelas sekaligus menu Target (`target_hafalan` = total juz per kelas dalam setahun).
 * super_admin & admin membaca, admin menulis (diatur di routes/api.php).
 */
class KelasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['kategori' => ['nullable', Rule::in(['ikh', 'akh'])]]);

        $kelas = Kelas::query()
            ->withCount('santri')
            ->when($request->query('search'), fn ($q, $term) => $q->where('kelas', 'like', $this->like($term)))
            ->when($request->query('kategori'), fn ($q, $kategori) => $q->where('kategori', $kategori))
            ->orderBy('kelas')
            ->orderBy('kategori')
            ->paginate($this->perPage($request));

        return $this->paginated($kelas, KelasResource::class);
    }

    public function show(Kelas $kelas): JsonResponse
    {
        return $this->item(new KelasResource($kelas->loadCount('santri')));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kelas'          => 'required|string|max:50',
            'kategori'       => ['required', Rule::in(['ikh', 'akh'])],
            'target_hafalan' => 'nullable|integer|between:1,30',
        ]);

        if (Kelas::where('kelas', $validated['kelas'])->where('kategori', $validated['kategori'])->exists()) {
            return $this->duplicate();
        }

        $kelas = Kelas::create($validated);

        return $this->item(new KelasResource($kelas->loadCount('santri')), 201);
    }

    /**
     * Popup pensil di halaman Target hanya mengirim `target_hafalan`, jadi field lain opsional.
     */
    public function update(Request $request, Kelas $kelas): JsonResponse
    {
        $validated = $request->validate([
            'kelas'          => 'sometimes|required|string|max:50',
            'kategori'       => ['sometimes', 'required', Rule::in(['ikh', 'akh'])],
            'target_hafalan' => 'sometimes|nullable|integer|between:1,30',
        ]);

        $nama = $validated['kelas'] ?? $kelas->kelas;
        $kategori = $validated['kategori'] ?? $kelas->kategori;

        $bentrok = Kelas::where('kelas', $nama)->where('kategori', $kategori)->where('id', '!=', $kelas->id)->exists();
        if ($bentrok) {
            return $this->duplicate();
        }

        DB::transaction(function () use ($kelas, $validated, $kategori) {
            $kelas->update($validated);

            // Kategori siswa mengikuti kelasnya.
            if (array_key_exists('kategori', $validated)) {
                $kelas->santri()->update(['kategori' => $kategori]);
            }
        });

        return $this->item(new KelasResource($kelas->loadCount('santri')));
    }

    public function destroy(Kelas $kelas): JsonResponse|Response
    {
        if ($kelas->santri()->exists()) {
            return $this->message('Kelas tidak dapat dihapus karena masih memiliki siswa.', 422);
        }

        $kelas->delete();

        return response()->noContent();
    }

    private function duplicate(): JsonResponse
    {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors'  => ['kelas' => ['Kelas dengan kategori tersebut sudah ada.']],
        ], 422);
    }
}
