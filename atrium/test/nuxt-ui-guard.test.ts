import { readdirSync, readFileSync } from 'node:fs'
import { join, relative } from 'node:path'
import { fileURLToPath } from 'node:url'
import { describe, expect, it } from 'vitest'

/*
 * Roster's module composes Nuxt UI, as Atrium's components do (Atrium D-091,
 * Roster D-039): no page or component under `src` renders a raw interactive
 * element Nuxt UI provides. Buttons are <UButton>, fields <UInput>/<USelect>/
 * <UTextarea>/<UCheckbox>/<UFileUpload>, tables <UTable>, disclosures
 * <UCollapsible> and links <UButton to> or <ULink>. The same rules as
 * Atrium's `ui/sdk/test/nuxt-ui-guard.test.ts`.
 */

// Read through a variable: Vite rewrites `new URL('…', import.meta.url)`.
const here = import.meta.url
const atrium = fileURLToPath(new URL('../', here))
const root = join(atrium, 'src')

const RAW = [
  ['<button>', /<button[\s>/]/],
  ['<input>', /<input[\s>/]/],
  ['<select>', /<select[\s>/]/],
  ['<textarea>', /<textarea[\s>/]/],
  ['<table>', /<table[\s>/]/],
  ['<details>', /<details[\s>/]/],
  ['<a href>', /<a\s[^>]*:?href=/]
] as const

function vueFiles(dir: string): string[] {
  return readdirSync(dir, { withFileTypes: true }).flatMap(entry => entry.isDirectory()
    ? vueFiles(join(dir, entry.name))
    : entry.name.endsWith('.vue') ? [join(dir, entry.name)] : [])
}

/** The template, without comments. */
function template(file: string): string {
  const source = readFileSync(file, 'utf8')
  const start = source.indexOf('<template>')

  return start === -1 ? '' : source.slice(start).replace(/<!--[\s\S]*?-->/g, '')
}

function rawElements(source: string, allowed: readonly string[] = []): string[] {
  return RAW.filter(([name, pattern]) => !allowed.includes(name) && pattern.test(source)).map(([name]) => name)
}

describe('Roster\'s module composes Nuxt UI', () => {
  const files = vueFiles(root)

  it('finds the module\'s pages and components', () => {
    expect(files.length).toBeGreaterThan(20)
  })

  it.each(files.map(file => [relative(atrium, file).replaceAll('\\', '/'), file]))('%s renders no raw interactive element', (name, file) => {
    expect(rawElements(template(file)), `${name}: use the Nuxt UI component instead`).toEqual([])
  })

  it('catches each raw element it guards against', () => {
    expect(rawElements('<template><button type="button">x</button></template>')).toEqual(['<button>'])
    expect(rawElements('<input v-model="q"><select></select><textarea />')).toEqual(['<input>', '<select>', '<textarea>'])
    expect(rawElements('<table><tr /></table> <details><summary>x</summary></details>')).toEqual(['<table>', '<details>'])
    expect(rawElements('<a :href="url">x</a>')).toEqual(['<a href>'])
    expect(rawElements('<a :href="url">x</a>', ['<a href>'])).toEqual([])
    expect(rawElements('<button\n  type="button"\n>x</button>')).toEqual(['<button>'])
    expect(rawElements('<UButton to="/x" /><UInput /><UTable /><USelect /><abbr>x</abbr>')).toEqual([])
  })
})
