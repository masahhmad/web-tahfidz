<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\KenaikanJuz */
class KenaikanJuzResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user('api');
        $pengampu = $this->santri?->pengampu;

        return [
            'id'            => $this->id,
            'santri'        => $this->santri ? ['id' => $this->santri->id, 'nama' => $this->santri->nama] : null,
            'kelas'         => $this->santri?->kelas?->kelas,
            'juz'           => $this->juz,
            'nilai_hafalan' => $this->nilai_setoran,
            'nilai_soal'    => $this->nilai_soal,
            'pengampu'      => $pengampu ? ['id' => $pengampu->id, 'nama' => $pengampu->username] : null,
            'penguji'       => $this->penguji ? ['id' => $this->penguji->id, 'nama' => $this->penguji->username] : null,
            // Aturan siapa boleh menilai ada di backend; frontend cukup membaca dua flag ini.
            'can_input_hafalan' => $user !== null && $this->santri?->isTaughtBy($user) === true,
            'can_input_soal'    => $user !== null && $this->penguji_id !== null && (int) $this->penguji_id === (int) $user->id,
        ];
    }
}
