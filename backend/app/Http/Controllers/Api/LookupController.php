<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Halaqah;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Data ringan untuk dropdown (id + label), terbuka untuk semua user login supaya guru_halaqah
 * bisa mengisi form tanpa mengakses menu Data Siswa/Halaqah/Target.
 */
class LookupController extends Controller
{
    public function kelas(): JsonResponse
    {
        $data = Kelas::orderBy('kelas')->orderBy('kategori')->get()
            ->map(fn (Kelas $k) => ['id' => $k->id, 'nama' => $k->kelas, 'kategori' => $k->kategori]);

        return response()->json(['data' => $data]);
    }

    public function halaqah(): JsonResponse
    {
        $data = Halaqah::orderBy('nama')->get()
            ->map(fn (Halaqah $h) => ['id' => $h->id, 'nama' => $h->nama]);

        return response()->json(['data' => $data]);
    }

    public function pengampu(): JsonResponse
    {
        $data = User::where('role', 'guru_halaqah')->where('is_active', true)->orderBy('username')->get()
            ->map(fn (User $u) => ['id' => $u->id, 'nama' => $u->username]);

        return response()->json(['data' => $data]);
    }

    public function santri(Request $request): JsonResponse
    {
        $data = Santri::query()
            ->with(['kelas', 'halaqah'])
            ->visibleTo($request->user('api'))
            ->when($request->query('halaqah_id'), fn ($q, $id) => $q->where('halaqah_id', $id))
            ->when($request->query('search'), fn ($q, $term) => $q->where('nama', 'like', $this->like($term)))
            ->orderBy('nama')
            ->orderBy('id')
            ->get()
            ->map(fn (Santri $s) => [
                'id'         => $s->id,
                'nama'       => $s->nama,
                'kelas'      => $s->kelas?->kelas,
                'halaqah_id' => $s->halaqah_id,
                'halaqah'    => $s->halaqah?->nama,
            ]);

        return response()->json(['data' => $data]);
    }
}
