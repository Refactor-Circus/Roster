import type { RouteRecordRaw } from 'vue-router'

/**
 * Roster's pages, added to Atrium's router before its first navigation.
 * Names follow Nuxt's file-based ones (`pages/{pkg}/runs/[run].vue` ->
 * `{pkg}-runs-run`), which the screen declarations name.
 */
export const routes: RouteRecordRaw[] = [
  { path: '/roster/impersonations', name: 'roster-impersonations', component: () => import('./pages/impersonations/index.vue') },
  { path: '/roster/invitations/:token', name: 'roster-invitations-token', component: () => import('./pages/invitations/[token].vue'), meta: { atrium: { public: true } } },
  { path: '/roster/organizations/:organization', name: 'roster-organizations-organization', component: () => import('./pages/organizations/[organization]/index.vue') },
  { path: '/roster/organizations/:organization/teams/:team', name: 'roster-organizations-organization-teams-team', component: () => import('./pages/organizations/[organization]/teams/[team].vue') },
  { path: '/roster/organizations/create', name: 'roster-organizations-create', component: () => import('./pages/organizations/create.vue') },
  { path: '/roster/organizations', name: 'roster-organizations', component: () => import('./pages/organizations/index.vue') },
  { path: '/roster/transfers/:transfer', name: 'roster-transfers-transfer', component: () => import('./pages/transfers/[transfer].vue') },
  { path: '/roster/transfers', name: 'roster-transfers', component: () => import('./pages/transfers/index.vue') },
  { path: '/roster/users/:user', name: 'roster-users-user', component: () => import('./pages/users/[user].vue') },
  { path: '/roster/users/create', name: 'roster-users-create', component: () => import('./pages/users/create.vue') },
  { path: '/roster/users', name: 'roster-users', component: () => import('./pages/users/index.vue') }
]
