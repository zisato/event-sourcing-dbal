#!/usr/bin/env bash

docker-compose -f ${PWD}/docker/docker-compose.yml pull
docker-compose -f ${PWD}/docker/docker-compose.yml run php-cli-xdebug tests/run.sh $*
exitCode=$?
docker-compose -f ${PWD}/docker/docker-compose.yml down
exit $exitCode