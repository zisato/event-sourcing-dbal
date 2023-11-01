## Executables
DOCKER_COMPOSE=docker compose -f docker/docker-compose.yml
DOCKER_COMPOSE_PCOV=docker compose -f docker/docker-compose.yml -f docker/docker-compose-pcov.yml
DOCKER_COMPOSE_XDEBUG=docker compose -f docker/docker-compose.yml -f docker/docker-compose-xdebug.yml

## Arguments
ARGUMENTS=$(filter-out $@,$(MAKECMDGOALS))

.DEFAULT_GOAL=help
.PHONY=help build composer test phpstan rector ecs

###
### Help
help: ## List targets
	@printf "\nUsage: make <command>\n"
	@grep -E '(^[a-zA-Z0-9\._-]+:$$)|(^[a-zA-Z0-9\._-]+:.*?##.*$$)|(^### .*$$)' $(MAKEFILE_LIST) \
	| sed -e 's/^###/\n\0/' \
	| sed -e 's/\(^[A-Za-z0-9\._-]\+:\)\( [ A-Za-z0-9\._-]\+ \)\(## .*$$\)/\1 \3/' \
	| sed -e 's/^[A-Za-z0-9\._-]/\t\0/' \
	| sed -e 's/://'

###
### Docker
build: ## Build container
	@$(DOCKER_COMPOSE) build --pull

###
### Composer
composer:
	@$(DOCKER_COMPOSE) run --rm --remove-orphans --no-deps --env COMPOSER_MEMORY_LIMIT=-1 php-cli composer $(ARGUMENTS)

###
### Test
unit:
	@$(DOCKER_COMPOSE) run --build --rm --no-deps php-cli bin/phpunit --no-coverage --testsuite=unit

functional:
	@$(DOCKER_COMPOSE) run --build --rm php-cli tests/run.sh integration

test: unit functional

test.coverage:
	@$(DOCKER_COMPOSE_PCOV) run --build --rm --no-deps php-cli bin/phpunit --testsuite=unit
	@$(DOCKER_COMPOSE_PCOV) run --build --rm php-cli tests/run.sh integrationCoverage
	@$(DOCKER_COMPOSE_PCOV) run --rm --no-deps php-cli bin/phpcov merge build/coverage --html build/coverage/merged/html

###
### Code Quality
phpstan:
	@$(DOCKER_COMPOSE) run --rm --no-deps php-cli bin/phpstan

rector:
	@$(DOCKER_COMPOSE) run --rm --no-deps php-cli bin/rector process src

ecs:
	@$(DOCKER_COMPOSE) run --rm --no-deps php-cli bin/ecs check src --fix

code.quality: rector ecs phpstan

###
## ARGUMENT no rule to make target message fix
%:
	@: