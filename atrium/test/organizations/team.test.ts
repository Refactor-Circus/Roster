import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/organizations/[organization]/teams/[team].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, mountRosterPage, page, resetRosterPages, rosterCalls, screensApi } from '../users/support'
import { acmeDetail, linusMember, opsDetail, owner, pendingMember } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { chooseOption, selectValues } from '@refactor-circus/atrium/testing'

/*
 * `roster.team` (AC 54, 69): one team with its members; update
 * (`teams.update`), delete after a confirmation (`teams.destroy`), add one of
 * the organization's members (`teams.members.store`) and remove one
 * (`teams.members.destroy`). A 403 shows no access and a 404 not found.
 *
 * Team members and organization members are cast: Scramble types them
 * loosely; `./support` types them as the page reads them.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = '/roster/organizations/acme/teams/ops'

function rosterWith(overrides: PackageRoutes<'roster'> = {}): void {
  mockPackageApi('roster', {
    'GET /organizations/{organization}/teams/{team}': { data: opsDetail } as never,
    'GET /organizations/{organization}': { data: acmeDetail },
    'GET /organizations/{organization}/members': page([owner, pendingMember, linusMember]) as never,
    ...overrides
  })
}

describe('roster.team', () => {
  beforeEach(() => {
    clearAtriumData()
    api.mockReset()
    api.mockImplementation(screensApi)
  })

  afterEach(async () => {
    resetRosterPages()
    resetPackageApis()
    vi.restoreAllMocks()
    await useRouter().replace('/')
  })

  it('loads the team and its members, and calls atrium.screens.show with its key and record', async () => {
    rosterWith()

    const wrapper = await mountRosterPage(Page, PATH)

    expect(rosterCalls('GET', '/organizations/{organization}/teams/{team}')[0]?.path).toBe('/organizations/acme/teams/ops')
    expect(api).toHaveBeenCalledWith('/screens/roster.team', expect.objectContaining({ query: expect.objectContaining({ record: 'ops' }) }))
    expect(wrapper.find('[data-testid="roster-team-title"]').text()).toBe('Ops')
    expect(wrapper.find('[data-testid="roster-team-organization"]').text()).toBe('Acme')
    expect(wrapper.find('[data-testid="roster-team-member-7"]').text()).toContain('Ada Lovelace')
    expect(wrapper.find('[data-testid="atrium-page-header-back"]').attributes('href')).toBe('/roster/organizations/acme?tab=teams')
  })

  it('adds one of the organization\'s members who is not in the team yet, then reloads', async () => {
    rosterWith({ 'POST /organizations/{organization}/teams/{team}/members': reply(201, { data: opsDetail }) as never })

    const wrapper = await mountRosterPage(Page, PATH)
    const options = selectValues(wrapper, 'roster-team-member-candidate')

    expect(options).toEqual(['8', '9'])

    await chooseOption(wrapper, 'roster-team-member-candidate', '9')
    await wrapper.find('[data-testid="roster-team-member-add-form"]').trigger('submit')
    await settle()

    const [call] = rosterCalls('POST', '/organizations/{organization}/teams/{team}/members')
    expect(call?.path).toBe('/organizations/acme/teams/ops/members')
    expect(call?.body).toEqual({ user: '9' })
    expect(rosterCalls('GET', '/organizations/{organization}/teams/{team}')).toHaveLength(2)
  })

  it('removes a member, then reloads', async () => {
    rosterWith({ 'DELETE /organizations/{organization}/teams/{team}/members/{user}': reply(204) })

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-team-member-remove-7"]').trigger('click')
    await settle()

    const [call] = rosterCalls('DELETE', '/organizations/{organization}/teams/{team}/members/{user}')
    expect(call?.path).toBe('/organizations/acme/teams/ops/members/7')
    expect(call?.body).toBeUndefined()
    expect(rosterCalls('GET', '/organizations/{organization}/teams/{team}')).toHaveLength(2)
  })

  it('updates the team with the old form\'s fields, then reloads', async () => {
    rosterWith({ 'PATCH /organizations/{organization}/teams/{team}': { data: opsDetail } as never })

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-team-name"]').setValue('Operations')
    await chooseOption(wrapper, 'roster-team-status', 'inactive')
    await wrapper.find('[data-testid="roster-team-status-reason"]').setValue('Merged')
    await wrapper.find('[data-testid="roster-team-settings-form"]').trigger('submit')
    await settle()

    const [call] = rosterCalls('PATCH', '/organizations/{organization}/teams/{team}')
    expect(call?.path).toBe('/organizations/acme/teams/ops')
    expect(call?.body).toEqual({ name: 'Operations', slug: 'ops', status: 'inactive', status_reason: 'Merged' })
    expect(rosterCalls('GET', '/organizations/{organization}/teams/{team}')).toHaveLength(2)
  })

  it('shows the field errors of a 422 on update', async () => {
    rosterWith({ 'PATCH /organizations/{organization}/teams/{team}': reply(422, { message: 'Invalid.', errors: { slug: ['The slug is taken.'] } }) })

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-team-settings-form"]').trigger('submit')
    await settle()

    expect(wrapper.text()).toContain('The slug is taken.')
  })

  it('deletes the team only after a confirmation, then returns to the teams tab', async () => {
    const confirm = confirmWith(false)
    rosterWith({ 'DELETE /organizations/{organization}/teams/{team}': reply(204) })

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-team-delete"]').trigger('click')
    await settle()

    expect(confirm).toHaveBeenCalled()
    expect(rosterCalls('DELETE', '/organizations/{organization}/teams/{team}')).toHaveLength(0)

    confirm.mockReturnValue(true)
    await wrapper.find('[data-testid="roster-team-delete"]').trigger('click')
    await settle()

    expect(rosterCalls('DELETE', '/organizations/{organization}/teams/{team}')[0]?.path).toBe('/organizations/acme/teams/ops')
    await vi.waitFor(() => expect(useRouter().currentRoute.value.fullPath).toBe('/roster/organizations/acme?tab=teams'))
  })

  it('shows the no-access notice when an action answers 403', async () => {
    rosterWith({ 'DELETE /organizations/{organization}/teams/{team}/members/{user}': reply(403) })

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-team-member-remove-7"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    rosterWith({ 'GET /organizations/{organization}/teams/{team}': reply(403) })

    const wrapper = await mountRosterPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-team-members"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    rosterWith({ 'GET /organizations/{organization}/teams/{team}': reply(404) })

    const wrapper = await mountRosterPage(Page, PATH)

    expect(wrapper.find('[data-testid="roster-not-found"]').text()).toContain('This team was not found.')
  })
})
