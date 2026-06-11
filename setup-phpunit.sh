#!/usr/bin/env bash
# Starts Docker services, prepares WP test suite, and runs PHPUnit in the phpunit container.
set -euo pipefail
DB_NAME="${DB_NAME:-wordpress}"
DB_USER="${DB_USER:-wordpress}"
DB_PASS="${DB_PASSWORD:-wordpress}"
DB_HOST="${DB_HOST:-db-phpunit}"
WP_VERSION="${WP_VERSION:-latest}"
echo "=== Starting Docker Compose stack ==="
docker compose up -d --remove-orphans
echo "=== Waiting for MySQL to be ready ==="
MAX_TRIES=30
TRIES=0
until docker compose exec -T db-phpunit mysqladmin ping -h "127.0.0.1" --silent 2>/dev/null; do
  TRIES=$((TRIES + 1))
  if [ "$TRIES" -ge "$MAX_TRIES" ]; then
    echo "ERROR: MySQL did not become ready after ${MAX_TRIES} attempts."
    exit 1
  fi
  echo "  Waiting for MySQL... (${TRIES}/${MAX_TRIES})"
  sleep 2
done
echo "MySQL is ready."
echo "=== Dropping test database if it exists ==="
docker compose exec -T db-phpunit mysqladmin drop "$DB_NAME" -f \
  --user="$DB_USER" --password="$DB_PASS" 2>/dev/null || true
echo "=== Installing WordPress test suite ==="
docker compose exec -T phpunit bash tests/scripts/install-wp-tests.sh \
  "$DB_NAME" "$DB_USER" "$DB_PASS" "$DB_HOST" "$WP_VERSION"
echo "=== Running PHPUnit ==="
docker compose exec -T phpunit vendor/bin/phpunit "$@"
