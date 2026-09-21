<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BantuanController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\HalaqahController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\KenaikanJuzController;
use App\Http\Controllers\Api\LookupController;
use App\Http\Controllers\Api\PresensiGuruController;
use App\Http\Controllers\Api\PresensiSiswaController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SantriController;
use App\Http\Controllers\Api\SetoranController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
| Urutan middleware: throttle -> auth:api (401) -> active (403) -> role (403) -> controller.
| Satu URL + method hanya didaftarkan sekali; baca dan tulis dipisah lewat grup role
| (itulah yang membuat super_admin read-only di Data Siswa/Halaqah/Target).
| Rute statis (santri/import, halaqah/siswa, ...) harus mendahului apiResource yang memuat {id}.
| Aturan row-level (pemilik data, pengampu/penguji) ada di controller. Lihat api-spec.md §3.
*/

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
// Halaman login butuh kontak super admin sebelum ada token.
Route::get('/bantuan/publik', [BantuanController::class, 'publik'])->middleware('throttle:public');

Route::middleware(['throttle:api', 'auth:api', 'active'])->group(function () {

    // — semua user login —
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [ProfileController::class, 'show']);
    Route::put('/me', [ProfileController::class, 'update']);
    Route::put('/me/password', [ProfileController::class, 'updatePassword']);
    Route::get('/dashboard/summary', DashboardController::class);
    Route::get('/bantuan', [BantuanController::class, 'index']);

    Route::prefix('lookup')->group(function () {
        Route::get('/kelas', [LookupController::class, 'kelas']);
        Route::get('/halaqah', [LookupController::class, 'halaqah']);
        Route::get('/pengampu', [LookupController::class, 'pengampu']);
        Route::get('/santri', [LookupController::class, 'santri']);
    });

    // — Pengguna: super_admin saja —
    Route::middleware('role:super_admin')->group(function () {
        Route::apiResource('user', UserController::class)->except('destroy');
        Route::put('user/{user}/password', [UserController::class, 'resetPassword']);
        Route::patch('user/{user}/status', [UserController::class, 'setStatus']);
    });

    // — Data Siswa / Halaqah / Target: BACA super_admin + admin —
    Route::middleware('role:super_admin,admin')->group(function () {
        Route::get('santri/{santri}/rekap', [SantriController::class, 'rekap']);
        Route::apiResource('santri', SantriController::class)->only(['index', 'show']);
        Route::apiResource('kelas', KelasController::class)->only(['index', 'show'])
            ->parameters(['kelas' => 'kelas']);
        Route::get('halaqah/siswa', [HalaqahController::class, 'siswa']);
        Route::apiResource('halaqah', HalaqahController::class)->only(['index', 'show']);
    });

    // — Data Siswa / Halaqah / Target: TULIS admin saja —
    Route::middleware('role:admin')->group(function () {
        Route::post('santri/import', [SantriController::class, 'import']);
        Route::apiResource('santri', SantriController::class)->except(['index', 'show']);
        Route::apiResource('kelas', KelasController::class)->except(['index', 'show'])
            ->parameters(['kelas' => 'kelas']);
        Route::put('halaqah/assign', [HalaqahController::class, 'assign']);
        Route::apiResource('halaqah', HalaqahController::class)->except(['index', 'show']);
    });

    // — Presensi Guru —
    Route::get('presensi-guru', [PresensiGuruController::class, 'index']);            // G: milik sendiri; A/SA: semua
    Route::post('presensi-guru', [PresensiGuruController::class, 'store'])
        ->middleware('role:admin,guru_halaqah');                                       // untuk diri sendiri
    Route::middleware('role:super_admin')->group(function () {
        Route::put('presensi-guru/{presensi_guru}', [PresensiGuruController::class, 'update']);
        Route::delete('presensi-guru/{presensi_guru}', [PresensiGuruController::class, 'destroy']);
    });

    // — Presensi Siswa —
    Route::get('presensi-siswa', [PresensiSiswaController::class, 'index']);
    Route::post('presensi-siswa/bulk', [PresensiSiswaController::class, 'bulk'])
        ->middleware('role:admin,guru_halaqah');
    Route::middleware('role:super_admin,admin')->group(function () {
        Route::put('presensi-siswa/{presensi_siswa}', [PresensiSiswaController::class, 'update']);
        Route::delete('presensi-siswa/{presensi_siswa}', [PresensiSiswaController::class, 'destroy']);
    });

    // — Setoran: super_admin hanya baca —
    Route::get('setoran', [SetoranController::class, 'index']);
    Route::middleware('role:admin,guru_halaqah')->group(function () {
        Route::post('setoran', [SetoranController::class, 'store']);
        Route::put('setoran/{setoran}', [SetoranController::class, 'update']);
        Route::delete('setoran/{setoran}', [SetoranController::class, 'destroy']);
    });

    // — Ujian Kenaikan Juz —
    Route::get('kenaikan-juz', [KenaikanJuzController::class, 'index']);
    Route::post('kenaikan-juz', [KenaikanJuzController::class, 'store'])
        ->middleware('role:admin,guru_halaqah');
    Route::patch('kenaikan-juz/{kenaikan_juz}/nilai', [KenaikanJuzController::class, 'nilai'])
        ->middleware('role:guru_halaqah');
    Route::delete('kenaikan-juz/{kenaikan_juz}', [KenaikanJuzController::class, 'destroy'])
        ->middleware('role:admin');
});
