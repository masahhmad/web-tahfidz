<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

#[Fillable(['username', 'email', 'password', 'category', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Tambahkan Method Wajib 1
     * Mengembalikan Primary Key dari User
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Tambahkan Method Wajib 2
     * Mengembalikan array data kustom yang ingin dimasukkan ke dalam Token
     */
    public function getJWTCustomClaims()
    {
        return [
            'role'     => $this->role,
            'category' => $this->category
        ];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function santri()
    {
        return $this->hasMany(Santri::class, 'guru_id');
    }

    public function presensi()
    {
        return $this->hasMany(PresensiGuru::class);
    }
}
