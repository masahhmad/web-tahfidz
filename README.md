# Web Tahfidz

A tahfidz management application (attendance, memorization deposits, juz advancement exams, tahfidz exams, and master data management) with a **Laravel 13** (PHP 8.3) backend and a **Next.js 16** frontend.

See [`api-spec.md`](./api-spec.md) for the API endpoint documentation.

## Project Structure

```
.
├── backend/    # Laravel API (PHP 8.3, Sanctum + JWT)
└── frontend/   # Next.js web app (React 19, Tailwind 4)
```

## Running with Docker (recommended)

The fastest way to run this project locally.

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) & Docker Compose v2

### Steps

1. Clone the repo and enter the project folder.
2. Run:

   ```bash
   docker compose up --build
   ```

   On first run, the backend container will automatically:
   - Create `backend/.env` from `backend/.env.example` (if it doesn't exist yet)
   - Generate `APP_KEY`
   - Create the `database/database.sqlite` file (default `DB_CONNECTION=sqlite`)
   - Run database migrations

3. Once the containers are ready, access:
   - **Frontend**: http://localhost:3000
   - **Backend API**: http://localhost:8000

4. To run in the background:

   ```bash
   docker compose up --build -d
   ```

5. To stop:

   ```bash
   docker compose down
   ```

### Other useful commands

Running `artisan` or `composer` inside the backend container:

```bash
docker compose exec backend php artisan migrate:fresh --seed
docker compose exec backend php artisan tinker
```

Running `npm` commands inside the frontend container:

```bash
docker compose exec frontend npm run lint
```

Viewing logs:

```bash
docker compose logs -f backend
docker compose logs -f frontend
```

Rebuilding images after changing `composer.json` / `package.json`:

```bash
docker compose up --build
```

> Code in `backend/` and `frontend/` is mounted directly into the containers, so file changes reload automatically (hot reload for Next.js, no restart needed for PHP changes).

## Running manually (without Docker)

### Backend (Laravel)

Prerequisites: PHP 8.3+, Composer, standard Laravel PHP extensions.

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # if using sqlite (default)
php artisan migrate
php artisan serve
```

The backend runs at http://localhost:8000.

### Frontend (Next.js)

Prerequisites: Node.js 20+.

```bash
cd frontend
npm install
npm run dev
```

The frontend runs at http://localhost:3000.

## Environment Configuration

- Backend configuration lives in `backend/.env` (see `backend/.env.example` for the list of variables).
- The frontend reads `NEXT_PUBLIC_API_URL` to know the backend API address (automatically set to `http://localhost:8000` when run via Docker).
