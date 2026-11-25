import { readFileSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'
import { screens } from '../../src/atrium'
import fixture from '@refactor-circus/atrium/fixtures/integrations/roster.json'
import { missingI18nKeys } from '@refactor-circus/atrium/testing'
import { useRouter } from '@refactor-circus/atrium/testing'

/*
 * The organizations, teams and invitation area of Roster (AC 49, 53, 54, 69,
 * 70): each of its screen keys has a layer page at the path the PHP
 * integration declares (`fixtures/integrations/roster.json`). Every page but
 * the public invitation page is framed with its own screen key, so it calls
 * `atrium.screens.show` with it; the invitation page loads no shell and no
 * screen (D-038). The area's index page lists the v1 actions it does not port
 * (D-032), and every `t()` key exists.
 */

/** Each screen of this area to its page file, under `src/pages/`. */
const PAGES: Record<string, string> = {
  'roster.organizations': 'organizations/index.vue',
  'roster.organization-create': 'organizations/create.vue',
  'roster.organization': 'organizations/[organization]/index.vue',
  'roster.team': 'organizations/[organization]/teams/[team].vue',
  'roster.invitation': 'invitations/[token].vue'
}

const FRAMED = Object.entries(PAGES).filter(([key]) => key !== 'roster.invitation')

// Read through a variable: Vite rewrites `new URL('…', import.meta.url)`
// into a served asset URL, which `fileURLToPath()` refuses.
const here = import.meta.url

function source(file: string): string {
  return readFileSync(fileURLToPath(new URL(`../../src/pages/${file}`, here)), 'utf8')
}

describe('the roster organizations, teams and invitation pages', () => {
  it.each(Object.keys(PAGES))('has a page for %s at its declared path, named in the screens map', (key) => {
    const declared = fixture.screens.find(screen => screen.key === key)
    const name = screens[key]
    const route = useRouter().getRoutes().find(candidate => candidate.name === name)

    expect(declared, key).toBeDefined()
    expect(route, `${key} -> ${name}`).toBeDefined()
    expect(route!.path.replace(/\(\)/g, '')).toBe(declared!.path)
  })

  it.each(FRAMED)('frames the %s page with its own screen key', (key, file) => {
    expect(source(file)).toContain(`screen-key="${key}"`)
  })

  it('declares the invitation page public, with no screen frame', () => {
    const page = source('invitations/[token].vue')
    const route = useRouter().getRoutes().find(candidate => candidate.name === screens['roster.invitation'])

    // Its route (`src/routes.ts`) carries the public flag the page meta did in the Nuxt layer.
    expect(route?.meta.atrium).toEqual({ public: true })
    expect(page).not.toContain('AtriumIntegrationScreen')
  })

  it('lists the v1 actions it does not port in a Not ported (D-032) comment at the top of the organizations index page', () => {
    const lines = source('organizations/index.vue').split('\n')

    expect(lines[0]).toBe('<script setup lang="ts">')
    expect(lines[1]).toBe('// Not ported (D-032)')
  })

  it('has every t() key it uses in its locale files', () => {
    expect(missingI18nKeys('roster')).toEqual([])
  })
})
