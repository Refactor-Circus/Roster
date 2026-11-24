import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/organizations/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis } from '@refactor-circus/atrium/testing'
import { confirmWith, mountRosterPage, page, resetRosterPages, rosterCalls, screensApi } from '../users/support'
import { acmeDetail, umbrella } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `roster.organizations` (AC 54, 69): the active and deleted tabs
 * (`trashed=only`), the search and external-record filters sent as
 * `roster.organizations.index`'s query, restoring and purging (after a
 * confirmation) a deleted organization, and the no-access state on a 403.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

describe('roster.organizations', () => {
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

  it('lists the active organizations and calls atrium.screens.show with its key', async () => {
    mockPackageApi('roster', { 'GET /organizations': page([acmeDetail, umbrella]) })

    const wrapper = await mountRosterPage(Page, '/roster/organizations')

    expect(rosterCalls('GET', '/organizations')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/roster.organizations', { query: { record: undefined, tab: undefined } })

    const row = wrapper.find('[data-testid="roster-organization-row-acme"]')
    expect(row.text()).toContain('Acme')
    expect(row.text()).toContain('crm')
    expect(row.text()).toContain('C-1')
    expect(row.find('a').attributes('href')).toBe('/roster/organizations/acme')
    expect(wrapper.find('[data-testid="roster-organization-row-umbrella"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-organizations-tab-active"]').attributes('aria-current')).toBe('page')
    expect(wrapper.find('[data-testid="roster-organization-restore-acme"]').exists()).toBe(false)
  })

  it('shows the deleted organizations on the deleted tab, through trashed=only', async () => {
    mockPackageApi('roster', { 'GET /organizations': page([umbrella]) })

    const wrapper = await mountRosterPage(Page, '/roster/organizations')
    await wrapper.find('[data-testid="roster-organizations-tab-deleted"]').trigger('click')
    await settle()

    expect(useRouter().currentRoute.value.query).toEqual({ trashed: 'only' })
    expect(rosterCalls('GET', '/organizations').at(-1)?.query).toEqual({ trashed: 'only' })
    expect(wrapper.find('[data-testid="roster-organizations-tab-deleted"]').attributes('aria-current')).toBe('page')
    expect(wrapper.find('[data-testid="roster-organization-row-umbrella"] a').exists()).toBe(false)
    expect(wrapper.find('[data-testid="roster-organization-restore-umbrella"]').exists()).toBe(true)
  })

  it('sends the search and external-record filters as the endpoint\'s query', async () => {
    mockPackageApi('roster', { 'GET /organizations': page([acmeDetail]) })

    const wrapper = await mountRosterPage(Page, '/roster/organizations?search=ac')

    expect(rosterCalls('GET', '/organizations')[0]?.query).toEqual({ search: 'ac' })

    await wrapper.find('[data-testid="roster-organizations-source"]').setValue('crm')
    await wrapper.find('[data-testid="roster-organizations-external-id"]').setValue('C-1')
    await wrapper.find('[data-testid="roster-organizations-filters"]').trigger('submit')
    await settle()

    expect(rosterCalls('GET', '/organizations').at(-1)?.query).toEqual({ search: 'ac', source: 'crm', external_id: 'C-1' })

    await wrapper.find('[data-testid="roster-organizations-clear"]').trigger('click')
    await settle()

    expect(rosterCalls('GET', '/organizations').at(-1)?.query).toEqual({})
  })

  it('restores a deleted organization from the deleted tab, then reloads', async () => {
    mockPackageApi('roster', { 'GET /organizations': page([umbrella]), 'POST /organizations/{organization}/restore': { data: umbrella } })

    const wrapper = await mountRosterPage(Page, '/roster/organizations?trashed=only')
    await wrapper.find('[data-testid="roster-organization-restore-umbrella"]').trigger('click')
    await settle()

    const [call] = rosterCalls('POST', '/organizations/{organization}/restore')
    expect(call?.path).toBe('/organizations/umbrella/restore')
    expect(call?.body).toBeUndefined()
    expect(rosterCalls('GET', '/organizations')).toHaveLength(2)
  })

  it('purges a deleted organization only after a confirmation, and says it is queued', async () => {
    const confirm = confirmWith(false)
    mockPackageApi('roster', {
      'GET /organizations': page([umbrella]),
      'DELETE /organizations/{organization}/purge': reply(202, { message: 'Purge queued.' })
    })

    const wrapper = await mountRosterPage(Page, '/roster/organizations?trashed=only')
    await wrapper.find('[data-testid="roster-organization-purge-umbrella"]').trigger('click')
    await settle()

    expect(confirm).toHaveBeenCalled()
    expect(rosterCalls('DELETE', '/organizations/{organization}/purge')).toHaveLength(0)

    confirm.mockReturnValue(true)
    await wrapper.find('[data-testid="roster-organization-purge-umbrella"]').trigger('click')
    await settle()

    expect(rosterCalls('DELETE', '/organizations/{organization}/purge')[0]?.path).toBe('/organizations/umbrella/purge')
    expect(wrapper.find('[data-testid="roster-organizations-notice"]').text()).toContain('Purge queued.')
    expect(rosterCalls('GET', '/organizations')).toHaveLength(2)
  })

  it('links to the create page', async () => {
    mockPackageApi('roster', { 'GET /organizations': page([]) })

    const wrapper = await mountRosterPage(Page, '/roster/organizations')

    expect(wrapper.find('[data-testid="roster-organizations-new"]').attributes('href')).toBe('/roster/organizations/create')
    expect(wrapper.find('[data-testid="roster-organizations-empty"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    mockPackageApi('roster', { 'GET /organizations': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/organizations')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-organizations-table"]').exists()).toBe(false)
  })

  it('shows the no-access notice when restore answers 403', async () => {
    mockPackageApi('roster', { 'GET /organizations': page([umbrella]), 'POST /organizations/{organization}/restore': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/organizations?trashed=only')
    await wrapper.find('[data-testid="roster-organization-restore-umbrella"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-organizations-table"]').exists()).toBe(true)
  })
})
