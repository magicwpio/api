#!/usr/bin/env bash
# Take a backup of one site and wait for it to finish. Needs curl and jq.
# Usage: ./create-backup.sh <site_id> [label]
set -euo pipefail
: "${MAGICWP_TOKEN:?Set MAGICWP_TOKEN to an API token (Settings → API tokens)}"
site_id=${1:?Usage: $0 <site_id> [label]}
label=${2:-Backup from the API}
api=https://api.magicwp.io/v1
auth="Authorization: Bearer $MAGICWP_TOKEN"

# The Idempotency-Key makes a retry of this request safe: it won't start a second backup.
task_id=$(curl -fsS -X POST "$api/sites/$site_id/backups" \
  -H "$auth" -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d "$(jq -n --arg label "$label" '{label: $label}')" | jq -r '.data.task.id')
echo "Backup started, task $task_id"

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
