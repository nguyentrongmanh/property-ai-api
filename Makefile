DC = docker compose
APP = webserver
# Run commands against the mounted code (compose mounts ./ at /var/www/html)
EXEC = $(DC) exec -w /var/www/html $(APP)

.PHONY: help up down restart build ps logs shell bash artisan composer install \
	migrate migrate-fresh migrate-rollback seed key-generate test pint pint-fix \
	mysql redis-cli

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-18s\033[0m %s\n", $$1, $$2}'

up: ## Start all containers
	$(DC) up -d

down: ## Stop and remove all containers
	$(DC) down

restart: down up ## Restart all containers

build: ## Build (or rebuild) the images
	$(DC) build --no-cache

ps: ## List running containers
	$(DC) ps

logs: ## Tail logs from all containers
	$(DC) logs -f

shell: ## Open a shell inside the webserver container
	$(EXEC) sh

bash: shell ## Alias for shell

install: build up composer key-generate migrate seed ## First-time project setup

artisan: ## Run an artisan command, e.g. make artisan cmd="route:list"
	$(EXEC) php artisan $(cmd)

composer: ## Run composer install
	$(EXEC) composer install

migrate: ## Run database migrations
	$(EXEC) php artisan migrate

migrate-fresh: ## Drop all tables and re-run migrations with seeders
	$(EXEC) php artisan migrate:fresh --seed

migrate-rollback: ## Rollback the last database migration
	$(EXEC) php artisan migrate:rollback

seed: ## Run database seeders
	$(EXEC) php artisan db:seed

key-generate: ## Generate the application key
	$(EXEC) php artisan key:generate

test: ## Run the PHPUnit test suite
	$(EXEC) php artisan test --compact

pint: ## Check code style without fixing
	$(EXEC) vendor/bin/pint --test

pint-fix: ## Fix code style
	$(EXEC) vendor/bin/pint

mysql: ## Open a MySQL shell
	$(DC) exec mysql mysql -u$${DB_USERNAME:-laravel} -p$${DB_PASSWORD:-secret} $${DB_DATABASE:-laravel}

redis-cli: ## Open a Redis CLI
	$(DC) exec redis redis-cli
