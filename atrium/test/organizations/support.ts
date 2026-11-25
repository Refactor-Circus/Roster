import type { components } from '../../../client/src/schema'
import type { RosterInvitationLookup } from '../../src/components/invitation/invitation'
import type {
  RosterInvitation,
  RosterMember,
  RosterTeamDetail
} from '../../src/components/organizations/organizations'
import type { RosterUserSummary } from '../../src/components/users/users'
import { acme, ops } from '../users/support'

/*
 * Shared payloads of the Roster organization, team and invitation suites
 * (AC 54, 69, 70), typed with `satisfies` against the vendored `components`
 * or, where Scramble types a resource loosely (a member's `user`, an
 * invitation's `teams`), against the shape the page reads
 * (`organizations.ts`, `invitation.ts`). The page mounter, the screen answer
 * and the call filters come from the users area's `../users/support`.
 */

type Schemas = components['schemas']

export { acme, ops }

export const acmeDetail = {
  ...acme,
  owner: { id: 7, name: 'Ada Lovelace', email: 'ada@example.com' },
  domains: ['acme.test'],
  members_count: 2,
  teams_count: 1,
  links: [{ source: 'crm', external_id: 'C-1', account_number: 'A-100', synced_at: null }]
} satisfies Schemas['OrganizationResource']

export const umbrella = {
  ...acme,
  name: 'Umbrella',
  slug: 'umbrella'
} satisfies Schemas['OrganizationResource']

export const adaSummary = { id: 7, name: 'Ada Lovelace', email: 'ada@example.com' } satisfies RosterUserSummary
export const graceSummary = { id: 8, name: 'Grace Hopper', email: 'grace@example.com' } satisfies RosterUserSummary
export const linusSummary = { id: 9, name: 'Linus', email: 'linus@example.com' } satisfies RosterUserSummary

export const owner = {
  user: adaSummary,
  owner: true,
  status: 'active',
  source: 'manual',
  teams: ['ops'],
  joined_at: '2026-10-01T10:00:00+00:00'
} satisfies RosterMember

export const pendingMember = {
  user: graceSummary,
  owner: false,
  status: 'pending',
  source: 'domain',
  teams: [],
  joined_at: '2026-10-02T10:00:00+00:00'
} satisfies RosterMember

export const linusMember = {
  ...pendingMember,
  user: linusSummary,
  status: 'active'
} satisfies RosterMember

export const opsDetail = {
  ...ops,
  members_count: 1,
  members: [adaSummary]
} satisfies RosterTeamDetail

export const invitation = {
  id: '01JINVITATION',
  email: 'new@example.com',
  organization: 'acme',
  teams: ['ops'],
  status: 'pending',
  expires_at: '2026-12-01T10:00:00+00:00',
  accepted_at: null,
  declined_at: null,
  revoked_at: null,
  created_at: '2026-11-20T10:00:00+00:00'
} satisfies RosterInvitation

export const acceptedInvitation = {
  ...invitation,
  id: '01JACCEPTED',
  status: 'accepted',
  accepted_at: '2026-11-21T10:00:00+00:00'
} satisfies RosterInvitation

export const lookup = {
  email: 'new@example.com',
  organization: { name: 'Acme' },
  teams: ['Ops', 'Sales'],
  expires_at: '2026-12-01T10:00:00+00:00'
} satisfies RosterInvitationLookup

/** The token of the public invitation page's tests. */
export const TOKEN = 'tok-3f9a1c7e5b2d4086'
