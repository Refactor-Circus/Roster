import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import Page from '../../src/pages/organizations/[organization]/index.vue'
import { settle } from '@refactor-circus/atrium/testing'
import { mockPackageApi, reply, resetPackageApis, type PackageRoutes } from '@refactor-circus/atrium/testing'
import { confirmWith, mountRosterPage, page, resetRosterPages, rosterCalls, screensApi } from '../users/support'
import { clearAtriumData, mockAtriumApi, useRouter } from '@refactor-circus/atrium/testing'
import { chooseOption, tableRow } from '@refactor-circus/atrium/testing'
import {
  acceptedInvitation,
  acmeDetail,
  invitation,
  linusMember,
  ops,
  owner,
  pendingMember
} from './support'

/*
 * `roster.organization` (AC 54, 69): one organization with v1's tabs in
 * `?tab=`. Members (add, approve, transfer ownership, remove), teams (list,
 * create), invitations (invite, revoke) and settings (update, link and unlink,
 * delete, then restore or purge). Role assignments moved to Guardian (AC 99). Each mutation is checked for its
 * method, path and body, and the page reloads after it. A 403 shows the
 * no-access state and a 404 the not-found state.
 *
 * Members and invitations are cast: Scramble types a member's `user` as
 * `unknown[]` and an invitation's `teams` as
 * `string | {}`; `./support` types them as the page reads them.
 */

const { api } = vi.hoisted(() => ({ api: vi.fn() }))

mockAtriumApi(api)

/** Roster answering the organization `acme` and each of its tabs. */
function rosterWith(overrides: PackageRoutes<'roster'> = {}): void {
  mockPackageApi('roster', {
    'GET /organizations/{organization}': { data: acmeDetail },
    'GET /organizations/{organization}/members': page([owner, pendingMember, linusMember]) as never,
    'GET /organizations/{organization}/teams': page([ops]),
    'GET /organizations/{organization}/invitations': page([invitation, acceptedInvitation]) as never,
    ...overrides
  })
}

