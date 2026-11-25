import { afterEach, describe, expect, it } from 'vitest'
import type { VueWrapper } from '@vue/test-utils'
import OrganizationsWidget from '../src/components/widgets/OrganizationsWidget.vue'
import UserStatusWidget from '../src/components/widgets/UserStatusWidget.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mountIntegration, reply, resetPackageApis, type PackageBody, mockPackageApi } from '@refactor-circus/atrium/testing'
import fixture from '@refactor-circus/atrium/fixtures/integrations/roster.json'

/*
 * Roster's two widgets (AC 60): `roster.user-status` reads
 * `roster.stats.users` and `roster.organizations` reads
 * `roster.stats.organizations`. The PHP side sends `data: {}`; a 403 renders
 * the no-access card, any other failure the error card.
 */

// Roster's vendored schema types these counts as strings; its resources send
// integers, as here.
const userStats = {
  data: { total: 42, by_status: { active: 35, pending: 3, suspended: 2, deactivated: 2 } }
} as unknown as PackageBody<'roster', 'GET /stats/users'>

const organizationStats = {
  data: { organizations: 7, teams: 12, pending_invitations: 4 }
} as unknown as PackageBody<'roster', 'GET /stats/organizations'>

const mounted: VueWrapper[] = []

async function mount(component: typeof UserStatusWidget | typeof OrganizationsWidget, key: string) {
  const widget = fixture.widgets.find(candidate => candidate.key === key)!
  const wrapper = await mountIntegration(component, 'roster', { props: { data: widget.data, settings: {} } })
  mounted.push(wrapper as unknown as VueWrapper)
  await settle()

  return wrapper
}

afterEach(() => {
  // Unmounted, so the next test's widgets ask again rather than share.
  for (const wrapper of mounted.splice(0)) {
    try {
      wrapper.unmount()
    } catch {
      // Already unmounted.
    }
  }

  resetPackageApis()
})

describe('roster.user-status', () => {
  it('is declared with static data only', () => {
    expect(fixture.widgets.find(widget => widget.key === 'roster.user-status')?.data).toEqual({})
  })

  it('renders the total and each status from roster.stats.users', async () => {
    const calls = mockPackageApi('roster', { 'GET /stats/users': userStats })

    const wrapper = await mount(UserStatusWidget, 'roster.user-status')

    expect(calls.map(call => `${call.method} ${call.route}`)).toEqual(['GET /stats/users'])
    expect(wrapper.find('[data-testid="roster-user-status-total"]').text()).toBe('42')

    const rows = Object.fromEntries(wrapper.findAll('[data-testid="roster-user-status-row"]').map(row => [row.attributes('data-status'), row.text()]))

    expect(rows).toEqual({
      active: 'Active35',
      pending: 'Pending approval3',
      suspended: 'Suspended2',
      deactivated: 'Deactivated2'
    })
  })

  it('shows a status it has no label for by its key', async () => {
    mockPackageApi('roster', {
      'GET /stats/users': { data: { total: 1, by_status: { archived: 1 } } } as unknown as PackageBody<'roster', 'GET /stats/users'>
    })

    const wrapper = await mount(UserStatusWidget, 'roster.user-status')

    expect(wrapper.find('[data-status="archived"]').text()).toBe('archived1')
  })

  it('renders the no-access card on a 403', async () => {
    mockPackageApi('roster', { 'GET /stats/users': reply(403) })

    const wrapper = await mount(UserStatusWidget, 'roster.user-status')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-user-status-total"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="atrium-error-card"]').exists()).toBe(false)
  })

  it('renders the error card on any other failure', async () => {
    mockPackageApi('roster', { 'GET /stats/users': reply(500) })

    const wrapper = await mount(UserStatusWidget, 'roster.user-status')

    expect(wrapper.find('[data-testid="atrium-error-card"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(false)
  })
})

describe('roster.organizations', () => {
  it('is declared with static data only', () => {
    expect(fixture.widgets.find(widget => widget.key === 'roster.organizations')?.data).toEqual({})
  })

  it('renders the organization, team and pending invitation counts from roster.stats.organizations', async () => {
    const calls = mockPackageApi('roster', { 'GET /stats/organizations': organizationStats })

    const wrapper = await mount(OrganizationsWidget, 'roster.organizations')

    expect(calls.map(call => `${call.method} ${call.route}`)).toEqual(['GET /stats/organizations'])

    const counts = Object.fromEntries(wrapper.findAll('[data-testid="roster-organizations-count"]').map(count => [count.attributes('data-count'), count.text()]))

    expect(counts).toEqual({
      organizations: 'Organizations7',
      teams: 'Teams12',
      pending_invitations: 'Pending invitations4'
    })
  })

  it('renders the no-access card on a 403', async () => {
    mockPackageApi('roster', { 'GET /stats/organizations': reply(403) })

    const wrapper = await mount(OrganizationsWidget, 'roster.organizations')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-organizations-count"]').exists()).toBe(false)
  })

  it('renders the error card on any other failure', async () => {
    mockPackageApi('roster', { 'GET /stats/organizations': reply(503) })

    const wrapper = await mount(OrganizationsWidget, 'roster.organizations')

    expect(wrapper.find('[data-testid="atrium-error-card"]').exists()).toBe(true)
  })
})

describe('both widgets on one dashboard', () => {
  it('share one request per stats endpoint while they are mounted', async () => {
    const calls = mockPackageApi('roster', {
      'GET /stats/users': userStats,
      'GET /stats/organizations': organizationStats
    })

    await mount(UserStatusWidget, 'roster.user-status')
    await mount(UserStatusWidget, 'roster.user-status')
    await mount(OrganizationsWidget, 'roster.organizations')

    expect(calls.map(call => `${call.method} ${call.route}`).sort()).toEqual(['GET /stats/organizations', 'GET /stats/users'])
  })
})
