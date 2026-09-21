<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nama'       => $this->username,
            'email'      => $this->email,
            'role'       => $this->role,
            'role_label' => $this->role_label,
            'kategori'   => $this->category,
            'is_active'  => (bool) $this->is_active,
        ];
    }
}
