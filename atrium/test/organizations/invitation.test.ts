import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/invitations/[token].vue'
import { screens } from '../../src/atrium'
import { settle, withSetup } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { rosterCalls } from '../users/support'
import { invitation, lookup, TOKEN } from './support'
import { useAtrium, useAtriumShellStore } from '@refactor-circus/atrium'
import { clearAtriumData, mockAtriumApi, mountSuspended, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `roster.invitation`, the public invitation page (AC 70, D-038). It is
 * declared public, so it takes the public layout. It loads no shell, and calls
 * no `atrium.screens.show`. A 401 on the lookup never calls
 * `onUnauthorized()`. It renders the invitation's email, organization, teams
 * and expiry; one generic state for any 404, another for a 429. Accept and
 * decline call `roster.invitations.accept` / `.decline`; a visitor who is not
 * signed in (Roster answers 401) is sent to sign in through
 * `onUnauthorized()` before anything is answered. The token never reaches
 * `document.title`, storage or the console.
 *
 * With no shell, the page finds Roster at its default prefix, `/roster`.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const ROUTE = `/roster/invitations/${TOKEN}`

const onUnauthorized = vi.fn()

function rosterWith(routes: PackageRoutes<'roster'>): void {
  mockPackageApi('roster', routes, '/roster')
}

const mounted: { unmount: () => void }[] = []

async function mountPage() {
  const wrapper = await mountSuspended(Page, { route: ROUTE })
  mounted.push(wrapper)
  await settle()

  return wrapper
}

describe('roster.invitation', () => {
  beforeEach(async () => {
    clearAtriumData()
    api.mockReset()
    onUnauthorized.mockReset()
    localStorage.clear()
    sessionStorage.clear()

    const store = await withSetup(() => useAtriumShellStore())
    store.shell = null

    const atrium = await withSetup(() => useAtrium())
    atrium.auth({ onUnauthorized })
  })

  afterEach(async () => {
    for (const wrapper of mounted.splice(0)) {
      wrapper.unmount()
    }

    const atrium = await withSetup(() => useAtrium())
    atrium.auth({})
    resetPackageApis()
    vi.restoreAllMocks()
    await useRouter().replace('/')
  })

  it('is declared public, in the public layout, at the path the screens map names', () => {
    const route = useRouter().getRoutes().find(candidate => candidate.name === screens['roster.invitation'])

    // A public route renders in the public layout (no Nuxt `meta.layout`).
    expect(route?.path).toBe('/roster/invitations/:token')
    expect(route?.meta.atrium).toEqual({ public: true })
  })

  it('looks the invitation up without loading the shell, and renders its details', async () => {
    rosterWith({ 'GET /invitations/{token}': { data: lookup } as never })

    const wrapper = await mountPage()

    expect(api).not.toHaveBeenCalled()
    expect((await withSetup(() => useAtriumShellStore())).shell).toBeNull()

    const [call] = rosterCalls('GET', '/invitations/{token}')
    expect(call?.path).toBe(`/invitations/${TOKEN}`)
    expect(call?.headers.get('Accept')).toBe('application/json')

    expect(wrapper.find('[data-testid="roster-invitation-heading"]').text()).toContain('Acme')
    expect(wrapper.find('[data-testid="roster-invitation-email"]').text()).toBe('new@example.com')
    expect(wrapper.find('[data-testid="roster-invitation-organization"]').text()).toBe('Acme')
    expect(wrapper.find('[data-testid="roster-invitation-teams"]').text()).toBe('Ops, Sales')
    expect(wrapper.find('[data-testid="roster-invitation-expires"]').text()).not.toBe('')
  })

  it('does not call onUnauthorized() when the lookup answers 401', async () => {
    rosterWith({ 'GET /invitations/{token}': reply(401) })

    const wrapper = await mountPage()

    expect(rosterCalls('GET', '/invitations/{token}')).toHaveLength(1)
    expect(onUnauthorized).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="roster-invitation-details"]').exists()).toBe(false)
  })

  it('shows one generic not-available state for any 404', async () => {
    rosterWith({ 'GET /invitations/{token}': reply(404, { message: 'Invitation expired.' }) })

    const wrapper = await mountPage()
    const state = wrapper.find('[data-testid="roster-invitation-unavailable"]')

    expect(state.exists()).toBe(true)
    expect(state.text()).toContain('This invitation is not available.')
    expect(state.text()).not.toContain('Invitation expired.')
    expect(wrapper.find('[data-testid="roster-invitation-details"]').exists()).toBe(false)
  })

  it('shows a too-many-attempts state for a 429', async () => {
    rosterWith({ 'GET /invitations/{token}': reply(429) })

    const wrapper = await mountPage()

    expect(wrapper.find('[data-testid="roster-invitation-throttled"]').text()).toContain('Too many attempts.')
    expect(wrapper.find('[data-testid="roster-invitation-unavailable"]').exists()).toBe(false)
  })

  it('accepts as the signed-in visitor', async () => {
    rosterWith({
      'GET /invitations/{token}': { data: lookup } as never,
      'POST /invitations/{token}/accept': { data: { ...invitation, status: 'accepted' } } as never
    })

    const wrapper = await mountPage()
    await wrapper.find('[data-testid="roster-invitation-accept"]').trigger('click')
    await settle()

    const [call] = rosterCalls('POST', '/invitations/{token}/accept')
    expect(call?.path).toBe(`/invitations/${TOKEN}/accept`)
    expect(call?.body).toEqual({ token: TOKEN })
    expect(onUnauthorized).not.toHaveBeenCalled()
    expect(wrapper.find('[data-testid="roster-invitation-accepted"]').text()).toContain('Acme')
  })

  it('declines as the signed-in visitor', async () => {
    rosterWith({
      'GET /invitations/{token}': { data: lookup } as never,
      'POST /invitations/{token}/decline': { data: { ...invitation, status: 'declined' } } as never
    })

    const wrapper = await mountPage()
    await wrapper.find('[data-testid="roster-invitation-decline"]').trigger('click')
    await settle()

    const [call] = rosterCalls('POST', '/invitations/{token}/decline')
    expect(call?.path).toBe(`/invitations/${TOKEN}/decline`)
    expect(call?.body).toEqual({ token: TOKEN })
    expect(wrapper.find('[data-testid="roster-invitation-declined"]').exists()).toBe(true)
  })

  it.each(['accept', 'decline'] as const)('calls onUnauthorized() first when a signed-out visitor chooses %s', async (verb) => {
    rosterWith({
      'GET /invitations/{token}': { data: lookup } as never,
      'POST /invitations/{token}/accept': reply(401),
      'POST /invitations/{token}/decline': reply(401)
    })

    const wrapper = await mountPage()
    await wrapper.find(`[data-testid="roster-invitation-${verb}"]`).trigger('click')
    await settle()

    expect(onUnauthorized).toHaveBeenCalledTimes(1)
    expect(wrapper.find('[data-testid="roster-invitation-accepted"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="roster-invitation-declined"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="roster-invitation-details"]').exists()).toBe(true)
  })

  it('never writes the token to the title, storage or the console', async () => {
    const consoleCalls = (['log', 'info', 'warn', 'error', 'debug'] as const).map(method => vi.spyOn(console, method))
    rosterWith({
      'GET /invitations/{token}': { data: lookup } as never,
      'POST /invitations/{token}/accept': { data: { ...invitation, status: 'accepted' } } as never
    })

    const wrapper = await mountPage()
    await wrapper.find('[data-testid="roster-invitation-accept"]').trigger('click')
    await settle()

    const stored = [localStorage, sessionStorage].flatMap(storage => Array.from({ length: storage.length }, (_, index) => {
      const key = storage.key(index) ?? ''

      return `${key}=${storage.getItem(key) ?? ''}`
    }))
    const logged = consoleCalls.flatMap(spy => spy.mock.calls.map(args => args.map(String).join(' ')))

    expect(document.title).not.toContain(TOKEN)
    expect(stored.join('\n')).not.toContain(TOKEN)
    expect(logged.join('\n')).not.toContain(TOKEN)
  })
})
