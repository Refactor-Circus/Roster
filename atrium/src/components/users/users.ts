import { ref, shallowRef, watch } from 'vue'
import type { Ref, ShallowRef, WatchSource } from 'vue'
import type { components } from '../../../../client/src/schema'
import { AtriumIntegrationError, useAtriumIntegration, type AtriumIntegrationClient } from '@refactor-circus/atrium'
import { atriumStatus } from '@refactor-circus/atrium'

/*
 * What the Roster users and impersonations pages share (AC 54, 69): the
 * payload types, the client, a loader, a page-paged list and an action
 * runner (field errors on a 422, no access on a 403).
 *
 * The types come from the vendored `client/schema.d.ts` where it is right.
 * Scramble types some of Roster's resources loosely (a user's `id` as
 * `unknown`, its `profile` and an impersonation's users as `unknown[]`), so
 * those are declared here as Roster's resources build them; the gaps are
 * Roster's to fix in its `schema()` declarations.
 */

type Schemas = components['schemas']

export type RosterClient = AtriumIntegrationClient<'roster'>
export type RosterUserStatus = Schemas['UserStatus']
export type RosterOrganization = Schemas['OrganizationResource']
export type RosterTeam = Schemas['TeamResource']

/** Every user status, in Roster's order (`roster.statuses.users`). */
export const USER_STATUSES: readonly RosterUserStatus[] = ['active', 'pending', 'suspended', 'deactivated']

/** `ProfileResource`. */
export interface RosterProfile {
  display_name: string | null
  avatar_url: string | null
  timezone: string | null
  locale: string | null
  bio: string | null
  meta: Record<string, unknown>
}

/** `UserResource` (the schema types `id` as `unknown` and `profile` as `unknown[]`). */
export interface RosterUser {
  id: string | number
  name: string | null
  email: string | null
  status: RosterUserStatus
  status_reason: string | null
  status_changed_at: string | null
  profile: RosterProfile
  current_organization: string | null
  current_team: string | null
  created_at: string | null
}

/** `UserSummaryResource`. */
export interface RosterUserSummary {
  id: string | number
  name: string | null
  email: string | null
}

/** `ImpersonationResource` (the schema types its two users as `unknown[]`). */
export interface RosterImpersonation {
  id: string
  user: RosterUserSummary | null
  impersonator: RosterUserSummary | null
  organization: string | null
  reason: string
  active: boolean
  link_expires_at: string
  started_at: string | null
  expires_at: string | null
  ended_at: string | null
  end_reason: string | null
}

/** The answer of `users.impersonate`: the impersonation and its one-time link. */
export interface RosterImpersonationStarted {
  data: RosterImpersonation
  url: string
}

/** Laravel's page paginator, as Roster's list endpoints answer. */
export interface RosterPage<T> {
  data: T[]
  meta?: { current_page?: number, last_page?: number, total?: number }
}

/** One record under `data`. */
export interface RosterOne<T> {
  data: T
}

/** The fields of the profile forms (create and the user page), as v1's. */
export interface RosterProfileForm {
  display_name: string
  avatar_url: string
  timezone: string
  locale: string
  bio: string
}

/** The profile fields, blank or from a profile. */
export function profileForm(profile?: RosterProfile | null): RosterProfileForm {
  return {
    display_name: profile?.display_name ?? '',
    avatar_url: profile?.avatar_url ?? '',
    timezone: profile?.timezone ?? '',
    locale: profile?.locale ?? '',
    bio: profile?.bio ?? ''
  }
}

/** The profile fields as the API takes them: a blank field is null. */
export function profileBody(form: RosterProfileForm): Record<keyof RosterProfileForm, string | null> {
  return {
    display_name: blankToNull(form.display_name),
    avatar_url: blankToNull(form.avatar_url),
    timezone: blankToNull(form.timezone),
    locale: blankToNull(form.locale),
    bio: blankToNull(form.bio)
  }
}

/** A trimmed form value, or null when blank. */
export function blankToNull(value: string | null | undefined): string | null {
  const trimmed = (value ?? '').trim()

  return trimmed === '' ? null : trimmed
}

/** The name a user shows under: the profile's display name, else the name or email. */
export function userName(user: RosterUser | RosterUserSummary | null | undefined): string | null {
  if (!user) {
    return null
  }

  const display = 'profile' in user ? user.profile?.display_name : null

  return display || user.name || user.email || null
}

/** The layer path of a user's page. */
export function userPath(id: string | number): string {
  return `/roster/users/${encodeURIComponent(String(id))}`
}

/** A timestamp as the user's locale shows it, or null. */
export function formatDate(value: string | null | undefined): string | null {
  if (!value) {
    return null
  }

  const date = new Date(value)

  return Number.isNaN(date.getTime()) ? value : date.toLocaleString()
}

/** A route query value as one string, or undefined. */
export function queryValue(value: unknown): string | undefined {
  const first = Array.isArray(value) ? value[0] : value

  return typeof first === 'string' && first !== '' ? first : undefined
}

/** A route query value as a page number (1 when absent or invalid). */
export function queryPage(value: unknown): number {
  const page = Number(queryValue(value))

  return Number.isInteger(page) && page > 1 ? page : 1
}

