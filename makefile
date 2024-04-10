build: down-clear
	docker compose up -d --build

down-clear:
	docker compose down -v --remove-orphans

start:
	docker compose up -d

stop:
	docker compose stop

test:
	docker compose exec -it -w /src/ php-fpm php test.php
