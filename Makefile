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
# 	docker compose down
# 	docker compose up -d --wait
	docker exec mysql-checa mysql -uroot -proot --execute="DROP DATABASE IF EXISTS checaflow; CREATE DATABASE checaflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON checaflow.* TO 'user'@'%'; FLUSH PRIVILEGES;"
	docker exec php-checa php artisan migrate --force
	docker exec php-checa php artisan initial-setup
# 	docker exec php-checa php artisan fake:seed

test:
	docker exec php-checa php artisan test
