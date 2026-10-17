#!/usr/bin/env bash
# Deploy one image tag with docker compose; roll back to the previous tag if it never gets healthy.
# Usage: IMAGE=ghcr.io/owner/repo:<sha> deploy/deploy.sh
# Runs on the self-hosted runner. The app dir holds the real .env, DB backups and the deployed-tag record.
# Migrations run in the container entrypoint (docker/entrypoint.sh) when the new app container starts.
set -euo pipefail

: "${IMAGE:?IMAGE must be set, e.g. ghcr.io/owner/repo:<sha>}"
APP_DIR="${APP_DIR:-/srv/apps/orderdesk}"
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1:8082/login}"
KEEP_BACKUPS="${KEEP_BACKUPS:-10}"
SRC_DIR="$(cd "$(dirname "$0")" && pwd)"

cd "$APP_DIR"
[ -f .env ] || { echo "Missing $APP_DIR/.env (create it from .env.example first)" >&2; exit 1; }
cp "$SRC_DIR/compose.prod.yml" compose.prod.yml

compose() { docker compose -f compose.prod.yml "$@"; }

healthy() {
  for _ in $(seq 1 60); do
    curl -fsS "$HEALTH_URL" >/dev/null 2>&1 && return 0
    sleep 3
  done
  return 1
}

PREVIOUS="$(cat .deployed-image 2>/dev/null || true)"

# Back up the database before a new image can migrate it. Skipped on the very first deploy.
if [ -n "$PREVIOUS" ] && compose ps --status running --services 2>/dev/null | grep -qx db; then
  mkdir -p backups
  # shellcheck disable=SC2016  # the password is expanded inside the container, not here
  compose exec -T db sh -c 'mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" --single-transaction --routines "$MYSQL_DATABASE"' \
    | gzip > "backups/pre-deploy-$(date +%Y%m%d-%H%M%S).sql.gz"
  ls -1t backups/pre-deploy-*.sql.gz | tail -n +"$((KEEP_BACKUPS + 1))" | xargs -r rm --
fi

echo "Deploying $IMAGE (previous: ${PREVIOUS:-none})"
IMAGE="$IMAGE" compose pull app
IMAGE="$IMAGE" compose up -d --remove-orphans

if healthy; then
  echo "$IMAGE" > .deployed-image
  docker image prune -f >/dev/null
  echo "Deployed $IMAGE"
  exit 0
fi

echo "Health check failed for $IMAGE" >&2
IMAGE="$IMAGE" compose logs --tail=50 app >&2 || true
if [ -n "$PREVIOUS" ]; then
  echo "Rolling back the app to $PREVIOUS (migrations are not reverted; restore from backups/ if needed)" >&2
  IMAGE="$PREVIOUS" compose up -d --remove-orphans
  healthy && echo "Rollback is healthy" >&2 || echo "Rollback is NOT healthy either" >&2
fi
exit 1
