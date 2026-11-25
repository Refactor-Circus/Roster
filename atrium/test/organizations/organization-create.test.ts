import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/organizations/create.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis } from '@refactor-circus/atrium/testing'
import { mountRosterPage, resetRosterPages, rosterCalls, screensApi } from '../users/support'
import { acmeDetail } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'

/*
 * `roster.organization-create` (AC 54, 69): v1's form posted to
 * `roster.organizations.store` with its fields (domains one per line), then
 * the new organization's page; a 422 shows the field errors, a 403 the
 * no-access notice.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

describe('roster.organization-create', () => {
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

  async function fill(wrapper: Awaited<ReturnType<typeof mountRosterPage>>): Promise<void> {
    await wrapper.find('[data-testid="roster-organization-name"]').setValue('Acme')
    await wrapper.find('[data-testid="roster-organization-owner"]').setValue('ada@example.com')
    await wrapper.find('[data-testid="roster-organization-domains"]').setValue('acme.test\n\n acme.example ')
    await wrapper.find('[data-testid="roster-organization-auto-join"]').trigger('click')
  }

  it('creates the organization with the old form\'s fields, then opens its page', async () => {
    mockPackageApi('roster', { 'POST /organizations': { data: acmeDetail } })

    const wrapper = await mountRosterPage(Page, '/roster/organizations/create')

    expect(api).toHaveBeenCalledWith('/screens/roster.organization-create', { query: { record: undefined, tab: undefined } })

    await fill(wrapper)
    await wrapper.find('[data-testid="roster-organization-create-form"]').trigger('submit')
    await settle()

    const [call] = rosterCalls('POST', '/organizations')
    expect(call?.body).toEqual({
      name: 'Acme',
      slug: null,
      owner: 'ada@example.com',
      domains: ['acme.test', 'acme.example'],
      auto_join: true
    })
    // The page navigates after the answer; `settle()` may return first.
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/roster/organizations/acme'))
  })

  it('shows the field errors of a 422 and stays on the form', async () => {
    mockPackageApi('roster', {
      'POST /organizations': reply(422, { message: 'Invalid.', errors: { name: ['The name is taken.'], 'domains.0': ['That domain is in use.'] } })
    })

    const wrapper = await mountRosterPage(Page, '/roster/organizations/create')
    await fill(wrapper)
    await wrapper.find('[data-testid="roster-organization-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.text()).toContain('The name is taken.')
    expect(wrapper.text()).toContain('That domain is in use.')
    expect(useRouter().currentRoute.value.path).toBe('/roster/organizations/create')
  })

  it('shows the no-access notice on a 403', async () => {
    mockPackageApi('roster', { 'POST /organizations': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/organizations/create')
    await fill(wrapper)
    await wrapper.find('[data-testid="roster-organization-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })
})
