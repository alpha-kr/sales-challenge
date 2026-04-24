# Sales Challenge

A sales management single-page application (SPA). Staff can register sales linking clients to products or services, browse paginated sales history with filters, and view a dashboard with inventory and revenue analytics.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     Frontend (Vue 3 SPA)                    │
│  Vite + TypeScript + Pinia + Tailwind CSS + shadcn-vue      │
│               (http://localhost:5173)                       │
└────────────────────────────┬────────────────────────────────┘
                             │ REST API (Sanctum Auth)
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                  Backend (Laravel 13 REST)                  │
│     DDD Architecture · Spatie Data DTOs · PHPUnit Tests      │
│                  (http://localhost:80)                      │
└────────────────────────────┬────────────────────────────────┘
                             │ Eloquent ORM
                             ▼
┌─────────────────────────────────────────────────────────────┐
│                     MySQL 8.4 Database                      │
│  Users · Clients · Products · Services · Sales · SaleDetails│
└─────────────────────────────────────────────────────────────┘
```

## Tech Stack

### Backend

| Package | Version | Purpose |
|---------|---------|---------|
| PHP | 8.4 | Runtime |
| Laravel | 13 | REST API framework |
| Laravel Sanctum | 4 | Token-based API authentication |
| Spatie Laravel Data | 4.22 | DTOs & request validation |
| Laravel Pint | 1 | Code style fixer |
| PHPUnit | 12 | Testing |
| Laravel Sail | 1 | Docker development environment |
| MySQL | 8.4 | Production database |

### Frontend

| Package | Version | Purpose |
|---------|---------|---------|
| Vue | 3 | UI framework |
| Vite | latest | Build tool & dev server |
| TypeScript | latest | Type safety |
| Pinia | latest | State management |
| Tailwind CSS | 4 | Utility-first CSS |
| shadcn-vue (reka-ui) | latest | Headless UI components |
| AG Grid | 32 | Data tables |
| ag-charts-community | 13.2 | Charts & analytics |

## Data Model

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `users` | Authentication & staff | id, name, email, password |
| `clients` | Customer management | id, name, tax_id (unique) |
| `products` | Inventory items | id, name, price, stock |
| `services` | Sellable services | id, name, price, disabled_at, required_product_id |
| `sales` | Sale transactions | id, client_id, daily_sequence, total, created_at |
| `sale_details` | Sale line items | id, sale_id, product_id, service_id, quantity, unit_price |

## Requirements

- [Docker Desktop](https://docs.docker.com/get-docker/) 24+ with Docker Compose v2

**Platform notes:**

- **macOS / Linux** — `make` is available out of the box (or install via `xcode-select --install` on macOS).
- **Windows** — [WSL2](https://learn.microsoft.com/en-us/windows/wsl/install) is required. Open a WSL2 terminal and install `make` with:
  ```bash
  sudo apt install make
  ```
  Run all subsequent commands inside that WSL2 terminal.

## Setup

1. Clone the repository and enter the project directory:
   ```bash
   git clone <repo-url>
   cd sales-challenge
   ```

2. Run the full first-time setup:
   ```bash
   make setup
   ```
   This will verify Docker is available, then:
   - Copy `laravel-backend/.env.example` → `laravel-backend/.env`
   - Build and start all Docker containers (waits until healthy)
   - Install PHP & Node.js dependencies
   - Generate the application key
   - Run database migrations and seed 1,000 sample sales records

3. Start the development servers:
   ```bash
   make dev
   ```

### Available Make Targets

| Command | Description |
|---------|-------------|
| `make setup` | First-time setup: installs deps, migrates & seeds the database |
| `make dev` | Start containers and launch the Vite dev server with HMR |
| `make build` | Build the frontend for production |
| `make test` | Run the PHPUnit test suite |
| `make down` | Stop and remove all containers |

## Accessing the Application

### Frontend SPA

Open your browser at:

```
http://localhost:5173
```

**Default login credentials** (created by the seeder):

| Field | Value |
|-------|-------|
| Email | `user@example.com` |
| Password | `password` |

### Backend API

The REST API is available at:

```
http://localhost:80
```

All endpoints require a Sanctum token in the `Authorization: Bearer <token>` header, except `POST /api/auth/login`.

| Endpoint | Description |
|----------|-------------|
| `POST   /api/auth/login` | Authenticate and receive an API token |
| `GET    /api/auth/user` | Get the currently authenticated user |
| `POST   /api/auth/logout` | Invalidate the current token |
| `GET\|POST\|PUT\|DELETE /api/clients` | Client CRUD |
| `GET\|POST\|PUT\|DELETE /api/products` | Product CRUD |
| `GET\|POST\|PUT\|DELETE /api/services` | Service CRUD |
| `GET    /api/sales` | List sales (`client_id`, `date_from`, `date_to`, `page`, `per_page`) |
| `POST   /api/sales` | Register a new sale |
| `GET    /api/dashboard` | Dashboard analytics |

The login endpoint accepts an optional `type` field:

| `type` value | Behavior |
|---|---|
| `session` (default) | Cookie-based session — used by the web SPA |
| `token` | Returns a Bearer token in `data.token` — use this for API clients |

**Example — obtain a Bearer token:**

```bash
curl -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password","type":"token"}'
```

### Postman Collection

A ready-to-use Postman collection is available at **`postman-collection.json`** in the project root.

Import it into Postman and run **Login (token)** first — the collection automatically saves the returned token to the `{{token}}` variable and attaches it as `Authorization: Bearer {{token}}` to every subsequent request.

All responses follow this envelope:

```json
// Success
{ "success": true, "data": {}, "message": "...", "meta": {} }

// Paginated
{ "success": true, "data": [], "message": "...", "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 70 } }

// Error
{ "success": false, "error": { "code": "VALIDATION_FAILED", "message": "...", "details": {} } }
```

## Seeded Data

The `SaleSeeder` loads `database/data/sales_dataset.json` which contains:

- 10 clients
- 20 products with varying stock levels
- 15 services (some requiring a product dependency)
- 1,000 pre-generated sales records

To re-seed at any time:

```bash
docker compose exec sales.challenge bash -c "cd /var/www/html && php artisan migrate:fresh --seed"
```
