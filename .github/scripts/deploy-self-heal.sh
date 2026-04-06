#!/usr/bin/env bash
set -euo pipefail

cd "${GITHUB_WORKSPACE:-$(pwd)}"

if ! command -v docker >/dev/null 2>&1; then
  echo "Self-heal failed: docker command is not available on this runner"
  exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
  echo "Self-heal failed: docker compose is not available on this runner"
  exit 1
fi

if [ ! -f compose.prod.yaml ]; then
  echo "Self-heal failed: compose.prod.yaml not found in ${GITHUB_WORKSPACE:-$(pwd)}"
  exit 1
fi

if [ ! -f .env.production ]; then
  echo "Self-heal failed: .env.production not found in ${GITHUB_WORKSPACE:-$(pwd)}"
  exit 1
fi

# Keep compose resources (containers/networks/volumes) stable across runs.
COMPOSE_PROJECT_NAME="${COMPOSE_PROJECT_NAME:-chirper}"

compose_prod() {
  docker compose -p "$COMPOSE_PROJECT_NAME" --env-file .env.production -f compose.prod.yaml "$@"
}

if [ -n "${DOCKER_USERNAME:-}" ] && [ -n "${DOCKER_PASSWORD:-}" ]; then
  echo "$DOCKER_PASSWORD" | docker login -u "$DOCKER_USERNAME" --password-stdin >/dev/null 2>&1 || true
fi

echo "--- compose status before self-heal ---"
compose_prod ps || true
echo "--- recent compose logs before self-heal ---"
compose_prod logs --tail=80 pgsql app nginx || true

echo "Self-heal: restarting dependency chain (pgsql -> app -> nginx)."
compose_prod up -d pgsql || true

pgsql_ready=false
for attempt in $(seq 1 20); do
  pgsql_id="$(compose_prod ps -q pgsql || true)"
  if [ -n "$pgsql_id" ]; then
    pgsql_health="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$pgsql_id" 2>/dev/null || true)"
    if [ "$pgsql_health" = "healthy" ] || [ "$pgsql_health" = "running" ]; then
      pgsql_ready=true
      echo "Self-heal: pgsql status is $pgsql_health (attempt $attempt)."
      break
    fi
  fi

  sleep 3
done

if [ "$pgsql_ready" != "true" ]; then
  echo "Self-heal warning: pgsql did not become healthy in time; proceeding with container recreation."
fi

compose_prod up -d --force-recreate --remove-orphans app nginx || true

# Clear stale Laravel caches when app container is reachable.
compose_prod exec -T app php artisan optimize:clear --no-interaction || true

echo "--- compose status after self-heal ---"
compose_prod ps || true
