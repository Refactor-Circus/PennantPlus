# @refactor-circus/pennantplus-client

A typed fetch client for the PennantPlus JSON API, built on [openapi-fetch](https://openapi-ts.dev/openapi-fetch/). Its types are generated from the package's `openapi.json`.

## Install

The client is not published to a registry. Install it from the PennantPlus repository on the v2 tag; the `prepare` script builds `dist/` on install. pnpm can install the `client/` folder directly:

```sh
pnpm add "github:Refactor-Circus/PennantPlus#v2.0.0&path:/client"
```

## Use

```ts
import { createPennantPlusClient } from '@refactor-circus/pennantplus-client';

const pennant = createPennantPlusClient({ baseUrl: '/pennantplus' });

const { data, error } = await pennant.GET('/features');
```

`createPennantPlusClient()` takes any openapi-fetch option (`baseUrl`, `headers`, `credentials`, a custom `fetch`). The `paths`, `components` and `operations` types are exported too.

## Regenerate

`openapi.json` and `src/schema.d.ts` are generated; do not edit them. From the package root:

```sh
npm --prefix client ci
composer client:generate
```
