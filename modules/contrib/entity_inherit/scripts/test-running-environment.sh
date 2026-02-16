#!/bin/bash
#
# Run some checks on a running environment
#
set -e

echo " => Making sure all modules are installed"
docker-compose exec -T drupal /bin/bash -c 'drush en -y entity_inherit'

echo " => Running self-tests"
docker-compose exec -T drupal /bin/bash -c 'drush ev "entity_inherit()->dev()->liveTest()"'

echo " => Uninstalling entity_inherit"
docker-compose exec -T drupal /bin/bash -c 'drush pmu -y entity_inherit'

echo " => Making sure no errors have been logged"
docker-compose exec -T drupal /bin/bash -c 'drush ws --extended'
docker-compose exec -T drupal /bin/bash -c 'drush ws --severity=Error 2>&1|grep "No log messages available"'

echo " => Done running self-tests. All EntityInherit modules should be uninstalled."
echo " =>"
