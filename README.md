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
