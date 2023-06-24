#!/usr/bin/env bash

docker-compose -f ${PWD}/docker/docker-compose.yml pull
docker-compose -f ${PWD}/docker/docker-compose.yml run php-cli-pcov tests/run.sh all-coverage $*
exitCode=$?
docker-compose -f ${PWD}/docker/docker-compose.yml down
exit $exitCode