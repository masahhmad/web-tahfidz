Berikut adalah draft **API Specification (RESTful API)** yang dirancang komprehensif berdasarkan ERD dan Userflow sistem Web Tahfidz yang kamu lampirkan.

Semua endpoint dilindungi oleh autentikasi (`Bearer Token` / Sanctum) kecuali auth public.

---

## 1. Authentication & Account Settings

### `POST /api/auth/login`

* **Akses:** Public
* **Request Body:**
```json
{
  "username": "guru_ahmad",
  "password": "password123"
}

```


* **Response (200 OK):**
```json
{
  "token": "1|qwerty12345...",
  "user": {
    "id": 1,
    "username": "guru_ahmad",
    "email": "ahmad@mail.com",
    "category": "ikh",
    "role": "guru_halaqah"
  }
}

```



### `GET /api/profile`

* **Akses:** Authenticated User
* **Response (200 OK):** Menampilkan detail profil user yang sedang login.

### `PUT /api/profile/password`

* **Akses:** Authenticated User
* **Request Body:**
```json
{
  "password_lama": "password123",
  "password_baru": "newpassword123",
  "password_baru_confirmation": "newpassword123"
}

```



### `POST /api/auth/logout`

* **Akses:** Authenticated User

---

## 2. Dashboard / Overview

Mengakomodasi menu *Overview* di Userflow (statistik statistik ketercapaian target & ujian).

### `GET /api/overview/stats`

* **Akses:** Authenticated User (Admin/Guru)
* **Response (200 OK):**
```json
{
  "tercapai_target": 45,
  "belum_tercapai_target": 12,
  "selesai_ujian_tahfidz": 30,
  "belum_selesai_ujian_tahfidz": 27
}

```



---

## 3. Presensi Guru

Berhubungan dengan tabel `Presensi` (relasi `guru_id`).

### `POST /api/guru/presensi`

* **Akses:** Guru Halaqah
* **Request Body:**
```json
{
  "status": "hadir", // enum: hadir, sakit, izin
  "ket": "Mengajar di Masjid Utama", // opsional untuk sakit/izin
  "lokasi": "-6.175392, 106.827153" // opsional
}

```



### `GET /api/guru/presensi/riwayat`

* **Akses:** Guru Halaqah / Admin
* **Query Params:** `?start_date=2026-07-01&end_date=2026-07-30`

---

## 4. Presensi Santri

Berhubungan dengan tabel `Presensi` (relasi `santri_id`).

### `POST /api/santri/presensi`

* **Akses:** Guru Halaqah
* **Request Body:**
```json
{
  "santri_id": 10,
  "status": "hadir", // enum: hadir, sakit, izin, tidur, alpha
  "ket": "-"
}

```



---

## 5. Setoran Hafalan Siswa

Berhubungan dengan tabel `Setoran`.

### `POST /api/setoran`

* **Akses:** Guru Halaqah
* **Request Body:**
```json
{
  "santri_id": 10,
  "surat": "Al-Baqarah",
  "ayat": 255
}

```



---

## 6. Ujian Kenaikan Juz

Berhubungan dengan tabel `Kenaikan Juz`.

### `POST /api/ujian-kenaikan-juz`

* **Akses:** Guru Halaqah / Admin
* **Request Body:**
```json
{
  "santri_id": 10,
  "juz": 1,
  "nilai_setoran": 85,
  "nilai_soal": 90,
  "nilai_tambahan": 5
}

```



---

## 7. Ujian Tahfidz

Berhubungan dengan tabel `Ujian Tahfidz` & Userflow Ujian Syarat / Syahadah.

### `GET /api/ujian-tahfidz/pendaftaran`

* **Akses:** Guru / Admin
* **Query Params:** `?type=halaqah_saya` / `?type=halaqah_lain`

### `POST /api/ujian-tahfidz/terima`

* **Akses:** Guru Halaqah (Penguji)
* **Request Body:**
```json
{
  "santri_id": 10,
  "juz": 30
}

```



### `POST /api/ujian-tahfidz/penilaian`

* **Akses:** Guru Halaqah (Penguji)
* **Request Body:**
```json
{
  "ujian_tahfidz_id": 1,
  "nilai": 88,
  "nilai_tambahan": 2,
  "status_diterima": true
}

```



---

## 8. Management Data Master (Admin & Super Admin)

### A. Data Siswa (`/api/santri`)

* **`GET /api/santri`** — Menampilkan daftar santri (Support search & filter kelas/kategori/status hafiz).
* **`POST /api/santri`** — Tambah Santri Baru.
* *Request:* `{"name": "...", "nim": 12345, "category": "ikh", "jumlah_hafalan": 5, "class_id": 1, "guru_id": 2}`


* **`PUT /api/santri/{id}`** — Edit Data Santri.
* **`DELETE /api/santri/{id}`** — Hapus Santri.

### B. Master Kelas & Target (`/api/classes`)

* **`GET /api/classes`** — Menampilkan daftar kelas & target hafalan.
* **`POST /api/classes`** — Buat Kelas Baru (Atur `class`, `category`, `target_hafalan`).
* **`PUT /api/classes/{id}`** — Update Target / Nama Kelas.

### C. Guru & Halaqah (`/api/guru-halaqah` & `/api/halaqah`)

* **`GET /api/guru-halaqah`** — Menampilkan list guru halaqah & kategorinya.
* **`GET /api/halaqah`** — Mapping kelompok halaqah (menampilkan Guru beserta daftar Santrinya).

### D. User Management (Khusus Super Admin)

* **`GET /api/users`** — Menampilkan daftar pengelola (`super_admin`, `admin`, `guru_halaqah`).
* **`POST /api/users`** — Menambah User / Guru baru.
* **`PUT /api/users/{id}`** — Update Role / Username / Category.
* **`DELETE /api/users/{id}`** — Hapus User.

---

### Tips Implementasi di Controller Laravel:

1. **Aturan Akses (Middleware/Policy):**
* Gunakan `can:super_admin` untuk rute `/api/users`.
* Gunakan `can:admin` untuk manajerial data siswa, kelas, & target.


2. **Relasi User Penguji:**
Pada endpoint Ujian Tahfidz, isi `penguji_id` secara otomatis di backend mengambil ID user yang sedang terautentikasi (`auth()->id()`).