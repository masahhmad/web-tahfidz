# Overall Task — Revisi Web Tahfidz

Dokumen ini mencatat konteks tiga revisi yang diminta di atas fondasi yang sudah dibangun (lihat `README.md` untuk cara menjalankan, `api-spec.md` untuk kontrak API yang sudah ada). Tujuannya: satu acuan bersama sebelum pekerjaan dipecah ke [`BACKEND_TASK.md`](./BACKEND_TASK.md) dan [`FRONTEND_TASK.md`](./FRONTEND_TASK.md).

Status kode saat dokumen ini ditulis: backend sudah punya route + controller untuk presensi guru/siswa, setoran, kenaikan juz, kelas, santri, user (lihat `backend/routes/api.php`, `backend/app/Http/Controllers/Api/*`); frontend sudah pindah dari mock statis ke `SessionProvider` (`frontend/app/dashboard/_components/session.tsx`) dan sudah ada `AddPresensiGuruModal` yang mengambil koordinasi lokasi browser. Backend **belum terhubung ke frontend** — frontend masih memakai data mock di tiap komponen.

---

## Revisi 1 — Tanggal & jam presensi otomatis sesuai sesi tahfidz, konsisten Guru ↔ Siswa

### Permintaan
> Presensi tanggal dan jam otomatis dibuat sesuai sesi tahfidz, sama antara presensi siswa dan presensi guru.

### Kondisi sekarang
- `presensi-guru/_components/FilterBar.tsx` dan `presensi-siswa/_components/FilterBar.tsx` masing-masing punya `<input type="datetime-local">` yang **tidak terhubung ke apa pun** (tidak dipakai memfilter tabel, tidak dikirim ke modal) — murni dekoratif dari desain awal.
- Sesi (`pagi`/`siang`/`sore`) **dipilih manual** lewat `<select>` di `FilterBar` guru, lalu labelnya diteruskan ke `AddPresensiGuruModal` sebagai judul popup. Di sisi siswa, `AddPresensiModal` (dipakai bersama, di `_components/AddPresensiModal.tsx`) malah punya `<select defaultValue="pagi">` sendiri di dalam form — dua mekanisme sesi yang berbeda untuk dua halaman yang seharusnya identik.
- Waktu presensi **sudah** diniatkan diambil dari `created_at` server (lihat komentar di `PresensiGuruManager.tsx`), jadi sebagian dari revisi ini (jam otomatis) sudah sejalan — yang belum konsisten adalah **sesi**-nya, yang masih pilihan manual, bukan turunan dari jadwal.
- `api-spec.md` §5.6–5.7 saat ini memvalidasi `tanggal`/`sesi` sebagai **input dari klien** (`required|date`, `required|in:pagi,siang,sore`).

### Target revisi
- Ada **jadwal sesi tahfidz** yang tetap (jam mulai–selesai per sesi: pagi/siang/sore) yang jadi rujukan tunggal di backend.
- Saat presensi dibuat (guru maupun siswa), **tanggal, jam, dan sesi ditentukan otomatis dari waktu server** saat request diterima — bukan dari input pengguna. Klien tidak lagi mengirim `tanggal`/`sesi`/`jam`.
- Presensi guru dan presensi siswa memakai **logika penentuan sesi yang sama** (satu sumber kebenaran, bukan dua implementasi terpisah seperti sekarang).
- UI: elemen filter tanggal/sesi yang sekarang dekoratif diubah jadi filter **history** yang sungguhan (lihat Revisi 2 — pola yang sama dipakai untuk Setoran), bukan input untuk presensi baru. Form pembuatan presensi tidak lagi punya kontrol sesi manual; sesi ditampilkan **read-only** (hasil deteksi otomatis) sebelum disimpan.

### ⚠ Keputusan yang perlu diambil sebelum implementasi
1. **Jam pasti tiap sesi** (mis. Pagi 04:30–06:30, Siang 12:00–13:30, Sore 15:30–17:30 — perlu angka sebenarnya dari pihak pesantren).
2. **Di luar jam sesi manapun**: request presensi ditolak (422 "bukan jam presensi"), atau jatuh ke sesi terdekat? Usulan: tolak, supaya jadwal punya arti.
3. Zona waktu server harus **WIB (Asia/Jakarta)** tetap, tidak ikut zona klien — supaya "otomatis" tidak berubah-ubah tergantung device.
4. Presensi guru: apakah guru bisa presensi lebih dari satu sesi per hari (pagi+siang+sore), atau hanya sekali per hari? Skema unik sekarang `(user_id, tanggal, sesi)` mengasumsikan bisa 3x sehari, satu per sesi — dipertahankan kecuali dikoreksi.

