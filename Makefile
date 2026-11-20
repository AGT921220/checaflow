.PHONY: up down install enter nginx clear restart test

up:
	chmod -R a+rwX storage bootstrap/cache
	docker compose down
	docker compose up -d

down:
	docker compose down

install:
	docker exec php-checa composer install

enter:
	docker exec -it php-checa /bin/bash

nginx:
	docker exec -it nginx-checa /bin/sh

clear:
	docker exec php-checa php artisan optimize:clear
	docker exec php-checa php artisan config:cache
	docker exec php-checa php artisan route:cache
	docker exec php-checa php artisan horizon:terminate
	docker exec php-checa php artisan queue:restart

restart:
	docker compose down
	docker compose up -d

test:
	docker exec php-checa php artisan test
