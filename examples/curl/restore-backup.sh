#!/usr/bin/env bash
# Restore a site from a backup and wait for it to finish. Needs curl and jq.
# With only a site ID it lists the site's backups so you can pick one.
# Usage: ./restore-backup.sh <site_id> [backup_id] [full|files|db]
set -euo pipefail
: "${MAGICWP_TOKEN:?Set MAGICWP_TOKEN to an API token (Settings → API tokens)}"
site_id=${1:?Usage: $0 <site_id> [backup_id] [full|files|db]}
backup_id=${2:-}
method=${3:-full}
api=https://api.magicwp.io/v1
auth="Authorization: Bearer $MAGICWP_TOKEN"

if [ -z "$backup_id" ]; then
  curl -fsS "$api/sites/$site_id/backups" -H "$auth" \
    | jq -r '.data.items[] | "\(.id)  \(.backup_datetime)  \(.backup_type)  \(.label // "")"'
  exit 0
fi

# Restoring overwrites the site. The Idempotency-Key makes a retry safe: it won't restore twice.
task_id=$(curl -fsS -X POST "$api/sites/$site_id/backups/$backup_id/restore" \
  -H "$auth" -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d "$(jq -n --arg m "$method" '{method: $m}')" | jq -r '.data.task.id')
echo "Restore started, task $task_id"

while :; do
  task=$(curl -fsS "$api/tasks/$task_id" -H "$auth")
  status=$(echo "$task" | jq -r '.data.status')
  echo "  $status"
  case $status in
    completed) exit 0 ;;
    failed) echo "$task" | jq -r '.data.error_message' >&2; exit 1 ;;
  esac
  sleep 5
done
