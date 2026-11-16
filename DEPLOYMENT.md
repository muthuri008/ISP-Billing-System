# Stellar Technologies production deployment

## Before first deployment

1. Provision a supported Linux VPS and point the production DNS name to it.
2. Copy the root `.env.example` to `.env` for Docker Compose's database variable interpolation. Use unique, long random database credentials.
3. Copy `backend/.env.example` to `backend/.env`. Set a unique `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` to the public HTTPS URL, and make its DB credentials match the root `.env` values.
4. Configure an external TLS reverse proxy (for example, Caddy or a managed load balancer) in front of the frontend service. Do not expose the application over plain HTTP to the public internet.
5. Set the production M-Pesa Daraja credentials and HTTPS callback URL through the deployment secret manager. Never commit secrets.
6. Restrict database, Redis, PHP-FPM and backend-web ports to the private Docker network. Only the TLS entry point should be public.

## First deployment / migrations

Build the images and run migrations as an explicit release step:

```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml --profile deploy run --rm migrate
docker compose -f docker-compose.prod.yml up -d
```

The migration service is deliberately opt-in; do not run schema changes implicitly every time the web container restarts. Back up the database and verify the backup before applying production migrations.

## Runtime services

- `backend`: PHP-FPM application runtime.
- `backend-web`: internal Nginx/PHP-FPM gateway.
- `frontend`: static frontend and same-origin `/api` reverse proxy.
- `queue`: Laravel queue worker.
- `scheduler`: Laravel scheduler process.
- `mysql` and `redis`: persistent application dependencies.

The scheduler is configured in `backend/routes/console.php`. Confirm the queue connection and any jobs requiring asynchronous execution before relying on queue processing.

## Operational checks required before launch

- Verify HTTPS, DNS, certificate renewal, firewall rules and trusted proxy configuration.
- Verify `/up` and `/api/v1/health` from outside the VPS.
- Run the full backend test suite and frontend production build.
- Verify an administrator login and role/permission boundaries.
- Test customer portal isolation using two different customer accounts.
- Complete an end-to-end Daraja sandbox STK push, callback, duplicate callback and invoice allocation test.
- Verify MikroTik provisioning and session disconnection on a test router.
- Verify FreeRADIUS authentication and accounting against the actual RADIUS server and router.
- Test invoice generation, overdue suspension and payment-driven restoration.
- Configure encrypted off-site backups and perform a restore drill to a separate database.
- Configure log collection, alerting, disk monitoring and uptime monitoring.

## Important

A successful container build or health endpoint is not proof of live payment, router or RADIUS integration. Do not accept real customer payments or automate customer disconnections until the relevant sandbox/hardware tests have passed.
