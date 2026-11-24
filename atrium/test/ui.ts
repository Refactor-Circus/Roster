import { DOMWrapper, type VueWrapper } from '@vue/test-utils'
import { nextTick, type ComponentPublicInstance } from 'vue'

/*
 * Driving the module's Nuxt UI fields and tables in tests (D-039): a
 * `USelect` keeps its options in a popper until it opens and a `UTable` row
 * takes no attributes, so these reach them by their `data-testid`.
 */

type Wrapper = VueWrapper | DOMWrapper<Element>

/** The `USelect` whose trigger carries `data-testid="{testid}"`. */
function select(wrapper: Wrapper, testid: string): VueWrapper<ComponentPublicInstance<{ items: { value: string }[], modelValue: unknown }>> {
  const found = (wrapper.findAllComponents({ name: 'Select' }) as VueWrapper[]).find(component => component.find(`[data-testid="${testid}"]`).exists())

  if (!found) {
    throw new Error(`No USelect with data-testid="${testid}"`)
  }

  return found as VueWrapper<ComponentPublicInstance<{ items: { value: string }[], modelValue: unknown }>>
}

/** Chooses `value` in a `USelect`, as picking its option does. */
export async function choose(wrapper: Wrapper, testid: string, value: string): Promise<void> {
  select(wrapper, testid).vm.$emit('update:modelValue', value)
  await nextTick()
}

/** The values a `USelect` offers, in order (disabled ones included). */
export function choices(wrapper: Wrapper, testid: string): string[] {
  return select(wrapper, testid).vm.items.map(item => item.value)
}

/** The value a `USelect` shows. */
export function chosen(wrapper: Wrapper, testid: string): unknown {
  return select(wrapper, testid).vm.modelValue
}

/** The `UTable` row holding the cell marked `data-testid="{testid}"`. */
export function row(wrapper: Wrapper, testid: string): DOMWrapper<Element> {
  const tr = wrapper.find(`[data-testid="${testid}"]`).element?.closest('tr')

  if (!tr) {
    throw new Error(`No table row marked data-testid="${testid}"`)
  }

  return new DOMWrapper(tr)
}

/** Whether a row marked `data-testid="{testid}"` is on the page. */
export function hasRow(wrapper: Wrapper, testid: string): boolean {
  return wrapper.find(`[data-testid="${testid}"]`).exists()
}
