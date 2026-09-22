# TASK FRONTEND — Revisi Tampilan & Integrasi Auth

Daftar pekerjaan frontend (`frontend/`, Next.js 16 App Router) untuk menyelesaikan revisi tampilan sesuai alur yang sudah ditetapkan. **Backend untuk semua poin di bawah sudah selesai dan teruji** (50 feature test + uji curl). Kontrak lengkap ada di [`api-spec.md`](./api-spec.md) (khusus Bantuan/telp: §5.11).

> Catatan: `*.md` di-ignore oleh `.gitignore` root (kecuali `README.md`), jadi file ini dan `api-spec.md` tidak muncul di `git status`. Pakai `git add -f TASK_FRONTEND.md` bila ingin di-commit.

Legenda prioritas: **P0** = wajib untuk alur revisi, **P1** = melengkapi alur, **P2** = lanjutan (di luar revisi ini).

---

## 0. Alur yang harus jadi (ringkasan)

1. `super_admin` menambah pengguna lewat **form Tambah Pengguna yang sama seperti sekarang** (Nama, Email, Kata Sandi, Role, Kategori) — **tidak ada input nomor telepon di form itu**.
2. Pengguna login → frontend menyimpan token → memanggil `GET /me`.
3. Bila `perlu_lengkapi_telp === true` (**role `admin` atau `super_admin` yang nomor teleponnya masih kosong**), seluruh area *main section* di semua halaman dashboard diganti **halaman putih** berisi:
   > **Nomer Telp belum diisi, edit pengguna dan isi terlebih dahulu nomer telp**
   >
   > `[ Ke menu Pengaturan ]`
   
   Satu-satunya halaman yang tetap normal adalah **Pengaturan**.
4. Di Pengaturan → **Edit Pengguna** mengisi nomor telepon (format `08xx`) → simpan (`PUT /me`) → gate hilang **tanpa reload halaman**.
5. Nomor telepon dipakai menu **Bantuan** (sidebar) dan tautan **"Hubungi Super Admin"** (halaman login) yang membuka WhatsApp (`wa.me`).

`guru_halaqah` tidak pernah dicegat (`perlu_lengkapi_telp` selalu `false`).

---

## 1. Kontrak API yang dipakai

Base URL: `NEXT_PUBLIC_API_URL` (di `docker-compose.yml` = `http://localhost:8000`, **tanpa `/api`** → tambahkan `/api` di client). Header: `Accept: application/json`, `Content-Type: application/json`, `Authorization: Bearer <access_token>`.

| Kebutuhan | Endpoint | Catatan |
|---|---|---|
| Login | `POST /login` `{email, password}` | 200 → `{access_token, token_type, expires_in, user}`. **401** email/password salah, **403** akun nonaktif, **422** validasi, **429** >5 percobaan/menit |
| Profil + flag gate | `GET /me` | `{data: {id, nama, email, telp, role, role_label, kategori, is_active, perlu_lengkapi_telp, jumlah_siswa}}` |
| Simpan profil | `PUT /me` `{nama, email, telp?}` | `telp` opsional (tidak dikirim = tidak berubah). Admin/super_admin **tidak boleh** mengirim `telp: null`/kosong (422). `role` **dilarang** ada di body (422) |
| Ganti password sendiri | `PUT /me/password` `{current_password, password, password_confirmation}` | 200 → `{message, access_token, ...}` → **token lama langsung mati, simpan `access_token` baru** |
| Logout | `POST /logout` | |
| Perpanjang token | `POST /refresh` | Hanya berfungsi selama token belum kedaluwarsa (TTL 60 menit) |
| Kontak bantuan (publik) | `GET /bantuan/publik` | **Tanpa token.** `{data: {super_admin: [{id,nama,telp,wa_url}], developer: {nama,telp,wa_url}}}` |
| Kontak bantuan (login) | `GET /bantuan` | `{data: {admin: [...], super_admin: [...], developer: {...}}}` |
| Pengguna | `GET/POST /user`, `PUT /user/{id}`, `PUT /user/{id}/password`, `PATCH /user/{id}/status` | Hanya `super_admin`. **Tidak ada DELETE** |

