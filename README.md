# ISP Billing System

A production-oriented ISP billing and network management platform.

## Planned capabilities

- Customer and account management
- ISP packages and subscriptions
- PPPoE and hotspot services
- MikroTik RouterOS integration
- FreeRADIUS integration
- Recurring billing and invoicing
- M-Pesa, Stripe and PayPal payment adapters
- Automatic suspension and reconnection
- Customer portal and admin portal
- Tickets, notifications, reports and audit logs
- Docker-based development and production deployment

## Architecture

- Backend: Laravel 12 / PHP 8.3+
- Frontend: React + TypeScript + Vite
- Database: MySQL 8
- Cache/queues: Redis
- Reverse proxy: Nginx
- API: REST with OpenAPI documentation

## Development philosophy

This repository is built incrementally. Each module must be runnable, tested, documented and secure before the next major module is added.

See `docs/ARCHITECTURE.md` for the current architecture and `docs/ROADMAP.md` for the implementation plan.
