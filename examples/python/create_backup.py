"""Take a backup of one site and wait for it to finish. Python 3, standard library only.

Usage: python3 create_backup.py <site_id> [label]
"""
import json
import os
import sys
import time
import urllib.error
import urllib.request
import uuid

API = "https://api.magicwp.io/v1"
token = os.environ.get("MAGICWP_TOKEN") or exit("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")
if len(sys.argv) < 2:
    exit("Usage: python3 create_backup.py <site_id> [label]")
site_id = sys.argv[1]
label = sys.argv[2] if len(sys.argv) > 2 else "Backup from the API"


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


# The Idempotency-Key makes a retry of this request safe: it won't start a second backup.
task = call(f"/sites/{site_id}/backups", "POST", {"label": label}, {"Idempotency-Key": str(uuid.uuid4())})["task"]
print(f"Backup started, task {task['id']}")

while True:
    t = call(f"/tasks/{task['id']}")
    print(f"  {t['status']}")
    if t["status"] == "completed":
        break
    if t["status"] == "failed":
        exit(t["error_message"] or "Backup failed")
    time.sleep(5)