Aturan kontrak penting:
- `telp` **selalu string** berformat lokal `08xxxxxxxxxx` (10–14 digit). Jangan pernah `Number()`/`parseInt` — angka 0 di depan hilang.
- `wa_url` sudah berupa `https://wa.me/628…` siap dipakai sebagai `href`. Bila frontend mau membangun sendiri: buang `0` di depan, awali `62`.
- Hanya akun **aktif** yang **sudah punya `telp`** muncul di daftar bantuan; daftarnya bisa **kosong** → siapkan tampilan fallback.
- Bentuk error: `{ "message": "...", "errors": { "field": ["pesan"] } }` (422) atau `{ "message": "..." }` (401/403/404/409/429).
- Backend **tidak** memblokir endpoint lain saat `perlu_lengkapi_telp = true`. Gate murni tanggung jawab frontend (UX, bukan keamanan).
- Flag `perlu_lengkapi_telp` juga ada di `user` pada respons login dan di tiap baris `GET /user`.

---

## 2. Pondasi (P0) — semua poin lain bergantung pada ini

Saat ini **belum ada satu pun panggilan API, session, atau proteksi route** (`LoginForm` hanya `router.push("/dashboard")`).

- [ ] **API client tunggal** (mis. `frontend/lib/api.ts`): prefix `NEXT_PUBLIC_API_URL + "/api"`, menyisipkan `Authorization`, mem-parse error `{message, errors}` jadi objek error yang bisa dipakai form.
  - `401` → hapus session, redirect `/login`. Khusus pesan `Password telah diganti, silakan login kembali.` tampilkan di halaman login.
  - `403` dengan pesan `Akun Anda dinonaktifkan.` → logout paksa + pesan di login. `403` lain → tampilkan "tidak punya akses".
  - `429` → tampilkan "terlalu banyak percobaan, coba lagi sebentar".
- [ ] **Penyimpanan token.** Rekomendasi (sesuai `api-spec.md` §8): cookie **httpOnly** yang di-set lewat Route Handler Next (`/login`, `/logout` memanggil backend), supaya `proxy.ts` bisa membaca cookie. Alternatif lebih sederhana: `localStorage` + guard client-side (kurang aman, tetap terima). Pilih satu dan konsisten.
  - Next 16 memakai **`proxy.ts`** (pengganti `middleware.ts`). Baca dulu `frontend/node_modules/next/dist/docs/01-app/03-api-reference/03-file-conventions/proxy.md`.
  - Gunakan `expires_in` (detik) untuk umur cookie; token 60 menit.
- [ ] **Guard route**: tanpa token → `/dashboard/*` redirect ke `/login`; sudah login → `/login` redirect ke `/dashboard`. (`proxy.ts` hanya untuk cek keberadaan token; **pengecekan gate telp butuh data `/me`**, jadi dilakukan di layout/shell, bukan di proxy.)
- [ ] **Session context** (`SessionProvider` + hook `useSession()`), dipasang di `app/dashboard/layout.tsx`/`DashboardShell`: memanggil `GET /me` saat mount, menyimpan `user`, menyediakan `refreshUser()` (dipakai setelah simpan Pengaturan) dan `logout()`. Tampilkan skeleton/loading selama `/me` belum selesai — **jangan render konten dashboard maupun gate** sebelum `/me` selesai (mencegah kedipan).
- [ ] Ganti data `HELP_OPTIONS`/mock yang perlu; **jangan** meng-hardcode role/nama user (mis. `"Muhammad Zaid Burhanuddin"` di `ProfileCard`).

---

## 3. Gate "Nomer Telp belum diisi" (P0) — inti revisi

Lokasi implementasi: `frontend/app/dashboard/_components/DashboardShell.tsx` (satu-satunya tempat `children` dirender di `<main>`), sehingga otomatis berlaku untuk **semua** halaman `/dashboard/*` tanpa mengubah tiap `page.tsx`.

- [ ] Buat komponen `PhoneRequiredGate` (mis. `dashboard/_components/PhoneRequiredGate.tsx`).
- [ ] Kondisi tampil: `user.perlu_lengkapi_telp === true` **dan** `pathname !== "/dashboard/pengaturan"` (dan sub-path-nya, bila ada).
- [ ] Yang diganti hanya isi `<main>`: **Sidebar dan Header tetap tampil** (menu Bantuan & Keluar harus tetap bisa dipakai saat terkunci; menu lain boleh tetap terlihat tetapi klik-nya akan berujung ke gate).
- [ ] Tampilan: area putih penuh (ikuti token warna tema, mis. `bg-card`/`bg-page` — pastikan tetap benar di dark mode via `ThemeToggle`), teks di tengah:
  - Judul/teks: **"Nomer Telp belum diisi, edit pengguna dan isi terlebih dahulu nomer telp"** (teks persis dari brief).
  - Di bawahnya tombol **"Ke menu Pengaturan"** → `/dashboard/pengaturan` (gaya tombol utama `bg-brand text-on-brand` seperti tombol lain).
