#!/usr/bin/env bash

docker-compose -f ${PWD}/docker/docker-compose.yml pull
docker-compose -f ${PWD}/docker/docker-compose.yml run --rm php-cli "/var/www/bin/rector" "process" "src" "--dry-run"
exitCode=$?
docker-compose -f ${PWD}/docker/docker-compose.yml down --remove-orphans
exit $exitCode