// Create a WordPress site and wait until it's ready. Node 18+, no packages.
// Usage: node create-site.mjs [label]   (label: 5-15 characters)
import { randomUUID } from "node:crypto"

const token = process.env.MAGICWP_TOKEN
if (!token) throw new Error("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")
const label = process.argv[2] ?? "My new site"

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

// The Idempotency-Key makes a retry of this request safe: it won't create a second site.
const { site, task } = await call("/sites", {
  method: "POST",
  headers: { "Idempotency-Key": randomUUID() },
  body: JSON.stringify({ site_label: label, site_type: "empty_site" }),
})
console.log(`Creating site ${site.id}, task ${task.id}`)
await wait(task.id, 10)

const ready = await call(`/sites/${site.id}`)
console.log(`Ready: https://${ready.domain_name}`)
