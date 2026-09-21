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
        $this->as($sa)->postJson('/api/user', $base + ['role' => 'admin', 'password' => 'password-yang-panjang-sekali'])
            ->assertStatus(201);
    }

    public function test_update_user_accepts_only_name_email_role(): void
    {
        $sa = $this->makeUser('super_admin');
        $guru = $this->makeUser('guru_halaqah');

        $this->as($sa)->putJson("/api/user/{$guru->id}", ['nama' => 'Zaid Baru', 'email' => 'zaid@example.com', 'role' => 'admin'])
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
}