/** The badge colour of a user status. */
export function statusColor(status: string | null | undefined): 'success' | 'warning' | 'error' | 'info' | 'neutral' {
  switch (status) {
    case 'active':
      return 'success'
    case 'pending':
      return 'info'
    case 'suspended':
      return 'warning'
    case 'deactivated':
      return 'error'
    default:
      return 'neutral'
  }
}

/**
 * A request body the vendored schema does not type (Scramble documents
 * `users.update` without one), sent as Roster validates it.
 */
export function untypedBody<T>(body: T): never {
  return body as never
}

/** The Roster client, signed by the app's auth adapter; null while Roster is not active. */
export function useRoster(): RosterClient | null {
  return useAtriumIntegration('roster').client
}

/**
 * The data of an `openapi-fetch` answer as `T`, or an `AtriumIntegrationError`
 * carrying its status and body. For the answers the vendored schema types
 * loosely, the page names the shape Roster really sends.
 */
export function rosterData<T>(result: { data?: unknown, error?: unknown, response: Response }): T {
  if (!result.response.ok) {
    throw new AtriumIntegrationError(result.response.status, result.error)
  }

  return result.data as T
}

export interface RosterLoad<T> {
  client: RosterClient | null
  data: ShallowRef<T | null>
  /** True only while there is nothing to show yet, so a reload keeps forms mounted */
  pending: Ref<boolean>
  failed: Ref<boolean>
  /** The HTTP status of a failed load: 403 shows no access, 404 not found */
  status: Ref<number | null>
  reload: () => Promise<void>
}

/**
 * Loads what a page (or one of its cards) shows from Roster, and again on
 * `reload()` or whenever `source` changes.
 */
export function useRosterLoad<T>(load: (client: RosterClient) => Promise<T>, source?: WatchSource): RosterLoad<T> {
  const client = useRoster()
  const data = shallowRef<T | null>(null)
  const pending = ref(true)
  const failed = ref(false)
  const status = ref<number | null>(null)

  async function reload(): Promise<void> {
    if (!client) {
      pending.value = false
      return
    }

    pending.value = data.value === null

    try {
      data.value = await load(client)
      failed.value = false
      status.value = null
    } catch (error) {
      failed.value = true
      status.value = atriumStatus(error)
    } finally {
      pending.value = false
    }
  }

  if (source) {
    watch(source, () => void reload(), { deep: true })
  }

  void reload()

  return { client, data, pending, failed, status, reload }
}

/**
 * The teams of the organization `organization()` names (none while it names
 * none), for a team picker; loaded again when it changes. A failure leaves
 * the picker empty: the form still works without a team.
 */
export function useOrganizationTeams(organization: () => string | null): ShallowRef<RosterTeam[]> {
  const client = useRoster()
  const teams = shallowRef<RosterTeam[]>([])

  async function load(slug: string | null): Promise<void> {
    if (!client || !slug) {
      teams.value = []
      return
    }

    try {
      teams.value = rosterData<RosterPage<RosterTeam>>(await client.GET('/organizations/{organization}/teams', {
        params: { path: { organization: slug }, query: { per_page: 100 } }
      })).data
    } catch {
      teams.value = []
    }
  }

  watch(organization, slug => void load(slug), { immediate: true })

  return teams
}

/** The page numbers of a paged answer. */
export function pageOf(page: RosterPage<unknown> | null): { current: number, last: number } {
  return {
    current: page?.meta?.current_page ?? 1,
    last: page?.meta?.last_page ?? 1
  }
}

export interface RosterAction {
  busy: Ref<boolean>
  /** The field errors of the last 422, by field */
  errors: Ref<Record<string, string[]>>
  /** The last action answered 403 */
  forbidden: Ref<boolean>
  /** Why the last action failed, other than a 422 or 403 */
  message: Ref<string | null>
  /** Runs `action`; resolves whether it succeeded, so the page reloads only then */
  run: (action: () => Promise<unknown>) => Promise<boolean>
  /** The first error of `field` */
  error: (field: string) => string | undefined
}

/**
 * Runs a mutation against Roster. A 422 fills `errors` (by field), a 403 sets
 * `forbidden`, and any other failure sets `message`.
 */
export function useRosterAction(): RosterAction {
  const busy = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const forbidden = ref(false)
  const message = ref<string | null>(null)

  async function run(action: () => Promise<unknown>): Promise<boolean> {
    busy.value = true
    errors.value = {}
    forbidden.value = false
    message.value = null

    try {
      await action()
      return true
    } catch (error) {
      const status = atriumStatus(error)
      const body = (error instanceof AtriumIntegrationError ? error.body : null) as { message?: unknown, errors?: Record<string, string[]> } | null

      if (status === 403) {
        forbidden.value = true
      } else if (status === 422 && body?.errors) {
        errors.value = body.errors
      } else {
        message.value = typeof body?.message === 'string' ? body.message : error instanceof Error ? error.message : String(error)
      }

      return false
    } finally {
      busy.value = false
    }
  }

  function error(field: string): string | undefined {
    return errors.value[field]?.[0]
  }

  return { busy, errors, forbidden, message, run, error }
}
