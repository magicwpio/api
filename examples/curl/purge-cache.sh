#!/usr/bin/env bash
# Purge a site's page cache. Needs curl and jq.
# Usage: ./purge-cache.sh <site_id>
set -euo pipefail
: "${MAGICWP_TOKEN:?Set MAGICWP_TOKEN to an API token (Settings → API tokens)}"
site_id=${1:?Usage: $0 <site_id>}

curl -fsS -X POST "https://api.magicwp.io/v1/sites/$site_id/nginx/cache/flush" \
  -H "Authorization: Bearer $MAGICWP_TOKEN" | jq -r '.message'
