# SalonDesk

Laravel 13 / PHP 8.4 booking API. Do not upgrade the runtime to PHP 8.5.

- Tenancy is one MySQL database plus `tenant_id`. See `docs/adr/0001-tenancy-strategy.md`.
- Tests: `php artisan test` against MySQL database `salondesk_testing` (see `phpunit.xml`).
- Lint: `vendor/bin/pint --test`. Static analysis: `vendor/bin/phpstan analyse --memory-limit=1G`.
- Billing and the AI assistant both have fake drivers. Do not add real Stripe or OpenAI secrets.
