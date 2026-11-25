<script setup lang="ts">
/**
 * What an organization or team page (or one of its cards) shows while it
 * loads: a skeleton, then its content, or the no-access state on a 403, a
 * not-found state on a 404 and an error card on any other failure.
 */
withDefaults(defineProps<{ ready: boolean, failed: boolean, status: number | null, compact?: boolean, notFound?: string }>(), {
  compact: false,
  notFound: undefined
})

defineSlots<{ default?: () => unknown }>()

const { t } = useI18n()
</script>

<template>
  <AtriumNoAccess v-if="failed && status === 403" :compact="compact" />
  <UAlert
    v-else-if="failed && status === 404"
    color="neutral"
    variant="subtle"
    icon="i-lucide-search-x"
    :title="notFound ?? t('roster.organizations.state.not_found')"
    data-testid="roster-not-found"
  />
  <AtriumErrorCard v-else-if="failed" :message="t('roster.organizations.state.load_failed')" />
  <slot v-else-if="ready" />
  <USkeleton v-else class="h-32" data-testid="roster-loading" />
</template>
