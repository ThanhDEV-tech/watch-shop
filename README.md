# Watchora

Watchora is a fashion watch storefront built with Laravel 11 and Vue 3. The MVP covers a product catalog with variants/SKUs, authenticated cart and checkout, VNPay Sandbox payments, inventory tracking, order management, and an admin dashboard.

## Requirements

- Docker Desktop
- Ports `3306`, `8000`, `5173`, and `8080` available

## Start the project

1. Copy `backend/.env.docker` to `backend/.env` if the latter does not exist, then fill in the VNPay and mail credentials you need.
2. Build and start the services:

```bash
docker compose up -d --build
```

3. On a fresh database, run migrations:

```bash
docker compose exec backend php artisan migrate
```

## URLs

- Frontend: http://localhost:5173
- Backend API: http://localhost:8000/api
- phpMyAdmin: http://localhost:8080

## Useful commands

```bash
docker compose ps
docker compose logs -f backend
docker compose logs -f queue
docker compose exec backend php artisan migrate:status
docker compose exec backend php artisan test
docker compose down
```

VNPay runs in Sandbox/Test mode. Secrets belong in environment files and must not be committed.
