#!/usr/bin/env bash
# List your sites, one page at a time. Needs curl and jq.
set -euo pipefail
: "${MAGICWP_TOKEN:?Set MAGICWP_TOKEN to an API token (Settings → API tokens)}"

cursor=""
while :; do
  page=$(curl -fsS "https://api.magicwp.io/v1/sites?limit=100${cursor:+&cursor=$cursor}" \
    -H "Authorization: Bearer $MAGICWP_TOKEN")
  echo "$page" | jq -r '.data.items[] | "\(.id)  \(.custom_domain // .domain_name)  \(.status)"'
  cursor=$(echo "$page" | jq -r '.data.next_cursor // empty')
  [ -z "$cursor" ] && break
done
