import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/impersonations/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis } from '@refactor-circus/atrium/testing'
import { endedImpersonation, impersonation, mountRosterPage, page, resetRosterPages, rosterCalls, screensApi } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `roster.impersonations` (AC 54, 69): the list (`roster.impersonations.index`,
 * page paging), and stop (`roster.impersonations.destroy`) on an impersonation
 * that has not ended, then a reload; a 403 on the list and on stop, and a 404
 * on stop.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

describe('roster.impersonations', () => {
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

  it('lists the impersonations and calls atrium.screens.show with its key', async () => {
    mockPackageApi('roster', { 'GET /impersonations': page([impersonation, endedImpersonation]) as never })

    const wrapper = await mountRosterPage(Page, '/roster/impersonations')

    expect(rosterCalls('GET', '/impersonations')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/roster.impersonations', { query: { record: undefined, tab: undefined } })

    const active = wrapper.find('[data-testid="roster-impersonation-01JIMPERSONATION"]')
    expect(active.text()).toContain('Root')
    expect(active.text()).toContain('Ada Lovelace')
    expect(active.text()).toContain('Ticket 42')
    expect(active.find('[data-testid="roster-impersonation-active"]').exists()).toBe(true)

    const ended = wrapper.find('[data-testid="roster-impersonation-01JENDED"]')
    expect(ended.text()).toContain('stopped')
    expect(ended.find('[data-testid="roster-impersonation-stop"]').exists()).toBe(false)
  })

  it('pages by the page number the endpoint answers with', async () => {
    mockPackageApi('roster', { 'GET /impersonations': call => page([impersonation], Number(call.query.page ?? 1), 2) as never })

    const wrapper = await mountRosterPage(Page, '/roster/impersonations')
    await wrapper.find('[data-testid="roster-impersonations-next"]').trigger('click')
    await settle()

    expect(rosterCalls('GET', '/impersonations').at(-1)?.query).toEqual({ page: '2' })
  })

  it('stops an impersonation through impersonations.destroy, then reloads', async () => {
    mockPackageApi('roster', {
      'GET /impersonations': page([impersonation]) as never,
      'DELETE /impersonations/{impersonation}': { data: endedImpersonation as never }
    })

    const wrapper = await mountRosterPage(Page, '/roster/impersonations')
    await wrapper.find('[data-testid="roster-impersonation-01JIMPERSONATION"] [data-testid="roster-impersonation-stop"]').trigger('click')
    await settle()

    const [call] = rosterCalls('DELETE', '/impersonations/{impersonation}')
    expect(call?.path).toBe('/impersonations/01JIMPERSONATION')
    expect(call?.body).toBeUndefined()
    expect(rosterCalls('GET', '/impersonations')).toHaveLength(2)
  })

  it('shows the no-access notice when stop answers 403', async () => {
    mockPackageApi('roster', {
      'GET /impersonations': page([impersonation]) as never,
      'DELETE /impersonations/{impersonation}': reply(403)
    })

    const wrapper = await mountRosterPage(Page, '/roster/impersonations')
    await wrapper.find('[data-testid="roster-impersonation-stop"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-impersonations-table"]').exists()).toBe(true)
  })

  it('says so when the impersonation to stop is gone (404)', async () => {
    mockPackageApi('roster', {
      'GET /impersonations': page([impersonation]) as never,
      'DELETE /impersonations/{impersonation}': reply(404, { message: 'Not found.' })
    })

    const wrapper = await mountRosterPage(Page, '/roster/impersonations')
    await wrapper.find('[data-testid="roster-impersonation-stop"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="roster-action-error"]').text()).toContain('Not found.')
  })

  it('shows the no-access state when the list answers 403', async () => {
    mockPackageApi('roster', { 'GET /impersonations': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/impersonations')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-impersonations-table"]').exists()).toBe(false)
  })

  it('shows an empty state with no impersonations', async () => {
    mockPackageApi('roster', { 'GET /impersonations': page([]) })

    const wrapper = await mountRosterPage(Page, '/roster/impersonations')

    expect(wrapper.find('[data-testid="roster-impersonations-empty"]').exists()).toBe(true)
  })
})
