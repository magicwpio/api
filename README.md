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

- **Lists** are paged: `?limit=` (1–100, default 50) and `?cursor=`. Pass
  `next_cursor` back until it is `null`.
- **Long actions** (backups, restores, clones, ...) return a `task`. Poll
  `GET /v1/tasks/{task_id}` until `status` is `completed` or `failed`.
- **Retries:** heavy writes accept an `Idempotency-Key` header.
- **Limits:** 300 reads and 60 writes per minute per token.

## Examples

Each example reads the token from `MAGICWP_TOKEN` and needs no extra packages.

| | List sites | Create a backup |
|---|---|---|
| curl | [`list-sites.sh`](examples/curl/list-sites.sh) | [`create-backup.sh`](examples/curl/create-backup.sh) |
| Node 18+ | [`list-sites.mjs`](examples/node/list-sites.mjs) | [`create-backup.mjs`](examples/node/create-backup.mjs) |
| Python 3 | [`list_sites.py`](examples/python/list_sites.py) | [`create_backup.py`](examples/python/create_backup.py) |
| PHP 8 | [`list-sites.php`](examples/php/list-sites.php) | [`create-backup.php`](examples/php/create-backup.php) |

```bash
node examples/node/list-sites.mjs
python3 examples/python/create_backup.py <site_id>
```

## Updating the spec

```bash
curl -fsS https://api.magicwp.io/v1/openapi.json -o openapi.json
```

## License

[MIT](LICENSE)
