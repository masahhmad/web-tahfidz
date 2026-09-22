<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Telp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Menu Pengguna — hanya super_admin (dijaga middleware `role:super_admin`).
 * Tidak ada `destroy`: akun dinonaktifkan lewat setStatus agar riwayat presensi guru tidak ikut terhapus.
 */
class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'role'   => ['nullable', Rule::in(['super_admin', 'admin', 'guru_halaqah'])],
            'status' => ['nullable', Rule::in(['aktif', 'nonaktif'])],
        ]);

        $users = User::query()
            ->when($request->query('search'), fn ($q, $term) => $q->where(function ($q) use ($term) {
                $q->where('username', 'like', $this->like($term))
                  ->orWhere('email', 'like', $this->like($term));
            }))
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->query('status'), fn ($q, $status) => $q->where('is_active', $status === 'aktif'))
            ->orderBy('id')
            ->paginate($this->perPage($request));

        return $this->paginated($users, UserResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama'     => 'required|string|min:3|max:255',
            'email'    => 'required|email|unique:users,email',
            // Form Tambah Pengguna tidak mengirim telp; admin/super_admin mengisinya sendiri di Pengaturan
            // (sampai itu terisi, frontend mencegat akses — lihat `perlu_lengkapi_telp` di /me).
            'telp'     => ['nullable', 'string', 'regex:'.Telp::PATTERN],
            'role'     => ['required', Rule::in(['admin', 'guru_halaqah'])],
            'kategori' => ['required', Rule::in(['ikh', 'akh'])],
            'password' => 'required|string|min:8',
        ], [
            'email.unique' => 'Email sudah digunakan.',
            'telp.regex'   => Telp::MESSAGE,
        ]);

        $user = User::create([
            'username' => $validated['nama'],
            'email'    => $validated['email'],
            'telp'     => $validated['telp'] ?? null,
            'role'     => $validated['role'],
            'category' => $validated['kategori'],
            'password' => $validated['password'],
        ]);

        return $this->item(new UserResource($user->refresh()), 201);
    }

    public function show(User $user): JsonResponse
    {
        return $this->item(new UserResource($user));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'nama'     => 'required|string|min:3|max:255',
            'email'    => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            // Tidak dikirim = tidak berubah. Bila dikirim harus valid, dan admin/super_admin
            // tidak boleh mengosongkannya (akan terkunci di frontend).
            'telp'     => [
                Rule::requiredIf(in_array($request->input('role'), ['admin', 'super_admin'], true) && $request->has('telp')),
                'nullable', 'string', 'regex:'.Telp::PATTERN,
            ],
            // super_admin hanya boleh tetap super_admin, supaya tidak ada yang terkunci keluar.
            'role'     => ['required', Rule::in($user->role === 'super_admin' ? ['super_admin'] : ['admin', 'guru_halaqah'])],
            'password' => 'prohibited',
        ], [
            'email.unique'        => 'Email sudah digunakan.',
            'telp.required'       => 'Nomor telepon tidak boleh dikosongkan untuk admin.',
            'telp.regex'          => Telp::MESSAGE,
            'password.prohibited' => 'Gunakan endpoint ganti password untuk mengubah password.',
        ]);

        $user->update([
            'username' => $validated['nama'],
            'email'    => $validated['email'],
            'role'     => $validated['role'],
        ] + (array_key_exists('telp', $validated) ? ['telp' => $validated['telp']] : []));

        return $this->item(new UserResource($user));
    }

    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $user->forceFill([
            'password'            => $validated['password'],
            'password_changed_at' => now(),
        ])->save();

        return $this->message('Password berhasil diganti.');
    }

    public function setStatus(Request $request, User $user): JsonResponse
    {
        $request->validate(['is_active' => 'required|boolean']);
        $isActive = $request->boolean('is_active');

        if (! $isActive) {
            if ($user->id === $request->user('api')->id) {
                return $this->message('Anda tidak dapat menonaktifkan akun sendiri.', 422);
            }

            $adaSuperAdminAktifLain = User::where('role', 'super_admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($user->role === 'super_admin' && ! $adaSuperAdminAktifLain) {
                return $this->message('Super admin terakhir tidak dapat dinonaktifkan.', 422);
            }
        }

        $user->is_active = $isActive;
        $user->save();

        return $this->item(new UserResource($user));
    }
}
