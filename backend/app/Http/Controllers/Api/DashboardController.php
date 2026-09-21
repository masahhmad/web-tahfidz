<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Santri;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Siswa "tercapai" bila jumlah hafalan >= target kelasnya. Kelas tanpa target dihitung "belum".
     * guru_halaqah dihitung untuk siswanya sendiri, role lain global.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user('api');

        $total = Santri::query()->visibleTo($user)->count();

        $tercapai = Santri::query()
            ->visibleTo($user)
            ->join('kelas', 'kelas.id', '=', 'santris.kelas_id')
            ->whereNotNull('kelas.target_hafalan')
            ->whereColumn('santris.jumlah_hafalan', '>=', 'kelas.target_hafalan')
            ->count();

        return response()->json(['data' => [
            'hafalan_tercapai'       => $tercapai,
            'hafalan_belum_tercapai' => $total - $tercapai,
        ]]);
    }
}
