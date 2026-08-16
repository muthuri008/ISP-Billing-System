# Development Guide

## Prerequisites

- PHP 8.3+
- Composer 2
- Node.js 22+
- Docker and Docker Compose
- Git

## First setup

1. Clone the repository.
2. Copy `backend/.env.example` to `backend/.env`.
3. Copy `frontend/.env.example` to `frontend/.env`.
4. Start infrastructure with `docker compose up -d`.
5. Install backend dependencies with `composer install` from `backend/` once the Laravel application bootstrap is present.
6. Install frontend dependencies with `npm install` from `frontend/` once the Vite application bootstrap is present.

## Learning rule

Every major module will include a short developer document explaining what the files do, how requests flow through the system, and how to test the module.

## Secrets

Never commit `.env` files, real API credentials, router passwords, payment secrets, encryption keys or production database credentials.
