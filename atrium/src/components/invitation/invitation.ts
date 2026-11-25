import createClient from 'openapi-fetch'
import type { components, paths } from '../../../../client/src/schema'
import { atriumFetch, resolveIntegrationBase, useAtriumBoot, useAtriumShellStore } from '@refactor-circus/atrium'
import type { RosterClient } from '../users/users'

/*
 * The public invitation page (AC 70, D-038). The invitee is usually signed
 * out, so the page loads no shell: it reaches Roster through a client of its
 * own, not `useAtriumIntegration()` (which hands out a client only for an
 * integration the shell lists as active). Its active check is the lookup.
 */

/** `InvitationLookupResource` (the schema types its `teams` as `string | {}`): team names. */
export interface RosterInvitationLookup {
  email: string
  organization: { name: string }
  teams: string[]
  expires_at: string
}

/** `InvitationResource`, as accept and decline answer. */
export type RosterAnsweredInvitation = components['schemas']['InvitationResource']

/**
 * Roster's API base when nothing else names it: its default
 * `roster.routes.prefix`, as Atrium derives an integration's `api_base`
 * from it (AC 40).
 */
export const ROSTER_DEFAULT_API_BASE = '/roster'

/**
 * Where the page finds Roster's API, in the order of D-021, without loading
 * the shell: the `api_base` the Blade page's boot data lists for Roster;
 * else the `api_base` of a shell this app already loaded (active or not);
 * else Roster's default prefix. A path resolves as an integration's does.
 */
export function invitationApiBase(override: string | undefined, shellBase: string | null | undefined, atriumApiBase: string, pageOrigin: string): string {
  return resolveIntegrationBase(override || shellBase || ROSTER_DEFAULT_API_BASE, atriumApiBase, pageOrigin)
}

/**
 * A Roster client for the public invitation page. Each request is signed as
 * every Atrium client's is (`atriumFetch`: the auth adapter's headers and
 * credentials, or the page's session, and `Accept: application/json`), so a
 * signed-in invitee answers as themselves. The page is public, so a 401
 * calls nothing: the page decides when to send the visitor to sign in
 * (never for the lookup).
 */
export function useInvitationClient(): RosterClient {
  const boot = useAtriumBoot()
  const shellBase = useAtriumShellStore().integration('roster')?.api_base

  return createClient<paths>({
    baseUrl: invitationApiBase(boot.integrations.roster?.api_base, shellBase, boot.api_base, window.location.origin),
    fetch: atriumFetch
  })
}
