# EduMarket development with Docker

Docker Compose starts MySQL, Laravel API, the database queue worker, and Vite together. The native `backend/.env` used by XAMPP is not modified.

## First run

Make sure ports `3306`, `8000`, and `5173` are free. In particular, stop the XAMPP MySQL service while Docker MySQL is running.

Fill the credentials you need in `backend/.env` when that file already exists. If it does not exist, Docker copies `backend/.env.docker` to `.env`; secrets in the template are intentionally blank.

Optionally create a root `.env` containing a MySQL root password. The same value is passed to Laravel automatically:

```dotenv
MYSQL_ROOT_PASSWORD=
```

Start all four services from the project root:

```bash
docker compose up -d --build
```

The backend automatically installs Composer dependencies when needed, generates `APP_KEY` only when blank, runs safe migrations without seeding, creates the storage link, and starts on port 8000.

The project deliberately does not seed on container restart. On a fresh Docker database, add demo data manually or run a specific seeder once:

```bash
docker compose exec backend php artisan db:seed
```

## Check status and logs

```bash
docker compose ps
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f queue
docker compose logs -f mysql
```

The queue log is the useful place to confirm queued email processing.

## Laravel commands

```bash
docker compose exec backend php artisan tinker
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan test
```

## Smoke test

```bash
curl http://localhost:8000/api/courses
```

Then open <http://localhost:5173> in a browser.

## Stop

```bash
docker compose down
```

The `mysql_data` volume persists database data. To intentionally remove all Docker database data, use `docker compose down -v` only when you really want a clean database.

Older Compose installations also accept `docker-compose` instead of `docker compose`.
