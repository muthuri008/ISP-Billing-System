# Stellar Technologies — Production Deployment

## 1. Server prerequisites

Use a supported Linux server with Docker Engine and Docker Compose plugin installed. Point your DNS record at the server before enabling HTTPS.

## 2. Clone

```bash
git clone https://github.com/muthuri008/ISP-Billing-System.git
cd ISP-Billing-System
```

## 3. Configure backend

```bash
cp backend/.env.example backend/.env
```

Set production values in `backend/.env`:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://YOUR-DOMAIN`
- a unique `APP_KEY`
- production MySQL credentials
- Redis connection
- M-Pesa production credentials/callback
- MikroTik/RADIUS credentials
- billing lifecycle settings

Never commit `backend/.env`.

## 4. Configure Docker secrets

Create a root database password in the deployment environment:

```bash
export DB_DATABASE=isp_billing
export DB_USERNAME=isp_billing
export DB_PASSWORD='CHANGE_THIS'
export DB_ROOT_PASSWORD='CHANGE_THIS_TOO'
```

## 5. Start

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Check containers:

```bash
docker compose -f docker-compose.prod.yml ps
```

## 6. Verify Laravel

```bash
docker compose -f docker-compose.prod.yml exec backend php artisan about
docker compose -f docker-compose.prod.yml exec backend php artisan migrate:status
docker compose -f docker-compose.prod.yml exec backend php artisan test
```

## 7. Scheduler and queues

The application scheduler must run continuously. In production use a dedicated scheduler process/container running:

```bash
php artisan schedule:work
```

Run a queue worker for Redis-backed jobs:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=120
```

Do not rely on a browser request to trigger scheduled billing work.

## 8. HTTPS

Put a TLS-enabled reverse proxy/load balancer in front of the application. Only expose HTTPS publicly. Redirect HTTP to HTTPS and use a valid certificate.

The included frontend Nginx container serves the SPA over HTTP internally.

## 9. Backups

Back up MySQL daily and retain multiple generations. Test restoration regularly. Backups must not be stored only on the same server.

Example logical backup:

```bash
docker compose -f docker-compose.prod.yml exec -T mysql mysqldump -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" > backup.sql
```

## 10. ISP integrations

Before live service:

1. Configure the MikroTik router and API credentials.
2. Configure RADIUS authentication/accounting.
3. Create matching MikroTik/RADIUS package profiles.
4. Configure the M-Pesa callback URL over HTTPS.
5. Test duplicate transaction handling.
6. Test payment allocation and invoice settlement.
7. Test billing suspension with the dry-run lifecycle command first.
8. Test restoration using a controlled test customer.

## 11. First launch checklist

- [ ] HTTPS working
- [ ] `APP_DEBUG=false`
- [ ] `APP_KEY` generated and backed up securely
- [ ] Database migrations successful
- [ ] Redis healthy
- [ ] Queue worker running
- [ ] Scheduler running
- [ ] Admin authentication tested
- [ ] Customer portal tested
- [ ] M-Pesa sandbox tested before production
- [ ] MikroTik test router connected
- [ ] RADIUS authentication tested
- [ ] Accounting tested
- [ ] Billing suspension dry-run reviewed
- [ ] Billing restoration tested
- [ ] Database backup verified
- [ ] Monitoring/logging configured

## Important

Do not place real M-Pesa, MikroTik, RADIUS, database or application secrets in GitHub. Use the server environment or a secrets manager.
