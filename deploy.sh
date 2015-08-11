#!/bin/bash
set -e # Quit the script on error
php app/console app:maintenance --env=prod
git pull --rebase origin master
composer install --no-dev --no-plugins --no-scripts --optimize-autoloader
npm install
php app/console cache:clear --env=prod
php app/console assets:install --env=prod
php app/console assetic:dump --env=prod
php app/console braincrafted:bootstrap:install --env=prod
# Only execute migrations when there are new migrations available.
php app/console doctrine:migrations:status --env=prod | grep "New Migrations:" | cut -d: -f2 |grep "^ *0" > /dev/null || \
php app/console doctrine:migrations:migrate --env=prod
php app/console app:maintenance -d --env=prod

# Log deploy to rollbar
ACCESS_TOKEN=02449345fe9f4044a4b3aef8413e2a7a
ENVIRONMENT=production
LOCAL_USERNAME=`whoami`
REVISION=`git log -n 1 --pretty=format:"%H"`
curl https://api.rollbar.com/api/1/deploy/ \
  -F access_token=$ACCESS_TOKEN \
  -F environment=$ENVIRONMENT \
  -F revision=$REVISION \
  -F local_username=$LOCAL_USERNAME
