#!/usr/bin/env bash

docker-compose -f ${PWD}/docker/docker-compose.yml pull
docker-compose -f ${PWD}/docker/docker-compose.yml run --rm php-cli composer $*
exitCode=$?
docker-compose -f ${PWD}/docker/docker-compose.yml down
exit $exitCode