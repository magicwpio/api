# Security

## Reporting a vulnerability

Please **don't open a public issue** for a security problem. Email
**security@magicwp.io** instead, with:

- what you found and where (endpoint, request, or example file),
- the steps to reproduce it,
- what an attacker could do with it.

Never include a real API token. If you need to show one, revoke it first
(Settings → API tokens).

We'll confirm we got your report within 3 business days, keep you posted while
we fix it, and credit you when it's out, unless you'd rather stay anonymous.

## Scope

- The MagicWP API at `https://api.magicwp.io/v1`
- The examples and spec in this repo

Please test only against sites and accounts you own, and don't run tests that
could slow the service down or touch other customers' data.

## A token was leaked

If you published a MagicWP API token by mistake (in a commit, a log, a
screenshot), revoke it right away in the dashboard under
**Settings → API tokens** and create a new one.