Detail teknis: [`BACKEND_TASK.md` §1](./BACKEND_TASK.md#1-sesi-tahfidz-otomatis), [`FRONTEND_TASK.md` §1](./FRONTEND_TASK.md#1-sesi-tahfidz-otomatis).

---

## Revisi 2 — Filter harian untuk riwayat pencatatan hafalan (setoran) siswa

### Permintaan
> Filter harian untuk history pencatatan hafalan siswa.

### Kondisi sekarang
- `/dashboard/setoran` (`setoran/page.tsx`) merender `AddHafalanForm` (form input setoran) + `SetoranTable` (`setoran/_components/SetoranTable.tsx`) langsung, **tanpa `FilterBar`** — beda dari Data Siswa/Halaqah/Presensi yang semuanya punya `FilterBar` sendiri.
- `SetoranTable` menampilkan **seluruh riwayat** tanpa filter tanggal, hanya paginasi (`PaginationFooter`, mock `currentPage=1, totalPages=6`).
- `api-spec.md` §5.7 (`GET /setoran`) belum mendefinisikan query filter tanggal — hanya `POST /setoran` yang menerima `tanggal`.

### Target revisi
- Tambah `FilterBar` di halaman Setoran dengan **filter tanggal (harian)** — minimal satu `<input type="date">` yang memfilter tabel ke setoran pada tanggal itu, default **hari ini**.
- Endpoint `GET /setoran` menerima query `tanggal` (single day) dan mengembalikan hanya baris pada tanggal tsb.
- Pola ini konsisten dengan Presensi Guru/Siswa yang juga akan punya filter tanggal sungguhan setelah Revisi 1 — pertimbangkan komponen filter tanggal yang dipakai bersama (`DateFilter`) alih-alih tiga implementasi terpisah.

Detail teknis: [`BACKEND_TASK.md` §2](./BACKEND_TASK.md#2-filter-harian-setoran), [`FRONTEND_TASK.md` §2](./FRONTEND_TASK.md#2-filter-harian-setoran).

---

## Revisi 3 — Halaqah dibagi per kelas, satu pengampu per kelas

### Permintaan
> Halaqah dibagi perkelas dengan 1 pengampu.

### Kondisi sekarang — model paling berubah dari tiga revisi ini
- DB: `santris.guru_id` adalah FK **per siswa** ke `users` (satu siswa → satu guru), **tidak ada** kolom pengampu di `kelas`. Tidak ada tabel `halaqahs`.
- `halaqah/_components/AssignHalaqahBar.tsx` menawarkan **dua dropdown independen**: `PENGAMPU_OPTIONS` (daftar guru) dan `HALAQAH_OPTIONS` (`"Kelas 7A-1"`, `"Kelas 7A-2"`, …) — nama-nama itu menyiratkan **halaqah adalah sub-kelompok di dalam kelas**, bukan kelas itu sendiri, dan **siswa dipilih satu-satu lewat checkbox** (`HalaqahManager.tsx` → `selectedIds: Set<number>`) lalu ditugaskan ke kombinasi pengampu+halaqah manapun — artinya secara desain sekarang, satu kelas *bisa* punya lebih dari satu pengampu (tiap sub-kelompok punya pengampunya sendiri), dan penugasan per-siswa, bukan per-kelas.
- `api-spec.md` §5.4 sudah menandai ini sebagai **⚠ Keputusan** terbuka: mengusulkan tabel `halaqahs` + `santris.halaqah_id` terpisah dari `guru_id`, persis karena model lama ambigu.
- Presensi Siswa (`presensi-siswa/_components/FilterBar.tsx`) juga memfilter dengan konsep "Halaqah Al-Fatih" / "Halaqah An-Nur" — nama halaqah yang **tidak berkorelasi dengan nama kelas** (`7A`/`8B`/…), memperkuat bahwa model lama memperlakukan halaqah sebagai entitas bebas.

### Target revisi
Model disederhanakan jadi **1 kelas = 1 halaqah = 1 pengampu**:
- **Halaqah bukan lagi entitas terpisah** dari kelas — tidak perlu tabel `halaqahs` maupun `santris.halaqah_id` seperti yang diusulkan (belum dibangun) di `api-spec.md` §5.4. Ini **menutup** keputusan terbuka di sana, bukan menambah entitas baru.
- Pengampu dipindah dari **per-siswa** (`santris.guru_id`) ke **per-kelas** (`kelas.guru_id`, nullable — kelas boleh belum punya pengampu). Semua siswa dalam satu kelas otomatis mewarisi pengampu kelasnya.
- "Atur Halaqah" berubah dari *pilih beberapa siswa → pilih pengampu+halaqah bebas* menjadi ***pilih satu kelas → tetapkan satu pengampu***. Checkbox multi-siswa di `HalaqahTable` tidak relevan lagi di model baru.
- Semua tempat yang sebelumnya menampilkan "Halaqah Al-Fatih" dsb. (Presensi Siswa, Data Siswa, dsb.) tampil sebagai **nama kelas** (`Kelas 7A`), karena halaqah = kelas.
- Konsekuensi berantai: setiap fitur yang bergantung pada "siswa milik guru X" (row-level policy di §3.5 `api-spec.md`: setoran, presensi siswa, kenaikan juz, Data Siswa untuk `admin`) sekarang dihitung lewat `santri.kelas.guru_id`, bukan `santri.guru_id` langsung.

### ⚠ Keputusan yang perlu diambil sebelum implementasi
1. **Migrasi data**: kelas yang sekarang punya siswa dengan `guru_id` berbeda-beda (kalau ada) harus diputuskan pengampu tunggalnya secara manual sebelum kolom lama dihapus.
2. **Kelas tanpa pengampu**: apakah presensi/setoran/ujian tetap bisa dicatat untuk siswa di kelas yang belum punya pengampu (dicatat oleh `admin`), atau diblokir sampai pengampu ditetapkan?
3. Apakah satu guru (`guru_halaqah`) bisa menjadi pengampu **lebih dari satu kelas** sekaligus? (Model ini tidak melarangnya — `kelas.guru_id` banyak-ke-satu — tapi perlu dikonfirmasi sesuai kondisi pesantren.)
4. Field `kelas` yang sudah dipakai UI (`CLASS_OPTIONS`, `PENGAMPU_OPTIONS` mock di banyak file — lihat `FRONTEND_TASK.md`) sekarang bisa disatukan jadi satu sumber data: daftar kelas **beserta** pengampunya.

Detail teknis: [`BACKEND_TASK.md` §3](./BACKEND_TASK.md#3-halaqah--1-kelas--1-pengampu), [`FRONTEND_TASK.md` §3](./FRONTEND_TASK.md#3-halaqah--1-kelas--1-pengampu).

---

## Dampak lintas dokumen

- **`api-spec.md`** perlu direvisi setelah tiga keputusan di atas diambil: §5.4 (Halaqah, model baru menggantikan usulan `halaqahs`), §5.6–5.7 (Presensi Guru/Siswa, sesi otomatis bukan input), §5.7 (Setoran, filter `tanggal`), §5.3/§6 (kolom `santris.guru_id` dihapus, `kelas.guru_id` ditambah), §3.5 (row-level policy lewat `kelas.guru_id`). Bukan bagian dari revisi ini untuk langsung diedit — dicatat di sini supaya tidak terlewat saat backend mulai kerja (lihat `BACKEND_TASK.md`).
- Ketiga revisi ini **tidak saling bergantung** secara implementasi (bisa dikerjakan paralel oleh backend/frontend berbeda), tapi Revisi 1 dan Revisi 3 sama-sama mengubah bentuk data presensi siswa (siapa pengampu sesi itu berubah dari per-siswa ke per-kelas), jadi urutan yang disarankan: **Revisi 3 (model kelas/pengampu) → Revisi 1 (sesi otomatis) → Revisi 2 (filter harian, paling independen)**.