- [ ] Berlaku untuk **`admin` dan `super_admin`** (flag sudah dihitung backend — cukup baca `perlu_lengkapi_telp`, jangan hitung sendiri dari role).
- [ ] Setelah nomor tersimpan di Pengaturan, panggil `refreshUser()` → flag jadi `false` → gate hilang seketika, tanpa reload.
- [ ] Sidebar: item **Pengaturan** tetap aktif/tersorot benar saat di halaman itu.
- [ ] `activeId` di `DashboardShell` diturunkan dari path terakhir — tetap benar; tidak perlu diubah.

---

## 4. Halaman Pengaturan (P0)

File: `dashboard/pengaturan/_components/ProfileCard.tsx` (+ `EditPenggunaModal`, `ResetPasswordModal` yang dipakai bersama halaman Pengguna).

- [ ] **Ambil data dari `/me`** (bukan state mock): `nama`, `email`, `role_label`, `telp`, `jumlah_siswa` ("Mengampu N siswa" — tampilkan untuk `guru_halaqah`; sembunyikan/`0` untuk role lain sesuai selera).
- [ ] Tampilkan **nomor telepon** di kartu profil (baris baru). Bila kosong pada admin/super_admin tampilkan penanda "Belum diisi".
- [ ] **Edit Pengguna** (`EditPenggunaModal`, `roleEditable={false}` sudah benar):
  - Tambah props/field **`telp`** ke tipe `PenggunaEdit` (`{ name, email, role, telp? }`).
  - **Field Nomor Telepon hanya muncul di mode Pengaturan (edit profil sendiri).** Modal yang sama juga dipakai halaman Pengguna, di mana **tidak ada input telp** (keputusan: form user tidak berubah) → buat opsional/prop (mis. `showPhone`).
  - `<input type="tel" inputMode="tel">`, placeholder `08xxxxxxxxxx`. **Wajib diisi** bila role `admin`/`super_admin` (`required`). **Catatan:** hanya `admin` dan `super_admin` yang diminta nomor telepon; role lain (`guru_halaqah`) sementara `null` — field tidak ditampilkan. Bila kelak role seorang guru diubah jadi `admin`, barulah nomor wajib diisi (gate berlaku).
  - Submit → `PUT /me` dengan `{ nama, email, telp }` (**jangan kirim `role`** → 422). Kirim `telp` hanya bila field ada.
  - Tampilkan error 422 per field (`errors.telp[0]`, `errors.email[0]`, ...).
- [ ] **Normalisasi input telp (logic frontend)** — backend hanya menerima `^08[0-9]{8,12}$` dan **menolak** `62…`, `+62…`, spasi, tanda hubung. Sebelum kirim:
  1. buang semua karakter selain digit dan `+` (spasi, `-`, `(`, `)`),
  2. bila diawali `+62` atau `62` → ganti jadi `0`,
  3. validasi `^08\d{8,12}$`; bila gagal tampilkan "Nomor harus diawali 08 dan 10–14 digit".
  4. simpan/kirim sebagai **string**.
  Contoh: `0821-4298-6689` → `082142986689`; `+62 821 4298 6689` → `082142986689`.
- [ ] **Ganti Password** (`ResetPasswordModal` dengan `requireCurrent` — sudah ada field "Kata Sandi Saat Ini"):
  - Submit → `PUT /me/password` `{current_password, password, password_confirmation}` (kirim `confirm` sebagai `password_confirmation`).
  - **Wajib simpan `access_token` baru dari respons** (token lama sudah dicabut; bila tidak diganti, user akan ter-logout di request berikutnya).
  - 422: `current_password` salah ("Password saat ini salah."), password < 8 karakter, konfirmasi tidak cocok.
  - Tampilkan notifikasi sukses.
- [ ] Setelah **Edit Pengguna** sukses → `refreshUser()` (memperbarui gate, sidebar, header).

---

## 5. Menu Bantuan (P0)

File: `dashboard/_components/Sidebar.tsx` (`HelpMenu`) + `nav-data.ts` (`HELP_OPTIONS = [Admin, Developer]`).

- [ ] Saat menu Bantuan dibuka (atau saat shell dimuat) → `GET /bantuan`; cache di session.
- [ ] Ganti `<a href="#">` pada opsi jadi tautan WhatsApp: `href={wa_url}` `target="_blank"` `rel="noopener noreferrer"`.
  - **Developer** → `data.developer.wa_url` (nomor developer di-hardcode di backend `config/tahfidz.php`: `082142986689`).
  - **Admin** → `data.admin[]`:
    - 1 admin → langsung tautan.
    - >1 admin → tampilkan daftar (nama) sebagai sub-item atau pilih yang pertama — putuskan bersama.
    - **kosong** → fallback ke `data.super_admin[]`; bila itu juga kosong tampilkan teks non-klik "Kontak admin belum tersedia".
