<?php

namespace Tests\Feature\Api;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProfileAndUserTest extends ApiTestCase
{
    public function test_me_shows_profile_with_student_count(): void
    {
        $guru = $this->makeUser('guru_halaqah');
        $this->makeSantri(['guru_id' => $guru->id]);
        $this->makeSantri(['guru_id' => $guru->id]);
        $this->makeSantri();

        $this->as($guru)->getJson('/api/me')->assertOk()
            ->assertJsonPath('data.role', 'guru_halaqah')
            ->assertJsonPath('data.role_label', 'Guru Pengampu')
            ->assertJsonPath('data.jumlah_siswa', 2);
    }

    public function test_me_update_changes_name_and_email_but_never_role(): void
    {
        $guru = $this->makeUser('guru_halaqah');

        $this->as($guru)->putJson('/api/me', ['nama' => 'Nama Baru', 'email' => 'baru@example.com'])
            ->assertOk()->assertJsonPath('data.nama', 'Nama Baru');

        $this->as($guru)->putJson('/api/me', ['nama' => 'Nama Baru', 'email' => 'baru@example.com', 'role' => 'super_admin'])
            ->assertStatus(422)->assertJsonValidationErrors('role');

        $this->assertSame('guru_halaqah', $guru->fresh()->role);
    }

    public function test_change_own_password_requires_current_password_and_keeps_session(): void
    {
        $guru = $this->makeUser('guru_halaqah');

        $this->as($guru)->putJson('/api/me/password', [
            'current_password' => 'salah', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1',
        ])->assertStatus(422)->assertJsonValidationErrors('current_password');

        $response = $this->as($guru)->putJson('/api/me/password', [
            'current_password' => 'rahasia123', 'password' => 'passwordbaru1', 'password_confirmation' => 'passwordbaru1',
        ])->assertOk()->assertJsonStructure(['access_token']);

        $this->newRequest();
        $this->withToken($response->json('access_token'))->getJson('/api/me')->assertOk();

        $this->postJson('/api/login', ['email' => $guru->email, 'password' => 'passwordbaru1'])->assertOk();
    }

