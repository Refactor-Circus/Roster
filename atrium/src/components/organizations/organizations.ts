import type { components } from '../../../../client/src/schema'
import type { RosterUserSummary } from '../users/users'

/*
 * What the Roster organization, team and invitation screens share (AC 54,
 * 69): their payload types and small helpers. Loading, paging and actions
 * come from the users area (`../users/users`), which every Roster screen
 * shares.
 *
 * The types come from the vendored `client/schema.d.ts` where it is right.
 * Scramble types some of Roster's resources loosely (a member's and an
 * assignment's `user` as `unknown[]`, a team's `members` as `unknown[][]`, an
 * invitation's `teams` as `string | {}`), so those are declared here as
 * Roster's resources build them; the gaps are Roster's to fix in its
 * `schema()` declarations.
 */

type Schemas = components['schemas']

export type RosterOrganizationStatus = Schemas['OrganizationStatus']
export type RosterTeamStatus = Schemas['TeamStatus']
export type RosterInvitationStatus = Schemas['InvitationStatus']
export type RosterOrganizationLink = Schemas['OrganizationResource']['links'][number]

/** Roster's default organization statuses (`roster.statuses.organizations`). */
export const ORGANIZATION_STATUSES: readonly RosterOrganizationStatus[] = ['active', 'inactive']

/** Roster's default team statuses (`roster.statuses.teams`). */
export const TEAM_STATUSES: readonly RosterTeamStatus[] = ['active', 'inactive']

/** The statuses an organization gives its externally provisioned accounts. */
export const PROVISIONED_STATUSES = ['active', 'pending'] as const

/** `MemberResource` (the schema types its `user` as `unknown[]`). */
export interface RosterMember {
  user: RosterUserSummary | null
  owner: boolean
  status: string | null
  source: string
  teams?: string[]
  joined_at: string | null
}

/** `TeamResource` with its members, as `teams.show` answers. */
export interface RosterTeamDetail {
  slug: string
  name: string
  organization: string
  status: string
  status_reason: string | null
  status_changed_at: string | null
  members_count?: number
  members?: RosterUserSummary[]
  created_at: string | null
}

/** `InvitationResource` (the schema types its `teams` as `string | {}`): team slugs. */
export interface RosterInvitation {
  id: string
  email: string
  organization: string
  teams: string[]
  status: RosterInvitationStatus
  expires_at: string
  accepted_at: string | null
  declined_at: string | null
  revoked_at: string | null
  created_at: string | null
}

/** The answer of `organizations.purge`: it is queued. */
export interface RosterQueued {
  message: string
}

/** The built-in tabs of the organization page, in v1's order; the first is the default. */
export const ORGANIZATION_TABS = ['members', 'teams', 'invitations', 'settings'] as const

export type RosterOrganizationTab = typeof ORGANIZATION_TABS[number]

/** The built-in tab a `?tab=` value names, or the default one. */
export function organizationTab(value: string | undefined): RosterOrganizationTab {
  return (ORGANIZATION_TABS as readonly string[]).includes(value ?? '') ? value as RosterOrganizationTab : 'members'
}

/** The layer path of an organization's page, optionally on one of its tabs. */
export function organizationPath(slug: string, tab?: RosterOrganizationTab): string {
  const path = `/roster/organizations/${encodeURIComponent(slug)}`

  return tab && tab !== 'members' ? `${path}?tab=${tab}` : path
}

/** The layer path of a team's page. */
export function teamPath(organization: string, team: string): string {
  return `/roster/organizations/${encodeURIComponent(organization)}/teams/${encodeURIComponent(team)}`
}

/** The domains of v1's textarea, one per line (or comma), blanks left out. */
export function domainsFrom(text: string): string[] {
  return text.split(/[\n,]/).map(domain => domain.trim()).filter(domain => domain !== '')
}

/** The name a member or assignee shows under. */
export function summaryName(user: RosterUserSummary | null | undefined): string | null {
  return user ? user.name || user.email || null : null
}

/** The badge colour of an organization, team or invitation status. */
export function recordStatusColor(status: string | null | undefined): 'success' | 'warning' | 'error' | 'info' | 'neutral' {
  switch (status) {
    case 'active':
    case 'accepted':
      return 'success'
    case 'pending':
      return 'info'
    case 'expired':
    case 'inactive':
      return 'warning'
    case 'declined':
    case 'revoked':
      return 'error'
    default:
      return 'neutral'
  }
}
