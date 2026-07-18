# Watchora development with Docker

Docker Compose starts MySQL, the Laravel API, the database queue worker, Vite, and phpMyAdmin. The default Docker database is `watchora`.

## First run

Make sure ports `3306`, `8000`, `5173`, and `8080` are free. Stop XAMPP MySQL while Docker MySQL is using port `3306`.

If `backend/.env` does not exist, copy `backend/.env.docker` to it. Keep credentials and secrets in the environment files, not in committed configuration.

Optionally create a root `.env` containing the MySQL settings used by Compose:

```dotenv
MYSQL_DATABASE=watchora
MYSQL_ROOT_PASSWORD=
```

Start the services:

```bash
docker compose up -d --build
```

The backend installs Composer dependencies when needed, generates `APP_KEY` when blank, runs safe migrations without seeding, creates the storage link, and starts on port `8000`.

## Database rename from an existing Docker volume

MySQL creates `MYSQL_DATABASE` only when its data directory is initialized. Changing the Compose variable does not rename the database in an existing `mysql_data` volume.

When you are ready to discard the existing Docker database and initialize `watchora`, run these commands yourself from the project root:

```bash
docker compose down -v
docker compose up -d --build
docker compose exec backend php artisan migrate
```

`docker compose down -v` permanently removes the current Docker volumes and their data. Back up anything needed before running it. Do not run it merely to restart the application.

## Status and logs

```bash
docker compose ps
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f queue
docker compose logs -f mysql
```

## Laravel commands

```bash
docker compose exec backend php artisan tinker
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan test
```

## Smoke test

The backend healthcheck uses the existing public Watchora category endpoint:

```bash
curl http://localhost:8000/api/categories
```

Then open http://localhost:5173.

## Stop without deleting data

```bash
docker compose down
```

The `mysql_data` volume persists unless you explicitly add `-v`.
