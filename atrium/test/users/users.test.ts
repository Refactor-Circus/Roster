import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/users/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis } from '@refactor-circus/atrium/testing'
import { ada, confirmWith, grace, mountRosterPage, page, resetRosterPages, rosterCalls, screensApi } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { chooseOption, selectItems, tableRow } from '@refactor-circus/atrium/testing'

/*
 * `roster.users` (AC 54, 69): the active and deleted tabs (`trashed=only`),
 * the status filter and search sent as `roster.users.index`'s query, page
 * paging, approving a pending user from its row, restoring and purging a
 * deleted one (after a confirmation), and the no-access state on a 403.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

describe('roster.users', () => {
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

  it('lists the active users and calls atrium.screens.show with its key', async () => {
    mockPackageApi('roster', { 'GET /users': page([ada, grace]) as never })

    const wrapper = await mountRosterPage(Page, '/roster/users')

    expect(rosterCalls('GET', '/users')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/roster.users', { query: { record: undefined, tab: undefined } })
    expect(tableRow(wrapper, 'roster-user-row-7').text()).toContain('Ada L.')
    expect(tableRow(wrapper, 'roster-user-row-7').find('a').attributes('href')).toBe('/roster/users/7')
    expect(tableRow(wrapper, 'roster-user-row-8').text()).toContain('grace@example.com')
    expect(wrapper.find('[data-testid="roster-users-tab-active"]').attributes('aria-current')).toBe('page')
  })

  it('shows the deleted users on the deleted tab, through trashed=only', async () => {
    mockPackageApi('roster', { 'GET /users': page([ada]) as never })

    const wrapper = await mountRosterPage(Page, '/roster/users')
    await wrapper.find('[data-testid="roster-users-tab-deleted"]').trigger('click')
    await settle()

    expect(useRouter().currentRoute.value.query).toEqual({ trashed: 'only' })
    expect(rosterCalls('GET', '/users').at(-1)?.query).toEqual({ trashed: 'only' })
    expect(wrapper.find('[data-testid="roster-users-tab-deleted"]').attributes('aria-current')).toBe('page')
    expect(wrapper.find('[data-testid="roster-user-restore-7"]').exists()).toBe(true)
  })

  it('sends the status filter and search as the endpoint\'s query, from the form and the URL', async () => {
    mockPackageApi('roster', { 'GET /users': page([ada]) as never })

    const wrapper = await mountRosterPage(Page, '/roster/users?status=suspended&search=ada')

    expect(rosterCalls('GET', '/users')[0]?.query).toEqual({ status: 'suspended', search: 'ada' })

    await chooseOption(wrapper, 'roster-users-status', 'pending')
    await wrapper.find('[data-testid="roster-users-search"]').setValue('grace')
    await wrapper.find('[data-testid="roster-users-filters"]').trigger('submit')
    await settle()

    expect(rosterCalls('GET', '/users').at(-1)?.query).toEqual({ status: 'pending', search: 'grace' })

    // "All statuses" holds '' and sends no status.
    expect(selectItems(wrapper, 'roster-users-status')[0]).toEqual({ value: '', label: 'All statuses' })
    await chooseOption(wrapper, 'roster-users-status', '')
    await wrapper.find('[data-testid="roster-users-filters"]').trigger('submit')
    await settle()

    expect(rosterCalls('GET', '/users').at(-1)?.query).toEqual({ search: 'grace' })

    await wrapper.find('[data-testid="roster-users-clear"]').trigger('click')
    await settle()

    expect(rosterCalls('GET', '/users').at(-1)?.query).toEqual({})
  })

  it('pages by the page number the endpoint answers with', async () => {
    mockPackageApi('roster', { 'GET /users': call => page([ada], Number(call.query.page ?? 1), 3) as never })

    const wrapper = await mountRosterPage(Page, '/roster/users?status=active')

    expect(wrapper.find('[data-testid="roster-users-page"]').text()).toContain('1')
    expect(wrapper.find('[data-testid="roster-users-prev"]').attributes('disabled')).toBeDefined()

    await wrapper.find('[data-testid="roster-users-next"]').trigger('click')
    await settle()

    expect(rosterCalls('GET', '/users').at(-1)?.query).toEqual({ status: 'active', page: '2' })

    await wrapper.find('[data-testid="roster-users-prev"]').trigger('click')
    await settle()

    expect(rosterCalls('GET', '/users').at(-1)?.query).toEqual({ status: 'active' })
  })

  it('approves a pending user from its row, then reloads', async () => {
    mockPackageApi('roster', { 'GET /users': page([ada, grace]) as never, 'POST /users/{user}/approve': { data: { ...grace, status: 'active' } as never } })

    const wrapper = await mountRosterPage(Page, '/roster/users')

    expect(wrapper.find('[data-testid="roster-user-approve-7"]').exists()).toBe(false)

    await wrapper.find('[data-testid="roster-user-approve-8"]').trigger('click')
    await settle()

    const [call] = rosterCalls('POST', '/users/{user}/approve')
    expect(call?.path).toBe('/users/8/approve')
    expect(call?.body).toBeUndefined()
    expect(rosterCalls('GET', '/users')).toHaveLength(2)
  })

  it('restores a deleted user from the deleted tab, then reloads', async () => {
    mockPackageApi('roster', { 'GET /users': page([ada]) as never, 'POST /users/{user}/restore': { data: ada as never } })

    const wrapper = await mountRosterPage(Page, '/roster/users?trashed=only')
    await wrapper.find('[data-testid="roster-user-restore-7"]').trigger('click')
    await settle()

    const [call] = rosterCalls('POST', '/users/{user}/restore')
    expect(call?.path).toBe('/users/7/restore')
    expect(call?.body).toBeUndefined()
    expect(rosterCalls('GET', '/users')).toHaveLength(2)
  })

  it('purges a deleted user only after a confirmation', async () => {
    const confirm = confirmWith(false)
    mockPackageApi('roster', { 'GET /users': page([ada]) as never, 'DELETE /users/{user}/purge': reply(204) })

    const wrapper = await mountRosterPage(Page, '/roster/users?trashed=only')
    await wrapper.find('[data-testid="roster-user-purge-7"]').trigger('click')
    await settle()

    expect(confirm).toHaveBeenCalled()
    expect(rosterCalls('DELETE', '/users/{user}/purge')).toHaveLength(0)

    confirm.mockReturnValue(true)
    await wrapper.find('[data-testid="roster-user-purge-7"]').trigger('click')
    await settle()

    expect(rosterCalls('DELETE', '/users/{user}/purge')[0]?.path).toBe('/users/7/purge')
    expect(rosterCalls('GET', '/users')).toHaveLength(2)
  })

  it('links to the create page and the transfers', async () => {
    mockPackageApi('roster', { 'GET /users': page([]) })

    const wrapper = await mountRosterPage(Page, '/roster/users')

    expect(wrapper.find('[data-testid="roster-users-new"]').attributes('href')).toBe('/roster/users/create')
    expect(wrapper.find('[data-testid="roster-users-transfers"]').attributes('href')).toBe('/roster/transfers')
    expect(wrapper.find('[data-testid="roster-users-empty"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    mockPackageApi('roster', { 'GET /users': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/users')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-users-table"]').exists()).toBe(false)
  })

  it('shows the no-access notice when an action answers 403', async () => {
    mockPackageApi('roster', { 'GET /users': page([grace]) as never, 'POST /users/{user}/approve': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/users')
    await wrapper.find('[data-testid="roster-user-approve-8"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-users-table"]').exists()).toBe(true)
  })
})
