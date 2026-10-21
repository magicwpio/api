// Purge a site's page cache. Node 18+, no packages.
// Usage: node purge-cache.mjs <site_id>
const token = process.env.MAGICWP_TOKEN
if (!token) throw new Error("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")
const siteId = process.argv[2]
if (!siteId) throw new Error("Usage: node purge-cache.mjs <site_id>")

const res = await fetch(`https://api.magicwp.io/v1/sites/${siteId}/nginx/cache/flush`, {
  method: "POST",
  headers: { Authorization: `Bearer ${token}` },
})
const body = await res.json()
if (!body.success) throw new Error(`${res.status}: ${body.message}`)
console.log(body.message)
