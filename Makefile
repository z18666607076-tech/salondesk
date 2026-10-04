.PHONY: up down logs test lint analyse

up:
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f app

test:
	docker compose exec -T \
		-e APP_ENV=testing \
		-e DB_DATABASE=salondesk_testing \
		-e DB_USERNAME=salondesk \
		-e DB_PASSWORD=secret \
		-e QUEUE_CONNECTION=sync \
		-e CACHE_STORE=array \
		-e SESSION_DRIVER=array \
		-e BILLING_DRIVER=fake \
		-e AI_BOOKING_DRIVER=fake \
		app php artisan test

lint:
	docker compose exec -T app vendor/bin/pint --test

analyse:
	docker compose exec -T app vendor/bin/phpstan analyse --memory-limit=1G --no-progress
