"""Create a WordPress site and wait until it's ready. Python 3, standard library only.

Usage: python3 create_site.py [label]   (label: 5-15 characters)
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


label = sys.argv[1] if len(sys.argv) > 1 else "My new site"

# The Idempotency-Key makes a retry of this request safe: it won't create a second site.
created = call("/sites", "POST", {"site_label": label, "site_type": "empty_site"}, {"Idempotency-Key": str(uuid.uuid4())})
site, task = created["site"], created["task"]
print(f"Creating site {site['id']}, task {task['id']}")
wait(task["id"], every=10)

ready = call(f"/sites/{site['id']}")
print(f"Ready: https://{ready['domain_name']}")
