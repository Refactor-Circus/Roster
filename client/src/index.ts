import createClient, { type Client, type ClientOptions } from 'openapi-fetch';
import type { paths } from './schema';

export type { components, operations, paths } from './schema';

export interface RosterClientOptions {
  /**
   * The host's API base URL, its `roster.routes.prefix` included
   * (e.g. `https://app.test/roster`): the document's paths are relative to it.
   */
  baseUrl: string;
  /** A fetch implementation to use instead of `globalThis.fetch`. */
  fetch?: ClientOptions['fetch'];
  /** Headers sent with every request, e.g. an `Authorization` header. */
  headers?: ClientOptions['headers'];
  /** Whether cookies are sent, e.g. `include` for a cookie-authenticated host. */
  credentials?: ClientOptions['credentials'];
}

export type RosterClient = Client<paths>;

/**
 * A typed openapi-fetch client for the Roster JSON API:
 * `client.GET('/search/users', { params: { query: { q: 'ada' } } })`.
 */
export function createRosterClient(options: RosterClientOptions): RosterClient {
  return createClient<paths>(options);
}
