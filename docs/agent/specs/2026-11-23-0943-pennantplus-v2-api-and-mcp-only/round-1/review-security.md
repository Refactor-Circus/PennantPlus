VERDICT: PASS

Security review, round 1. No findings at or above minor.

- The diff removes code (Atrium page, controller, requests, views, resolver, Blade directive) and adds dev-only tooling. The JSON API and MCP surfaces are unchanged, and the defaults (middleware ['api'], ability null) are settled by D-002.
- dedoc/scramble is in require-dev only. It is loaded only from testbench.yaml and the test case, so no docs routes ship to consumers.
- openapi.json holds only `http://localhost/pennantplus`, with no absolute paths or keys. A test enforces this.
- CI actions are pinned by SHA. The new client job runs `npm ci` from a committed lockfile and adds no secrets or elevated permissions.
- The client has no secrets or hard-coded credentials. The client wrapper only passes caller-supplied options to openapi-fetch.
- The OpenAPI test writes to a tempnam() file and unlinks it. There is no injection or path-traversal exposure.
