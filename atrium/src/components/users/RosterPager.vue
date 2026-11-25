<script setup lang="ts">
/**
 * Page paging, as Roster's list endpoints page: the previous and next page
 * go in the route's `?page=` (left out for the first), which the page sends
 * as the endpoint's `page`.
 */
const props = defineProps<{ current: number, last: number, testid: string }>()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

function go(page: number): void {
  router.replace({ query: { ...route.query, page: page > 1 ? String(page) : undefined } })
}
</script>

<template>
  <div v-if="props.last > 1" class="flex items-center gap-3 text-sm">
    <UButton
      variant="outline"
      size="sm"
      icon="i-lucide-chevron-left"
      :disabled="props.current <= 1"
      :data-testid="`${testid}-prev`"
      @click="go(props.current - 1)"
    >
      {{ t('roster.users.pager.previous') }}
    </UButton>
    <span class="text-muted" :data-testid="`${testid}-page`">{{ t('roster.users.pager.page', { current: props.current, last: props.last }) }}</span>
    <UButton
      variant="outline"
      size="sm"
      trailing-icon="i-lucide-chevron-right"
      :disabled="props.current >= props.last"
      :data-testid="`${testid}-next`"
      @click="go(props.current + 1)"
    >
      {{ t('roster.users.pager.next') }}
    </UButton>
  </div>
</template>
