import createClient, { type Client, type ClientOptions } from 'openapi-fetch';
// tsc never copies the generated schema.d.ts into dist/, so the specifier
// points back at the committed file: it resolves from src/ and from the
// emitted dist/index.d.ts alike.
import type { paths } from '../src/schema.js';
export type { paths, components, operations } from '../src/schema.js';

/**
 * A typed fetch client for the PennantPlus JSON API. Set `baseUrl` to the
 * API's prefix, `/pennantplus` by default, and pass any other openapi-fetch
 * option (headers, credentials, a custom fetch) through.
 */
export function createPennantPlusClient(options: ClientOptions = {}): Client<paths> {
  return createClient<paths>(options);
}
