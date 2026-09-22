<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Telp;
use Illuminate\Http\JsonResponse;

/**
 * Kontak bantuan via WhatsApp (menu "Bantuan" di sidebar dan "Hubungi Super Admin" di halaman login).
 * `telp` tetap format lokal 08xx; `wa_url` = tautan wa.me siap pakai (628xx).
 */
class BantuanController extends Controller
{
    /**
     * Publik (dipakai sebelum login): hanya super_admin dan developer.
     * Nomor admin tidak dibuka ke publik supaya tidak bisa di-scrape.
     */
    public function publik(): JsonResponse
    {
        return response()->json(['data' => [
            'super_admin' => $this->contacts('super_admin'),
            'developer'   => $this->developer(),
        ]]);
    }

    /** Untuk user login: admin, super_admin, dan developer. */
    public function index(): JsonResponse
    {
        return response()->json(['data' => [
            'admin'       => $this->contacts('admin'),
            'super_admin' => $this->contacts('super_admin'),
            'developer'   => $this->developer(),
        ]]);
    }

    /**
     * Hanya akun aktif yang sudah mengisi nomor telepon.
     *
     * @return array<int, array<string, mixed>>
     */
    private function contacts(string $role): array
    {
        return User::where('role', $role)
            ->where('is_active', true)
            ->whereNotNull('telp')
            ->orderBy('username')
            ->get()
            ->map(fn (User $u) => [
                'id'     => $u->id,
                'nama'   => $u->username,
                'telp'   => $u->telp,
                'wa_url' => Telp::waUrl($u->telp),
            ])
            ->all();
    }

    /** @return array<string, mixed> */
    private function developer(): array
    {
        $telp = config('tahfidz.developer.telp');

        return [
            'nama'   => config('tahfidz.developer.nama'),
            'telp'   => $telp,
            'wa_url' => Telp::waUrl($telp),
        ];
    }
}
