#!/bin/bash

mysql -u root -p123 -e "DROP DATABASE phoenix"
mysql -u root -p123 -e "CREATE DATABASE phoenix"

PGPASSWORD=123 psql -h 127.0.0.1 -U postgres -c "DROP DATABASE IF EXISTS phoenix"
PGPASSWORD=123 psql -h 127.0.0.1 -U postgres -c "CREATE DATABASE phoenix"

echo "" > /var/www/phoenix/testing_migrations/phoenix.sqlite

composer remove nette/neon --dev --no-interaction
PHOENIX_PGSQL_PASSWORD=123 PHOENIX_MYSQL_PASSWORD=123 hhvm vendor/bin/phpunit --coverage-html=coverage
composer require nette/neon --dev --no-interaction