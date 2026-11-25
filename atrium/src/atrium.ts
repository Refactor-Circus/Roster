import { searchRosterOrganizations, searchRosterUsers } from './components/search/sources'
import OrganizationsWidget from './components/widgets/OrganizationsWidget.vue'
import UserStatusWidget from './components/widgets/UserStatusWidget.vue'
import './atrium.css'
import { defineAtriumModule, mergeAtriumMessages } from '@refactor-circus/atrium'
import roster_access_en from './locales/roster-access.en.json'
import roster_organizations_en from './locales/roster-organizations.en.json'
import roster_users_en from './locales/roster-users.en.json'
import roster_en from './locales/roster.en.json'
import { routes } from './routes'

/*
 * Roster in the layer (AC 60, 62, 69): its two widgets, its two ⌘K sources
 * and the map of its screens to their pages. The pages live under
 * `app/pages/roster/`, split by area (users and impersonations;
 * organizations, teams and the public invitation page; transfers). Each
 * area's index page lists the v1 actions it does not port (D-032). Roles and
 * permissions moved to the Guardian integration (AC 99).
 */

/**
 * Each screen key Roster declares (`src/integration.json`) to the
 * name of the layer route that renders it: the page at the declared path.
 */
export const screens: Record<string, string> = {
  'roster.users': 'roster-users',
  'roster.user-create': 'roster-users-create',
  'roster.user': 'roster-users-user',
  'roster.organizations': 'roster-organizations',
  'roster.organization-create': 'roster-organizations-create',
  'roster.organization': 'roster-organizations-organization',
  'roster.team': 'roster-organizations-organization-teams-team',
  'roster.impersonations': 'roster-impersonations',
  'roster.transfers': 'roster-transfers',
  'roster.transfer': 'roster-transfers-transfer',
  // The public invitation page (AC 70), outside the shell.
  'roster.invitation': 'roster-invitations-token'
}

/**
 * Roster's Atrium module (D-082): everything above, registered while the
 * roster integration is active.
 */
export default defineAtriumModule({
  key: 'roster',
  routes,
  screens,
  widgets: {
    'roster.user-status': UserStatusWidget,
    'roster.organizations': OrganizationsWidget
  },
  searchSources: {
    'roster-users': searchRosterUsers,
    'roster-organizations': searchRosterOrganizations
  },
  messages: { en: mergeAtriumMessages(roster_access_en, roster_organizations_en, roster_users_en, roster_en) }
})
