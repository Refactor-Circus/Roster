import { describe, expect, it } from 'vitest'
import { AtriumRegistry } from '@refactor-circus/atrium'
import Roster, { screens } from '../src/atrium'
import OrganizationsWidget from '../src/components/widgets/OrganizationsWidget.vue'
import UserStatusWidget from '../src/components/widgets/UserStatusWidget.vue'
import { searchRosterOrganizations, searchRosterUsers } from '../src/components/search/sources'
import { missingI18nKeys } from '@refactor-circus/atrium/testing'
import fixture from '@refactor-circus/atrium/fixtures/integrations/roster.json'

/*
 * Roster's `index.ts` (AC 49, 60, 62, 69) against what its PHP integration
 * declares in `fixtures/integrations/roster.json`: a route for every screen,
 * and a registration for every widget and search source.
 */

/** The route name Roster gives the page at a declared path (Nuxt's file-based convention): `/roster/users/:user` is `roster-users-user`. */
function routeNameFor(path: string): string {
  return path.slice(1).split('/').map(part => part.replace(/^:/, '')).join('-')
}

describe('the roster screens map', () => {
  it('has an entry for every screen key in the fixture, and no other', () => {
    const declared = fixture.screens.map(screen => screen.key)

    expect(Object.keys(screens).sort()).toEqual([...declared].sort())
    expect(declared).toHaveLength(11)
  })

  it('names, for each screen, the page at its declared path', () => {
    for (const screen of fixture.screens) {
      expect(screens[screen.key], screen.key).toBe(routeNameFor(screen.path))
    }
  })

  it('covers every screen of AC 69 and the public invitation page', () => {
    expect(screens).toMatchObject({
      'roster.users': 'roster-users',
      'roster.user': 'roster-users-user',
      'roster.organization': 'roster-organizations-organization',
      'roster.team': 'roster-organizations-organization-teams-team',
      'roster.invitation': 'roster-invitations-token'
    })
  })

  it('maps no roles or permissions screen, which moved to Guardian (AC 99, D-046)', () => {
    expect(Object.keys(screens)).not.toContain('roster.roles')
    expect(Object.keys(screens)).not.toContain('roster.role')
    expect(Object.keys(screens)).not.toContain('roster.permissions')
  })
})

/** A fresh registry holding what Roster's module definition registers, as `installAtriumModules()` does. */
function registryOf(definition: typeof Roster): AtriumRegistry {
  const atrium = new AtriumRegistry()

  for (const [key, component] of Object.entries(definition.widgets ?? {})) atrium.widget(key, component)
  for (const [key, component] of Object.entries(definition.extensions ?? {})) atrium.extension(key, component)
  for (const [key, component] of Object.entries(definition.settingsPanels ?? {})) atrium.settingsPanel(key, component)
  for (const [key, resolver] of Object.entries(definition.liveResolvers ?? {})) atrium.live(key, resolver)
  for (const [key, resolver] of Object.entries(definition.searchSources ?? {})) atrium.searchSource(key, resolver)

  return atrium
}

describe('the roster registrations', () => {
  const atrium = registryOf(Roster)

  it('registers a component for every widget the fixture declares', () => {
    expect(fixture.widgets.map(widget => widget.component)).toEqual(['roster.user-status', 'roster.organizations'])

    for (const widget of fixture.widgets) {
      expect(atrium.widgets.has(widget.component), widget.key).toBe(true)
    }

    expect(atrium.widgets.get('roster.user-status')).toBe(UserStatusWidget)
    expect(atrium.widgets.get('roster.organizations')).toBe(OrganizationsWidget)
  })

  it('registers a resolver for every search source the fixture declares', () => {
    expect(fixture.search_sources.map(source => source.key)).toEqual(['roster-users', 'roster-organizations'])

    expect(atrium.searchSources.get('roster-users')).toBe(searchRosterUsers)
    expect(atrium.searchSources.get('roster-organizations')).toBe(searchRosterOrganizations)
  })

  it('registers no settings panel, extension or live resolver', () => {
    expect(fixture.settings).toBeNull()
    expect(atrium.panels.size).toBe(0)
    expect(atrium.extensions.size).toBe(0)
    expect(atrium.liveResolvers.size).toBe(0)
  })

  it('declares nav items with layer paths under /roster/ and no badge', () => {
    expect(fixture.navigation.map(item => item.link?.path)).toEqual([
      '/roster/users',
      '/roster/organizations',
      '/roster/impersonations',
      '/roster/transfers'
    ])

    for (const item of fixture.navigation) {
      expect(item.badge, item.label).toBeNull()
    }
  })
})

describe('the roster strings', () => {
  it('has every t() key it uses in its locale files', () => {
    expect(missingI18nKeys('roster')).toEqual([])
  })
})
