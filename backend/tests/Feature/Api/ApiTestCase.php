<?php

namespace Tests\Feature\Api;

use App\Models\Halaqah;
use App\Models\Kelas;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

abstract class ApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(string $role, array $attrs = []): User
    {
        static $n = 0;
        $n++;

        // forceFill: is_active bukan mass-assignable (hanya diubah lewat endpoint status).
        $user = (new User)->forceFill($attrs + [
            'username' => ucfirst($role)." $n",
            'email'    => "$role$n@example.com",
            'password' => 'rahasia123',
            'role'     => $role,
            'category' => 'ikh',
        ]);
        $user->save();

        return $user->refresh();
    }

    /** Request sebagai user tertentu (token JWT sungguhan). */
    protected function as(User $user): static
    {
        // Aplikasi test dipakai ulang antar request; di produksi guard dibuat baru tiap request.
        $this->newRequest();

        return $this->withToken(JWTAuth::fromUser($user));
    }

    /** Lepas state per-request (user & token ter-cache di singleton selama satu test). */
    protected function newRequest(): void
    {
        $this->app['auth']->forgetGuards();
        $this->app->make('tymon.jwt')->unsetToken(); // instance JWT yang dipakai guard
    }

    protected function setActive(User $user, bool $active): void
    {
        $user->forceFill(['is_active' => $active])->save();
    }

    protected function makeKelas(string $nama = '7A', string $kategori = 'ikh', ?int $target = null): Kelas
    {
        return Kelas::create(['kelas' => $nama, 'kategori' => $kategori, 'target_hafalan' => $target]);
    }

    protected function makeHalaqah(string $nama = 'Al-Fatih'): Halaqah
    {
        return Halaqah::create(['nama' => $nama]);
    }

    protected function makeSantri(array $attrs = []): Santri
    {
        static $n = 0;
        $n++;
        $kelas = $attrs['kelas_id'] ?? $this->makeKelas("K$n")->id;

        return Santri::create($attrs + [
            'nama'           => "Siswa $n",
            'nisn'           => str_pad((string) $n, 10, '0', STR_PAD_LEFT),
            'kategori'       => 'ikh',
            'jumlah_hafalan' => 0,
            'kelas_id'       => $kelas,
        ]);
    }
}
