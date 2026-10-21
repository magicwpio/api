#!/usr/bin/env bash
# Create a WordPress site and wait until it's ready. Needs curl and jq.
# Usage: ./create-site.sh [label]   (label: 5-15 characters)
set -euo pipefail
: "${MAGICWP_TOKEN:?Set MAGICWP_TOKEN to an API token (Settings → API tokens)}"
label=${1:-My new site}
api=https://api.magicwp.io/v1
auth="Authorization: Bearer $MAGICWP_TOKEN"

# The Idempotency-Key makes a retry of this request safe: it won't create a second site.
created=$(curl -fsS -X POST "$api/sites" \
  -H "$auth" -H "Content-Type: application/json" \
  -H "Idempotency-Key: $(uuidgen)" \
  -d "$(jq -n --arg label "$label" '{site_label: $label, site_type: "empty_site"}')")
site_id=$(echo "$created" | jq -r '.data.site.id')
task_id=$(echo "$created" | jq -r '.data.task.id')
echo "Creating site $site_id, task $task_id"

while :; do
  task=$(curl -fsS "$api/tasks/$task_id" -H "$auth")
  status=$(echo "$task" | jq -r '.data.status')
  echo "  $status"
  case $status in
    completed) break ;;
    failed) echo "$task" | jq -r '.data.error_message' >&2; exit 1 ;;
  esac
  sleep 10
done

curl -fsS "$api/sites/$site_id" -H "$auth" | jq -r '"Ready: https://\(.data.domain_name)"'
