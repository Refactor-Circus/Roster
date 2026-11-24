import { integrationData, useAtriumIntegration } from '@refactor-circus/atrium'
import type { AtriumSearchResult, AtriumSearchSourceResolver } from '@refactor-circus/atrium'

/*
 * Roster's two ⌘K sources, keeping their v1 keys (AC 62, D-030). Each asks
 * Roster's own typeahead for at most `search.per_source` results, which match
 * from the start of a name, email or slug, as v1's did. A source whose API
 * answers 403 (the user may not list users or organizations) rejects, and
 * the search drops it.
 */

/** A path segment, encoded. */
function segment(value: unknown): string {
  return encodeURIComponent(String(value))
}

/** `roster-users`: `roster.search.users`, each result linking to `/roster/users/:id`. */
export const searchRosterUsers: AtriumSearchSourceResolver = async (q, { limit }) => {
  const { client } = useAtriumIntegration('roster')

  if (!client) {
    return []
  }

  const { data } = integrationData(await client.GET('/search/users', { params: { query: { q, limit } } }))

  return data.map((user): AtriumSearchResult => ({
    title: user.name || user.email || String(user.id),
    subtitle: user.email || null,
    icon: null,
    group: null,
    link: { path: `/roster/users/${segment(user.id)}`, external: false }
  }))
}

/** `roster-organizations`: `roster.search.organizations`, each result linking to `/roster/organizations/:slug`. */
export const searchRosterOrganizations: AtriumSearchSourceResolver = async (q, { limit }) => {
  const { client } = useAtriumIntegration('roster')

  if (!client) {
    return []
  }

  const { data } = integrationData(await client.GET('/search/organizations', { params: { query: { q, limit } } }))

  return data.map((organization): AtriumSearchResult => ({
    title: organization.name,
    subtitle: organization.slug,
    icon: null,
    group: null,
    link: { path: `/roster/organizations/${segment(organization.slug)}`, external: false }
  }))
}
