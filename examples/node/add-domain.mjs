// Add a custom domain to a site: print the DNS records to add, wait until
// they're in place, then connect the domain. Node 18+, no packages.
// Usage: node add-domain.mjs <site_id> <domain>
const token = process.env.MAGICWP_TOKEN
if (!token) throw new Error("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")
const [siteId, domain] = process.argv.slice(2)
if (!siteId || !domain) throw new Error("Usage: node add-domain.mjs <site_id> <domain>")

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

// defer_verification: add it as pending and hand back the records, even if DNS isn't set up yet.
const added = await call(`/sites/${siteId}/domains`, {
  method: "POST",
  body: JSON.stringify({ domain, defer_verification: true }),
})
console.log("Add these records at your DNS provider:")
for (const r of added.records) console.log(`  ${r.type}  ${r.name}  ${r.value}`)

const base = `/sites/${siteId}/domains/${added.domain.id}`
while (!(await call(`${base}/verify`, { method: "POST" })).verified) {
  console.log("  not verified yet, checking again in 30 seconds")
  await sleep(30)
}
console.log("Verified, connecting")

const { task } = await call(`${base}/connect`, { method: "POST" })
await wait(task.id)
console.log(`Connected: https://${domain}`)
