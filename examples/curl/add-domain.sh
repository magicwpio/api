#!/usr/bin/env bash
# Add a custom domain to a site: print the DNS records to add, wait until
# they're in place, then connect the domain. Needs curl and jq.
# Usage: ./add-domain.sh <site_id> <domain>
set -euo pipefail
: "${MAGICWP_TOKEN:?Set MAGICWP_TOKEN to an API token (Settings → API tokens)}"
site_id=${1:?Usage: $0 <site_id> <domain>}
domain=${2:?Usage: $0 <site_id> <domain>}
api=https://api.magicwp.io/v1/sites/$site_id
auth="Authorization: Bearer $MAGICWP_TOKEN"

# defer_verification: add it as pending and hand back the records, even if DNS isn't set up yet.
added=$(curl -fsS -X POST "$api/domains" \
  -H "$auth" -H "Content-Type: application/json" \
  -d "$(jq -n --arg d "$domain" '{domain: $d, defer_verification: true}')")
domain_id=$(echo "$added" | jq -r '.data.domain.id')
echo "Add these records at your DNS provider:"
echo "$added" | jq -r '.data.records[] | "  \(.type)  \(.name)  \(.value)"'

until curl -fsS -X POST "$api/domains/$domain_id/verify" -H "$auth" | jq -e '.data.verified' >/dev/null; do
  echo "  not verified yet, checking again in 30 seconds"
  sleep 30
done
echo "Verified, connecting"

task_id=$(curl -fsS -X POST "$api/domains/$domain_id/connect" -H "$auth" | jq -r '.data.task.id')
while :; do
  task=$(curl -fsS "https://api.magicwp.io/v1/tasks/$task_id" -H "$auth")
  status=$(echo "$task" | jq -r '.data.status')
  echo "  $status"
  case $status in
    completed) echo "Connected: https://$domain"; exit 0 ;;
    failed) echo "$task" | jq -r '.data.error_message' >&2; exit 1 ;;
  esac
  sleep 5
done
