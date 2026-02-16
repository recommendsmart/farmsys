#!/bin/bash
#
# This script is run when the Drupal docker container is ready. It prepares
# an environment for development or testing, which contains a full Drupal
# 9 installation with a running website.
#
set -e

TRIES=20
echo "Will try to connect to MySQL container until it is up. This can take up to $TRIES seconds if the container has just been spun up."
OUTPUT="ERROR"
for i in $(seq 1 "$TRIES");
do
  OUTPUT=$(echo 'show databases'|{ mysql -h mysql -u root --password=drupal 2>&1 || true; })
  if [[ "$OUTPUT" == *"ERROR"* ]]; then
    echo "Try $i of $TRIES. MySQL container is not available yet. Should not be long..."
    sleep 1
  else
    echo "MySQL is up! Moving on..."
    break;
  fi
done

drush si -y --db-url "mysqli://root:drupal@mysql/drupal" standard
cat /var/www/html/modules/custom/entity_inherit/scripts/lib/docker-resources/dev-settings.txt >> /var/www/html/sites/default/settings.php

drush en -y \
  entity_inherit

echo "Adding the reference field"
rm -rf /drupal-settings
mkdir -p /drupal-settings
drush cex -y --destination=/drupal-settings
/bin/cp -r /var/www/html/modules/custom/entity_inherit/scripts/lib/docker-resources/dev-config/* /drupal-settings/
drush cim -y --source=/drupal-settings

mkdir -p /var/www/html/sites/default/files
chown -R www-data:www-data /var/www/html/sites/default/files
