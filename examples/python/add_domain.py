"""Add a custom domain to a site: print the DNS records to add, wait until
they're in place, then connect the domain. Python 3, standard library only.

Usage: python3 add_domain.py <site_id> <domain>
"""
import json
import os
import sys
import time
import urllib.error
import urllib.request

API = "https://api.magicwp.io/v1"
token = os.environ.get("MAGICWP_TOKEN") or exit("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")


def call(path, method="GET", body=None, headers=None):
    req = urllib.request.Request(
        API + path,
        method=method,
        data=json.dumps(body).encode() if body is not None else None,
        headers={"Authorization": f"Bearer {token}", "Content-Type": "application/json", **(headers or {})},
    )
    try:
        with urllib.request.urlopen(req) as res:
            return json.load(res)["data"]
    except urllib.error.HTTPError as e:
        exit(f"{e.code}: {json.load(e).get('message')}")


def wait(task_id, every=5):
    """Long actions return a task; poll it until it finishes."""
    while True:
        t = call(f"/tasks/{task_id}")
        print(f"  {t['status']}")
        if t["status"] == "completed":
            return
        if t["status"] == "failed":
            exit(t["error_message"] or "Task failed")
        time.sleep(every)


if len(sys.argv) < 3:
    exit("Usage: python3 add_domain.py <site_id> <domain>")
site_id, domain = sys.argv[1], sys.argv[2]

# defer_verification: add it as pending and hand back the records, even if DNS isn't set up yet.
added = call(f"/sites/{site_id}/domains", "POST", {"domain": domain, "defer_verification": True})
print("Add these records at your DNS provider:")
for r in added["records"]:
    print(f"  {r['type']}  {r['name']}  {r['value']}")

base = f"/sites/{site_id}/domains/{added['domain']['id']}"
while not call(f"{base}/verify", "POST")["verified"]:
    print("  not verified yet, checking again in 30 seconds")
    time.sleep(30)
print("Verified, connecting")

wait(call(f"{base}/connect", "POST")["task"]["id"])
print(f"Connected: https://{domain}")
