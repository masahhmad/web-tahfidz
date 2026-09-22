<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\PresensiGuru */
class PresensiGuruResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'tanggal'    => $this->tanggal?->toDateString(),
            'sesi'       => $this->sesi,
            'status'     => $this->status,
            'waktu'      => $this->created_at?->format('H:i'),
            'lokasi'     => $this->lokasi,
            'keterangan' => $this->keterangan,
            // Admin/super_admin melihat presensi banyak guru, jadi perlu tahu milik siapa.
            'guru'       => $this->whenLoaded('user', fn () => [
                'id'   => $this->user->id,
                'nama' => $this->user->username,
            ]),
        ];
    }
}
