DOCKER_COMPOSE ?= docker compose -f .docker/docker-compose.yml
# Lowest supported PHP version, used by the targets without a version suffix.
PHP ?= 82

default: help

# https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
.PHONY: help
help: ## Display this help screen
	@grep -hP '^[\w/%]+:.*##.*$$' $(MAKEFILE_LIST) | sort -u | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: build
build: ## Build the containers
	$(DOCKER_COMPOSE) build

.PHONY: down
down: ## Remove the containers and volumes
	$(DOCKER_COMPOSE) down --remove-orphans --volumes

.PHONY: bash
bash: bash$(PHP) ## Open a shell in the lowest PHP container

.PHONY: bash%
bash%: ## Open a shell in a PHP container (`make bash85`)
	$(DOCKER_COMPOSE) run --rm php$* bash

.PHONY: install
install: install$(PHP) ## Install the dependencies for the lowest PHP version

.PHONY: install%
install%: ## Install the dependencies for a PHP version (`make install85`), vendor/ is shared
	$(DOCKER_COMPOSE) run --rm php$* composer update --no-interaction --no-progress

.PHONY: test
test: test$(PHP) ## Run the tests on the lowest PHP version

.PHONY: test%
test%: install% ## Run the tests on a PHP version (`make test85`)
	$(DOCKER_COMPOSE) run --rm php$* vendor/bin/phpunit

.PHONY: test/all
test/all: test82 test85 ## Run the tests on the lowest and the highest PHP version

.PHONY: cs
cs: ## Check the coding style
	$(DOCKER_COMPOSE) run --rm php$(PHP) composer cs

.PHONY: cs/fix
cs/fix: ## Fix the coding style
	$(DOCKER_COMPOSE) run --rm php$(PHP) composer cs:fix

.PHONY: rector
rector: ## Run Rector in dry-run mode
	$(DOCKER_COMPOSE) run --rm php$(PHP) vendor/bin/rector process --dry-run

.PHONY: rector/fix
rector/fix: ## Apply Rector changes
	$(DOCKER_COMPOSE) run --rm php$(PHP) vendor/bin/rector process

.PHONY: bench
bench: ## Run the PHPBench benchmarks
	$(DOCKER_COMPOSE) run --rm php$(PHP) composer bench

.PHONY: stan
stan: ## Run PHPStan
	$(DOCKER_COMPOSE) run --rm php$(PHP) composer stan
