<script setup lang="ts">
/**
 * What a roles, permissions or transfers page shows while it loads: a
 * skeleton, then its content, or the no-access state on a 403, the page's
 * not-found state on a 404 and an error card on any other failure.
 */
withDefaults(defineProps<{ ready: boolean, failed: boolean, status: number | null, notFound?: string }>(), {
  notFound: undefined
})

defineSlots<{ default?: () => unknown }>()

const { t } = useI18n()
</script>

<template>
  <AtriumNoAccess v-if="failed && status === 403" />
  <UAlert
    v-else-if="failed && status === 404"
    color="neutral"
    variant="subtle"
    icon="i-lucide-search-x"
    :title="notFound ?? t('roster.access.not_found')"
    data-testid="roster-not-found"
  />
  <AtriumErrorCard v-else-if="failed" :message="t('roster.access.load_failed')" />
  <slot v-else-if="ready" />
  <USkeleton v-else class="h-32" data-testid="roster-loading" />
</template>
