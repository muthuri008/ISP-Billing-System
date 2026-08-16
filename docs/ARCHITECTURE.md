# Architecture

## Monorepo layout

```text
ISP-Billing-System/
├── backend/                 # Laravel API and domain services
├── frontend/                # React/TypeScript web application
├── infrastructure/          # Docker, Nginx, database and deployment assets
├── docs/                    # Architecture, API and operational documentation
├── tests/                   # Cross-service/integration tests
└── .github/workflows/       # CI/CD
```

## Backend

Laravel is the system of record for customers, subscriptions, plans, billing, payments, network resources and administrative operations.

Business logic is kept in domain/application services rather than controllers. External integrations are implemented behind interfaces/adapters so a payment or network vendor can be replaced without rewriting billing logic.

## Frontend

React + TypeScript communicates with the backend through versioned REST APIs. The frontend contains separate administrative and customer-facing areas with shared components and strict permission-aware routing.

## Infrastructure

MySQL stores durable application data. Redis handles cache, queues and scheduled background work. Nginx terminates HTTP(S) traffic and proxies API/frontend requests.

## Security principles

- Secrets are supplied through environment variables and never committed.
- Passwords use modern password hashing.
- Sensitive integration credentials are encrypted at rest.
- API endpoints use authentication, authorisation and rate limiting.
- Financial operations use database transactions and idempotency keys.
- Administrative mutations produce audit records.
- Production deployment uses HTTPS and least-privilege service accounts.

## Integration boundaries

### MikroTik

Router credentials and connection details are stored encrypted. Router operations are performed by a dedicated service layer with timeouts, validation and structured error handling.

### RADIUS

AAA operations are isolated behind a RADIUS service boundary. The billing system remains authoritative for commercial account status while RADIUS enforces network authentication/authorisation.

### Payments

Payment gateways implement a common contract. Webhooks are verified, persisted, deduplicated and processed asynchronously where appropriate.

## Billing invariants

- Money is represented using fixed-precision decimal values.
- Invoice numbers are unique.
- Payment references are unique where supplied by a gateway.
- A successful payment cannot be applied twice.
- Suspension/reconnection jobs are idempotent.
- Financial records are never silently deleted.