describe('roster.organization', () => {
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

  describe('the members tab', () => {
    it('loads the organization and its members, and calls atrium.screens.show with its key and record', async () => {
      rosterWith()

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')

      expect(rosterCalls('GET', '/organizations/{organization}')[0]?.path).toBe('/organizations/acme')
      expect(rosterCalls('GET', '/organizations/{organization}/members')[0]?.path).toBe('/organizations/acme/members')
      expect(api).toHaveBeenCalledWith('/screens/roster.organization', expect.objectContaining({ query: expect.objectContaining({ record: 'acme' }) }))
      expect(wrapper.find('[data-testid="roster-organization-title"]').text()).toBe('Acme')
      expect(wrapper.find('[data-testid="roster-organization-tab-members"]').attributes('aria-current')).toBe('page')
      expect(tableRow(wrapper, 'roster-member-row-7').text()).toContain('Ada Lovelace')
      expect(tableRow(wrapper, 'roster-member-row-7').find('[data-testid="roster-member-owner"]').exists()).toBe(true)
      expect(tableRow(wrapper, 'roster-member-row-7').text()).toContain('ops')
      expect(wrapper.find('[data-testid="roster-member-remove-7"]').exists()).toBe(false)
      expect(wrapper.find('[data-testid="roster-organization-no-owner"]').exists()).toBe(false)
    })

    it('adds a member by user key, then reloads', async () => {
      rosterWith({ 'POST /organizations/{organization}/members': reply(201, { data: linusMember }) })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')
      await wrapper.find('[data-testid="roster-member-add-user"]').setValue(' linus@example.com ')
      await wrapper.find('[data-testid="roster-member-add-form"]').trigger('submit')
      await settle()

      const [call] = rosterCalls('POST', '/organizations/{organization}/members')
      expect(call?.path).toBe('/organizations/acme/members')
      expect(call?.body).toEqual({ user: 'linus@example.com' })
      expect(rosterCalls('GET', '/organizations/{organization}/members')).toHaveLength(2)
    })

    it('shows the field error of a 422 on add member', async () => {
      rosterWith({ 'POST /organizations/{organization}/members': reply(422, { message: 'Invalid.', errors: { user: ['No such user.'] } }) })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')
      await wrapper.find('[data-testid="roster-member-add-user"]').setValue('nobody')
      await wrapper.find('[data-testid="roster-member-add-form"]').trigger('submit')
      await settle()

      expect(wrapper.text()).toContain('No such user.')
    })

    it('approves a pending member', async () => {
      rosterWith({ 'POST /users/{user}/approve': { data: {} } as never })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')

      expect(wrapper.find('[data-testid="roster-member-approve-9"]').exists()).toBe(false)

      await wrapper.find('[data-testid="roster-member-approve-8"]').trigger('click')
      await settle()

      expect(rosterCalls('POST', '/users/{user}/approve')[0]?.path).toBe('/users/8/approve')
      expect(rosterCalls('GET', '/organizations/{organization}/members')).toHaveLength(2)
    })

    it('transfers the ownership to a member, then reloads', async () => {
      rosterWith({ 'POST /organizations/{organization}/transfer': { data: acmeDetail } })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')
      await wrapper.find('[data-testid="roster-member-make-owner-9"]').trigger('click')
      await settle()

      const [call] = rosterCalls('POST', '/organizations/{organization}/transfer')
      expect(call?.path).toBe('/organizations/acme/transfer')
      expect(call?.body).toEqual({ user: '9' })
      expect(rosterCalls('GET', '/organizations/{organization}')).toHaveLength(2)
    })

    it('removes a member, then reloads', async () => {
      rosterWith({ 'DELETE /organizations/{organization}/members/{user}': reply(204) })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')
      await wrapper.find('[data-testid="roster-member-remove-9"]').trigger('click')
      await settle()

      const [call] = rosterCalls('DELETE', '/organizations/{organization}/members/{user}')
      expect(call?.path).toBe('/organizations/acme/members/9')
      expect(call?.body).toBeUndefined()
      expect(rosterCalls('GET', '/organizations/{organization}/members')).toHaveLength(2)
    })

    it('shows the no-access notice when an action answers 403', async () => {
      rosterWith({ 'DELETE /organizations/{organization}/members/{user}': reply(403) })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')
      await wrapper.find('[data-testid="roster-member-remove-9"]').trigger('click')
      await settle()

      expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
      expect(wrapper.find('[data-testid="roster-members-table"]').exists()).toBe(true)
    })
  })

  describe('the teams tab', () => {
    it('opens on ?tab=teams, lists the teams with links to their pages', async () => {
      rosterWith()

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')
      await wrapper.find('[data-testid="roster-organization-tab-teams"]').trigger('click')
      await settle()

      expect(useRouter().currentRoute.value.query).toEqual({ tab: 'teams' })
      expect(rosterCalls('GET', '/organizations/{organization}/teams')[0]?.path).toBe('/organizations/acme/teams')
      expect(tableRow(wrapper, 'roster-team-row-ops').find('a').attributes('href')).toBe('/roster/organizations/acme/teams/ops')
    })

    it('creates a team, then reloads', async () => {
      rosterWith({ 'POST /organizations/{organization}/teams': reply(201, { data: ops }) })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=teams')
      await wrapper.find('[data-testid="roster-team-create-name"]').setValue('Sales')
      await wrapper.find('[data-testid="roster-team-create-form"]').trigger('submit')
      await settle()

      const [call] = rosterCalls('POST', '/organizations/{organization}/teams')
      expect(call?.path).toBe('/organizations/acme/teams')
      expect(call?.body).toEqual({ name: 'Sales', slug: null })
      expect(rosterCalls('GET', '/organizations/{organization}/teams').length).toBeGreaterThanOrEqual(2)
    })
  })

  describe('the invitations tab', () => {
    it('lists the invitations, with revoke on a pending one only', async () => {
      rosterWith()

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=invitations')

      expect(rosterCalls('GET', '/organizations/{organization}/invitations')[0]?.path).toBe('/organizations/acme/invitations')
      expect(tableRow(wrapper, `roster-invitation-row-${invitation.id}`).text()).toContain('new@example.com')
      expect(wrapper.find(`[data-testid="roster-invitation-revoke-${invitation.id}"]`).exists()).toBe(true)
      expect(wrapper.find(`[data-testid="roster-invitation-revoke-${acceptedInvitation.id}"]`).exists()).toBe(false)
    })

    it('invites an email to the chosen teams, then reloads', async () => {
      rosterWith({ 'POST /organizations/{organization}/invitations': reply(201, { data: invitation }) as never })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=invitations')
      await wrapper.find('[data-testid="roster-invite-email"]').setValue('new@example.com')
      await wrapper.find('[data-testid="roster-invite-team-ops"]').trigger('click')
      await wrapper.find('[data-testid="roster-invite-form"]').trigger('submit')
      await settle()

      const [call] = rosterCalls('POST', '/organizations/{organization}/invitations')
      expect(call?.path).toBe('/organizations/acme/invitations')
      expect(call?.body).toEqual({ email: 'new@example.com', teams: ['ops'] })
      expect(rosterCalls('GET', '/organizations/{organization}/invitations')).toHaveLength(2)
    })

    it('revokes a pending invitation, then reloads', async () => {
      rosterWith({ 'DELETE /organizations/{organization}/invitations/{invitation}': { data: { ...invitation, status: 'revoked' } } as never })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=invitations')
      await wrapper.find(`[data-testid="roster-invitation-revoke-${invitation.id}"]`).trigger('click')
      await settle()

      const [call] = rosterCalls('DELETE', '/organizations/{organization}/invitations/{invitation}')
      expect(call?.path).toBe(`/organizations/acme/invitations/${invitation.id}`)
      expect(rosterCalls('GET', '/organizations/{organization}/invitations')).toHaveLength(2)
    })
  })

  describe('the settings tab', () => {
    it('updates the organization with the old form\'s fields, then reloads', async () => {
      rosterWith({ 'PATCH /organizations/{organization}': { data: acmeDetail } })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=settings')
      await wrapper.find('[data-testid="roster-settings-name"]').setValue('Acme Inc')
      await wrapper.find('[data-testid="roster-settings-domains"]').setValue('acme.test\nacme.example')
      await chooseOption(wrapper, 'roster-settings-status', 'inactive')
      await wrapper.find('[data-testid="roster-settings-status-reason"]').setValue('Closed')
      await wrapper.find('[data-testid="roster-organization-settings-form"]').trigger('submit')
      await settle()

      const [call] = rosterCalls('PATCH', '/organizations/{organization}')
      expect(call?.path).toBe('/organizations/acme')
      expect(call?.body).toEqual({
        name: 'Acme Inc',
        slug: 'acme',
        domains: ['acme.test', 'acme.example'],
        auto_join: false,
        provisioned_status: 'active',
        status: 'inactive',
        status_reason: 'Closed'
      })
      expect(rosterCalls('GET', '/organizations/{organization}')).toHaveLength(2)
    })

    it('follows a changed slug to the organization\'s new page', async () => {
      rosterWith({ 'PATCH /organizations/{organization}': { data: { ...acmeDetail, slug: 'acme-inc' } } })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=settings')
      await wrapper.find('[data-testid="roster-settings-slug"]').setValue('acme-inc')
      await wrapper.find('[data-testid="roster-organization-settings-form"]').trigger('submit')
      await settle()

      await vi.waitFor(() => expect(useRouter().currentRoute.value.fullPath).toBe('/roster/organizations/acme-inc?tab=settings'))
    })

    it('shows the field errors of a 422 on update', async () => {
      rosterWith({ 'PATCH /organizations/{organization}': reply(422, { message: 'Invalid.', errors: { slug: ['The slug is taken.'] } }) })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=settings')
      await wrapper.find('[data-testid="roster-organization-settings-form"]').trigger('submit')
      await settle()

      expect(wrapper.text()).toContain('The slug is taken.')
    })

    it('links an external record by source, then reloads', async () => {
      rosterWith({ 'PUT /organizations/{organization}/links/{source}': { data: acmeDetail } as never })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=settings')
      await wrapper.find('[data-testid="roster-link-source"]').setValue('erp')
      await wrapper.find('[data-testid="roster-link-external-id"]').setValue('E-9')
      await wrapper.find('[data-testid="roster-link-form"]').trigger('submit')
      await settle()

      const [call] = rosterCalls('PUT', '/organizations/{organization}/links/{source}')
      expect(call?.path).toBe('/organizations/acme/links/erp')
      expect(call?.body).toEqual({ source: 'erp', external_id: 'E-9', account_number: null })
      expect(rosterCalls('GET', '/organizations/{organization}')).toHaveLength(2)
    })

    it('unlinks an external record, then reloads', async () => {
      rosterWith({ 'DELETE /organizations/{organization}/links/{source}': reply(204) })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=settings')
      await wrapper.find('[data-testid="roster-unlink-crm"]').trigger('click')
      await settle()

      expect(rosterCalls('DELETE', '/organizations/{organization}/links/{source}')[0]?.path).toBe('/organizations/acme/links/crm')
      expect(rosterCalls('GET', '/organizations/{organization}')).toHaveLength(2)
    })

    it('deletes only after a confirmation, then offers restore', async () => {
      const confirm = confirmWith(false)
      rosterWith({
        'DELETE /organizations/{organization}': reply(204),
        'POST /organizations/{organization}/restore': { data: acmeDetail }
      })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=settings')
      await wrapper.find('[data-testid="roster-organization-delete"]').trigger('click')
      await settle()

      expect(confirm).toHaveBeenCalled()
      expect(rosterCalls('DELETE', '/organizations/{organization}')).toHaveLength(0)

      confirm.mockReturnValue(true)
      await wrapper.find('[data-testid="roster-organization-delete"]').trigger('click')
      await settle()

      expect(rosterCalls('DELETE', '/organizations/{organization}')[0]?.path).toBe('/organizations/acme')
      expect(wrapper.find('[data-testid="roster-organization-deleted"]').exists()).toBe(true)

      await wrapper.find('[data-testid="roster-organization-restore"]').trigger('click')
      await settle()

      expect(rosterCalls('POST', '/organizations/{organization}/restore')[0]?.path).toBe('/organizations/acme/restore')
      expect(wrapper.find('[data-testid="roster-organization-deleted"]').exists()).toBe(false)
    })

    it('purges a deleted organization only after a confirmation, then shows the deleted tab', async () => {
      const confirm = confirmWith(true)
      rosterWith({
        'DELETE /organizations/{organization}': reply(204),
        'DELETE /organizations/{organization}/purge': reply(202, { message: 'Purge queued.' })
      })

      const wrapper = await mountRosterPage(Page, '/roster/organizations/acme?tab=settings')
      await wrapper.find('[data-testid="roster-organization-delete"]').trigger('click')
      await settle()

      confirm.mockReturnValue(false)
      await wrapper.find('[data-testid="roster-organization-purge"]').trigger('click')
      await settle()

      expect(rosterCalls('DELETE', '/organizations/{organization}/purge')).toHaveLength(0)

      confirm.mockReturnValue(true)
      await wrapper.find('[data-testid="roster-organization-purge"]').trigger('click')
      await settle()

      expect(rosterCalls('DELETE', '/organizations/{organization}/purge')[0]?.path).toBe('/organizations/acme/purge')
      await vi.waitFor(() => expect(useRouter().currentRoute.value.fullPath).toBe('/roster/organizations?trashed=only'))
    })
  })

  it('shows the no-access state on a 403', async () => {
    rosterWith({ 'GET /organizations/{organization}': reply(403) })

    const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')

    expect(wrapper.find('[data-testid="atrium-no-access"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-organization-tabs"]').exists()).toBe(false)
  })

  it('shows the not-found state on a 404', async () => {
    rosterWith({ 'GET /organizations/{organization}': reply(404) })

    const wrapper = await mountRosterPage(Page, '/roster/organizations/acme')

    expect(wrapper.find('[data-testid="roster-not-found"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="roster-organization-tabs"]').exists()).toBe(false)
  })
})
