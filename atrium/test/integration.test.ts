import { describe, expect, it } from 'vitest'
import { AtriumRegistry } from '@refactor-circus/atrium'
import Roster, { screens } from '../src/atrium'
import OrganizationsWidget from '../src/components/widgets/OrganizationsWidget.vue'
import UserStatusWidget from '../src/components/widgets/UserStatusWidget.vue'
import { searchRosterOrganizations, searchRosterUsers } from '../src/components/search/sources'
import { expectIntegrationManifest, missingI18nKeys } from '@refactor-circus/atrium/testing'

/*
 * Roster's `atrium.ts` (AC 49, 60, 62, 69) against its integration manifest
 * (`src/integration.json`, D-090): the manifest is valid as Atrium checks it,
 * and the module has a route for every screen and a registration for every
 * widget, search source and hook it declares.
 */
const manifest = expectIntegrationManifest('src/integration.json', 'roster')

/** A manifest list field, typed as the records it holds. */
function listOf<T>(field: unknown): T[] {
  return (field ?? []) as T[]
}

const declared = {
  screens: manifest.screens ?? [],
  widgets: listOf<{ key: string, component?: string }>(manifest.widgets).map(widget => ({ ...widget, component: widget.component ?? widget.key })),
  search_sources: listOf<{ key: string }>(manifest.search_sources),
  navigation: listOf<{ label: string, path: string }>(manifest.navigation),
  hooks: listOf<{ tabs?: Record<string, unknown>, sections?: Record<string, unknown> }>(manifest.hooks),
  settings: manifest.settings ?? null
}

describe('the roster integration manifest', () => {
  it('is valid, keyed roster, behind the roster.atrium feature', () => {
    expect(manifest.version).toBe(1)
    expect(manifest.label).toBe('Roster')
    expect(manifest.features).toEqual(['roster.atrium'])
  })

  it('declares the subject types other packages hook onto', () => {
    expect(declared.screens.filter(screen => screen.subject_type !== undefined).map(screen => [screen.key, screen.subject_type])).toEqual([
      ['roster.user', 'roster.user'],
      ['roster.organization', 'roster.organization']
    ])
  })
})

/** The route name Roster gives the page at a declared path (Nuxt's file-based convention): `/roster/users/:user` is `roster-users-user`. */
function routeNameFor(path: string): string {
  return path.slice(1).split('/').map(part => part.replace(/^:/, '')).join('-')
}

describe('the roster screens map', () => {
  it('has an entry for every screen key in the manifest, and no other', () => {
    const keys = declared.screens.map(screen => screen.key)

    expect(Object.keys(screens).sort()).toEqual([...keys].sort())
    expect(keys).toHaveLength(11)
  })

  it('names, for each screen, the page at its declared path', () => {
    for (const screen of declared.screens) {
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

  it('registers a component for every widget the manifest declares', () => {
    expect(declared.widgets.map(widget => widget.component)).toEqual(['roster.user-status', 'roster.organizations'])

    for (const widget of declared.widgets) {
      expect(atrium.widgets.has(widget.component), widget.key).toBe(true)
    }

    expect(atrium.widgets.get('roster.user-status')).toBe(UserStatusWidget)
    expect(atrium.widgets.get('roster.organizations')).toBe(OrganizationsWidget)
  })

  it('registers a resolver for every search source the manifest declares', () => {
    expect(declared.search_sources.map(source => source.key)).toEqual(['roster-users', 'roster-organizations'])

    expect(atrium.searchSources.get('roster-users')).toBe(searchRosterUsers)
    expect(atrium.searchSources.get('roster-organizations')).toBe(searchRosterOrganizations)
  })

  it('registers no settings panel, extension or live resolver', () => {
    expect(declared.settings).toBeNull()
    expect(atrium.panels.size).toBe(0)
    expect(atrium.extensions.size).toBe(0)
    expect(atrium.liveResolvers.size).toBe(0)
  })

  it('registers an extension for every hook tab and section the manifest declares (none)', () => {
    const keys = declared.hooks.flatMap(hook => [...Object.keys(hook.tabs ?? {}), ...Object.keys(hook.sections ?? {})])

    for (const key of keys) {
      expect(atrium.extensions.has(key), key).toBe(true)
    }

    expect(keys).toEqual([])
  })

  it('declares nav items with layer paths under /roster/ and no live badge', () => {
    expect(declared.navigation.map(item => item.path)).toEqual([
      '/roster/users',
      '/roster/organizations',
      '/roster/impersonations',
      '/roster/transfers'
    ])

    for (const item of declared.navigation) {
      expect(item, item.label).not.toHaveProperty('live')
    }
  })
})

describe('the roster strings', () => {
  it('has every t() key it uses in its locale files', () => {
    expect(missingI18nKeys('roster')).toEqual([])
  })
})
