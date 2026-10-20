// List your sites, one page at a time. Node 18+, no packages.
// Usage: node list-sites.mjs
const token = process.env.MAGICWP_TOKEN
if (!token) throw new Error("Set MAGICWP_TOKEN to an API token (Settings → API tokens)")

let cursor = null
do {
  const url = new URL("https://api.magicwp.io/v1/sites")
  url.searchParams.set("limit", "100")
  if (cursor) url.searchParams.set("cursor", cursor)

  const res = await fetch(url, { headers: { Authorization: `Bearer ${token}` } })
  const body = await res.json()
  if (!body.success) throw new Error(`${res.status}: ${body.message}`)

  for (const site of body.data.items) {
    console.log(site.id, site.custom_domain ?? site.domain_name, site.status)
  }
  cursor = body.data.next_cursor
} while (cursor)