- [ ] Opsional: pesan awal WhatsApp lewat `?text=` (mis. `Halo, saya butuh bantuan Tahfidz System`) — tambahkan ke `wa_url` dengan `encodeURIComponent`.
- [ ] Bekerja juga di **MobileDrawer**; menutup drawer (`onNavigate`) setelah klik.
- [ ] Menu Bantuan **harus tetap berfungsi saat gate aktif**.

---

## 6. Halaman Login (P0)

File: `app/(auth)/login/_components/LoginForm.tsx`, `app/(auth)/login/page.tsx`.

- [ ] Hubungkan submit ke `POST /login` (hapus placeholder `router.push("/dashboard")`): state `loading`, disable tombol saat proses, tampilkan pesan error:
  - 401 → "Email atau password salah."
  - 403 → "Akun Anda dinonaktifkan."
  - 422 → error per field.
  - 429 → "Terlalu banyak percobaan, coba lagi sebentar."
  - Sukses → simpan token → redirect `/dashboard` (gate menyusul otomatis dari `/me`).
- [ ] **"Butuh Bantuan? Hubungi Super Admin"** (`page.tsx`, saat ini `href="#"`): ambil `GET /bantuan/publik` (tanpa token), arahkan ke `super_admin[0].wa_url` (buka tab baru). Bila `super_admin` kosong (belum ada yang mengisi nomor) fallback ke `developer.wa_url` atau sembunyikan tautan — putuskan.
- [ ] Checkbox **"Ingat saya"**: putuskan semantiknya (cookie persisten vs sesi browser); saat ini hanya dekoratif.
- [ ] **"Lupa Sandi?"** belum punya backend (tidak ada endpoint reset password mandiri). Saran: arahkan ke bantuan super admin yang sama, atau sembunyikan.
- [ ] Pesan sesi berakhir (`?reason=expired|disabled`) ditampilkan di atas form.

---

## 7. Tombol Keluar (P1)

- [ ] `FOOTER_ITEMS` item `keluar` (saat ini anchor kosong) → `POST /logout`, hapus cookie/token, kosongkan session context, redirect `/login`. Tetap jalankan logout lokal walau request gagal (mis. token sudah kedaluwarsa).

---

## 8. Halaman Pengguna (P1) — hanya `super_admin`

File: `dashboard/pengguna/_components/*`. **Form/tabel tidak menambah kolom/input telp** (sesuai keputusan), tetapi harus terhubung ke API:

- [ ] `PenggunaTable`: ganti `INITIAL_RECORDS` dengan `GET /user` (`page`, `per_page`, `search`, `role`, `status=aktif|nonaktif`) + `PaginationFooter` dari `meta` (`current_page`, `last_page`, `per_page`, `total`). Kolom "No" dihitung dari index + halaman. Role tampilkan `role_label`.
- [ ] `AddPenggunaModal` → `POST /user` `{nama, email, role, kategori, password}`. Mapping label→nilai: role `Admin→admin`, `Guru Pengampu→guru_halaqah`; kategori `Ikhwan→ikh`, `Akhwat→akh`. **Jangan kirim `telp`.** Tangani 422 (email sudah dipakai, password < 8).
- [ ] `EditPenggunaModal` → `PUT /user/{id}` `{nama, email, role}` (**tanpa `telp` dan tanpa `password`** — `password` dilarang, 422). Untuk baris `super_admin` role **tidak boleh diubah** (backend 422): kunci/sembunyikan select role pada baris tersebut dan kirim `role: "super_admin"`.
- [ ] `ResetPasswordModal` (tanpa `requireCurrent`) → `PUT /user/{id}/password` `{password, password_confirmation}`. Label sudah "Kata Sandi Baru".
- [ ] `ToggleActiveModal` → `PATCH /user/{id}/status` `{is_active: true|false}`. Tampilkan pesan 422 dari backend (tidak boleh menonaktifkan diri sendiri / super admin terakhir).
- [ ] Filter (`FilterBar`) role: hanya **Admin** dan **Guru Pengampu** (opsi "Wali Kelas" sudah dihapus; jangan dikembalikan tanpa keputusan backend).
- [ ] *(Nice-to-have)* badge kecil "Belum isi telp" pada baris admin/super_admin dengan `perlu_lengkapi_telp = true`, supaya super_admin tahu siapa yang masih terkunci.
- [ ] Tidak ada tombol hapus pengguna (endpoint DELETE tidak disediakan; pakai nonaktifkan).

