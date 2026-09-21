<?php

namespace Tests\Feature\Api;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthAndMiddlewareTest extends ApiTestCase
{
    public function test_login_success_returns_token_and_user(): void
    {
        $user = $this->makeUser('admin', ['email' => 'a@example.com']);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'rahasia123'])
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'user' => ['id', 'nama', 'email', 'role', 'kategori']])
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonMissingPath('user.password');
    }

    public function test_login_wrong_password_is_401_not_200(): void
    {
        $this->makeUser('admin', ['email' => 'a@example.com']);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'salah'])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Email atau password salah.');
    }

    public function test_login_inactive_account_is_403(): void
    {
        $this->makeUser('admin', ['email' => 'a@example.com', 'is_active' => false]);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'rahasia123'])
            ->assertStatus(403)
            ->assertJsonPath('message', 'Akun Anda dinonaktifkan.');
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'salah'])->assertStatus(401);
        }

        $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'salah'])->assertStatus(429);
    }

    public function test_missing_or_invalid_token_is_401(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
        $this->withToken('bukan-token')->getJson('/api/me')->assertStatus(401);
    }

    public function test_role_middleware_returns_403_and_tolerates_spaces(): void
    {
        $guru = $this->makeUser('guru_halaqah');

        $this->as($guru)->getJson('/api/santri')->assertStatus(403)
            ->assertJsonPath('message', 'Akses ditolak, anda tidak memiliki akses untuk fitur ini.');

        // Regresi: `role:super_admin, admin` dulu hanya mencocokkan role pertama.
        $request = \Illuminate\Http\Request::create('/x');
        $middleware = new \App\Http\Middleware\CheckRole();
        auth('api')->setUser($this->makeUser('admin'));
        $response = $middleware->handle($request, fn () => response('ok'), 'super_admin', ' admin');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_deactivated_user_with_existing_token_is_cut_off(): void
    {
        $admin = $this->makeUser('admin');
        $token = JWTAuth::fromUser($admin);

        $this->withToken($token)->getJson('/api/me')->assertOk();

        $this->setActive($admin, false);
        // Guard memakai user dari request sebelumnya; mulai request baru.
        $this->newRequest();

        $this->withToken($token)->getJson('/api/me')->assertStatus(403)
            ->assertJsonPath('message', 'Akun Anda dinonaktifkan.');
    }

    public function test_logout_invalidates_token(): void
    {
        $admin = $this->makeUser('admin');
        $token = JWTAuth::fromUser($admin);

        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->newRequest();

        $this->withToken($token)->getJson('/api/me')->assertStatus(401);
    }

    public function test_refresh_returns_new_token(): void
    {
        $admin = $this->makeUser('admin');

        $this->as($admin)->postJson('/api/refresh')->assertOk()->assertJsonStructure(['access_token', 'user']);
    }

    public function test_unknown_record_is_404_json(): void
    {
        $admin = $this->makeUser('admin');

        $this->as($admin)->getJson('/api/santri/999')->assertStatus(404)->assertJsonPath('message', 'Data tidak ditemukan.');
    }

    public function test_cors_allows_only_configured_origin(): void
    {
        $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:3000',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ])->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');

        // Origin asing tidak pernah diizinkan: header (bila ada) tetap origin terkonfigurasi, sehingga browser menolaknya.
        $evil = $this->call('OPTIONS', '/api/login', [], [], [], [
            'HTTP_ORIGIN' => 'http://evil.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);
        $this->assertNotSame('http://evil.example', $evil->headers->get('Access-Control-Allow-Origin'));
        $this->assertNotSame('*', $evil->headers->get('Access-Control-Allow-Origin'));
    }
}
