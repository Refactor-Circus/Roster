import { vi } from 'vitest'
import type { components } from '../../../client/src/schema'
import type {
  RosterImpersonation,
  RosterUser
} from '../../src/components/users/users'
import type { AtriumIntegrationScreenData } from '@refactor-circus/atrium'
import { settle } from '@refactor-circus/atrium/testing'
import { mountIntegration, packageCalls, type PackageCall } from '@refactor-circus/atrium/testing'

/*
 * Shared pieces of the Roster users and impersonations suites (AC 54, 69):
 * payloads typed with `satisfies` against the vendored `components` (or,
 * where Scramble types a resource loosely, against the shape the page reads,
 * `users.ts`), the `atrium.screens.show` answer, and a page mounter.
 *
 * Each suite mocks `useAtriumApi` itself (`mockAtriumApi()`, per file) and
 * answers `/screens/{key}` with `screensApi`.
 */

type Schemas = components['schemas']

export const ada = {
  id: 7,
  name: 'Ada Lovelace',
  email: 'ada@example.com',
  status: 'active',
  status_reason: null,
  status_changed_at: null,
  profile: { display_name: 'Ada L.', avatar_url: null, timezone: 'Europe/London', locale: 'en', bio: null, meta: {} },
  current_organization: 'acme',
  current_team: null,
  created_at: '2026-11-01T10:00:00+00:00'
} satisfies RosterUser

export const grace = {
  id: 8,
  name: 'Grace Hopper',
  email: 'grace@example.com',
  status: 'pending',
  status_reason: null,
  status_changed_at: null,
  profile: { display_name: null, avatar_url: null, timezone: null, locale: null, bio: null, meta: {} },
  current_organization: null,
  current_team: null,
  created_at: '2026-11-02T10:00:00+00:00'
} satisfies RosterUser

export const acme = {
  name: 'Acme',
  slug: 'acme',
  status: 'active',
  status_reason: null,
  status_changed_at: null,
  provisioned_status: 'active',
  personal: false,
  auto_join: false,
  owner: null,
  links: [],
  created_at: '2026-10-01T10:00:00+00:00'
} satisfies Schemas['OrganizationResource']

export const globex = {
  ...acme,
  name: 'Globex',
  slug: 'globex'
} satisfies Schemas['OrganizationResource']

export const ops = {
  name: 'Ops',
  slug: 'ops',
  organization: 'acme',
  status: 'active',
  status_reason: null,
  status_changed_at: null,
  created_at: '2026-10-01T10:00:00+00:00'
} satisfies Schemas['TeamResource']

export const impersonation = {
  id: '01JIMPERSONATION',
  user: { id: 7, name: 'Ada Lovelace', email: 'ada@example.com' },
  impersonator: { id: 1, name: 'Root', email: 'root@example.com' },
  organization: null,
  reason: 'Ticket 42',
  active: true,
  link_expires_at: '2026-11-23T10:05:00+00:00',
  started_at: '2026-11-23T10:01:00+00:00',
  expires_at: '2026-11-23T11:01:00+00:00',
  ended_at: null,
  end_reason: null
} satisfies RosterImpersonation

export const endedImpersonation = {
  ...impersonation,
  id: '01JENDED',
  active: false,
  ended_at: '2026-11-22T10:00:00+00:00',
  end_reason: 'stopped'
} satisfies RosterImpersonation

/** A page of a paged list, as Laravel's paginator answers. */
export function page<T>(data: T[], current = 1, last = 1): { data: T[], meta: { current_page: number, last_page: number, total: number } } {
  return { data, meta: { current_page: current, last_page: last, total: data.length } }
}

/** The `atrium.screens.show` answer for a Roster screen with no extensions. */
export function screenOf(key: string, record: string | null = null): { data: AtriumIntegrationScreenData } {
  return {
    data: {
      key,
      kind: record === null ? 'list' : 'detail',
      path: '/roster',
      subject_type: null,
      record,
      open_tab: 'overview',
      tabs: [],
      sections: {},
      rows: {}
    }
  }
}

/** A `useAtriumApi()` stand-in answering `/screens/{key}` with `screenOf()`. */
export function screensApi(path: string, options?: { query?: Record<string, unknown> }): Promise<unknown> {
  const match = /^\/screens\/(.+)$/.exec(path)
  const record = options?.query?.record

  return match
    ? Promise.resolve(screenOf(decodeURIComponent(match[1]!), typeof record === 'string' ? record : null))
    : Promise.reject(new Error(`Unexpected Atrium API call: ${path}`))
}

const mounted: { unmount: () => void }[] = []

/** Mounts a Roster page at `route`, with Roster active in the shell, and lets it load. */
export async function mountRosterPage<T>(page: T, route: string) {
  const wrapper = await mountIntegration(page, 'roster', { route })
  mounted.push(wrapper)
  await settle()

  return wrapper
}

/**
 * Unmounts every page `mountRosterPage()` mounted and drops the stubbed
 * globals. Call it in `afterEach`: a page left mounted keeps watching the
 * route and reloads against the next test's mocks.
 */
export function resetRosterPages(): void {
  for (const wrapper of mounted.splice(0)) {
    wrapper.unmount()
  }

  vi.unstubAllGlobals()
}

/** The Roster calls with `method` to the route template `route` (`/users/{user}`). */
export function rosterCalls(method: string, route: string): PackageCall[] {
  return packageCalls.filter(call => call.pkg === 'roster' && call.method === method && call.route === route)
}

/**
 * Answers every `window.confirm()` with `answer` (happy-dom has no
 * `confirm` to spy on, so it is stubbed; `resetRosterPages()` drops it).
 */
export function confirmWith(answer: boolean) {
  const confirm = vi.fn<(message?: string) => boolean>().mockReturnValue(answer)
  vi.stubGlobal('confirm', confirm)

  return confirm
}
