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
    <h1 class="text-xl font-semibold">{{ t('roster.impersonations.title') }}</h1>

    <AtriumIntegrationScreen screen-key="roster.impersonations">
      <RosterState :ready="!pending && data !== null" :failed="failed" :status="status">
        <div class="space-y-4">
          <RosterActionError :action="action" />

          <p v-if="impersonations.length === 0" class="atrium-empty" data-testid="roster-impersonations-empty">{{ t('roster.impersonations.empty') }}</p>
          <table v-else class="w-full text-left text-sm" data-testid="roster-impersonations-table">
            <thead class="text-muted">
              <tr>
                <th class="py-2 pr-3 font-medium">{{ t('roster.impersonations.impersonator') }}</th>
                <th class="py-2 pr-3 font-medium">{{ t('roster.impersonations.user') }}</th>
                <th class="py-2 pr-3 font-medium">{{ t('roster.users.fields.reason') }}</th>
                <th class="py-2 pr-3 font-medium">{{ t('roster.users.fields.status') }}</th>
                <th class="py-2 text-right font-medium">{{ t('roster.users.fields.actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-default">
              <tr v-for="impersonation in impersonations" :key="impersonation.id" :data-testid="`roster-impersonation-${impersonation.id}`">
                <td class="py-2 pr-3">{{ userName(impersonation.impersonator) ?? t('roster.users.none') }}</td>
                <td class="py-2 pr-3">{{ userName(impersonation.user) ?? t('roster.users.none') }}</td>
                <td class="py-2 pr-3">{{ impersonation.reason }}</td>
                <td class="py-2 pr-3">
                  <UBadge v-if="impersonation.active" color="warning" variant="subtle" data-testid="roster-impersonation-active">{{ t('roster.impersonations.active') }}</UBadge>
                  <span v-else-if="impersonation.ended_at">{{ impersonation.end_reason ?? t('roster.impersonations.ended') }} · {{ formatDate(impersonation.ended_at) }}</span>
                  <span v-else>{{ t('roster.impersonations.link_issued') }}</span>
                </td>
                <td class="py-2">
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
                </td>
              </tr>
            </tbody>
          </table>

          <RosterPager :current="paging.current" :last="paging.last" testid="roster-impersonations" />
        </div>
      </RosterState>
    </AtriumIntegrationScreen>
  </div>
</template>
