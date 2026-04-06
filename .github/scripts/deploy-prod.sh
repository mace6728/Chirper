#!/usr/bin/env bash
set -euo pipefail

cd "${GITHUB_WORKSPACE:-$(pwd)}"

if ! command -v docker >/dev/null 2>&1; then
  echo "Deploy failed: docker command is not available on this runner"
  exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
  echo "Deploy failed: docker compose is not available on this runner"
  exit 1
fi

if [ ! -f compose.prod.yaml ]; then
  echo "Deploy failed: compose.prod.yaml not found in ${GITHUB_WORKSPACE:-$(pwd)}"
  exit 1
fi

if [ ! -f .env.production ]; then
  echo "Deploy failed: .env.production not found in ${GITHUB_WORKSPACE:-$(pwd)}"
  exit 1
fi

: "${DOCKER_USERNAME:?Deploy failed: DOCKER_USERNAME is required}"
: "${DOCKER_PASSWORD:?Deploy failed: DOCKER_PASSWORD is required}"
: "${IMAGE_TAG:?Deploy failed: IMAGE_TAG is required}"

compose_prod() {
  docker compose --env-file .env.production -f compose.prod.yaml "$@"
}

# Ensure Compose uses workflow-provided image coordinates instead of file defaults.
export DOCKER_USERNAME IMAGE_TAG

echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin

compose_prod pull app nginx
compose_prod up -d --wait pgsql

DB_DATABASE="$(grep -E '^DB_DATABASE=' .env.production | head -n1 | cut -d'=' -f2- | tr -d '"')"
DB_USERNAME="$(grep -E '^DB_USERNAME=' .env.production | head -n1 | cut -d'=' -f2- | tr -d '"')"
DB_PASSWORD="$(grep -E '^DB_PASSWORD=' .env.production | head -n1 | cut -d'=' -f2- | tr -d '"')"

if [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ] || [ -z "$DB_PASSWORD" ]; then
  echo "Deploy failed: DB_DATABASE, DB_USERNAME, or DB_PASSWORD is empty in .env.production"
  exit 1
fi

if ! printf '%s' "$DB_USERNAME" | grep -qE '^[a-zA-Z_][a-zA-Z0-9_]*$'; then
  echo "Deploy failed: DB_USERNAME '$DB_USERNAME' contains characters that are not allowed in a PostgreSQL role name"
  exit 1
fi

# Keep DB credentials in sync when pgsql data volume already exists.
# On a fresh volume POSTGRES_USER=DB_USERNAME is the superuser, so ALTER ROLE works directly.
# On a pre-existing volume that was initialised with a different superuser (e.g. 'postgres'),
# the role may be missing; fall back to the 'postgres' superuser and create it there.
DB_PASSWORD_ESCAPED="$(printf '%s' "$DB_PASSWORD" | sed "s/'/''/g")"
if ! compose_prod exec -T pgsql psql \
     -v ON_ERROR_STOP=1 \
     -U "$DB_USERNAME" \
     -d "$DB_DATABASE" \
     -c "ALTER ROLE \"$DB_USERNAME\" WITH PASSWORD '$DB_PASSWORD_ESCAPED';" \
     2>/dev/null
then
  echo "Could not connect as '$DB_USERNAME'; attempting role upsert via the 'postgres' superuser."
  compose_prod exec -T pgsql psql \
    -v ON_ERROR_STOP=1 \
    -U postgres \
    -d postgres \
    -c "DO \$\$
        BEGIN
          IF NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = '$DB_USERNAME') THEN
            CREATE ROLE \"$DB_USERNAME\" WITH LOGIN PASSWORD '$DB_PASSWORD_ESCAPED';
            GRANT ALL PRIVILEGES ON DATABASE \"$DB_DATABASE\" TO \"$DB_USERNAME\";
          ELSE
            ALTER ROLE \"$DB_USERNAME\" WITH PASSWORD '$DB_PASSWORD_ESCAPED';
          END IF;
        END
        \$\$;"
fi

compose_prod up -d app
compose_prod up -d nginx

if ! compose_prod exec -T app sh -lc 'test -f public/build/manifest.json'; then
  echo "Deploy failed: missing Vite manifest at /var/www/html/public/build/manifest.json inside app image"
  echo "Ensure docker/app/Dockerfile.prod builds frontend assets (npm run build) before publishing the app image."
  exit 1
fi

compose_prod exec -T app php artisan migrate:status --no-interaction
compose_prod exec -T app php artisan migrate --pretend --force --no-interaction
compose_prod exec -T app php artisan migrate --force --no-interaction

# Smoke test: verify endpoint from APP_URL and force local resolution.
# Tries HTTPS first; falls back to HTTP to support both TLS termination modes.
APP_URL="$(grep -E '^APP_URL=' .env.production | head -n1 | cut -d'=' -f2- | tr -d '"')"
if [ -z "$APP_URL" ]; then
  echo "Smoke test failed: APP_URL is not set in .env.production"
  exit 1
fi

APP_HOST="$(printf '%s' "$APP_URL" | sed -E 's#^https?://([^/]+).*$#\1#')"
if [ -z "$APP_HOST" ]; then
  echo "Smoke test failed: cannot parse host from APP_URL=$APP_URL"
  exit 1
fi

echo "Smoke test target: https://$APP_HOST/"

if ! command -v curl >/dev/null 2>&1; then
  echo "Smoke test failed: curl is required on the self-hosted runner"
  exit 1
fi

attempt=1
max_attempts=10
while [ "$attempt" -le "$max_attempts" ]; do
  if curl -fsS --connect-timeout 5 --max-time 15 --resolve "$APP_HOST:443:127.0.0.1" -o /dev/null "https://$APP_HOST/" || \
     curl -fsS --connect-timeout 5 --max-time 15 --resolve "$APP_HOST:80:127.0.0.1"  -o /dev/null "http://$APP_HOST/"; then
    echo "Smoke test passed on attempt $attempt"
    break
  fi

  if [ "$attempt" -eq "$max_attempts" ]; then
    echo "Smoke test failed after $max_attempts attempts"
    echo "--- curl diagnostics ---"
    curl -v --connect-timeout 5 --max-time 15 --resolve "$APP_HOST:443:127.0.0.1" -o /dev/null "https://$APP_HOST/" 2>&1 || true
    curl -v --connect-timeout 5 --max-time 15 --resolve "$APP_HOST:80:127.0.0.1"  -o /dev/null "http://$APP_HOST/"  2>&1 || true
    echo "--- compose status ---"
    compose_prod ps
    compose_prod logs --tail=80 app nginx
    compose_prod exec -T app sh -lc 'tail -n 120 storage/logs/laravel.log || true'
    exit 1
  fi

  attempt=$((attempt + 1))
  sleep 3
done

compose_prod ps
docker image prune -f
