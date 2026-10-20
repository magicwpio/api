// Take a backup of one site and wait for it to finish. Node 18+, no packages.
// Usage: node create-backup.mjs <site_id> [label]
import { randomUUID } from "node:crypto"

const token = process.env.MAGICWP_TOKEN
if (!token) throw new Error("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")
const [siteId, label = "Backup from the API"] = process.argv.slice(2)
if (!siteId) throw new Error("Usage: node create-backup.mjs <site_id> [label]")

const api = "https://api.magicwp.io/v1"
const headers = { Authorization: `Bearer ${token}`, "Content-Type": "application/json" }

async function call(path, init = {}) {
  const res = await fetch(api + path, { ...init, headers: { ...headers, ...init.headers } })
  const body = await res.json()
  if (!body.success) throw new Error(`${res.status}: ${body.message}`)
  return body.data
}

// The Idempotency-Key makes a retry of this request safe: it won't start a second backup.
const { task } = await call(`/sites/${siteId}/backups`, {
  method: "POST",
  headers: { "Idempotency-Key": randomUUID() },
  body: JSON.stringify({ label }),
})
console.log(`Backup started, task ${task.id}`)

for (;;) {
  const t = await call(`/tasks/${task.id}`)
  console.log(`  ${t.status}`)
  if (t.status === "completed") break
  if (t.status === "failed") throw new Error(t.error_message ?? "Backup failed")
  await new Promise((r) => setTimeout(r, 5000))
}
