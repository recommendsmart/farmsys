#!/bin/bash
#
# Destroy the environment.
#
set -e

docker-compose down -v
docker network rm entity_inherit_default
