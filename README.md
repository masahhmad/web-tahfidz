# Web Tahfidz

Aplikasi manajemen tahfidz (presensi, setoran hafalan, ujian kenaikan juz, ujian tahfidz, dan manajemen data master) dengan backend **Laravel 13** (PHP 8.3) dan frontend **Next.js 16**.

Lihat [`api-spec.md`](./api-spec.md) untuk dokumentasi endpoint API.

## Struktur Project

```
.
├── backend/    # API Laravel (PHP 8.3, Sanctum + JWT)
└── frontend/   # Web app Next.js (React 19, Tailwind 4)
```

## Menjalankan dengan Docker (disarankan)

Cara tercepat untuk menjalankan project ini secara lokal.

### Prasyarat

- [Docker](https://docs.docker.com/get-docker/) & Docker Compose v2

### Langkah

1. Clone repo dan masuk ke folder project.
2. Jalankan:

   ```bash
   docker compose up --build
   ```

   Saat pertama kali dijalankan, container backend akan otomatis:
   - Membuat `backend/.env` dari `backend/.env.example` (jika belum ada)
   - Generate `APP_KEY`
   - Membuat file `database/database.sqlite` (default `DB_CONNECTION=sqlite`)
   - Menjalankan migrasi database

3. Setelah container siap, akses:
   - **Frontend**: http://localhost:3000
   - **Backend API**: http://localhost:8000

4. Untuk menjalankan di background:

   ```bash
   docker compose up --build -d
   ```

5. Untuk menghentikan:

   ```bash
   docker compose down
   ```

### Perintah berguna lainnya

Menjalankan `artisan` atau `composer` di dalam container backend:

```bash
docker compose exec backend php artisan migrate:fresh --seed
docker compose exec backend php artisan tinker
```

Menjalankan perintah `npm` di dalam container frontend:

```bash
docker compose exec frontend npm run lint
```

Melihat log:

```bash
docker compose logs -f backend
docker compose logs -f frontend
```

Rebuild image setelah mengubah `composer.json` / `package.json`:

```bash
docker compose up --build
```

> Kode di `backend/` dan `frontend/` di-*mount* langsung ke dalam container, jadi perubahan file akan otomatis ter-reload (hot reload untuk Next.js, tidak perlu restart untuk perubahan PHP).

## Menjalankan secara manual (tanpa Docker)

### Backend (Laravel)

Prasyarat: PHP 8.3+, Composer, ekstensi PHP standar Laravel.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # jika menggunakan sqlite (default)
php artisan migrate
php artisan serve
```

Backend berjalan di http://localhost:8000

### Frontend (Next.js)

Prasyarat: Node.js 20+.

```bash
cd frontend
npm install
npm run dev
```

Frontend berjalan di http://localhost:3000.

## Konfigurasi Environment

- Konfigurasi backend ada di `backend/.env` (lihat `backend/.env.example` untuk daftar variabel).
- Frontend membaca `NEXT_PUBLIC_API_URL` untuk mengetahui alamat API backend (diset otomatis ke `http://localhost:8000` saat dijalankan lewat Docker).
