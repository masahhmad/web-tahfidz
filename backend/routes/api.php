<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KelasController;
use App\Http\Controllers\Api\KenaikanJuzController;
use App\Http\Controllers\Api\PresensiGuruController;
use App\Http\Controllers\Api\PresensiSiswaController;
use App\Http\Controllers\Api\SantriController;
use App\Http\Controllers\Api\SetoranController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('role:super_admin')->group(function () {
        Route::apiResource('/user', UserController::class);
        Route::apiResource('/presensi-guru', PresensiGuruController::class);
        Route::apiResource('/presensi-siswa', PresensiSiswaController::class);
        Route::apiResource('/setoran', SetoranController::class);
    });

    Route::middleware('role:super_admin, admin')->group(function () {
        Route::apiResource('/presensi-siswa', PresensiSiswaController::class);
        Route::get('/presensi-guru/semua', [PresensiGuruController::class, 'getAllAtendance']);
        Route::apiResource('/presensi-guru', PresensiGuruController::class)->except([
            'update',
            'destroy'
        ]);
    });

    Route::middleware('role:super_admin, admin')->group(function () {
        Route::apiResource('/kelas', KelasController::class);
        Route::apiResource('/santri', SantriController::class);
    });

    Route::middleware('role:admin, guru_halaqah')->group(function () {
        Route::apiResource('/presensi-guru', PresensiGuruController::class)->except([
            'update',
            'destroy'
        ]);
    });

    Route::middleware('role:super_admin, admin, guru_halaqah')->group(function () {
        Route::apiResource('/presensi-siswa', PresensiSiswaController::class);
        Route::apiResource('/kenaikan-juz', KenaikanJuzController::class);
    });
});