---

## 9. Menu sidebar per role (P1)

Sumber: `api-spec.md` §2. Saring `NAV_ITEMS` berdasarkan `user.role`:

| Item | `super_admin` | `admin` | `guru_halaqah` |
|---|:-:|:-:|:-:|
| Dashboard, Presensi Guru, Presensi Siswa, Setoran, Ujian Kenaikan Juz, Pengaturan | ✅ | ✅ | ✅ |
| Data Siswa, Halaqah, Target | ✅ (**tanpa** tombol tambah/ubah/hapus/atur) | ✅ | ❌ |
| Pengguna | ✅ | ❌ | ❌ |

- [ ] Akses langsung via URL ke halaman terlarang → tampilkan halaman "Anda tidak punya akses" (bukan hanya menyembunyikan menu). Backend tetap penjaga sebenarnya (403).

---

## 10. Lanjutan di luar revisi ini (P2)

Halaman data masih memakai mock. Menghubungkannya ke API mengikuti `api-spec.md` §4–§5 (peta halaman → endpoint), memakai API client dari §2 di atas:
Dashboard (`/dashboard/summary`), Presensi Guru, Presensi Siswa (`/presensi-siswa/bulk`), Setoran, Ujian Kenaikan Juz (flag `can_input_hafalan`/`can_input_soal`), Data Siswa (+ import CSV, rekap), Halaqah (`/halaqah/siswa`, `/halaqah/assign`), Target (`/kelas`), dan dropdown via `/lookup/*`. Filter/dropdown memakai **id**, bukan nama.

---

## 11. Checklist penerimaan (uji manual)

Akun dev bawaan seeder: `aahmadabdillah001@gmail.com` / `super123` (super_admin, **ganti password di deploy nyata**). Jalankan backend: `docker compose up` (`http://localhost:8000`), frontend `http://localhost:3000` (CORS backend mengizinkan origin ini; ubah lewat `CORS_ALLOWED_ORIGINS`).

1. Login super_admin baru (nomor kosong) → seluruh main section `/dashboard`, `/dashboard/data-siswa`, `/dashboard/pengguna`, dst. berupa halaman putih + teks + tombol; Sidebar & Header tetap ada.
2. Klik tombol → masuk Pengaturan (halaman normal, bukan gate). Edit Pengguna → isi `0821-4298-6689` → tersimpan sebagai `082142986689` → gate hilang tanpa reload; semua halaman terbuka.
3. Coba kosongkan nomor lagi → ditolak (pesan error), gate tidak kembali.
4. Input nomor tidak valid (`123`, `abc`) → pesan error di field, tidak terkirim.
5. Super_admin membuat user Admin lewat form yang sama (tanpa telp) → berhasil. Login sebagai admin itu → gate muncul; isi nomor di Pengaturan → gate hilang.
6. Login sebagai guru_halaqah → **tidak ada gate**, menu Data Siswa/Halaqah/Target/Pengguna tidak tampil.
7. Menu Bantuan: "Developer" membuka `https://wa.me/6282142986689`; "Admin" membuka WhatsApp admin yang sudah punya nomor (fallback super_admin bila belum ada); dicoba di desktop dan mobile drawer.
8. Halaman login (belum login): "Hubungi Super Admin" membuka `wa.me` nomor super_admin (setelah nomornya terisi).
9. Ganti password di Pengaturan → tetap login (token baru tersimpan); login ulang dengan password lama gagal.
10. Super_admin menonaktifkan user yang sedang login di tab lain → request berikutnya user itu diarahkan ke `/login` dengan pesan akun dinonaktifkan.
11. Logout → kembali ke `/login`; membuka `/dashboard` langsung → redirect ke `/login`.
12. Tema gelap: gate, modal telp, dan dropdown Bantuan tetap terbaca.
13. `npx tsc --noEmit` dan `npx eslint app` bersih.

---

## 12. Yang sudah dikerjakan (jangan diulang)

- `AddPenggunaModal`: field **Kata Sandi** (min 8) dan **Kategori** (Ikhwan/Akhwat) sudah ada; opsi "Wali Kelas" sudah dihapus dari form dan filter.
- `ResetPasswordModal`: prop `requireCurrent` (field "Kata Sandi Saat Ini") sudah ada dan dipakai `ProfileCard`.
- Backend: semua endpoint di §1, flag `perlu_lengkapi_telp`, validasi `telp`, CORS, JWT secret otomatis di `docker-entrypoint.sh`.
