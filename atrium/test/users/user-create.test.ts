import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/users/create.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis } from '@refactor-circus/atrium/testing'
import { ada, mountRosterPage, resetRosterPages, rosterCalls, screensApi } from './support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { chooseOption, selectValue } from '@refactor-circus/atrium/testing'

/*
 * `roster.user-create` (AC 54, 69): the v1 form (name, email, password,
 * initial status and the profile fields) posted to `roster.users.store`,
 * then the new user's page; a 422 shows the field errors and a 403 the
 * no-access notice.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

async function fill(wrapper: Awaited<ReturnType<typeof mountRosterPage>>): Promise<void> {
  await wrapper.find('[data-testid="roster-user-name"]').setValue('Ada Lovelace')
  await wrapper.find('[data-testid="roster-user-email"]').setValue('ada@example.com')
  await wrapper.find('[data-testid="roster-profile-display_name"]').setValue('Ada L.')
  await wrapper.find('[data-testid="roster-profile-timezone"]').setValue('Europe/London')
}

describe('roster.user-create', () => {
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

  it('frames the form with its screen', async () => {
    mockPackageApi('roster', {})

    const wrapper = await mountRosterPage(Page, '/roster/users/create')

    expect(api).toHaveBeenCalledWith('/screens/roster.user-create', { query: { record: undefined, tab: undefined } })
    expect(wrapper.find('[data-testid="roster-user-create-form"]').exists()).toBe(true)
    expect(selectValue(wrapper, 'roster-user-status')).toBe('active')
  })

  it('creates the user through roster.users.store, leaving out a blank password, then opens its page', async () => {
    mockPackageApi('roster', { 'POST /users': { data: ada as never } })

    const wrapper = await mountRosterPage(Page, '/roster/users/create')
    await fill(wrapper)
    await chooseOption(wrapper, 'roster-user-status', 'pending')
    await wrapper.find('[data-testid="roster-user-create-form"]').trigger('submit')
    await settle()

    const [call] = rosterCalls('POST', '/users')
    expect(call?.body).toEqual({
      name: 'Ada Lovelace',
      email: 'ada@example.com',
      status: 'pending',
      display_name: 'Ada L.',
      avatar_url: null,
      timezone: 'Europe/London',
      locale: null,
      bio: null
    })
    // The page navigates after the answer; `settle()` may return first.
    await vi.waitFor(() => expect(useRouter().currentRoute.value.path).toBe('/roster/users/7'))
  })

  it('sends a typed password', async () => {
    mockPackageApi('roster', { 'POST /users': { data: ada as never } })

    const wrapper = await mountRosterPage(Page, '/roster/users/create')
    await fill(wrapper)
    await wrapper.find('[data-testid="roster-user-password"]').setValue('correct horse')
    await wrapper.find('[data-testid="roster-user-create-form"]').trigger('submit')
    await settle()

    expect(rosterCalls('POST', '/users')[0]?.body).toMatchObject({ password: 'correct horse', status: 'active' })
  })

  it('shows the field errors of a 422 and stays on the form', async () => {
    mockPackageApi('roster', {
      'POST /users': reply(422, { message: 'Invalid.', errors: { email: ['The email has already been taken.'], avatar_url: ['The avatar url must be a valid URL.'] } })
    })

    const wrapper = await mountRosterPage(Page, '/roster/users/create')
    await fill(wrapper)
    await wrapper.find('[data-testid="roster-user-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.text()).toContain('The email has already been taken.')
    expect(wrapper.text()).toContain('The avatar url must be a valid URL.')
    expect(useRouter().currentRoute.value.path).toBe('/roster/users/create')
  })

  it('shows the no-access notice on a 403', async () => {
    mockPackageApi('roster', { 'POST /users': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/users/create')
    await fill(wrapper)
    await wrapper.find('[data-testid="roster-user-create-form"]').trigger('submit')
    await settle()

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
  })
})
