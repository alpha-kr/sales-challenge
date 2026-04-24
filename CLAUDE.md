<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Project Overview

**Sales Challenge** is a sales management SPA. Staff can register sales (linking clients to products/services), browse sales history with filters and pagination, and view a dashboard with inventory and revenue analytics.

- Backend: Laravel 13 REST API (Docker/Sail) — `./laravel-backend`
- Frontend: Vue 3 SPA (Vite + TypeScript) — `./vue-fronted`

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

## Skills Activation

- `laravel-best-practices` — Apply whenever writing, reviewing, or refactoring Laravel PHP code.
- `tailwindcss-development` — Apply when writing or fixing Tailwind utility classes in Vue templates.

## Conventions

- Follow existing code conventions — check sibling files before writing anything new.
- Use descriptive names: `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before creating new ones.
- Do not create verification scripts when tests cover the functionality.
- Do not change dependencies without approval.
- Do not create documentation files unless explicitly requested.
- Stick to the existing directory structure; don't create new base folders without approval.
- Be concise — focus on what matters.

## Running the Project

```bash
# Backend (from laravel-backend/)
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed   # seeds users + 1000 sales via SaleSeeder

# Frontend (from vue-fronted/)
npm run dev        # dev server
npm run build      # production build
npm run type-check # TypeScript check
```

---

## Backend Architecture

### DDD Domain Structure

```
app/
├── Domain/
│   ├── Identity/          # Auth (login/logout)
│   │   ├── Actions/       # LoginAction, LogoutAction
│   │   ├── DTOs/          # LoginData
│   │   └── Models/        # User
│   ├── Clients/           # Client CRUD
│   │   ├── Actions/       # Create/Update/DeleteClientAction
│   │   ├── DTOs/          # CreateClientData, UpdateClientData
│   │   └── Models/        # Client
│   ├── Products/          # Product CRUD
│   │   ├── Actions/       # Create/Update/DeleteProductAction
│   │   ├── DTOs/          # CreateProductData, UpdateProductData
│   │   └── Models/        # Product
│   ├── Services/          # Service CRUD
│   │   ├── Actions/       # Create/Update/DeleteServiceAction
│   │   ├── DTOs/          # CreateServiceData, UpdateServiceData
│   │   └── Models/        # Service
│   ├── Sales/             # Sale processing and listing
│   │   ├── Actions/       # ProcessSaleAction
│   │   ├── DTOs/          # CreateSaleData, SaleItemData, ListSalesData
│   │   ├── Enums/         # SaleItem
│   │   ├── Models/        # Sale, SaleDetail
│   │   └── QueryObjects/  # SaleListQuery (returns Builder)
│   ├── Dashboard/
│   │   └── QueryObjects/  # DashboardStatsQuery (invokable)
│   └── Shared/
│       └── Enums/         # ApiErrorCode
├── Exceptions/
│   └── DomainException.php
├── Http/
│   ├── Controllers/       # Thin controllers, delegate to Actions/QueryObjects
│   └── Resources/         # Eloquent API Resources
└── Traits/
    └── ApiResponse.php    # Standardised JSON responses
```

### Data Model

| Table | Key columns |
|-------|-------------|
| `users` | id, name, email, password |
| `clients` | id, name, tax_id (unique) |
| `products` | id, name, price (decimal:2), stock (int) |
| `services` | id, name, price (decimal:2), disabled_at (nullable), required_product_id (nullable FK) |
| `sales` | id, client_id, daily_sequence, total (decimal:2), created_at |
| `sale_details` | id, sale_id, product_id (nullable), service_id (nullable), quantity, unit_price (decimal:2) |

`sale_details`: at least one of `product_id` / `service_id` must be non-null (CHECK constraint on MySQL; enforced in DTO validation for SQLite).

### API Endpoints

All routes require `auth:sanctum` except login.

```
POST   /api/auth/login
GET    /api/auth/user
POST   /api/auth/logout

GET|POST|PUT|DELETE  /api/clients
GET|POST|PUT|DELETE  /api/products
GET|POST|PUT|DELETE  /api/services

GET    /api/sales          ?client_id, date_from, date_to, page, per_page (default 15)
POST   /api/sales

GET    /api/dashboard
```

### Response Format

All responses go through the `ApiResponse` trait:

```json
// Success
{ "success": true, "data": ..., "message": "...", "meta": {} }

