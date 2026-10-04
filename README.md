# SalonDesk

Multi-tenant appointment booking for salons, beauty studios, and other service businesses.

A salon signs up, adds services and staff, publishes a booking link, and takes appointments without double-booking a chair. Guests book at `/book/{slug}`. Owners and the front desk run the day from a Filament calendar. The public API is what a mini program or another client would call. An assistant turns “a haircut with Anna next Tuesday afternoon” into a real open slot.

This repository is the foundation of that product: tenant isolation, the booking engine, a versioned API, roles, subscription plans, and the assistant boundary. It is built to be read as much as it is built to run.

[![CI](https://github.com/z18666607076-tech/salondesk/actions/workflows/ci.yml/badge.svg)](https://github.com/z18666607076-tech/salondesk/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13.34-FF2D20)](https://laravel.com/)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

## Problem

Independent salons still run bookings in chat threads and shared spreadsheets. A vertical SaaS has to keep those businesses on one platform without ever mixing their calendars, their staff, or their invoices. The interesting problems are not the CRUD screens. They are conflict-free slots, a cancellation policy, a plan that limits how many stylists a salon can add, and an assistant that is not allowed to invent a time.

## Features

- Tenants are businesses. Data is isolated by `tenant_id` in one MySQL database. See [ADR 0001](docs/adr/0001-tenancy-strategy.md).
- Services carry a duration and a price. Staff have weekly working hours. Slots stay inside the shift, on the salon's interval, and never overlap a confirmed or completed visit.
- Public booking page at `/book/{slug}`, mobile-friendly, using the same booking engine and assistant as the API.
- Public API under `/api/v1`: services, staff, availability, book, cancel, and reschedule. Cancel and reschedule require the customer email used at booking.
- Sanctum tokens for staff. A token from one salon gets **403** on another salon's API, not a silent empty list.
- Filament admin at `/admin/{slug}` with owner, receptionist, and staff roles. Staff see their own appointments. Receptionists see every stylist on a shared day and week calendar and can bypass the cancellation window. They cannot manage billing or staff. Owners manage the salon, the plan, and every visit.
- Each salon has its own currency, timezone, and locale. The booking page and admin chrome ship in English and Simplified Chinese.
- Stripe subscriptions through Laravel Cashier, one billable customer per salon. `BILLING_DRIVER=fake` writes Cashier rows and needs no API keys. Set the driver to `stripe` and add test-mode keys when you want Checkout.
- Booking assistant behind `BookingAssistant`. The fake driver is the default and is what tests use. The Laravel AI SDK driver runs only when `AI_BOOKING_DRIVER=laravel` and `OPENAI_API_KEY` is set, and it still has to match a slot the booking engine would return.
- Booking confirmation mail, queued on the `notifications` queue.

## Architecture

```mermaid
flowchart TD
    client[Browser or API client]
    client --> book["/book/{slug}"]
    client --> filament["Filament /admin/{slug}"]
    client --> api["/api/v1"]
    book --> ctx[TenantContext]
    filament --> access{Owner can access this salon?}
    access -->|no| missing[404]
    access -->|yes| sync[SyncFilamentTenant]
    api --> identify{Subdomain or X-Tenant}
    identify -->|missing| bad[400]
    identify -->|unknown slug| notfound[404]
    identify -->|found| ctx[TenantContext]
    sync --> ctx
    ctx --> scope[TenantScope adds tenant_id]
    api --> token[Sanctum bearer token]
    token --> member{Token belongs to this salon?}
    member -->|no| forbidden[403]
    member -->|yes| handlers[Policies and actions]
    scope --> mysql[(MySQL)]
    handlers --> mysql
```

The full walkthrough is in [docs/architecture.md](docs/architecture.md).

| Piece | Choice |
| --- | --- |
| Tenancy | Single database, `tenant_id`, global scope. Not a database-per-tenant package. |
| Plans | Basic: 3 staff, no assistant. Pro: unlimited staff and the assistant. A generic trial counts as Pro. |
| Time | Stored in UTC. Shifts and “afternoon” use the tenant timezone. |
| Locale | `en` or `zh_CN` on the tenant. Currency is per salon too. |
| Assistant | Interface plus a fake driver. The SDK path is a tool-using agent whose proposal is checked against `CalculateAvailability`. |
| Billing | Cashier on the `Tenant` model. Fake driver in tests and in the default Compose file. |

## Tech stack

| | |
| --- | --- |
| PHP | 8.4 |
| Laravel | 13.34 |
| MySQL | 8.4 |
| Redis | 7 |
| Admin | Filament 5 |
| API auth | Sanctum 4 |
| Roles | spatie/laravel-permission 8, teams enabled |
| Billing | Laravel Cashier 16 (Stripe API `2025-06-30.basil`) |
| Assistant | laravel/ai 1 |
| HTTP docs | Scramble, at `/docs/api` |
| Tests | Pest 5, PHPUnit 13 |
| Style and types | Pint, Larastan level 6 |
| Local mail | Mailpit |

Versions above match `composer.lock`.

## Quick start

Docker Compose is the path that matches CI: PHP 8.4, MySQL 8.4, Redis 7, Mailpit, and a queue worker.

```bash
docker compose up -d --build
```

- App: http://localhost:8000
- Glow Studio booking: http://localhost:8000/book/glow-studio
- Northshore Nails booking (简体中文): http://localhost:8000/book/northshore-nails
- Admin: http://localhost:8000/admin
- Calendar: http://localhost:8000/admin/glow-studio/calendar
- API docs: http://localhost:8000/docs/api
- Mailpit: http://localhost:8025
- Health: http://localhost:8000/up

The app container migrates, seeds, and publishes Filament assets on boot. The Compose `APP_KEY` is a local demo key. Do not reuse it anywhere else.

### Without Docker

You need PHP 8.4, Composer, MySQL 8, and Redis.

```bash
cp .env.example .env
composer install
php artisan key:generate
# create the salondesk database and the salondesk user from .env.example
php artisan migrate --seed
php artisan filament:assets
php artisan serve
php artisan queue:work redis --queue=notifications,default
```

Create a second database named `salondesk_testing` and grant the same user access before running the test suite. `phpunit.xml` points at it.

## Demo credentials

Every seeded password is `password`.

| Salon | Plan | Who | Email | API header |
| --- | --- | --- | --- | --- |
| Glow Studio | Pro trial, 14 days. English, SGD, Asia/Singapore | Owner Maya Tan | `maya@glow-studio.test` | `X-Tenant: glow-studio` |
| Glow Studio | | Receptionist Rina Lim | `rina@glow-studio.test` | |
| Glow Studio | | Stylist Anna Chen | `anna@glow-studio.test` | |
| Glow Studio | | Stylist Ben Ong | `ben@glow-studio.test` | |
| Northshore Nails | Basic. 简体中文, CNY, Asia/Shanghai | Owner Lina Koh | `lina@northshore-nails.test` | `X-Tenant: northshore-nails` |
| Northshore Nails | | Staff Noor Idris | `noor@northshore-nails.test` | |

Glow Studio has Haircut (45 min, SGD 48.00), Color, and Blowdry. Anna works Monday–Saturday 10:00–19:00. Priya Shah already has a Haircut next Monday at 10:00 with Anna. Wei Tan has a Haircut next Tuesday at 14:00 with Ben, so the week calendar shows two stylists. Northshore Nails is the Chinese booking page: the assistant is hidden because the plan is Basic.

Admin URLs: `/admin/glow-studio` and `/admin/northshore-nails`. Opening the other salon's URL returns 404.

```bash
curl -s -H 'X-Tenant: glow-studio' -H 'Accept: application/json' \
  'http://localhost:8000/api/v1/availability?service_id=1&date=2026-10-06&staff_id=2&time_of_day=afternoon'
```

`service_id` and `staff_id` come from `/api/v1/services` and `/api/v1/staff`.

Log in as Maya, then ask the assistant:

```bash
curl -s -H 'X-Tenant: glow-studio' -H 'Accept: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"message":"a haircut with Anna next Tuesday afternoon"}' \
  http://localhost:8000/api/v1/assistant/bookings
```

Northshore Nails is on Basic, so the same call returns 403.

## Tests

```bash
composer test
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=1G --no-progress
```

Inside Compose: `make test`, `make lint`, `make analyse`.

Pest covers:

- no cross-tenant reads by query, by id, or by another salon's Sanctum token
- afternoon slots, double-booking, cancel, reschedule, and the staff cancellation rule
- the hosted booking page, including a Simplified Chinese salon and an assistant confirmation
- the receptionist calendar, including a stylist who cannot see another chair
- the Basic staff cap
- fake Cashier activation and a signed webhook
- the assistant proposal, including the Laravel AI SDK path with `BookingAgent::fake()`
- queued confirmation mail
- Filament tenant pages
- the OpenAPI document

GitHub Actions runs Pint, Larastan, and Pest against MySQL 8.4 and Redis 7.

## Configuration

| Variable | Default | Purpose |
| --- | --- | --- |
| `TENANT_BASE_DOMAIN` | `salondesk.test` | `{slug}.{domain}` selects the tenant |
| `BILLING_DRIVER` | `fake` | `fake` or `stripe` |
| `AI_BOOKING_DRIVER` | `fake` | `fake`, or `laravel` when `OPENAI_API_KEY` is set |
| `STRIPE_PRICE_BASIC` / `STRIPE_PRICE_PRO` | `price_fake_*` | Cashier price ids |
| `CASHIER_CURRENCY` | `sgd` | |
| `OPENAI_API_KEY` | empty | Required only for the live assistant |

Leave Stripe and OpenAI blank. The app boots and the tests pass without them. Never commit live keys.

Production on a 4-core, 4 GB CentOS host in mainland China is a separate Compose file. The steps for Docker, firewalld, SELinux, registry mirrors, and the memory caps are in [docs/DEPLOY.md](docs/DEPLOY.md).

## Roadmap

- Customer accounts, so a guest can see upcoming visits after booking on the hosted page
- Laravel Reverb so the day calendar updates without a refresh
- Feature flags with Laravel Pennant for plan experiments
- An MCP server in front of the same assistant tools
- A WeChat mini program that talks to `/api/v1`
- More locales beyond English and Simplified Chinese

## License

MIT. See [LICENSE](LICENSE).
