// Restore a site from a backup and wait for it to finish. Node 18+, no packages.
// With only a site ID it lists the site's backups so you can pick one.
// Usage: node restore-backup.mjs <site_id> [backup_id] [full|files|db]
import { randomUUID } from "node:crypto"

const token = process.env.MAGICWP_TOKEN
if (!token) throw new Error("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")
const [siteId, backupId, method = "full"] = process.argv.slice(2)
if (!siteId) throw new Error("Usage: node restore-backup.mjs <site_id> [backup_id] [full|files|db]")

const api = "https://api.magicwp.io/v1"

async function call(path, init = {}) {
  const res = await fetch(api + path, {
    ...init,
    headers: { Authorization: `Bearer ${token}`, "Content-Type": "application/json", ...init.headers },
  })
  const body = await res.json()
  if (!body.success) throw new Error(`${res.status}: ${body.message}`)
  return body.data
}

const sleep = (s) => new Promise((r) => setTimeout(r, s * 1000))

// Long actions return a task; poll it until it finishes.
async function wait(taskId, every = 5) {
  for (;;) {
    const t = await call(`/tasks/${taskId}`)
    console.log(`  ${t.status}`)
    if (t.status === "completed") return
    if (t.status === "failed") throw new Error(t.error_message ?? "Task failed")
    await sleep(every)
  }
}

if (!backupId) {
  const { items } = await call(`/sites/${siteId}/backups`)
  for (const b of items) console.log(b.id, b.backup_datetime, b.backup_type, b.label ?? "")
} else {
  // Restoring overwrites the site. The Idempotency-Key makes a retry safe: it won't restore twice.
  const { task } = await call(`/sites/${siteId}/backups/${backupId}/restore`, {
    method: "POST",
    headers: { "Idempotency-Key": randomUUID() },
    body: JSON.stringify({ method }),
  })
  console.log(`Restore started, task ${task.id}`)
  await wait(task.id)
}
