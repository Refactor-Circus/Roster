<script setup lang="ts">
import { integrationData } from '@refactor-circus/atrium'
import { useIntegrationQuery } from '@refactor-circus/atrium'
import { countOf, type RosterOrganizationStats } from './stats'

/**
 * `roster.organizations`: how many organizations, teams and pending
 * invitations there are, from `roster.stats.organizations` (AC 60). The PHP
 * side sends `data: {}`; every widget of this kind on a dashboard shares one
 * request. A 403 shows the no-access card, any other failure the error card.
 */
defineProps<{ data?: Record<string, unknown>, settings?: Record<string, unknown> }>()

const { t } = useI18n()

const { data: stats, status, forbidden } = useIntegrationQuery('roster', 'roster.stats.organizations', async client =>
  // Roster's schema types the counts loosely (strings); the resource sends integers.
  integrationData(await client.GET('/stats/organizations')).data as unknown as RosterOrganizationStats
)

const counts = computed(() => stats.value === null
  ? []
  : [
      { key: 'organizations', label: t('roster.widgets.organizations.organizations'), count: countOf(stats.value.organizations) },
      { key: 'teams', label: t('roster.widgets.organizations.teams'), count: countOf(stats.value.teams) },
      { key: 'pending_invitations', label: t('roster.widgets.organizations.pending_invitations'), count: countOf(stats.value.pending_invitations) }
    ])
</script>

<template>
  <div class="h-full" data-testid="roster-organizations">
    <AtriumNoAccess v-if="forbidden" compact />
    <AtriumErrorCard v-else-if="status === 'error'" :message="t('atrium.widget.failed')" />
    <dl v-else-if="stats" class="grid h-full grid-cols-3 gap-3">
      <div v-for="item in counts" :key="item.key" class="flex flex-col-reverse" data-testid="roster-organizations-count" :data-count="item.key">
        <dt class="text-sm text-muted">{{ item.label }}</dt>
        <dd class="text-2xl font-semibold">{{ item.count }}</dd>
      </div>
    </dl>
    <div v-else class="grid grid-cols-3 gap-3">
      <USkeleton v-for="n in 3" :key="n" class="h-10" />
    </div>
  </div>
</template>
