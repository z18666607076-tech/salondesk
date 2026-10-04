# ADR 0001: Single database, shared schema, `tenant_id` scope

## Status

Accepted

## Context

SalonDesk is a multi-tenant booking product. A salon (the tenant) owns staff, services, schedules, customers, and appointments. Two salons must never see each other's rows. The same MySQL database also has to carry Laravel Cashier subscriptions, Filament's admin panel, and a public booking API.

`stancl/tenancy` would give each salon its own database or schema. That fits products that need physical isolation or per-tenant migrations. It costs more operationally: more connections, a harder Cashier mapping (the billable model moves between databases), and a heavier CI story. FlashMall, the sibling API in this portfolio, already runs one MySQL 8.4 database with explicit ownership columns. SalonDesk should be operable the same way.

## Decision

Use one MySQL database and one schema. Every tenant-owned table has `tenant_id`. Isolation is enforced in four layers:

1. `TenantContext` is a singleton. `IdentifyTenant` sets it from `{slug}.salondesk.test` or the `X-Tenant` header. A missing or unknown salon is rejected before a controller runs (400 or 404).
2. `BelongsToTenant` adds `TenantScope`. When a context is set, queries include `tenant_id`. `tenant_id` is not fillable; the creating hook copies it from the context.
3. Sanctum loads the token owner without that scope, then `EnsureUserBelongsToTenant` returns 403 when the token's salon does not match the request salon. Authentication and authorization stay distinct: a foreign token is a known user who is not allowed, not an anonymous request.
4. Filament tenancy uses `/admin/{slug}`. `canAccessTenant` plus Filament's identifier returns 404 for another salon's URL, so the panel does not reveal that the other business exists. `SyncFilamentTenant` copies the panel tenant into `TenantContext`. Spatie Permission teams use the same id, so owner and staff roles do not leak across salons.

The scope is fail-open when no tenant is current. Seeders, the queue worker, and the Filament login page need that. HTTP API routes are not fail-open: they always identify a tenant or refuse the request. The Stripe webhook is the exception, because Stripe does not send `X-Tenant`; Cashier finds the salon by `stripe_id`.

Billing plans live on the tenant row (`Laravel\Cashier\Billable` on `Tenant`), not on the user. Basic allows three staff accounts and no assistant. Pro removes the staff cap and enables the assistant. A generic Cashier trial is treated as Pro.

## Consequences

- Cross-tenant bugs are application bugs, not database bugs. Feature tests cover list, show, availability, and token reuse across two salons.
- Adding a tenant-owned table means adding `tenant_id`, the scope, and a test. There is no second migration path.
- Moving a large salon to its own database later is possible, but it is not the current design.
- Jobs that touch appointments must load them with `withoutGlobalScope(TenantScope::class)` and then set the context, because a worker has no HTTP tenant.
