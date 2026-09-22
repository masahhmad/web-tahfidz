# API Spec — Web Tahfidz

Dokumen ini diturunkan dari **pembacaan seluruh frontend** (`frontend/app/dashboard/*`) dan dicocokkan dengan backend Laravel yang sudah ada (`backend/routes/api.php`, migrations, models, `CheckRole`). Tujuannya: backend punya satu acuan untuk endpoint, struktur data, middleware, dan hak akses per menu.

- Stack backend: Laravel 13, PHP 8.3, auth JWT (`php-open-source-saver/jwt-auth`, guard `api`).
- Frontend: Next.js 16 (App Router). Saat ini **semua data masih mock/state lokal** — belum ada satu pun pemanggilan API, halaman login, maupun proteksi route.
- Bagian bertanda **⚠ Keputusan** adalah asumsi yang saya ambil karena frontend/DB belum menjawabnya. Mohon dikonfirmasi sebelum implementasi.

Daftar isi
1. [Konvensi API](#1-konvensi-api)
2. [Role & matriks hak akses](#2-role--matriks-hak-akses)
3. [Middleware](#3-middleware)
4. [Peta halaman frontend → endpoint](#4-peta-halaman-frontend--endpoint)
5. [Referensi endpoint](#5-referensi-endpoint)
6. [Perubahan database](#6-perubahan-database)
7. [Temuan pada backend yang sudah ada](#7-temuan-pada-backend-yang-sudah-ada)
8. [Catatan untuk frontend](#8-catatan-untuk-frontend)

---

## 1. Konvensi API

| Hal | Aturan |
|---|---|
| Base URL | `http://localhost:8000/api` (frontend membacanya dari `NEXT_PUBLIC_API_URL`, tanpa `/api`) |
| Format | JSON (`Accept: application/json`, `Content-Type: application/json`; upload CSV memakai `multipart/form-data`) |
| Auth | `Authorization: Bearer <access_token>` (JWT). Klaim token: `sub`, `role`, `category` |
| Penamaan field | `snake_case`, istilah Indonesia sesuai label UI (`nama`, `kelas`, `nisn`, `jumlah_hafalan`, …) |
| Nilai enum | huruf kecil: `hadir` `izin` `sakit` `alpa`; sesi `pagi` `siang` `sore`; role `super_admin` `admin` `guru_halaqah` |
| Tanggal | `YYYY-MM-DD` (tanggal), ISO 8601 untuk timestamp. Tampilan `10/8/2026` diformat di frontend |

### Kode status (wajib benar — jangan `200` untuk error)

| Kode | Kapan |
|---|---|
| `200` / `201` / `204` | sukses / dibuat / sukses tanpa body (delete) |
| `401` | token tidak ada / kedaluwarsa / tidak valid |
| `403` | login valid tapi role tidak boleh, atau akun nonaktif, atau bukan pemilik data |
| `404` | data tidak ditemukan |
| `409` | konflik unik (mis. presensi guru sesi yang sama sudah ada) |
| `422` | validasi gagal |
| `429` | rate limit |

### Bentuk respons

Sukses satu objek / daftar:

```json
{ "data": { "id": 1, "...": "..." } }
```

Daftar berpaginasi (dipakai `PaginationFooter`: `currentPage`, `totalPages`, teks ringkasan):

```json
{
  "data": [ { "id": 1 } ],
  "meta": { "current_page": 1, "last_page": 6, "per_page": 10, "total": 57 }
}
```

Query umum untuk semua daftar: `page` (default 1), `per_page` (default 10, maks 100), `search` (bila relevan).

Error:

```json
{ "message": "Akses ditolak, anda tidak memiliki akses untuk fitur ini." }
```

```json
{
  "message": "The given data was invalid.",
  "errors": { "email": ["Email sudah digunakan."] }
}
```

> Ini **mengubah** bentuk respons `AuthController` yang sekarang (`{status: "success"|"failed", ...}`). Frontend belum memakainya, jadi paling murah diubah sekarang.

---

## 2. Role & matriks hak akses

Role di DB (`users.role`): `super_admin`, `admin`, `guru_halaqah`.

**⚠ Keputusan — daftar role di form Pengguna.** Modal "Tambah Pengguna" di frontend menawarkan `Admin`, `Guru Pengampu`, `Wali Kelas`. Pemetaan usulan: `Admin → admin`, `Guru Pengampu → guru_halaqah`. **`Wali Kelas` belum ada di enum DB** — pilih salah satu: (a) tambahkan `wali_kelas` ke enum dan tentukan aksesnya, atau (b) hapus opsi itu dari frontend. `super_admin` tidak bisa dibuat lewat UI (hanya seeder).

### Aturan yang sudah ditetapkan

| Menu | `super_admin` | `admin` | `guru_halaqah` |
|---|---|---|---|
| **Pengguna** | CRUD penuh | ❌ 403 | ❌ 403 |
| **Data Siswa** | **read-only** | CRUD | ❌ 403 |
| **Halaqah** | **read-only** | CRUD | ❌ 403 |
| **Target** | **read-only** | CRUD | ❌ 403 |

"Read-only" = hanya `GET`. `POST`/`PUT`/`PATCH`/`DELETE` dari `super_admin` ke menu ini harus **403** (walau `super_admin` — ini berbeda dari `routes/api.php` sekarang yang memberi `super_admin` CRUD di `kelas`/`santri`).

### Menu lain (usulan — mengacu ke `routes/api.php` yang sudah ada, mohon dikonfirmasi)

| Menu | `super_admin` | `admin` | `guru_halaqah` |
|---|---|---|---|
| Dashboard | baca | baca | baca (ringkasan halaqah sendiri) |
| Presensi Guru | baca semua | baca semua + buat presensi sendiri | buat + baca presensi **sendiri** |
| Presensi Siswa | baca semua | CRUD semua | buat + baca untuk siswa **halaqahnya** |
| Setoran Siswa | baca semua | CRUD semua | CRUD untuk siswa **halaqahnya** |
| Ujian Kenaikan Juz | baca semua | buat/hapus baris ujian, atur penguji | baca yang terkait; **input nilai** sesuai aturan §5.8 |
| Pengaturan (profil sendiri) | ✅ | ✅ | ✅ |

Semua menu selain Data Siswa/Halaqah/Target/Pengguna butuh data siswa/kelas/pengampu untuk dropdown. Karena `guru_halaqah` **tidak boleh** memanggil `/santri`, `/kelas`, `/halaqah`, disediakan endpoint `GET /lookup/*` yang hanya mengembalikan `id` + label (§5.10).

### Menu yang tampil di sidebar (frontend menyaring dari `role` di `/me`)

| Item | `super_admin` | `admin` | `guru_halaqah` |
|---|:-:|:-:|:-:|
| Dashboard, Presensi Guru, Presensi Siswa, Setoran, Ujian Kenaikan Juz, Pengaturan | ✅ | ✅ | ✅ |
| Data Siswa, Halaqah, Target | ✅ (tanpa tombol tambah/ubah/hapus) | ✅ | ❌ |
| Pengguna | ✅ | ❌ | ❌ |

Menyembunyikan menu hanya UX. **Backend adalah satu-satunya penjaga sebenarnya.**

---

## 3. Middleware

### 3.1 Urutan (Laravel)

```
request
  → throttle            (login: 5/menit per email+IP; lainnya: 60/menit per user)
  → auth:api            (JWT valid?  tidak → 401)
  → active              (users.is_active = true?  tidak → 403)   ← baru
  → role:...            (role termasuk daftar?  tidak → 403)      ← diperbaiki
  → controller
      → FormRequest     (validasi → 422)
      → Policy/Gate     (row-level: pemilik data, pengampu/penguji → 403)
```

### 3.2 Middleware yang dibutuhkan

| Alias | Kelas | Tugas |
|---|---|---|
| `auth:api` | bawaan (guard JWT) | 401 bila token hilang/tidak valid/kedaluwarsa |
| `active` | `EnsureUserIsActive` (**baru**) | tolak (403) user `is_active = false`. Wajib karena UI Pengguna punya tombol *nonaktifkan* — token yang sudah terbit harus ikut mati, jadi cek dilakukan **di setiap request**, bukan hanya saat login |
| `role` | `CheckRole` (**perbaiki**) | 403 bila role bukan salah satu parameter |
| `throttle:login` | bawaan | batasi brute force |

Daftarkan di `bootstrap/app.php`:

```php
$middleware->alias([
    'role'   => \App\Http\Middleware\CheckRole::class,
    'active' => \App\Http\Middleware\EnsureUserIsActive::class,
]);
```

### 3.3 `CheckRole` yang benar

Yang sekarang mengembalikan **HTTP 200** untuk error dan tidak men-`trim` parameter (lihat §7).

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
{
    $user = auth('api')->user();               // auth:api sudah jalan lebih dulu
    $allowed = array_map('trim', $roles);

    if (! $user || ! in_array($user->role, $allowed, true)) {
        return response()->json([
            'message' => 'Akses ditolak, anda tidak memiliki akses untuk fitur ini.',
        ], 403);
    }

    return $next($request);
}
```

```php
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('api')->user()?->is_active) {
            return response()->json(['message' => 'Akun Anda dinonaktifkan.'], 403);
        }
        return $next($request);
    }
}
```

### 3.4 Kerangka `routes/api.php`

Prinsip: **satu URL + satu method hanya didaftarkan sekali**. Pisahkan *baca* dan *tulis* dengan grup middleware — inilah yang memberi `super_admin` read-only.

```php
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:api', 'active'])->group(function () {

    // — semua user login —
    Route::post('/logout',       [AuthController::class, 'logout']);
    Route::post('/refresh',      [AuthController::class, 'refresh']);
    Route::get('/me',            [ProfileController::class, 'show']);
    Route::put('/me',            [ProfileController::class, 'update']);
    Route::put('/me/password',   [ProfileController::class, 'updatePassword']);
    Route::prefix('lookup')->group(function () { /* §5.10 */ });
    Route::get('/dashboard/summary', DashboardController::class);

    // — Pengguna: super_admin saja —
    Route::middleware('role:super_admin')->group(function () {
        Route::apiResource('user', UserController::class);
        Route::put('user/{user}/password', [UserController::class, 'resetPassword']);
        Route::patch('user/{user}/status', [UserController::class, 'setStatus']);
    });

    // — Data Siswa / Halaqah / Target: BACA super_admin+admin, TULIS admin —
    Route::middleware('role:super_admin,admin')->group(function () {
        Route::apiResource('santri',  SantriController::class)->only(['index', 'show']);
        Route::get('santri/{santri}/rekap', [SantriController::class, 'rekap']);
        Route::apiResource('kelas',   KelasController::class)->only(['index', 'show']);
        Route::apiResource('halaqah', HalaqahController::class)->only(['index', 'show']);
        Route::get('halaqah/siswa',   [HalaqahController::class, 'siswa']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::post('santri/import', [SantriController::class, 'import']);
        Route::apiResource('santri',  SantriController::class)->except(['index', 'show']);
        Route::apiResource('kelas',   KelasController::class)->except(['index', 'show']);
        Route::apiResource('halaqah', HalaqahController::class)->except(['index', 'show']);
        Route::put('halaqah/assign',  [HalaqahController::class, 'assign']);
    });

    // — Presensi / Setoran / Kenaikan Juz: role longgar, dijaga Policy (§5.6–5.8) —
    Route::middleware('role:super_admin,admin,guru_halaqah')->group(function () {
        // ...
    });
});
```

> Catatan urutan: rute statis (`santri/import`, `halaqah/siswa`, `halaqah/assign`) harus didaftarkan **sebelum** `apiResource` yang memuat `{santri}`/`{halaqah}`, kalau tidak `import`/`siswa`/`assign` tertangkap sebagai `{id}`.

### 3.5 Row-level (Policy) — role saja tidak cukup

| Aturan | Di mana |
|---|---|
| `guru_halaqah` hanya boleh menyentuh siswa dengan `santris.guru_id = user.id` | Presensi Siswa, Setoran, lookup siswa |
| `guru_halaqah` hanya membaca presensi guru miliknya sendiri (`presensi_gurus.user_id = user.id`) | Presensi Guru |
| Nilai *hafalan* hanya oleh **pengampu** siswa tsb; nilai *soal* hanya oleh **penguji** yang ditunjuk | Kenaikan Juz (§5.8) |
| `super_admin` tidak boleh menonaktifkan **dirinya sendiri** / super_admin terakhir | Pengguna |

### 3.6 Lain-lain

- **CORS** (`config/cors.php`): izinkan origin frontend (`http://localhost:3000`, plus domain produksi), header `Authorization, Content-Type, Accept`, method `GET,POST,PUT,PATCH,DELETE,OPTIONS`.
- **JWT**: TTL pendek (mis. 60 menit) + `POST /refresh`. Setelah `reset password` / `nonaktifkan`, token lama harus tidak berlaku — cukup dijaga middleware `active` untuk nonaktif; untuk reset password aktifkan blacklist JWT (`JWT_BLACKLIST_ENABLED=true`) atau simpan `password_changed_at` dan bandingkan dengan klaim `iat`.
- **Seeder**: `SuperAdminSeeder` menyimpan password `super123` di kode — ganti password pada deploy pertama.

---

## 4. Peta halaman frontend → endpoint

| Route frontend | Yang ditampilkan / dilakukan | Endpoint |
|---|---|---|
| `/login` *(belum ada — harus dibuat)* | Form email + password | `POST /login` |
| Sidebar → **Keluar** | Sign out (sekarang anchor kosong) | `POST /logout` |
| Semua halaman `/dashboard/*` | Muat profil + role untuk sidebar & guard | `GET /me` |
| `/dashboard` | 2 kartu: *Hafalan Tercapai*, *Hafalan Belum Tercapai* | `GET /dashboard/summary` |
| `/dashboard/presensi-guru` | Riwayat presensi + filter tanggal/sesi; modal "Buat Presensi" (status, keterangan) | `GET /presensi-guru`, `POST /presensi-guru` |
| `/dashboard/presensi-siswa` | Riwayat + filter tanggal/halaqah; modal daftar siswa dengan status & keterangan per siswa | `GET /presensi-siswa`, `POST /presensi-siswa/bulk`, `GET /lookup/santri`, `GET /lookup/halaqah` |
| `/dashboard/setoran` | Form (siswa, baris, juz) + tabel riwayat + edit | `GET/POST/PUT/DELETE /setoran`, `GET /lookup/santri` |
| `/dashboard/kenaikan-juz` | Tabel ujian; "Tambah Siswa" (siswa+juz); edit nilai (hafalan/soal sesuai hak) | `GET/POST/DELETE /kenaikan-juz`, `PATCH /kenaikan-juz/{id}/nilai`, `GET /lookup/santri` |
| `/dashboard/data-siswa` | Tabel + filter kelas & pengampu; tambah manual / CSV; edit; hapus; "Lihat rekap laporan" | `GET/POST/PUT/DELETE /santri`, `POST /santri/import`, `GET /santri/{id}/rekap`, `GET /lookup/kelas`, `GET /lookup/pengampu` |
| `/dashboard/halaqah` | Tabel siswa + pengampu; mode "Atur Halaqah": centang siswa → pilih pengampu & halaqah → simpan | `GET /halaqah/siswa`, `PUT /halaqah/assign`, `GET/POST/PUT/DELETE /halaqah` |
| `/dashboard/target` | Tabel kelas + target; pensil → popup "Target Kelas X", input angka total juz per tahun | `GET /kelas`, `PUT /kelas/{id}` (+ `POST`/`DELETE`) |
| `/dashboard/pengguna` | Tabel + filter; tambah; pensil = ubah nama/email/role; kunci = ganti password (2 input); ikon bulat ✓/✗ = aktif/nonaktif + konfirmasi | `GET/POST/PUT /user`, `PUT /user/{id}/password`, `PATCH /user/{id}/status` |
| `/dashboard/pengaturan` | Kartu profil (nama, email, role, "Mengampu N siswa"); Edit Pengguna (tanpa ganti role); Ganti Password | `GET /me`, `PUT /me`, `PUT /me/password` |

Alur sekali jalan: `login → simpan token → GET /me → sidebar disaring dari role → halaman memanggil endpoint miliknya → 401 ⇒ kembali ke /login; 403 ⇒ tampilkan "tidak punya akses"`.

---

## 5. Referensi endpoint

Kolom **Akses**: `SA` super_admin, `A` admin, `G` guru_halaqah.

### 5.1 Auth

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| POST | `/login` | publik | `throttle:login` |
| POST | `/logout` | semua | invalidate token |
| POST | `/refresh` | semua | token baru |
| GET | `/me` | semua | profil + role |
| PUT | `/me` | semua | ubah `nama`, `email` — **role tidak boleh diubah** |
| PUT | `/me/password` | semua | ganti password sendiri |

`POST /login`
```json
// request
{ "email": "zaid@example.com", "password": "rahasia123" }
// 200
{
  "access_token": "eyJ0eXAi…", "token_type": "bearer", "expires_in": 3600,
  "user": { "id": 3, "nama": "Muhammad Zaid Burhanuddin", "email": "zaid@example.com", "role": "guru_halaqah", "kategori": "ikh" }
}
// 401  { "message": "Email atau password salah." }
// 403  { "message": "Akun Anda dinonaktifkan." }
```

`GET /me`
```json
{ "data": { "id": 3, "nama": "Muhammad Zaid Burhanuddin", "email": "zaid@example.com",
            "role": "guru_halaqah", "kategori": "ikh", "jumlah_siswa": 16 } }
```
`jumlah_siswa` = `count(santris.guru_id = user.id)` → teks "Mengampu 16 siswa" di Pengaturan (untuk role lain `0`/`null`).

`PUT /me` — body `{ "nama": "…", "email": "…", "telp": "08…" }` (`telp` opsional: tidak dikirim = tidak berubah; `admin`/`super_admin` tidak boleh mengosongkannya). Body yang berisi `role` **diabaikan/422**.

`PUT /me/password`
```json
{ "current_password": "…", "password": "…", "password_confirmation": "…" }
```
**⚠ Keputusan:** popup "Ganti Password" di frontend saat ini hanya punya *Kata Sandi* + *Konfirmasi*. Untuk password **sendiri** sebaiknya `current_password` wajib (mencegah pengambilalihan sesi yang terbuka). Frontend perlu menambah satu input di Pengaturan; popup di halaman Pengguna (reset oleh super_admin) tidak memakainya.

### 5.2 Pengguna — `super_admin` saja

| Method | Path | Keterangan |
|---|---|---|
| GET | `/user` | filter: `search` (nama/email), `role`, `status` (`aktif`/`nonaktif`) |
| POST | `/user` | tambah |
| GET | `/user/{id}` | detail |
| PUT | `/user/{id}` | ubah **nama, email, role** (modal pensil) |
| PUT | `/user/{id}/password` | ganti password (modal kunci) |
| PATCH | `/user/{id}/status` | aktifkan / nonaktifkan (ikon bulat ✓/✗) |
| DELETE | `/user/{id}` | opsional (soft delete) — **tidak dipakai UI saat ini**; tombol hapus sudah diganti nonaktifkan |

Objek pengguna:
```json
{ "id": 7, "no": 1, "nama": "Ahmad Rasyid", "email": "ahmadrasyid@gmail.com", "telp": "085712345678",
  "role": "guru_halaqah", "role_label": "Guru Pengampu", "kategori": "ikh", "is_active": true }
```
(`nama` dipetakan dari kolom `users.username` lewat `UserResource`. Kolom `no` di tabel dihitung frontend dari index + halaman — tidak perlu dari backend.)

`POST /user` — validasi:
```json
{ "nama": "required|string|max:255",
  "email": "required|email|unique:users,email",
  "telp": "nullable|regex:/^08[0-9]{8,12}$/",   // opsional; form tidak mengirimnya, lihat §5.11
  "role": "required|in:admin,guru_halaqah",          // + wali_kelas bila diputuskan (§2)
  "kategori": "required|in:ikh,akh",                 // lihat catatan
  "password": "required|string|min:8" }            // bukan between:6,12 seperti sekarang
```
**⚠ Keputusan:** form "Tambah Pengguna" belum punya field **password** dan **kategori** (`users.category` ikh/akh, default `ikh`). Pilih: frontend menambah kedua field, atau backend membuat password acak/undangan dan default kategori `ikh`.

`PUT /user/{id}` — `nama`, `email` (unique kecuali diri sendiri), `role`. Tidak menerima `password`.

`PUT /user/{id}/password`
```json
{ "password": "min:8|confirmed", "password_confirmation": "…" }
```
Frontend menandai *Konfirmasi tidak cocok* di sisi klien, tetapi backend tetap wajib memvalidasi `confirmed`.

`PATCH /user/{id}/status` — `{ "is_active": false }`. Aturan: tidak boleh menonaktifkan diri sendiri / super_admin terakhir (422). Respons berisi objek pengguna terbaru. Efek: request berikutnya dari user itu ditolak `active` middleware (403).

### 5.3 Data Siswa — SA baca · A CRUD

Objek siswa:
```json
{ "id": 12, "nama": "Ahmad Rasyid", "nisn": "0987654321", "kategori": "ikh",
  "kelas": { "id": 1, "nama": "7A" }, "jumlah_hafalan": 15,
  "pengampu": { "id": 3, "nama": "Ustadz Zaid" }, "halaqah": { "id": 2, "nama": "Al-Fatih" } }
```

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| GET | `/santri` | SA, A | filter `kelas_id`, `guru_id`, `search` (nama/NISN) — sesuai *Pilih Kelas* & *Semua Pengampu* |
| GET | `/santri/{id}` | SA, A | |
| POST | `/santri` | A | tambah manual |
| PUT | `/santri/{id}` | A | ubah |
| DELETE | `/santri/{id}` | A | hapus (cascade ke setoran/ujian sesuai FK — pertimbangkan soft delete agar riwayat aman) |
| POST | `/santri/import` | A | tambah lewat CSV |
| GET | `/santri/{id}/rekap` | SA, A | "Lihat rekap laporan" |

`POST /santri` / `PUT`:
```json
{ "nama": "required|string|max:255",
  "kelas_id": "required|exists:kelas,id",
  "nisn": "required|digits:10|unique:santris,nisn",
  "jumlah_hafalan": "required|integer|between:0,30" }
```
`kategori` diturunkan dari `kelas.kategori` (form tidak memintanya). **NISN wajib `string`** — contoh data `0987654321` berawalan nol; kolom `nim integer` yang sekarang akan menghilangkan nol itu (§6). `jumlah_hafalan` disimpan **integer (juz)**; frontend menampilkannya sebagai "15 Juz".

`POST /santri/import` — `multipart/form-data`, field `file` (`.csv`, maks 2 MB). Format header **persis** template yang diunduh frontend:
```csv
nama_siswa,kelas,nisn,jumlah_hafalan
Ahmad Rasyid,7A,0987654321,15 Juz
```
`kelas` dicocokkan ke `kelas.kelas` (mis. `7A`); `jumlah_hafalan` boleh `15` atau `15 Juz` (ambil angkanya). Perilaku: **semua-atau-tidak** (transaksi). Respons:
```json
// 201
{ "data": { "dibuat": 25 } }
// 422 — baris yang gagal dilaporkan berikut nomor barisnya
{ "message": "Import dibatalkan.", "errors": [
  { "baris": 3, "field": "nisn", "pesan": "NISN sudah terdaftar." },
  { "baris": 5, "field": "kelas", "pesan": "Kelas 9Z tidak ditemukan." } ] }
```

`GET /santri/{id}/rekap` — ringkasan untuk tautan "Lihat rekap laporan":
```json
{ "data": {
  "siswa": { "id": 12, "nama": "Ahmad Rasyid", "kelas": "7A" },
  "presensi": { "hadir": 40, "izin": 2, "sakit": 1, "alpa": 0 },
  "setoran": { "total_baris": 1240, "terakhir": "2026-08-10" },
  "kenaikan_juz": [ { "juz": 1, "nilai_hafalan": 85, "nilai_soal": 85 } ] } }
```
**⚠ Keputusan:** frontend belum punya halaman tujuan rekap; bentuk ini usulan minimal.

### 5.4 Halaqah — SA baca · A CRUD

**⚠ Keputusan — model data.** UI memisahkan **pengampu** dan **halaqah** (dua dropdown di "Atur Halaqah": `Ustadz Zaid` dan `Kelas 7A-1`; di Presensi Siswa ada `Halaqah Al-Fatih`). DB sekarang hanya punya `santris.guru_id` (pengampu). Usulan: tambah tabel `halaqahs` (`nama`) dan `santris.halaqah_id`; **pengampu tetap `santris.guru_id`**. "Atur Halaqah" menetapkan keduanya untuk siswa terpilih. Bila halaqah sebenarnya cukup = pengampu, hapus dropdown kedua dari frontend dan lewati tabel `halaqahs`.

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| GET | `/halaqah/siswa` | SA, A | tabel halaman Halaqah; filter `guru_id`, `kelas_id`, `search` |
| PUT | `/halaqah/assign` | A | simpan dari mode "Atur Halaqah" |
| GET | `/halaqah` | SA, A | daftar halaqah + `jumlah_siswa` |
| POST | `/halaqah` | A | `{ "nama": "Al-Fatih" }` (unik) |
| PUT | `/halaqah/{id}` | A | ubah nama |
| DELETE | `/halaqah/{id}` | A | 422 bila masih ada siswa (atau lepas dulu) |

`GET /halaqah/siswa` — baris tabel (No, Nama Siswa, Kelas, NISN, Jumlah Hafalan, Guru Pengampu):
```json
{ "data": [ { "id": 12, "nama": "Ahmad Rasyid", "kelas": "7A", "nisn": "0987654321",
              "jumlah_hafalan": 15,
              "pengampu": { "id": 3, "nama": "Ustadz Zaid" }, "halaqah": { "id": 2, "nama": "Al-Fatih" } } ],
  "meta": { "current_page": 1, "last_page": 6, "per_page": 10, "total": 57 } }
```

`PUT /halaqah/assign`
```json
{ "santri_ids": [12, 13, 14],            // required|array|min:1|exists:santris,id
  "guru_id": 3,                          // required|exists:users,id  (harus role guru_halaqah & aktif)
  "halaqah_id": 2 }                      // required|exists:halaqahs,id
// 200 { "data": { "diperbarui": 3 } }
```
Atomik (transaksi). Mengganti pengampu siswa tidak mengubah riwayat presensi/setoran/ujian yang sudah tercatat.

### 5.5 Target — SA baca · A CRUD

"Target" = total juz yang harus dicapai siswa **satu kelas dalam setahun**. Data ini sudah ada di `kelas.target_hafalan`, jadi resource-nya `kelas`.

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| GET | `/kelas` | SA, A | tabel Target |
| GET | `/kelas/{id}` | SA, A | |
| POST | `/kelas` | A | buat kelas (+ target opsional) |
| PUT | `/kelas/{id}` | A | **dipakai popup pensil**; boleh hanya `target_hafalan` |
| DELETE | `/kelas/{id}` | A | 422 bila masih dipakai siswa |

```json
// item
{ "id": 1, "kelas": "7A", "kategori": "ikh", "target_hafalan": null, "jumlah_siswa": 32 }
```
`target_hafalan: null` → frontend menampilkan **"Belum Diatur"**; angka → **"15 Juz"**.

```json
// PUT /kelas/1  (popup "Target Kelas 7A")
{ "target_hafalan": 15 }               // nullable|integer|between:1,30
```
**⚠ Keputusan:** teks "untuk tahun itu" menyiratkan target per **tahun ajaran**, tetapi tabel hanya punya satu nilai. Bila target tahun lalu harus tersimpan, gunakan tabel `kelas_targets (kelas_id, tahun_ajaran, target_hafalan)`. Spek ini mengasumsikan satu nilai aktif per kelas.

### 5.6 Presensi Guru

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| GET | `/presensi-guru` | G: milik sendiri · A, SA: semua | filter `tanggal`, `sesi`, `user_id` (A/SA) |
| POST | `/presensi-guru` | A, G | presensi **untuk diri sendiri** (`user_id` dari token, bukan dari body) |
| PUT/DELETE | `/presensi-guru/{id}` | SA | koreksi/hapus |

Item (kolom tabel: Tanggal, Sesi, Status, Waktu, Lokasi, Keterangan):
```json
{ "id": 5, "tanggal": "2026-08-10", "sesi": "pagi", "status": "hadir",
  "waktu": "05:00", "lokasi": "-6.2,106.8", "keterangan": null }
```
`POST`:
```json
{ "tanggal": "required|date",
  "sesi": "required|in:pagi,siang,sore",
  "status": "required|in:hadir,izin,sakit,alpa",
  "keterangan": "required_unless:status,hadir|nullable|string|max:255",
  "lokasi": "nullable|string|max:100" }
```
`waktu` = jam server saat dibuat (`created_at`). Unik `(user_id, tanggal, sesi)` → **409** bila sudah presensi. Aturan UI yang dijaga ulang di backend: keterangan **wajib** bila status ≠ `hadir`.
**⚠ Keputusan:** data contoh `lokasi` = `011011011011011011` (mock). Diasumsikan string koordinat opsional dari geolokasi browser.

### 5.7 Presensi Siswa & Setoran

**Presensi siswa** — modal menyimpan status **banyak siswa sekaligus**:

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| GET | `/presensi-siswa` | G: siswa halaqahnya · A, SA: semua | filter `tanggal`, `halaqah_id`, `sesi`, `santri_id` |
| POST | `/presensi-siswa/bulk` | A, G | simpan 1 sesi |
| PUT/DELETE | `/presensi-siswa/{id}` | A, SA | koreksi / hapus (G hanya milik siswanya, pada hari yang sama — usulan) |

```json
// POST /presensi-siswa/bulk
{ "tanggal": "2026-08-10", "sesi": "pagi", "halaqah_id": 2,
  "items": [
    { "santri_id": 12, "status": "hadir" },
    { "santri_id": 13, "status": "sakit", "keterangan": "Demam" } ] }
```
Default status frontend = `hadir` bila tidak diubah; backend menerima daftar lengkap. Unik `(santri_id, tanggal, sesi)`: kirim ulang sesi yang sama → **upsert** (bukan 409), supaya guru bisa memperbaiki. Item tabel: `nama_siswa`, `halaqah`, `tanggal`, `sesi`, `status`, `keterangan`.

**Setoran**

| Method | Path | Akses |
|---|---|---|
| GET | `/setoran` | G: siswa halaqahnya · A, SA: semua |
| POST | `/setoran` | A, G |
| PUT/DELETE | `/setoran/{id}` | A; G untuk milik siswanya |

```json
// POST /setoran
{ "santri_id": 12, "juz": 1, "baris": 30, "tanggal": "2026-08-10" }   // tanggal default hari ini
// item
{ "id": 9, "santri": { "id": 12, "nama": "Ahmad Rasyid" }, "kelas": "7A",
  "juz": 1, "baris": 30, "tanggal": "2026-08-10" }
```
Validasi: `juz` 1–30, `baris` integer ≥ 1. Tabel menampilkan `"30 baris"` (format di frontend). `guru_id` pencatat diisi dari token.

### 5.8 Ujian Kenaikan Juz

Tabel: Nama Siswa, Kelas, Juz, **Nilai Hafalan**, **Nilai Soal**, Aksi. Aturan input nilai (saat ini di frontend dengan membandingkan **nama** string `CURRENT_TEACHER` — harus pindah ke backend, berdasarkan **id**):

| Nilai | Boleh diisi oleh |
|---|---|
| `nilai_hafalan` | **pengampu** siswa (`santris.guru_id = user.id`) |
| `nilai_soal` | **penguji** yang ditunjuk di baris itu (`penguji_id = user.id`) |
| keduanya | user yang merangkap pengampu **dan** penguji |

| Method | Path | Akses | Keterangan |
|---|---|---|---|
| GET | `/kenaikan-juz` | G: baris yang ia pengampu/penguji · A, SA: semua | filter `juz`, `kelas_id`, `search` |
| POST | `/kenaikan-juz` | A, G (untuk siswanya) | tombol "Tambah Siswa": `{ santri_id, juz, penguji_id? }` — nilai awal `null` |
| PATCH | `/kenaikan-juz/{id}/nilai` | G (pengampu/penguji) | modal pensil |
| DELETE | `/kenaikan-juz/{id}` | A | |

```json
// item — dua flag menggantikan CURRENT_TEACHER di frontend
{ "id": 4, "santri": { "id": 12, "nama": "Ahmad Rasyid" }, "kelas": "7A", "juz": 1,
  "nilai_hafalan": 85, "nilai_soal": null,
  "pengampu": { "id": 3, "nama": "Ustadz Zaid" }, "penguji": { "id": 9, "nama": "Ahmad" },
  "can_input_hafalan": true, "can_input_soal": false }
```
```json
// PATCH /kenaikan-juz/4/nilai — kirim hanya field yang diedit
{ "nilai_hafalan": 90 }                // integer|between:0,100
```
Field yang **tidak** dimiliki haknya oleh pemanggil → **403** (bukan diabaikan diam-diam). `nilai_hafalan` dipetakan ke kolom `kenaikan_juzs.nilai_setoran`. Kolom `nilai_tambahan` di DB belum dipakai UI — biarkan nullable.
**⚠ Keputusan:** satu baris per `(santri_id, juz)` (unik) — ujian ulang menimpa baris. Juga: apakah `santris.jumlah_hafalan` otomatis naik saat lulus (perlu KKM)? Frontend tidak menunjukkan aturan ini, jadi spek ini **tidak** mengubah `jumlah_hafalan` otomatis.

### 5.9 Dashboard

`GET /dashboard/summary` — semua role; `guru_halaqah` dihitung untuk siswa halaqahnya, `admin`/`super_admin` global.
```json
{ "data": {
  "hafalan_tercapai": 128,
  "hafalan_belum_tercapai": 46 } }
```
**⚠ Keputusan:** definisinya belum tertulis di UI. Usulan: siswa `tercapai` bila `jumlah_hafalan >= kelas.target_hafalan`, selainnya `belum` (kelas tanpa target dihitung `belum`). Label "Bertambah/Berkurang dari bulan lalu" saat ini teks statis; bila ingin nyata, perlu snapshot bulanan (`dashboard_snapshots`) lalu tambahkan `perubahan_bulan_lalu` — kalau tidak, frontend cukup menghapus teks itu.

### 5.10 Lookup (untuk dropdown) — semua user login

Hanya `id` + label. Ada agar `guru_halaqah` tetap bisa mengisi form tanpa mengakses menu Data Siswa/Halaqah/Target.

| Path | Isi | Scope |
|---|---|---|
| `GET /lookup/kelas` | `[{id, nama}]` | semua |
| `GET /lookup/halaqah` | `[{id, nama}]` | semua |
| `GET /lookup/pengampu` | `[{id, nama}]` — role `guru_halaqah` aktif | semua |
| `GET /lookup/santri?halaqah_id=&search=` | `[{id, nama, kelas, halaqah}]` | G: hanya siswa halaqahnya · A, SA: semua |

### 5.11 Bantuan (WhatsApp)

Nomor telepon disimpan sebagai **string format lokal `08xxxxxxxxxx`** (kolom `users.telp`, 10–14 digit; angka 0 di depan utuh). Form Tambah Pengguna **tidak** mengirim `telp` (opsional saat dibuat). `admin` dan `super_admin` wajib mengisinya sendiri lewat `PUT /me` (Pengaturan → Edit Pengguna); guru tidak wajib.

**Pencegatan (frontend):** `GET /me` dan objek `user` di login memuat `perlu_lengkapi_telp` (`true` bila role `admin`/`super_admin` dan `telp` masih kosong). Selama `true`, frontend menampilkan halaman putih "Nomer Telp belum diisi, edit pengguna dan isi terlebih dahulu nomer telp" + tombol ke Pengaturan di semua halaman dashboard **kecuali Pengaturan**. Backend sengaja tidak memblokir endpoint lain — `/me`, `PUT /me`, dan `/bantuan` tetap terbuka supaya Pengaturan bisa dipakai. Flag yang sama ada di daftar `/user` sehingga super_admin bisa melihat siapa yang belum mengisi. Backend hanya menerima format `08…` (bukan `62…`/`+62`/spasi/tanda hubung) — **frontend yang membersihkan input**.

| Method | Path | Akses | Isi |
|---|---|---|---|
| GET | `/bantuan/publik` | publik, `throttle:public` (30/mnt/IP) | `super_admin[]`, `developer` — untuk "Hubungi Super Admin" di halaman login. Nomor admin **tidak** dibuka ke publik |
| GET | `/bantuan` | semua user login | `admin[]`, `super_admin[]`, `developer` — untuk dropdown Bantuan di sidebar |

```json
{ "data": {
  "admin": [ { "id": 2, "nama": "Admin Satu", "telp": "085712345678", "wa_url": "https://wa.me/6285712345678" } ],
  "super_admin": [ { "id": 1, "nama": "Super Admin", "telp": "081234567890", "wa_url": "https://wa.me/6281234567890" } ],
  "developer": { "nama": "Developer", "telp": "082142986689", "wa_url": "https://wa.me/6282142986689" } } }
```
Hanya akun **aktif** yang sudah punya `telp` yang muncul. `telp` tetap `08…` (untuk ditampilkan); `wa_url` adalah `08…` → `628…` siap dipakai sebagai `href` (bila frontend memilih membangun URL sendiri, aturannya: buang `0` di depan, awali `62`). Nomor developer di-hardcode di `config/tahfidz.php` (`DEVELOPER_TELP` untuk override).

---

## 6. Perubahan database

Migration yang sudah ada belum cukup untuk UI. Berikut **delta** yang dibutuhkan (nama kolom mengikuti yang sudah ada):

| Tabel | Perubahan | Alasan |
|---|---|---|
| `users` | + `is_active` boolean default `true` | tombol nonaktifkan |
| `users` | + `telp` string(15) nullable | kontak bantuan WhatsApp (§5.11) |
| `users` | `role` enum: tambah `wali_kelas` **atau** hapus opsinya dari UI | §2 |
| `santris` | `nim` integer → **`nisn` string(10) unique** | NISN berawalan nol (`0987654321`) |
| `santris` | `jumlah_hafalan` → unsigned tinyint default 0 (0–30) | juz |
| `santris` | + `halaqah_id` FK nullable → `halaqahs` | §5.4 |
| `halaqahs` *(baru)* | `id`, `nama` unique, timestamps | §5.4 |
| `kelas` | `unique(kelas, kategori)`; `target_hafalan` tinyint nullable | Target |
| `presensi_gurus` | + `tanggal` date, + `sesi` enum(pagi,siang,sore); `status` enum + `alpa`; `unique(user_id, tanggal, sesi)` | UI punya sesi & Alpa; tabel hanya menyimpan `created_at` |
| `presensi_siswas` | + `tanggal`, + `sesi`, + `guru_id` (pencatat); `status` enum: `alpha` → `alpa` (`tidur` tak dipakai UI — hapus atau biarkan); `unique(santri_id, tanggal, sesi)` | sama |
| `setorans` | + `juz` tinyint, + `tanggal` date, + `guru_id` (pencatat) | form Setoran punya Juz |
| `kenaikan_juzs` | `nilai_setoran`, `nilai_soal` → **nullable**; + `penguji_id` FK → users; `unique(santri_id, juz)` | baris ujian dibuat sebelum dinilai; ada penguji |

Indeks yang disarankan: `santris(guru_id)`, `santris(halaqah_id)`, `santris(kelas_id)`, `presensi_siswas(tanggal)`, `setorans(santri_id, tanggal)`.

---

## 7. Temuan pada backend yang sudah ada

Hal-hal di kode sekarang yang akan membuat aturan di atas gagal bila dibiarkan:

1. **`CheckRole` mengembalikan HTTP 200** untuk "belum login" dan "akses ditolak" — klien tidak bisa membedakan dari sukses. Harus `401`/`403` (§3.3).
2. **Spasi di parameter role**: `role:super_admin, admin` → Laravel memecah di `,` **tanpa trim**, sehingga yang dibandingkan adalah `" admin"` (dengan spasi) ≠ `"admin"`. Hanya nama role **pertama** di tiap daftar yang pernah cocok. Tulis `role:super_admin,admin` dan tambahkan `array_map('trim', …)`.
3. **URL ganda di `routes/api.php`**: `presensi-siswa`, `presensi-guru`, `setoran` didaftarkan berkali-kali di grup role berbeda. Untuk method + URI yang sama, pendaftaran **terakhir menimpa** yang sebelumnya, jadi middleware yang berlaku ditentukan urutan file, bukan niat penulisnya. Digabung dengan poin 2, hasil kira-kira: `guru_halaqah` tidak bisa mengakses satu endpoint pun; `admin` hanya lolos di `presensi-guru` (index/store/show); `presensi-siswa`, `setoran`, `kelas`, `santri`, `kenaikan-juz` praktis hanya bisa dipakai `super_admin`. (Ini hasil membaca kode; vendor backend belum ter-install di sini sehingga `route:list` belum saya jalankan — cek dengan `php artisan route:list` setelah diperbaiki.) Satu URL + method = satu pendaftaran (§3.4).
4. **`super_admin` punya CRUD di `kelas`/`santri`** — bertentangan dengan aturan read-only.
5. **`Santri::guruTahfidz()`** memakai `belongsTo(User::class)` tanpa foreign key → Laravel mencari `user_id` padahal kolomnya `guru_id`. Tulis `belongsTo(User::class, 'guru_id')`.
6. **`AuthController`** memakai body `{status: 'failed'}` dengan HTTP 200 dan mengembalikan objek `user` mentah; pindahkan ke `UserResource` dan status kode yang benar.
7. **Controller sudah ada dan sudah memvalidasi (inline `$request->validate`)**, tetapi kontraknya berbeda dari yang dibutuhkan frontend:
   - Semua daftar memakai `Model::all()` → **tanpa paginasi, filter, maupun pencarian** (frontend punya footer paginasi dan filter kelas/pengampu/halaqah/tanggal).
   - Tidak ada scoping per pengguna (guru_halaqah melihat semua data) dan tidak ada Policy.
   - Respons memakai `{status, message, data|user}`; §1 menggantinya dengan `{data, meta}` + kode HTTP yang benar.
   - `UserController`: validasi password `between:6, 12` membatasi **maksimal 12 karakter** (dan berspasi) — ganti `min:8` tanpa batas atas sempit. `update` mewajibkan `category` dan menganggap `username` unik, padahal modal ubah di frontend hanya mengirim nama, email, role, dan kolom `username` tidak unik di migration.
   - `SantriController`: `nim` divalidasi `integer` (§6, NISN) dan `kelas_id`/`guru_id` tidak dicek `exists`.
8. **Belum ada** endpoint `/me`, `/refresh`, `/halaqah`, `/lookup/*`, `/dashboard/summary`, `/santri/import`, `/santri/{id}/rekap`, `/presensi-siswa/bulk`, `PATCH /kenaikan-juz/{id}/nilai`, `PATCH /user/{id}/status`, `PUT /user/{id}/password`.

### Pemetaan nama field (kontrak API ↔ kolom/kode sekarang)

Frontend belum memanggil API, jadi nama di kontrak ini bebas dipilih. Spek memakai istilah UI; **bila backend lebih suka mempertahankan nama sekarang, cukup beri tahu** — yang penting satu nama dipakai konsisten.

| Kontrak (spek ini) | Sekarang di backend |
|---|---|
| `user.nama` | `users.username` |
| `user.kategori` | `users.category` |
| `santri.nisn` (string) | `santris.nim` (integer) |
| `kenaikan_juz.nilai_hafalan` | `kenaikan_juzs.nilai_setoran` |
| `status: alpa` | `presensi_siswas.status = alpha` |
| `kelas` (objek `{id, nama}`) di siswa | `santris.kelas_id` |
| `pengampu` (objek) di siswa | `santris.guru_id` |

---

## 8. Catatan untuk frontend

Agar kontrak ini bisa dipakai, pekerjaan frontend yang menyertainya (bukan tanggung jawab backend, tapi mempengaruhi desain):

- **Halaman `/login`** + tombol **Keluar** di sidebar → `POST /logout`.
- **Guard route**: Next.js 16 memakai `proxy.ts` (pengganti `middleware.ts`, sudah deprecated). Simpan token di **cookie httpOnly** (di-set lewat Route Handler Next yang memanggil `/login`) agar `proxy.ts` bisa membaca klaim `role` dan mengalihkan: tanpa token → `/login`; `guru_halaqah` ke `/dashboard/data-siswa|halaqah|target|pengguna` atau `admin` ke `/dashboard/pengguna` → halaman "tidak punya akses". Ini **hanya UX**; penegakan tetap di backend (§3).
- **Sidebar** disaring dengan `role` dari `/me` (tabel §2). Pada Data Siswa/Halaqah/Target, sembunyikan tombol Tambah/Ubah/Hapus/Atur Halaqah bila `role === 'super_admin'`.
- **API client** tunggal: menyisipkan `Authorization`, menangani `401` (→ login) dan `403`, dan membaca `meta` untuk `PaginationFooter`.
- Ganti semua mock (`RECORDS`, `STUDENTS`, `CLASS_OPTIONS`, `PENGAMPU_OPTIONS`, `CURRENT_TEACHER`, …) dengan hasil endpoint; filter dan dropdown memakai **id** (bukan nama).
- Form yang perlu field tambahan: **Tambah Pengguna** (password, kategori), **Ganti Password di Pengaturan** (`current_password`) — lihat ⚠ di §5.1–5.2.
