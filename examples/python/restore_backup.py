"""Restore a site from a backup and wait for it to finish. Python 3, standard library only.
With only a site ID it lists the site's backups so you can pick one.

Usage: python3 restore_backup.py <site_id> [backup_id] [full|files|db]
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


if len(sys.argv) < 2:
    exit("Usage: python3 restore_backup.py <site_id> [backup_id] [full|files|db]")
site_id = sys.argv[1]

if len(sys.argv) < 3:
    for b in call(f"/sites/{site_id}/backups")["items"]:
        print(b["id"], b["backup_datetime"], b["backup_type"], b["label"] or "")
    sys.exit()

method = sys.argv[3] if len(sys.argv) > 3 else "full"
# Restoring overwrites the site. The Idempotency-Key makes a retry safe: it won't restore twice.
task = call(f"/sites/{site_id}/backups/{sys.argv[2]}/restore", "POST", {"method": method}, {"Idempotency-Key": str(uuid.uuid4())})["task"]
print(f"Restore started, task {task['id']}")
wait(task["id"])
