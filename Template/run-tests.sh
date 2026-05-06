#!/bin/bash
set -e

echo "Installing system libraries..."
apt-get update -qq && apt-get install -yq libsqlite3-dev libpq-dev 2>/dev/null

echo "Installing PHP extensions..."
docker-php-ext-install pdo pdo_sqlite pdo_pgsql pcntl 2>/dev/null

echo "Running Pest test suite..."
./vendor/bin/pest --no-coverage
