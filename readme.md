# Sales Challenge

It is a sales management SPA. Staff can register sales (linking clients to products/services), browse sales history with filters and pagination, and view a dashboard with inventory and revenue analytics.

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
│                 MySQL / SQLite Database                     │
│  Users · Clients · Products · Services · Sales · SaleDetails│
└─────────────────────────────────────────────────────────────┘
```

## Data Model

| Table | Purpose | Key Fields |
|-------|---------|-----------|
| `users` | Authentication & staff | id, name, email, password |
| `clients` | Customer management | id, name, tax_id (unique) |
| `products` | Inventory items | id, name, price, stock |
| `services` | Sellable services | id, name, price, disabled_at, required_product_id |
| `sales` | Sale transactions | id, client_id, daily_sequence, total, created_at |
| `sale_details` | Sale line items | id, sale_id, product_id, service_id, quantity, unit_price |

## Initial Data & Seeding

When running `make setup`:
1. **User Creation**: A default staff user is automatically created
2. **Dataset Seeding**: `SaleSeeder` loads a complete dataset:
   - 10 clients
   - 20 products with stock levels
   - 15 services (some with product dependencies)
   - 1,000 pre-generated sales records
   - All data is loaded from `database/data/sales_dataset.json`

To re-seed: `docker compose exec sales.challenge bash -c "cd /var/www/html && php artisan db:seed"`

### Key packages & versions

**Backend**
- php 8.4 · laravel/framework v13 · laravel/sanctum v4
- spatie/laravel-data v4.22 (DTOs + validation)
- laravel/pint v1 · phpunit/phpunit v12 · laravel/sail v1

**Frontend**
- Vue 3 · Vite · TypeScript · Pinia
- Tailwind CSS v4 · shadcn-vue (reka-ui primitives)
- ag-charts-community v13.2 + ag-charts-vue3 v13.2 (charts)
- AG Grid v32 (tables)

### Requirements
- Docker and Docker compose

### Installation
1. `make setup`
2. `make dev`

