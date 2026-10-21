"""Purge a site's page cache. Python 3, standard library only.

Usage: python3 purge_cache.py <site_id>
"""
import json
import os
import sys
import urllib.error
import urllib.request

token = os.environ.get("MAGICWP_TOKEN") or exit("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")
if len(sys.argv) < 2:
    exit("Usage: python3 purge_cache.py <site_id>")

req = urllib.request.Request(
    f"https://api.magicwp.io/v1/sites/{sys.argv[1]}/nginx/cache/flush",
    method="POST",
    headers={"Authorization": f"Bearer {token}"},
)
try:
    with urllib.request.urlopen(req) as res:
        print(json.load(res)["message"])
except urllib.error.HTTPError as e:
    exit(f"{e.code}: {json.load(e).get('message')}")
