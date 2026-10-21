# MagicWP API

The MagicWP public API lets you manage your WordPress sites from your own code:
sites, backups, domains, PHP, caching, Cloudflare and more. This repo holds the
OpenAPI spec and a few short examples.

- **Base URL:** `https://api.magicwp.io/v1`
- **Docs:** https://docs.magicwp.io/api
- **Spec:** [`openapi.json`](openapi.json) (OpenAPI 3.1)

## Quick start

Create a token in the dashboard (**Settings → API tokens**), then:

```bash
export MAGICWP_TOKEN="mwp_..."

curl https://api.magicwp.io/v1/sites \
  -H "Authorization: Bearer $MAGICWP_TOKEN"
```

Every response comes in the same envelope:

```json
{ "success": true, "data": { "items": [ ... ], "next_cursor": null }, "message": "...", "status_code": 200, "timestamp": "..." }
```

- **Errors** carry a `code`; see [Error codes](#error-codes).
- **Lists** are paged: `?limit=` (1–100, default 50) and `?cursor=`. Pass
  `next_cursor` back until it is `null`.
- **Long actions** (backups, restores, clones, ...) return a `task`. Poll
  `GET /v1/tasks/{task_id}` until `status` is `completed` or `failed`.
- **Retries:** heavy writes accept an `Idempotency-Key` header.
- **Limits:** 300 reads and 60 writes per minute per token.

## Error codes

Errors use the same envelope with `"success": false`, a stable `code`, a
human `message`, and any details in `data`:

```json
{ "success": false, "code": "permission_denied", "message": "...", "data": { "code": "permission_denied", "permission": "backups.manage" }, "status_code": 403, "timestamp": "..." }
```

Branch on the top-level `code`, not on `message`. New codes may be added; existing ones keep
their meaning. The full list is the `ErrorCode` schema in
[`openapi.json`](openapi.json).

| Code | HTTP | Meaning |
|---|---|---|
| `unauthenticated` | 401 | The token is missing, wrong, expired or revoked. |
| `permission_denied` | 403 | The token or your role lacks the permission named in `data.permission`. |
| `forbidden` | 403 | Not allowed. |
| `owner_only` | 403 | Only the workspace Owner can do this. |
| `ip_not_allowed` | 403 | The token isn't allowed from this IP address. |
| `token_wrong_workspace` | 403 | The token belongs to a different workspace. |
| `workspace_locked` | 403 | The workspace is locked. |
| `workspace_2fa_required` | 403 | This workspace requires two-factor authentication. |
| `owner_2fa_required` | 403 | The workspace Owner must turn on two-factor authentication first. |
| `limit_reached` | 403/409 | A plan or per-site limit has been reached. |
| `not_found` | 404 | It doesn't exist, or isn't in this token's workspace. |
| `gone` | 410 | It's no longer available. |
| `bad_request` | 400 | The request can't be carried out as sent. |
| `site_not_ready` | 400 | The site isn't in a state that allows this yet (still being created, for example). |
| `validation_error` | 422 | The request failed validation; `data` lists the fields. |
| `method_not_allowed` | 405 | This method isn't supported on this path. |
| `payload_too_large` | 413 | The request body is too large. |
| `conflict` | 409 | The item's current state doesn't allow this. |
| `site_busy` | 409 | Another action is running on this site; retry when it finishes. |
| `dns_records_exist` | 409 | The domain already has DNS records that would be replaced. |
| `idempotency_in_progress` | 409 | A request with this `Idempotency-Key` is still running; retry shortly. |
| `idempotency_key_reused` | 422 | This `Idempotency-Key` was already used with a different request. |
| `rate_limited` | 429 | Too many requests; wait for the `Retry-After` header's seconds. |
| `upstream_error` | 502/503/504 | The hosting server or a provider didn't complete the action; retry later. |
| `internal_error` | 500 | Something failed on our side. |

## Examples

Each example reads the token from `MAGICWP_TOKEN` and needs no extra packages.

To keep the token in a file instead of your shell history, copy
[`.env.example`](.env.example) to `.env`, fill it in, and load it:

```bash
cp .env.example .env
set -a; source .env; set +a      # any language
node --env-file=.env examples/node/list-sites.mjs   # Node 20.6+
```

| | curl | Node 18+ | Python 3 | PHP 8 |
|---|---|---|---|---|
| List sites | [`.sh`](examples/curl/list-sites.sh) | [`.mjs`](examples/node/list-sites.mjs) | [`.py`](examples/python/list_sites.py) | [`.php`](examples/php/list-sites.php) |
| Create a backup | [`.sh`](examples/curl/create-backup.sh) | [`.mjs`](examples/node/create-backup.mjs) | [`.py`](examples/python/create_backup.py) | [`.php`](examples/php/create-backup.php) |
| Create a site and wait until it's ready | [`.sh`](examples/curl/create-site.sh) | [`.mjs`](examples/node/create-site.mjs) | [`.py`](examples/python/create_site.py) | [`.php`](examples/php/create-site.php) |
| Add a domain (DNS records, verify, connect) | [`.sh`](examples/curl/add-domain.sh) | [`.mjs`](examples/node/add-domain.mjs) | [`.py`](examples/python/add_domain.py) | [`.php`](examples/php/add-domain.php) |
| Purge the page cache | [`.sh`](examples/curl/purge-cache.sh) | [`.mjs`](examples/node/purge-cache.mjs) | [`.py`](examples/python/purge_cache.py) | [`.php`](examples/php/purge-cache.php) |
| Restore a backup (or list backups) | [`.sh`](examples/curl/restore-backup.sh) | [`.mjs`](examples/node/restore-backup.mjs) | [`.py`](examples/python/restore_backup.py) | [`.php`](examples/php/restore-backup.php) |

```bash
node examples/node/list-sites.mjs
python3 examples/python/create_backup.py <site_id>
php examples/php/restore-backup.php <site_id>            # lists backups
php examples/php/restore-backup.php <site_id> <backup_id>
```

## Updating the spec

```bash
curl -fsS https://api.magicwp.io/v1/openapi.json -o openapi.json
```

## Issues and security

Found a bug or need an endpoint? [Open an issue](https://github.com/magicwpio/api/issues/new/choose).
For security problems, see [SECURITY.md](SECURITY.md) and email security@magicwp.io; please don't open a public issue.

## License

[MIT](LICENSE)
