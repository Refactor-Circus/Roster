<script setup lang="ts">
import { integrationData } from '@refactor-circus/atrium'
import { useIntegrationQuery } from '@refactor-circus/atrium'
import { countOf, type RosterUserStats } from './stats'

/**
 * `roster.user-status`: how many users there are, and how many hold each
 * status, from `roster.stats.users` (AC 60). The PHP side sends `data: {}`;
 * every widget of this kind on a dashboard shares one request. A 403 shows
 * the no-access card, any other failure the error card.
 */
defineProps<{ data?: Record<string, unknown>, settings?: Record<string, unknown> }>()

const { t, te } = useI18n()

const { data: stats, status, forbidden } = useIntegrationQuery('roster', 'roster.stats.users', async client =>
  // Roster's schema types the counts loosely (strings); the resource sends integers.
  integrationData(await client.GET('/stats/users')).data as unknown as RosterUserStats
)

const statuses = computed(() => Object.entries(stats.value?.by_status ?? {}).map(([key, count]) => ({
  key,
  label: te(`roster.widgets.user_status.statuses.${key}`) ? t(`roster.widgets.user_status.statuses.${key}`) : key,
  count: countOf(count)
})))
</script>

<template>
  <div class="h-full" data-testid="roster-user-status">
    <AtriumNoAccess v-if="forbidden" compact />
    <AtriumErrorCard v-else-if="status === 'error'" :message="t('atrium.widget.failed')" />
    <div v-else-if="stats" class="flex h-full flex-col gap-3">
      <div>
        <p class="text-3xl font-semibold" data-testid="roster-user-status-total">{{ countOf(stats.total) }}</p>
        <p class="text-sm text-muted">{{ t('roster.widgets.user_status.total') }}</p>
      </div>
      <ul class="grid grid-cols-2 gap-2 text-sm">
        <li
          v-for="row in statuses"
          :key="row.key"
          class="flex items-center justify-between gap-2"
          data-testid="roster-user-status-row"
          :data-status="row.key"
        >
          <span class="text-muted">{{ row.label }}</span>
          <span class="font-medium">{{ row.count }}</span>
        </li>
      </ul>
    </div>
    <div v-else class="space-y-2">
      <USkeleton class="h-8 w-1/3" />
      <USkeleton class="h-4 w-2/3" />
    </div>
  </div>
</template>
