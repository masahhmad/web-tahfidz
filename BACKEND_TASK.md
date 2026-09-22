# Backend Task — Revisi Web Tahfidz

Turunan teknis untuk backend dari [`OVERALL_TASK.md`](./OVERALL_TASK.md). Setiap bagian mengacu ke file yang sudah ada di `backend/` dan ke bagian `api-spec.md` yang perlu diperbarui setelahnya (spec **belum** diedit di commit ini — edit spec sebagai bagian dari mengerjakan tugas ini, sekaligus, supaya spec tidak pernah menyimpang dari kode).

Urutan disarankan: §3 → §1 → §2 (alasan di `OVERALL_TASK.md`).

---

## 1. Sesi tahfidz otomatis

**Tujuan:** tanggal, jam, dan sesi presensi dihitung server dari jadwal tetap — tidak pernah dikirim klien.

### 1.1 Jadwal sesi
- Buat sumber jadwal tunggal, mis. `config/tahfidz.php`:
  ```php
  return [
      'sesi' => [
          'pagi'  => ['mulai' => '04:30', 'selesai' => '06:30'],
          'siang' => ['mulai' => '12:00', 'selesai' => '13:30'],
          'sore'  => ['mulai' => '15:30', 'selesai' => '17:30'],
      ],
  ];
  ```
  Nilai jam **placeholder** — perlu dikonfirmasi (`OVERALL_TASK.md` §1 keputusan #1) sebelum di-hardcode final; taruh di config (bukan tersebar di controller) supaya sekali ubah.
- Pastikan `config/app.php` → `'timezone' => 'Asia/Jakarta'` (cek nilai sekarang; jangan biarkan default `UTC`) — ini yang membuat "otomatis" konsisten lepas dari zona waktu klien.

### 1.2 Helper terpusat — dipakai presensi guru **dan** siswa
Buat satu tempat (mis. `app/Support/SesiTahfidz.php` atau service class) dengan:
- `SesiTahfidz::current(): ?string` — mengembalikan `'pagi'|'siang'|'sore'` berdasarkan `now()`, atau `null` bila di luar semua jendela waktu.
- `SesiTahfidz::assertActive(): string` — seperti di atas tapi `throw ValidationException` (→ 422 `"Saat ini bukan jam sesi tahfidz."`) bila `null`. Dipanggil di awal `store()` kedua controller presensi.

Kedua controller (`PresensiGuruController::store`, `PresensiSiswaController::store`/`bulk`) memanggil helper yang **sama** — jangan duplikasi logika jam di masing-masing controller (ini akar masalah "tidak konsisten" yang disebut di revisi).

### 1.3 `PresensiGuruController`
- Hapus `tanggal`, `sesi`, `waktu` dari `$request->validate([...])` — field-field ini **tidak lagi** datang dari body.
- Set otomatis: `tanggal = now()->toDateString()`, `sesi = SesiTahfidz::assertActive()`, `waktu`/`created_at` = timestamp Eloquent bawaan (sudah begitu).
- Pertahankan validasi `status`, `keterangan` (`required_unless:status,hadir`), `lokasi`.
- Pertahankan aturan unik `(user_id, tanggal, sesi)` → `409` bila sudah presensi sesi ini hari ini (menjawab keputusan #4 `OVERALL_TASK.md`: 1x per sesi, maks 3x/hari — ubah di sini kalau jawabannya beda).

### 1.4 `PresensiSiswaController`
- Endpoint `bulk` (`POST /presensi-siswa/bulk`, §5.7 `api-spec.md`) — hapus `tanggal`/`sesi` dari body request; tentukan otomatis sama seperti §1.3.
- `halaqah_id` di payload contoh (`api-spec.md`) berubah jadi `kelas_id` mengikuti Revisi 3 (lihat §3.5 di bawah) — satu sesi presensi dibuat untuk satu kelas sekaligus, bukan "halaqah" bebas.

### 1.5 `GET /presensi-guru` & `GET /presensi-siswa` (read/filter)
- Endpoint baca **tetap** menerima `tanggal` sebagai **query filter** (bukan untuk membuat data — untuk menyaring riwayat, lihat §2 di bawah untuk pola yang sama dipakai di Setoran). Ini beda dari `POST` yang sekarang tidak menerima `tanggal` sama sekali.

### 1.6 Test
`backend/tests/Feature/Api/TransactionsTest.php` sudah ada — sesuaikan test presensi guru/siswa yang sekarang (kemungkinan) mengirim `tanggal`/`sesi` di body `POST`; ganti jadi memanipulasi waktu lewat `Carbon::setTestNow()` untuk menguji tiap sesi dan kasus "di luar jam sesi → 422".

---

## 2. Filter harian setoran

**Tujuan:** `GET /setoran` bisa disaring ke satu tanggal.

### 2.1 `SetoranController@index`
- Terima query `tanggal` (opsional; `nullable|date`). Bila diberikan → `where('tanggal', $request->query('tanggal'))`. Bila tidak diberikan → default **hari ini** (`whereDate('tanggal', today())`) supaya perilaku awal tabel = riwayat hari ini, sesuai kata "harian" di permintaan — **atau** default tanpa filter (semua riwayat) dan biarkan frontend yang memilih default "hari ini" di UI. **Putuskan salah satu** — usulan: default **tanpa filter** di backend (lebih fleksibel), frontend yang mengatur default tanggal awal ke hari ini (§2 `FRONTEND_TASK.md`), supaya `GET /setoran` tanpa query tetap berguna untuk kasus lain (mis. rekap siswa di §5.3 `api-spec.md`).
- Tambahkan paginasi sungguhan (`->paginate()`) — controller sekarang (`Setoran::all()`) tidak berpaginasi; frontend sudah punya `PaginationFooter` yang menunggu `meta`.
- Row-level: `guru_halaqah` hanya melihat setoran siswa di kelas yang ia pengampu (`api-spec.md` §3.5) — pastikan filter ini **digabung dengan**, bukan menggantikan, filter `tanggal` dari query.

### 2.2 `api-spec.md` §5.7 (Setoran) — update setelah §2.1 selesai
Tambahkan baris query filter di tabel endpoint:
```
GET /setoran?tanggal=2026-08-10
```
dan contoh respons dengan `meta` paginasi (pola yang sama dengan §1 `api-spec.md`).

---

## 3. Halaqah = 1 kelas + 1 pengampu

**Tujuan:** hapus model "halaqah bebas + penugasan per-siswa", ganti dengan pengampu di level kelas.

### 3.1 Migration
- `database/migrations/…_create_kelas_table.php` sudah lama dijalankan → buat migration baru (jangan edit yang lama):
  ```php
  Schema::table('kelas', function (Blueprint $table) {
      $table->foreignId('guru_id')->nullable()->after('kategori')
            ->constrained('users')->nullOnDelete();
  });
  ```
- Migration terpisah untuk menghapus `santris.guru_id` (**setelah** data lama dipetakan ke `kelas.guru_id` — lihat §3.2):
  ```php
  Schema::table('santris', function (Blueprint $table) {
      $table->dropForeign(['guru_id']);
      $table->dropColumn('guru_id');
  });
  ```
- **Tidak perlu** tabel `halaqahs` maupun `santris.halaqah_id` — usulan itu di `api-spec.md` §5.4 tidak jadi dibangun; catat di spec sebagai keputusan yang sudah ditutup, bukan dihapus diam-diam (supaya histori keputusan tetap terbaca).

### 3.2 Migrasi data (sebelum drop kolom)
- Tulis migration/seeder satu-kali yang, untuk tiap `kelas`, mengisi `kelas.guru_id` dari `guru_id` **yang paling sering muncul** di antara siswa kelas itu (atau `null` bila kelas belum ada siswa/guru). Ini keputusan otomatis "terbaik tebakan" — beri tahu pihak yang berwenang (lihat keputusan #1 `OVERALL_TASK.md`) untuk meninjau/mengoreksi manual sebelum kolom lama dihapus, terutama kelas yang siswanya dulu tersebar ke beberapa guru berbeda (di seed/data sekarang berarti "halaqah" berbeda dalam satu kelas — semuanya digabung jadi satu pengampu).

### 3.3 Model
- `app/Models/Kelas.php`: tambah
  ```php
  public function guru()
  {
      return $this->belongsTo(User::class, 'guru_id');
  }
  ```
- `app/Models/Santri.php`: hapus relasi `guruTahfidz()` (yang saat ini salah — lihat `api-spec.md` §7 temuan #5, `belongsTo(User::class)` tanpa FK eksplisit) dan tambah accessor turunan:
  ```php
  public function guru()
  {
      // Pengampu siswa == pengampu kelasnya, bukan lagi kolom sendiri.
      return $this->kelas?->guru;
  }
  ```
  (atau relasi `hasOneThrough` bila butuh dipakai dalam query Eloquent, bukan hanya akses objek.)
- `app/Models/User.php`: ganti `santri()` (`hasMany(Santri::class, 'guru_id')`) menjadi turunan lewat kelas:
  ```php
  public function kelasDiampu()
  {
      return $this->hasMany(Kelas::class, 'guru_id');
  }
  public function santriDiampu()
  {
      return Santri::whereIn('kelas_id', $this->kelasDiampu()->pluck('id'));
      // atau hasManyThrough(Santri::class, Kelas::class, 'guru_id', 'kelas_id')
  }
  ```

### 3.4 `KelasController`
- `store`/`update`: terima `guru_id` (`nullable|exists:users,id`) — tambahkan validasi bahwa user tsb ber-`role = guru_halaqah` (bukan admin/super_admin) dan `is_active`.
- `index`: sertakan `guru` (eager load `with('guru')`) supaya tabel Target (§5.5 `api-spec.md`) dan tabel Halaqah baru (§3.6 di bawah) bisa dipakai dari endpoint yang sama.

### 3.5 Endpoint "Atur Halaqah" baru
Ganti rencana `PUT /halaqah/assign` (bentuk lama di `api-spec.md` §5.4, `{santri_ids, guru_id, halaqah_id}`) dengan penugasan **per kelas**:
```
PUT /kelas/{id}/guru
{ "guru_id": 3 }        // nullable|exists:users,id (null = lepas pengampu)
```
Middleware: `role:admin` (sama seperti tulis Kelas lain — `super_admin` tetap read-only sesuai `api-spec.md` §2). Efeknya otomatis berlaku ke semua siswa di kelas itu — **tidak ada** lagi endpoint yang menerima daftar `santri_ids`.

Endpoint baca (`GET /halaqah/siswa` di spec lama) bisa **dihapus**; halaman Halaqah di frontend sekarang cukup memanggil `GET /santri` yang sudah menyertakan `kelas.guru`.

### 3.6 Ikutan ke controller lain (siapa boleh apa)
Semua tempat yang sekarang membaca `santri.guru_id` untuk row-level policy (`api-spec.md` §3.5) pindah ke `santri.kelas.guru_id`:
- `SantriController@index` (Data Siswa, filter "siswa milik saya" untuk `admin`— tapi ingat: `admin` melihat **semua**, hanya `guru_halaqah` yang dibatasi, dan `guru_halaqah` sendiri tidak punya akses ke menu ini sama sekali per `api-spec.md` §2).
- `SetoranController`, `PresensiSiswaController`, `KenaikanJuzController`: ganti setiap `where('guru_id', auth()->id())` (di kode saat ini kemungkinan lewat relasi `santri.guru_id`) menjadi `whereHas('kelas', fn ($q) => $q->where('guru_id', auth()->id()))`.
- Cek `SantriController@store`/`update`: hapus field `guru_id` dari validasi (`nullable|integer` sekarang) — pengampu tidak lagi di-set per siswa.

### 3.7 `UserResource` / respons siswa
- Response siswa yang sebelumnya punya `pengampu: {id, nama}` langsung dari `santri.guru_id` sekarang diisi dari `santri.kelas.guru` (nullable bila kelas belum punya pengampu — lihat keputusan #2 `OVERALL_TASK.md`).
- Response `kelas` (dipakai Target §5.5 dan sekarang juga Halaqah) tambahkan `guru: {id, nama} | null`.

### 3.8 `api-spec.md` — bagian yang perlu ditulis ulang setelah §3.1–3.7 selesai
- §5.4 seluruhnya: ganti model `halaqahs`/`assign per-siswa` dengan `kelas.guru_id` + `PUT /kelas/{id}/guru`, hapus ⚠ Keputusan model data (sudah ditutup).
- §5.3 contoh objek siswa: `pengampu` sekarang turunan, bukan field siswa langsung — perjelas di dokumentasi.
- §6 (Perubahan database): hapus baris `santris + halaqah_id`, `halaqahs (baru)`; tambah baris `kelas + guru_id`; ubah baris `santris.nim → nisn` (tidak berubah oleh revisi ini, tetap perlu, hanya disebut ulang di konteks yang sama).
- §3.5 (row-level): tulis ulang aturan `guru_halaqah` dari "siswa dengan `santris.guru_id = user.id`" menjadi "siswa di kelas dengan `kelas.guru_id = user.id`".

### 3.9 Test
`backend/tests/Feature/Api/MasterDataTest.php` — sesuaikan test Halaqah/Santri yang mengasumsikan `guru_id` di level siswa; tambah test baru: `PUT /kelas/{id}/guru`, siswa mewarisi pengampu kelasnya, `guru_halaqah` hanya melihat siswa di kelas yang ia ampu.