    public function test_only_super_admin_can_manage_users(): void
    {
        $sa = $this->makeUser('super_admin');

        foreach (['admin', 'guru_halaqah'] as $role) {
            $this->as($this->makeUser($role))->getJson('/api/user')->assertStatus(403);
            $this->as($this->makeUser($role))->postJson('/api/user', [])->assertStatus(403);
        }

        $this->as($sa)->getJson('/api/user')->assertOk()->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_create_user_maps_fields_and_hides_password(): void
    {
        $sa = $this->makeUser('super_admin');

        $this->as($sa)->postJson('/api/user', [
            'nama' => 'Ahmad Rasyid', 'email' => 'rasyid@example.com', 'role' => 'guru_halaqah', 'kategori' => 'akh', 'password' => 'rahasia123',
        ])->assertStatus(201)
            ->assertJsonPath('data.nama', 'Ahmad Rasyid')
            ->assertJsonPath('data.kategori', 'akh')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonMissingPath('data.password');

        $this->assertDatabaseHas('users', ['username' => 'Ahmad Rasyid', 'category' => 'akh']);
    }

    public function test_super_admin_cannot_be_created_from_ui_and_password_min_is_8(): void
    {
        $sa = $this->makeUser('super_admin');
        $base = ['nama' => 'Budi Santoso', 'email' => 'budi@example.com', 'kategori' => 'ikh'];

        $this->as($sa)->postJson('/api/user', $base + ['role' => 'super_admin', 'password' => 'rahasia123'])
            ->assertStatus(422)->assertJsonValidationErrors('role');
        $this->as($sa)->postJson('/api/user', $base + ['role' => 'admin', 'password' => 'pendek'])
            ->assertStatus(422)->assertJsonValidationErrors('password');
        // Regresi: dulu `between:6, 12` membatasi maksimal 12 karakter.
        $this->as($sa)->postJson('/api/user', $base + ['role' => 'admin', 'telp' => '082142986689', 'password' => 'password-yang-panjang-sekali'])
            ->assertStatus(201);
    }

    public function test_update_user_accepts_only_name_email_role(): void
    {
        $sa = $this->makeUser('super_admin');
        $guru = $this->makeUser('guru_halaqah');

        $this->as($sa)->putJson("/api/user/{$guru->id}", ['nama' => 'Zaid Baru', 'email' => 'zaid@example.com', 'role' => 'admin', 'telp' => '082142986689'])
            ->assertOk()->assertJsonPath('data.role', 'admin');

        $this->as($sa)->putJson("/api/user/{$guru->id}", ['nama' => 'Zaid', 'email' => 'zaid@example.com', 'role' => 'admin', 'password' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        // super_admin tidak bisa diturunkan lewat UI.
        $this->as($sa)->putJson("/api/user/{$sa->id}", ['nama' => 'Super', 'email' => $sa->email, 'role' => 'admin'])
            ->assertStatus(422)->assertJsonValidationErrors('role');
    }

    public function test_reset_password_kills_old_tokens_of_that_user(): void
    {
        $sa = $this->makeUser('super_admin');
        $guru = $this->makeUser('guru_halaqah');
        $oldToken = JWTAuth::fromUser($guru);

        $this->as($sa)->putJson("/api/user/{$guru->id}/password", ['password' => 'baru12345', 'password_confirmation' => 'tidakcocok'])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->travel(2)->seconds();
        $this->as($sa)->putJson("/api/user/{$guru->id}/password", ['password' => 'baru12345', 'password_confirmation' => 'baru12345'])
            ->assertOk();

        $this->newRequest();
        $this->withToken($oldToken)->getJson('/api/me')->assertStatus(401);
        $this->postJson('/api/login', ['email' => $guru->email, 'password' => 'baru12345'])->assertOk();
    }

    public function test_set_status_guards(): void
    {
        $sa = $this->makeUser('super_admin');
        $other = $this->makeUser('super_admin');
        $guru = $this->makeUser('guru_halaqah');

        $this->as($sa)->patchJson("/api/user/{$guru->id}/status", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->as($sa)->patchJson("/api/user/{$guru->id}/status", ['is_active' => true])
            ->assertOk()->assertJsonPath('data.is_active', true);

        $this->as($sa)->patchJson("/api/user/{$sa->id}/status", ['is_active' => false])->assertStatus(422);

        $this->setActive($other, false);
        $this->as($sa)->patchJson("/api/user/{$other->id}/status", ['is_active' => true])->assertOk();
        $this->as($other)->patchJson("/api/user/{$sa->id}/status", ['is_active' => false])->assertStatus(200);
        // $sa nonaktif; $other kini super_admin aktif terakhir.
        $this->as($other)->patchJson("/api/user/{$other->id}/status", ['is_active' => false])->assertStatus(422);
    }

    public function test_user_list_filters(): void
    {
        $sa = $this->makeUser('super_admin');
        $this->makeUser('guru_halaqah', ['username' => 'Ahmad Rasyid']);
        $this->makeUser('admin', ['username' => 'Budi', 'is_active' => false]);

        $this->as($sa)->getJson('/api/user?search=rasyid')->assertOk()->assertJsonCount(1, 'data');
        $this->as($sa)->getJson('/api/user?role=admin')->assertOk()->assertJsonCount(1, 'data');
        $this->as($sa)->getJson('/api/user?status=nonaktif')->assertOk()->assertJsonCount(1, 'data');
        $this->as($sa)->getJson('/api/user?per_page=2')->assertOk()->assertJsonPath('meta.per_page', 2)->assertJsonPath('meta.last_page', 2);
    }
    // ---------- Nomor telepon ----------

    public function test_create_user_without_telp_is_allowed_and_flags_admin_to_complete_it(): void
    {
        $sa = $this->makeUser('super_admin');
        $base = ['kategori' => 'ikh', 'password' => 'rahasia123'];

        // Form Tambah Pengguna tidak mengirim telp.
        $this->as($sa)->postJson('/api/user', $base + ['nama' => 'Admin Baru', 'email' => 'adminbaru@example.com', 'role' => 'admin'])
            ->assertStatus(201)->assertJsonPath('data.telp', null)->assertJsonPath('data.perlu_lengkapi_telp', true);
        $this->as($sa)->postJson('/api/user', $base + ['nama' => 'Guru Baru', 'email' => 'gurubaru@example.com', 'role' => 'guru_halaqah'])
            ->assertStatus(201)->assertJsonPath('data.perlu_lengkapi_telp', false); // guru tidak dicegat

        // Bila dikirim: harus format 08xx, disimpan sebagai string dengan 0 di depan.
        foreach (['628214298668', '+6282142986689', '0821-4298-6689', '0821 4298 6689', '08123', '0812345678901234', 'abc'] as $i => $bad) {
            $this->as($sa)->postJson('/api/user', $base + ['nama' => 'Admin X', 'email' => "x$i@example.com", 'role' => 'admin', 'telp' => $bad])
                ->assertStatus(422)->assertJsonValidationErrors('telp');
        }
        $this->as($sa)->postJson('/api/user', $base + ['nama' => 'Admin Ber Nomor', 'email' => 'bernomor@example.com', 'role' => 'admin', 'telp' => '082142986689'])
            ->assertStatus(201)->assertJsonPath('data.telp', '082142986689')->assertJsonPath('data.perlu_lengkapi_telp', false);
        $this->assertSame('082142986689', \App\Models\User::where('email', 'bernomor@example.com')->value('telp'));
    }

    public function test_me_flags_admin_and_super_admin_until_telp_is_filled(): void
    {
        $admin = $this->makeUser('admin');
        $sa = $this->makeUser('super_admin');
        $guru = $this->makeUser('guru_halaqah');

        $this->as($admin)->getJson('/api/me')->assertJsonPath('data.perlu_lengkapi_telp', true);
        $this->as($sa)->getJson('/api/me')->assertJsonPath('data.perlu_lengkapi_telp', true);
        $this->as($guru)->getJson('/api/me')->assertJsonPath('data.perlu_lengkapi_telp', false);

        // Login juga membawa flag, dan Pengaturan tetap bisa diakses selagi terkunci.
        $this->postJson('/api/login', ['email' => $admin->email, 'password' => 'rahasia123'])
            ->assertOk()->assertJsonPath('user.perlu_lengkapi_telp', true);
        $this->as($admin)->putJson('/api/me', ['nama' => 'Admin Nama', 'email' => $admin->email, 'telp' => '081234567890'])
            ->assertOk()->assertJsonPath('data.perlu_lengkapi_telp', false)->assertJsonPath('data.telp', '081234567890');
        $this->as($sa)->putJson('/api/me', ['nama' => 'Super Admin', 'email' => $sa->email, 'telp' => '085555555555'])
            ->assertOk()->assertJsonPath('data.perlu_lengkapi_telp', false);
    }

    public function test_admin_and_super_admin_cannot_clear_own_telp(): void
    {
        $sa = $this->makeUser('super_admin', ['telp' => '085555555555']);
        $admin = $this->makeUser('admin', ['telp' => '081111111111']);
        $guru = $this->makeUser('guru_halaqah', ['telp' => '082222222222']);

        // Tidak mengirim telp = tidak berubah.
        $this->as($sa)->putJson('/api/me', ['nama' => 'Super Admin', 'email' => $sa->email])->assertOk()->assertJsonPath('data.telp', '085555555555');

        foreach ([$sa, $admin] as $u) {
            $this->as($u)->putJson('/api/me', ['nama' => 'Nama Baru', 'email' => $u->email, 'telp' => null])->assertStatus(422)->assertJsonValidationErrors('telp');
            $this->as($u)->putJson('/api/me', ['nama' => 'Nama Baru', 'email' => $u->email, 'telp' => '628123'])->assertStatus(422);
        }
        $this->as($admin)->getJson('/api/me')->assertJsonPath('data.telp', '081111111111');

        // Guru tidak dikunci, jadi boleh mengosongkan.
        $this->as($guru)->putJson('/api/me', ['nama' => 'Guru Nama', 'email' => $guru->email, 'telp' => null])->assertOk()->assertJsonPath('data.telp', null);
    }

    public function test_super_admin_editing_users_telp(): void
    {
        $sa = $this->makeUser('super_admin');
        $admin = $this->makeUser('admin');
        $body = fn ($u, array $extra = []) => ['nama' => 'Nama Admin', 'email' => $u->email, 'role' => 'admin'] + $extra;

        // Edit tanpa telp (seperti modal Edit yang lama) tetap berhasil.
        $this->as($sa)->putJson("/api/user/{$admin->id}", $body($admin))->assertOk()->assertJsonPath('data.telp', null);
        $this->as($sa)->putJson("/api/user/{$admin->id}", $body($admin, ['telp' => '083333333333']))->assertOk()->assertJsonPath('data.telp', '083333333333');
        $this->as($sa)->putJson("/api/user/{$admin->id}", $body($admin))->assertOk()->assertJsonPath('data.telp', '083333333333'); // tidak berubah
        $this->as($sa)->putJson("/api/user/{$admin->id}", $body($admin, ['telp' => null]))->assertStatus(422);
        $this->as($sa)->putJson("/api/user/{$admin->id}", $body($admin, ['telp' => '08333']))->assertStatus(422);
    }

    // ---------- Bantuan (WhatsApp) ----------

    public function test_public_help_exposes_only_super_admin_and_developer(): void
    {
        $this->makeUser('super_admin', ['username' => 'Super Satu', 'telp' => '081234567890']);
        $this->makeUser('super_admin', ['username' => 'Super Tanpa Nomor']);
        $nonaktif = $this->makeUser('super_admin', ['username' => 'Super Nonaktif', 'telp' => '081999999999']);
        $this->setActive($nonaktif, false);
        $this->makeUser('admin', ['username' => 'Admin Rahasia', 'telp' => '087777777777']);

        $r = $this->getJson('/api/bantuan/publik')->assertOk(); // tanpa token
        $r->assertJsonCount(1, 'data.super_admin')
            ->assertJsonPath('data.super_admin.0.nama', 'Super Satu')
            ->assertJsonPath('data.super_admin.0.telp', '081234567890')          // tetap 08xx
            ->assertJsonPath('data.super_admin.0.wa_url', 'https://wa.me/6281234567890')
            ->assertJsonPath('data.developer.telp', '082142986689')
            ->assertJsonPath('data.developer.wa_url', 'https://wa.me/6282142986689')
            ->assertJsonMissingPath('data.admin');
        $this->assertStringNotContainsString('087777777777', $r->getContent());
        $this->assertStringNotContainsString('081999999999', $r->getContent());
    }

    public function test_authenticated_help_lists_admins_super_admins_and_developer(): void
    {
        $guru = $this->makeUser('guru_halaqah');
        $this->makeUser('admin', ['username' => 'Admin A', 'telp' => '087777777777']);
        $this->makeUser('admin', ['username' => 'Admin Tanpa Nomor']);
        $this->makeUser('super_admin', ['telp' => '081234567890']);

        $this->getJson('/api/bantuan')->assertStatus(401);

        $this->as($guru)->getJson('/api/bantuan')->assertOk()
            ->assertJsonCount(1, 'data.admin')
            ->assertJsonPath('data.admin.0.wa_url', 'https://wa.me/6287777777777')
            ->assertJsonCount(1, 'data.super_admin')
            ->assertJsonPath('data.developer.nama', 'Developer');
    }

    public function test_telp_to_whatsapp_conversion(): void
    {
        $this->assertSame('6282142986689', \App\Support\Telp::toWhatsapp('082142986689'));
        $this->assertSame('https://wa.me/6281234567890', \App\Support\Telp::waUrl('081234567890'));
        $this->assertNull(\App\Support\Telp::waUrl(null));
    }
}
