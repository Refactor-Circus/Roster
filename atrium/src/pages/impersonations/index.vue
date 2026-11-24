<script setup lang="ts">
import RosterActionError from '../../components/users/RosterActionError.vue'
import RosterPager from '../../components/users/RosterPager.vue'
import RosterState from '../../components/users/RosterState.vue'
import {
  formatDate,
  pageOf,
  queryPage,
  rosterData,
  userName,
  useRosterAction,
  useRosterLoad,
  type RosterImpersonation,
  type RosterPage
} from '../../components/users/users'
import type { TableColumn } from '@nuxt/ui'

/**
 * `roster.impersonations`: every impersonation the user may review
 * (`roster.impersonations.index`), newest first, a page at a time, ported
 * from v1's `impersonations/index`. One that has not ended can be stopped
 * (`roster.impersonations.destroy`); Roster answers 403 to a user who may not.
 */
const { t } = useI18n()
const route = useRoute()
const action = useRosterAction()

const query = computed(() => {
  const page = queryPage(route.query.page)

  return page > 1 ? { page } : {}
})

const { client, data, pending, failed, status, reload } = useRosterLoad(async roster =>
  rosterData<RosterPage<RosterImpersonation>>(await roster.GET('/impersonations', { params: { query: query.value } })), query)

const impersonations = computed(() => data.value?.data ?? [])
const paging = computed(() => pageOf(data.value))

const columns = computed<TableColumn<RosterImpersonation>[]>(() => [
  { id: 'impersonator', header: t('roster.impersonations.impersonator') },
  { id: 'user', header: t('roster.impersonations.user') },
  { accessorKey: 'reason', header: t('roster.users.fields.reason') },
  { id: 'status', header: t('roster.users.fields.status') },
  { id: 'actions', header: t('roster.users.fields.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])

async function stop(impersonation: RosterImpersonation): Promise<void> {
  if (client && await action.run(async () => rosterData(await client.DELETE('/impersonations/{impersonation}', {
    params: { path: { impersonation: impersonation.id } }
  })))) {
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-impersonations">
    <UPageHeader :title="t('roster.impersonations.title')" />

    <AtriumIntegrationScreen screen-key="roster.impersonations">
      <RosterState :ready="!pending && data !== null" :failed="failed" :status="status">
        <div class="space-y-4">
          <RosterActionError :action="action" />

          <UEmpty v-if="impersonations.length === 0" icon="i-lucide-venetian-mask" :title="t('roster.impersonations.empty')" data-testid="roster-impersonations-empty" />
          <UTable v-else :data="impersonations" :columns="columns" class="text-sm" data-testid="roster-impersonations-table">
            <template #impersonator-cell="{ row: { original: impersonation } }">
              <span :data-testid="`roster-impersonation-${impersonation.id}`">{{ userName(impersonation.impersonator) ?? t('roster.users.none') }}</span>
            </template>
            <template #user-cell="{ row: { original: impersonation } }">{{ userName(impersonation.user) ?? t('roster.users.none') }}</template>
            <template #status-cell="{ row: { original: impersonation } }">
              <UBadge v-if="impersonation.active" color="warning" variant="subtle" data-testid="roster-impersonation-active">{{ t('roster.impersonations.active') }}</UBadge>
              <span v-else-if="impersonation.ended_at">{{ impersonation.end_reason ?? t('roster.impersonations.ended') }} · {{ formatDate(impersonation.ended_at) }}</span>
              <span v-else>{{ t('roster.impersonations.link_issued') }}</span>
            </template>
            <template #actions-cell="{ row: { original: impersonation } }">
              <div class="flex justify-end">
                <UButton
                  v-if="!impersonation.ended_at"
                  size="sm"
                  color="error"
                  icon="i-lucide-square"
                  :loading="action.busy.value"
                  data-testid="roster-impersonation-stop"
                  @click="stop(impersonation)"
                >
                  {{ t('roster.impersonations.stop') }}
                </UButton>
              </div>
            </template>
          </UTable>

          <RosterPager :current="paging.current" :last="paging.last" testid="roster-impersonations" />
        </div>
      </RosterState>
    </AtriumIntegrationScreen>
  </div>
</template>
