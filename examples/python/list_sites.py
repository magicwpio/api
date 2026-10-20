"""List your sites, one page at a time. Python 3, standard library only.

Usage: python3 list_sites.py
"""
import json
import os
import urllib.error
import urllib.parse
import urllib.request

token = os.environ.get("MAGICWP_TOKEN") or exit("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")

cursor = None
while True:
    query = {"limit": 100, **({"cursor": cursor} if cursor else {})}
    req = urllib.request.Request(
        "https://api.magicwp.io/v1/sites?" + urllib.parse.urlencode(query),
        headers={"Authorization": f"Bearer {token}"},
    )
    try:
        with urllib.request.urlopen(req) as res:
            page = json.load(res)["data"]
    except urllib.error.HTTPError as e:
        exit(f"{e.code}: {json.load(e).get('message')}")

    for site in page["items"]:
        print(site["id"], site["custom_domain"] or site["domain_name"], site["status"])

    cursor = page.get("next_cursor")
    if not cursor:
        break