// Paginated success — meta contains pagination info
{ "success": true, "data": [...], "message": "...", "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 70 } }

// Error
{ "success": false, "error": { "code": "VALIDATION_FAILED", "message": "...", "details": {} } }
```

`ApiErrorCode` values: `INTERNAL_ERROR`, `UNAUTHORIZED`, `FORBIDDEN`, `RESOURCE_NOT_FOUND`, `VALIDATION_FAILED`, `INSUFFICIENT_STOCK`, `DAILY_LIMIT_REACHED`, `SERVICE_DEPENDENCY_FAILED`, `HAS_ACTIVE_SALES`.

### Key Business Rules (ProcessSaleAction)

1. **Stock check** — products with `stock <= 0` throw `INSUFFICIENT_STOCK`.
2. **Service availability** — services with `disabled_at != null` throw `SERVICE_DEPENDENCY_FAILED`.
3. **Service dependency** — if a service has `required_product_id`, that product must have `stock > 0`.
4. **Daily product limit** — a product can be sold to at most 3 distinct clients per day; exceeding throws `DAILY_LIMIT_REACHED`.
5. **daily_sequence** — count of the client's sales today + 1, computed at sale time.
6. **Stock decrement** — product stock is decremented by the sold quantity inside the transaction.

### DTOs (spatie/laravel-data)

- Use `Data::from($request)` or constructor injection in controllers.
- Cross-field validation (e.g. product_id XOR service_id) uses `withValidator(Validator $validator)` — **not** `rules()` or `#[Rule]`, because `Rule::forEach()` in DataCollections intercepts those.
- `Sale::total` is cast as `decimal:2`, so it serialises as a **string** in JSON (e.g. `"200.00"`).

### DomainException

Throw `DomainException(errorCode: ApiErrorCode::X, message: '...')` from any Action for business-rule violations. `AppServiceProvider` maps it to an error JSON response automatically.

### Testing

- 75 tests / 350 assertions, all passing.
- Tests use SQLite in-memory (`phpunit.xml`). DB-level MySQL-only features (CHECK constraints) are skipped conditionally via `DB::getDriverName()`.
- `SaleFactory::onDate(Carbon|string)` state for date-sensitive tests.
- Always run `./vendor/bin/sail artisan test --compact` to verify.
- Run Pint after every PHP change: `./vendor/bin/pint --dirty --format agent`.

### Seeders

`SaleSeeder` loads `database/data/sales_dataset.json` — a self-contained dataset with 10 clients, 20 products, 15 services, 1000 sales. It truncates all related tables before seeding. Run via `sail artisan db:seed` or `migrate:fresh --seed`.

---

## Frontend Architecture

```
src/
├── api/             # Axios client (apiClient) + interceptors
├── components/
│   ├── base/        # SkeletonTable, BaseModal, ToastContainer, etc.
│   └── ui/          # shadcn-vue components (button, card, table, pagination…)
├── composables/     # useValidationErrors
├── layouts/         # DashboardLayout.vue (sidebar + RouterView)
├── lib/
│   └── utils.ts     # cn() + formatMoney() (Intl.NumberFormat USD)
├── router/          # Vue Router (auth guards)
├── services/        # AuthService, ClientService, ProductService, ServiceService, SaleService, DashboardService
├── stores/          # auth, ui (Pinia)
├── types/           # api.ts, auth.ts, client.ts, product.ts, service.ts, sale.ts, dashboard.ts
└── views/           # LoginView, DashboardView, SalesView, SaleFormView, ClientsView, ProductsView, ServicesView
```

### Key Patterns

- **Services**: class-based (`SaleService`, `DashboardService`…), each wraps `apiClient`. Export both the class and a singleton instance.
- **Types**: `ApiSuccessResponse<T>` / `PaginationMeta` in `types/api.ts`. `Sale.total` is typed `number | string` because the backend serialises it as a decimal string.
- **Money formatting**: always use `formatMoney(value)` from `@/lib/utils` — never `.toFixed(2)` directly.
- **Pagination**: uses shadcn-vue `Pagination` + `PaginationContent` + `PaginationItem` + `PaginationLink` from `@/components/ui/pagination`. `PaginationPrevious` / `PaginationNext` / `PaginationEllipsis` go **directly** in `PaginationContent`, not inside `PaginationItem`.
- **AG Charts v13**: requires `ModuleRegistry.registerModules([AllCommunityModule])` (imported from `ag-charts-community`) at the top of any component that uses charts. The `axes` option is not passed — AG Charts infers it automatically for bar charts.
- **Error handling**: `useValidationErrors().handleApiError(error)` in every catch block.

### Routes

| Path | Name | View |
|------|------|------|
| `/login` | login | LoginView |
| `/dashboard` | dashboard | DashboardView |
| `/sales` | sales | SalesView |
| `/sales/new` | sales-new | SaleFormView |
| `/clients` | clients | ClientsView |
| `/products` | products | ProductsView |
| `/services` | services | ServicesView |

Default redirect: `/` → `/dashboard`.

---

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands in the context of the backend app in the laravel-backend folder directly via sail commands line (e.g., `sail artisan route:list`). Use `sail artisan list` to discover available commands and `sail artisan [command] --help` to check parameters.
- Inspect routes with `sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `sail artisan config:show app.name`, `sail artisan config:show database.default`. Or read config files directly from the `config/` directory.
- To check environment variables, read the `.env` file directly.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

# Do Things the Laravel Way

- Use `sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `sail artisan list` and check their parameters with `sail artisan [command] --help`.
- If you're creating a generic PHP class, use `sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `sail artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `sail artisan test --compact`.
- To run all tests in a file: `sail artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `sail artisan test --compact --filter=testName` (recommended after making a change to a related file).

</laravel-boost-guidelines>
