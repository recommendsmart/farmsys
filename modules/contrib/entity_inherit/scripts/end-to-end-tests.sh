#!/bin/bash
#
# Run end-to-end tests and keep track of markup and screenshots.
#

set -e

PASS=$(./scripts/uuid.sh)
echo 'Updating password for admin so our testbot knows how to login'
docker exec "$(docker-compose ps -q drupal)" /bin/bash -c 'drush upwd $''(drush uinf --uid=1 --field=name) '"$PASS"

echo 'Running our tests'
docker run -e DRUPALUSER=admin -e DRUPALPASS="$PASS" --rm -v "$(pwd)"/tests/browser-tests:/app/test \
  --network entity_inherit_default \
  -v "$(pwd)"/do-not-commit/screenshots:/artifacts/screenshots \
  -v "$(pwd)"/do-not-commit/dom-captures:/artifacts/dom-captures \
  dcycle/browsertesting:3
