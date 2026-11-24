import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/transfers/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageCall, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { mountRosterPage, page, resetRosterPages, rosterCalls, screensApi } from '../users/support'
import { catchDownloads, CSV, finishedExport, pendingImport } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { choose, row as rowOf } from '../ui'

/*
 * `roster.transfers` (AC 54, 69): the transfers (`roster.transfers.index`),
 * filtered by v1's `?organization=` and page paged; the import template link
 * (`roster.imports.templates.show`, saved as a file); v1's import form (type,
 * organization and the CSV file) posted as multipart to
 * `roster.imports.store`; and v1's export form (type, plus the organization
 * for members or the external source for organizations) posted to
 * `roster.exports.store`. Both then open the new transfer. A 422 shows the
 * field errors and a 403 the no-access state.
 *
 * Transfers are cast: Scramble types a transfer's `progress`, `results` and
 * `requested_by` loosely; `./support` types them as the page reads them.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

function rosterWith(overrides: PackageRoutes<'roster'> = {}): void {
  mockPackageApi('roster', {
    'GET /transfers': page([pendingImport, finishedExport]) as never,
    ...overrides
  })
}

type Wrapper = Awaited<ReturnType<typeof mountRosterPage>>

async function pickFile(wrapper: Wrapper, file: File): Promise<void> {
  const input = wrapper.find('[data-testid="roster-import-file"]')
  Object.defineProperty(input.element, 'files', { value: [file], configurable: true })
  await input.trigger('change')
}

describe('roster.transfers', () => {
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

  it('lists the transfers and calls atrium.screens.show with its key', async () => {
    rosterWith()

    const wrapper = await mountRosterPage(Page, '/roster/transfers')

    expect(rosterCalls('GET', '/transfers')[0]?.query).toEqual({})
    expect(api).toHaveBeenCalledWith('/screens/roster.transfers', { query: { record: undefined, tab: undefined } })

    const row = rowOf(wrapper, `roster-transfer-row-${pendingImport.id}`)
    expect(row.find('a').attributes('href')).toBe(`/roster/transfers/${pendingImport.id}`)
    expect(row.text()).toContain('Import users')
    expect(row.text()).toContain('acme')
    expect(row.text()).toContain('Ada Lovelace')
    expect(row.text()).toContain('Awaiting confirmation')
    expect(rowOf(wrapper, `roster-transfer-row-${finishedExport.id}`).text()).toContain('Export users')
  })

  it('filters by the organization in the URL, sent as the endpoint\'s query, and pages', async () => {
    rosterWith({ 'GET /transfers': ((call: PackageCall) => page([pendingImport], Number(call.query.page ?? 1), 2)) as never })

    const wrapper = await mountRosterPage(Page, '/roster/transfers?organization=acme')

    expect(rosterCalls('GET', '/transfers')[0]?.query).toEqual({ organization: 'acme' })
    expect((wrapper.find('[data-testid="roster-import-organization"]').element as HTMLInputElement).value).toBe('acme')

    await wrapper.find('[data-testid="roster-transfers-next"]').trigger('click')
    await settle()

    expect(rosterCalls('GET', '/transfers').at(-1)?.query).toEqual({ organization: 'acme', page: '2' })
  })

  it('downloads the template of the picked import type through the signed-in client', async () => {
    const { saved } = catchDownloads()
    rosterWith({ 'GET /imports/templates/{type}': CSV as never })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')
    await choose(wrapper, 'roster-template-type', 'import_members')
    await wrapper.find('[data-testid="roster-template-download"]').trigger('click')
    await settle()

    const [call] = rosterCalls('GET', '/imports/templates/{type}')
    expect(call?.path).toBe('/imports/templates/import_members')
    expect(call?.query).toEqual({ type: 'import_members' })
    expect(saved).toEqual([{ href: 'blob:roster-1', download: 'roster-import_members-template.csv' }])
  })

  it('starts an import with the old form\'s fields as multipart, then opens the transfer', async () => {
    rosterWith({ 'POST /imports': reply(202, { data: pendingImport }) })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')
    await choose(wrapper, 'roster-import-type', 'import_users')
    await wrapper.find('[data-testid="roster-import-organization"]').setValue('acme')
    await pickFile(wrapper, new File([CSV], 'people.csv', { type: 'text/csv' }))
    await wrapper.find('[data-testid="roster-import-form"]').trigger('submit')
    await settle()

    const [call] = rosterCalls('POST', '/imports')
    expect(call?.headers.get('content-type')).toContain('multipart/form-data')
    expect(call?.body).toEqual(expect.stringMatching(/name="type"\r\n\r\nimport_users\r\n/))
    expect(call?.body).toEqual(expect.stringMatching(/name="organization"\r\n\r\nacme\r\n/))
    expect(call?.body).toEqual(expect.stringContaining('filename="people.csv"'))
    expect(call?.body).toEqual(expect.stringContaining('ada@example.com,Ada'))
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe(`/roster/transfers/${pendingImport.id}`))
  })

  it('leaves a blank organization out of the import', async () => {
    rosterWith({ 'POST /imports': reply(202, { data: pendingImport }) })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')
    await pickFile(wrapper, new File([CSV], 'people.csv', { type: 'text/csv' }))
    await wrapper.find('[data-testid="roster-import-form"]').trigger('submit')
    await settle()

    const body = String(rosterCalls('POST', '/imports')[0]?.body)
    expect(body).toMatch(/name="type"\r\n\r\nimport_members\r\n/)
    expect(body).not.toContain('name="organization"')
  })

  it('shows the field errors of a 422 on import and stays on the list', async () => {
    rosterWith({ 'POST /imports': reply(422, { message: 'Invalid.', errors: { file: ['The file field is required.'] } }) })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')
    await wrapper.find('[data-testid="roster-import-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-testid="roster-import-form"]').text()).toContain('The file field is required.')
    expect(useRouter().currentRoute.value.path).toBe('/roster/transfers')
  })

  it('starts a members export with its organization, then opens the transfer', async () => {
    rosterWith({ 'POST /exports': reply(202, { data: finishedExport }) })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')
    await choose(wrapper, 'roster-export-type', 'export_members')
    await wrapper.find('[data-testid="roster-export-organization"]').setValue('acme')
    await wrapper.find('[data-testid="roster-export-form"]').trigger('submit')
    await settle()

    expect(rosterCalls('POST', '/exports')[0]?.body).toEqual({ type: 'export_members', organization: 'acme' })
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe(`/roster/transfers/${finishedExport.id}`))
  })

  it('starts an organizations export with its external source filter only', async () => {
    rosterWith({ 'POST /exports': reply(202, { data: finishedExport }) })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')
    await choose(wrapper, 'roster-export-type', 'export_organizations')

    expect(wrapper.find('[data-testid="roster-export-organization"]').exists()).toBe(false)

    await wrapper.find('[data-testid="roster-export-external-source"]').setValue('crm')
    await wrapper.find('[data-testid="roster-export-form"]').trigger('submit')
    await settle()

    expect(rosterCalls('POST', '/exports')[0]?.body).toEqual({ type: 'export_organizations', filters: { external_source: 'crm' } })
  })

  it('starts a users export with the type alone', async () => {
    rosterWith({ 'POST /exports': reply(202, { data: finishedExport }) })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')
    await choose(wrapper, 'roster-export-type', 'export_users')

    expect(wrapper.find('[data-testid="roster-export-organization"]').exists()).toBe(false)
    expect(wrapper.find('[data-testid="roster-export-external-source"]').exists()).toBe(false)

    await wrapper.find('[data-testid="roster-export-form"]').trigger('submit')
    await settle()

    expect(rosterCalls('POST', '/exports')[0]?.body).toEqual({ type: 'export_users' })
  })

  it('shows the field errors of a 422 on export', async () => {
    rosterWith({ 'POST /exports': reply(422, { message: 'Invalid.', errors: { organization: ['The organization field is required.'] } }) })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')
    await choose(wrapper, 'roster-export-type', 'export_members')
    await wrapper.find('[data-testid="roster-export-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-testid="roster-export-form"]').text()).toContain('The organization field is required.')
  })

  it('shows the no-access notice when starting answers 403', async () => {
    rosterWith({ 'POST /exports': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')
    await wrapper.find('[data-testid="roster-export-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })

  it('shows the no-access state when the list answers 403', async () => {
    rosterWith({ 'GET /transfers': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/transfers')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-transfers-table"]').exists()).toBe(false)
  })
})
