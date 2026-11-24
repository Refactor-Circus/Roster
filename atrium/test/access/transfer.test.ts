import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/transfers/[transfer].vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { mountRosterPage, resetRosterPages, rosterCalls, screensApi } from '../users/support'
import { catchDownloads, CSV, failedImport, finishedExport, pendingImport, runningImport } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `roster.transfer` (AC 54, 69): one transfer (`roster.transfers.show`) with
 * its status, requester, rows, summary, progress and error, and its rows a
 * page at a time (`rows_page`); confirm an import awaiting confirmation
 * (`roster.imports.confirm`), cancel a transfer that has not finished
 * (`roster.transfers.destroy`), and the download link of a finished export
 * (`roster.transfers.download`, saved as a file). A 422 shows Roster's
 * message, a 403 no access and a 404 not found.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

const PATH = `/roster/transfers/${pendingImport.id}`

function rosterWith(transfer: object = pendingImport, overrides: PackageRoutes<'roster'> = {}): void {
  mockPackageApi('roster', {
    'GET /transfers/{transfer}': { data: transfer } as never,
    ...overrides
  })
}

describe('roster.transfer', () => {
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

  it('loads the transfer with its rows, and calls atrium.screens.show with its key and record', async () => {
    rosterWith()

    const wrapper = await mountRosterPage(Page, PATH)

    const [load] = rosterCalls('GET', '/transfers/{transfer}')
    expect(load?.path).toBe(`/transfers/${pendingImport.id}`)
    expect(load?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/roster.transfer', expect.objectContaining({ query: expect.objectContaining({ record: pendingImport.id }) }))
    expect(wrapper.find('[data-testid="roster-transfer-title"]').text()).toBe('Import users')
    expect(wrapper.find('[data-testid="roster-transfer-status"]').text()).toBe('Awaiting confirmation')
    expect(wrapper.text()).toContain('Ada Lovelace')
    expect(wrapper.find('[data-testid="roster-transfer-confirm-by"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-transfer-summary"]').text()).toContain('Create: 1')
    expect(wrapper.find('[data-testid="roster-transfer-line-2"]').text()).toContain('new@example.com')
    expect(wrapper.find('[data-testid="roster-transfer-line-3"]').text()).toContain('Already a user.')
    expect(wrapper.find('[data-testid="roster-transfer-back"]').attributes('href')).toBe('/roster/transfers?organization=acme')
    expect(wrapper.find('[data-testid="roster-transfer-download"]').exists()).toBe(false)
  })

  it('pages the rows through rows_page', async () => {
    rosterWith()

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-transfer-rows-next"]').trigger('click')
    await settle()

    expect(useRouter().currentRoute.value.query).toEqual({ rows_page: '2' })
    expect(rosterCalls('GET', '/transfers/{transfer}').at(-1)?.query).toEqual({ rows_page: '2' })
  })

  it('confirms an import awaiting confirmation, then reloads', async () => {
    rosterWith(pendingImport, { 'POST /imports/{transfer}/confirm': { data: runningImport } as never })

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-transfer-confirm"]').trigger('click')
    await settle()

    const [call] = rosterCalls('POST', '/imports/{transfer}/confirm')
    expect(call?.path).toBe(`/imports/${pendingImport.id}/confirm`)
    expect(call?.body).toBeUndefined()
    expect(rosterCalls('GET', '/transfers/{transfer}')).toHaveLength(2)
  })

  it('cancels a transfer that has not finished, then reloads', async () => {
    rosterWith(pendingImport, { 'DELETE /transfers/{transfer}': { data: { ...pendingImport, status: 'cancelled' } } as never })

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-transfer-cancel"]').trigger('click')
    await settle()

    const [call] = rosterCalls('DELETE', '/transfers/{transfer}')
    expect(call?.path).toBe(`/transfers/${pendingImport.id}`)
    expect(call?.body).toBeUndefined()
    expect(rosterCalls('GET', '/transfers/{transfer}')).toHaveLength(2)
  })

  it('shows the progress of a running import, which can be cancelled but not confirmed', async () => {
    rosterWith(runningImport)

    const wrapper = await mountRosterPage(Page, `/roster/transfers/${runningImport.id}`)

    expect(wrapper.find('[data-testid="roster-transfer-progress"]').text()).toContain('1 of 2')
    expect(wrapper.find('[data-testid="roster-transfer-confirm"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="roster-transfer-cancel"]').exists()).toBe(true)
  })

  it('shows the error of a failed import, with no actions', async () => {
    rosterWith(failedImport)

    const wrapper = await mountRosterPage(Page, `/roster/transfers/${failedImport.id}`)

    expect(wrapper.find('[data-testid="roster-transfer-error"]').text()).toContain('The file has no email column.')
    expect(wrapper.find('[data-testid="roster-transfer-confirm"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="roster-transfer-cancel"]').exists()).toBe(false)
  })

  it('downloads a finished export through the signed-in client', async () => {
    const { saved } = catchDownloads()
    rosterWith(finishedExport, { 'GET /transfers/{transfer}/download': CSV as never })

    const wrapper = await mountRosterPage(Page, `/roster/transfers/${finishedExport.id}`)

    expect(wrapper.find('[data-testid="roster-transfer-cancel"]').exists()).toBe(false)

    await wrapper.find('[data-testid="roster-transfer-download"]').trigger('click')
    await settle()

    expect(rosterCalls('GET', '/transfers/{transfer}/download')[0]?.path).toBe(`/transfers/${finishedExport.id}/download`)
    expect(saved).toEqual([{ href: 'blob:roster-1', download: `roster-export_users-${finishedExport.id}.csv` }])
    expect(wrapper.find('[data-testid="roster-transfer-back"]').attributes('href')).toBe('/roster/transfers')
  })

  it('shows Roster\'s message when confirming answers 422', async () => {
    rosterWith(pendingImport, { 'POST /imports/{transfer}/confirm': reply(422, { message: 'This import has expired.', errors: { transfer: ['This import has expired.'] } }) })

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-transfer-confirm"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="roster-transfer-action-errors"]').text()).toContain('This import has expired.')
  })

  it('shows the no-access notice when an action answers 403', async () => {
    rosterWith(pendingImport, { 'POST /imports/{transfer}/confirm': reply(403) })

    const wrapper = await mountRosterPage(Page, PATH)
    await wrapper.find('[data-testid="roster-transfer-confirm"]').trigger('click')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state on a 403', async () => {
    mockPackageApi('roster', { 'GET /transfers/{transfer}': reply(403) })

    const wrapper = await mountRosterPage(Page, PATH)

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-transfer-title"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    mockPackageApi('roster', { 'GET /transfers/{transfer}': reply(404) })

    const wrapper = await mountRosterPage(Page, PATH)

    expect(wrapper.find('[data-testid="roster-not-found"]').text()).toContain('This transfer was not found.')
  })
})
