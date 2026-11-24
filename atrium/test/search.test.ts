import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import type { AtriumRegistry } from '@refactor-circus/atrium'
import type { AtriumSearchSource } from '@refactor-circus/atrium'
import Roster from '../src/atrium'
import { withSetup } from '@refactor-circus/atrium/testing'
import { integrationEntry, integrationShell, mockPackageApi, reply, resetPackageApis, useShell, type PackageBody } from '@refactor-circus/atrium/testing'
import fixture from '@refactor-circus/atrium/fixtures/integrations/roster.json'
import { useAtrium, useAtriumSearch } from '@refactor-circus/atrium'
import { mockAtriumApi } from '@refactor-circus/atrium/testing'

/*
 * Roster's ⌘K sources (AC 62): `roster-users` asks `roster.search.users` and
 * links each result to `/roster/users/:id`; `roster-organizations` asks
 * `roster.search.organizations` and links to `/roster/organizations/:slug`.
 * Each asks for at most `search.per_source` results.
 */

const { api } = vi.hoisted(() => ({
  api: vi.fn<(path: string, options?: unknown) => Promise<unknown>>()
}))

mockAtriumApi(api)

const sources = fixture.search_sources as AtriumSearchSource[]

const users = {
  data: [
    { id: 7, name: 'Ada Lovelace', email: 'ada@example.com' },
    { id: 'u-9', name: '', email: 'adam@example.com' }
  ]
} as unknown as PackageBody<'roster', 'GET /search/users'>

const organizations = {
  data: [
    { name: 'Acme Corp', slug: 'acme', status: 'active' },
    { name: 'Ad Astra', slug: 'ad astra', status: 'active' }
  ]
} as unknown as PackageBody<'roster', 'GET /search/organizations'>

function items(groups: { label?: string, items?: { label?: string }[] }[]) {
  return Object.fromEntries(groups.flatMap(group => group.items ?? []).map(item => [item.label, item]))
}

describe('the roster search sources', () => {
  let atrium: AtriumRegistry

  beforeEach(async () => {
    api.mockReset()
    api.mockResolvedValue({ data: [] })

    atrium = await withSetup(() => useAtrium())
    atrium.searchSources.clear()
    for (const [key, resolver] of Object.entries(Roster.searchSources ?? {})) {
      atrium.searchSource(key, resolver)
    }

    await useShell(integrationShell([integrationEntry('roster')], { search: { min_length: 2, sources, per_source: 3 } }))
  })

  afterEach(() => {
    atrium.searchSources.clear()
    resetPackageApis()
  })

  it('are the two sources the fixture declares', () => {
    expect(sources.map(source => [source.key, source.integration])).toEqual([
      ['roster-users', 'roster'],
      ['roster-organizations', 'roster']
    ])
  })

  it('ask roster.search.users and roster.search.organizations with the term and search.per_source', async () => {
    const calls = mockPackageApi('roster', { 'GET /search/users': users, 'GET /search/organizations': organizations })

    const search = await withSetup(() => useAtriumSearch())
    await search.search('ad')

    expect(calls.map(call => [`${call.method} ${call.route}`, call.query]).sort()).toEqual([
      ['GET /search/organizations', { q: 'ad', limit: '3' }],
      ['GET /search/users', { q: 'ad', limit: '3' }]
    ])
  })

  it('link users to /roster/users/:id and organizations to /roster/organizations/:slug', async () => {
    mockPackageApi('roster', { 'GET /search/users': users, 'GET /search/organizations': organizations })

    const search = await withSetup(() => useAtriumSearch())
    await search.search('ad')

    expect(search.results.value).toEqual([
      { title: 'Ada Lovelace', subtitle: 'ada@example.com', icon: null, group: 'Users', link: { path: '/roster/users/7', external: false } },
      { title: 'adam@example.com', subtitle: 'adam@example.com', icon: null, group: 'Users', link: { path: '/roster/users/u-9', external: false } },
      { title: 'Acme Corp', subtitle: 'acme', icon: null, group: 'Organizations', link: { path: '/roster/organizations/acme', external: false } },
      { title: 'Ad Astra', subtitle: 'ad astra', icon: null, group: 'Organizations', link: { path: '/roster/organizations/ad%20astra', external: false } }
    ])

    const byLabel = items(search.groups.value)
    expect(search.groups.value.map(group => group.label)).toEqual(['Users', 'Organizations'])
    expect(byLabel['Ada Lovelace']).toMatchObject({ to: '/roster/users/7', suffix: 'ada@example.com' })
    expect(byLabel['Acme Corp']).toMatchObject({ to: '/roster/organizations/acme', suffix: 'acme' })
  })

  it('drop a source the user may not search (403), and keep the other', async () => {
    mockPackageApi('roster', { 'GET /search/users': reply(403), 'GET /search/organizations': organizations })

    const search = await withSetup(() => useAtriumSearch())
    await search.search('ac')

    expect(search.results.value.map(result => result.title)).toEqual(['Acme Corp', 'Ad Astra'])
  })

  it('ask nothing while the roster integration is inactive', async () => {
    const calls = mockPackageApi('roster', { 'GET /search/users': users, 'GET /search/organizations': organizations })
    await useShell(integrationShell([integrationEntry('roster', { active: false })], { search: { min_length: 2, sources, per_source: 3 } }))

    const search = await withSetup(() => useAtriumSearch())
    await search.search('ad')

    expect(calls).toEqual([])
    expect(search.results.value).toEqual([])
  })
})
