<script setup lang="ts">
// Not ported (D-032)
// Roster's users and impersonations screens (`roster.users`, `roster.user-create`,
// `roster.user`, `roster.impersonations`) leave out these v1 features, which have
// no v2 API route:
// - The deleted user's page. v1's `users.show` rendered a deleted user, but v2's
//   `roster.users.show` answers 404 for one. Restore and purge are offered on the
//   Deleted tab, and on the user page right after a delete.
// - The "I understand" checkbox before delete and purge (v1's `confirm` field).
//   The v2 routes take no confirmation field, so a browser confirmation replaces it.
// - Roster's impersonation banner (`roster::impersonation-banner`) on each screen.
//   It is Roster's own view, with no API route.
// - Hiding each card and button by the viewer's permissions (v1's `@rosterCan`).
//   No API route reports them, so a 403 shows the no-access notice instead (D-031).
// - v1's single status route (`users.status`). It maps to `roster.users.suspend`,
//   `roster.users.deactivate` and `roster.users.reactivate` (Roster AC 6), so it is
//   ported as that mapping.
import RosterActionError from '../../components/users/RosterActionError.vue'
import RosterPager from '../../components/users/RosterPager.vue'
import RosterState from '../../components/users/RosterState.vue'
import RosterStatusBadge from '../../components/users/RosterStatusBadge.vue'
import RosterTabs from '../../components/users/RosterTabs.vue'
import {
  formatDate,
  pageOf,
  queryPage,
  queryValue,
  rosterData,
  USER_STATUSES,
  userName,
  userPath,
  useRosterAction,
  useRosterLoad,
  type RosterPage,
  type RosterUser,
  type RosterUserStatus
} from '../../components/users/users'
import type { TableColumn } from '@refactor-circus/atrium/ui'

/**
 * `roster.users`: every user (`roster.users.index`), ported from v1's
 * `users/index`. The Active and Deleted tabs (`trashed=only`), the status
 * filter and the search live in the URL and go to the endpoint as its query,
 * a page at a time. A pending user is approved from its row; a deleted one is
 * restored or purged (after a confirmation) from the Deleted tab.
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useRosterAction()

const deleted = computed(() => queryValue(route.query.trashed) === 'only')

const query = computed(() => {
  const status = queryValue(route.query.status) as RosterUserStatus | undefined
  const search = queryValue(route.query.search)
  const page = queryPage(route.query.page)

  return {
    ...(status ? { status } : {}),
    ...(search ? { search } : {}),
    ...(deleted.value ? { trashed: 'only' as const } : {}),
    ...(page > 1 ? { page } : {})
  }
})

const { client, data, pending, failed, status, reload } = useRosterLoad(async roster =>
  rosterData<RosterPage<RosterUser>>(await roster.GET('/users', { params: { query: query.value } })), query)

const users = computed(() => data.value?.data ?? [])
const paging = computed(() => pageOf(data.value))

const filters = reactive({ status: '', search: '' })

const statusItems = computed(() => USER_STATUSES.map(choice => ({ label: t(`roster.users.statuses.${choice}`), value: choice })))

const tabs = computed(() => [
  { key: 'active' as const, label: t('roster.users.tabs.active') },
  { key: 'deleted' as const, label: t('roster.users.tabs.deleted') }
])

const columns = computed<TableColumn<RosterUser>[]>(() => [
  { id: 'name', header: t('roster.users.fields.name') },
  { id: 'email', header: t('roster.users.fields.email') },
  { id: 'status', header: t('roster.users.fields.status') },
  { id: 'created', header: t('roster.users.fields.created') },
  { id: 'actions', header: t('roster.users.fields.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])

watch(() => [route.query.status, route.query.search], () => {
  filters.status = queryValue(route.query.status) ?? ''
  filters.search = queryValue(route.query.search) ?? ''
}, { immediate: true })

function showTab(trashed: boolean): void {
  router.replace({ query: { ...route.query, trashed: trashed ? 'only' : undefined, page: undefined } })
}

function filter(): void {
  router.replace({ query: { ...route.query, status: filters.status || undefined, search: filters.search || undefined, page: undefined } })
}

function clear(): void {
  router.replace({ query: { ...route.query, status: undefined, search: undefined, page: undefined } })
}

async function approve(user: RosterUser): Promise<void> {
  if (client && await action.run(async () => rosterData(await client.POST('/users/{user}/approve', { params: { path: { user: String(user.id) } } })))) {
    await reload()
  }
}

async function restore(user: RosterUser): Promise<void> {
  if (client && await action.run(async () => rosterData(await client.POST('/users/{user}/restore', { params: { path: { user: String(user.id) } } })))) {
    await reload()
  }
}

async function purge(user: RosterUser): Promise<void> {
  if (!client || !window.confirm(t('roster.users.danger.confirm_purge'))) {
    return
  }

  if (await action.run(async () => rosterData(await client.DELETE('/users/{user}/purge', { params: { path: { user: String(user.id) } } })))) {
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-users">
    <AtriumPageHeader :title="t('roster.users.title')">
      <UButton to="/roster/transfers" variant="outline" icon="i-lucide-arrow-up-down" data-testid="roster-users-transfers">{{ t('roster.users.import_export') }}</UButton>
      <UButton to="/roster/users/create" icon="i-lucide-plus" data-testid="roster-users-new">{{ t('roster.users.new') }}</UButton>
    </AtriumPageHeader>

    <AtriumIntegrationScreen screen-key="roster.users">
      <div class="space-y-4">
        <RosterTabs :tabs="tabs" :current="deleted ? 'deleted' : 'active'" :label="t('roster.users.title')" testid="roster-users-tab" @select="key => showTab(key === 'deleted')" />

        <UForm :state="filters" class="flex flex-wrap items-end gap-3" data-testid="roster-users-filters" @submit="filter">
          <UFormField :label="t('roster.users.filters.search')" name="search">
            <UInput v-model="filters.search" name="search" class="w-64" data-testid="roster-users-search" />
          </UFormField>
          <UFormField :label="t('roster.users.fields.status')" name="status">
            <AtriumSelect v-model="filters.status" :items="statusItems" :blank="t('roster.users.filters.all_statuses')" class="w-48" data-testid="roster-users-status" />
          </UFormField>
          <UButton type="submit" icon="i-lucide-filter" data-testid="roster-users-filter">{{ t('roster.users.filters.filter') }}</UButton>
          <UButton variant="ghost" icon="i-lucide-x" data-testid="roster-users-clear" @click="clear">{{ t('roster.users.filters.clear') }}</UButton>
        </UForm>

        <RosterState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <RosterActionError :action="action" />

            <UEmpty v-if="users.length === 0" icon="i-lucide-users" :title="t('roster.users.empty')" data-testid="roster-users-empty" />
            <UTable v-else :data="users" :columns="columns" data-testid="roster-users-table">
              <template #name-cell="{ row: { original: user } }">
                <span :data-testid="`roster-user-row-${user.id}`">
                  <ULink v-if="!deleted" :to="userPath(user.id)" class="font-medium text-default hover:underline">{{ userName(user) ?? t('roster.users.none') }}</ULink>
                  <span v-else class="font-medium">{{ userName(user) ?? t('roster.users.none') }}</span>
                </span>
              </template>
              <template #email-cell="{ row: { original: user } }">{{ user.email ?? t('roster.users.none') }}</template>
              <template #status-cell="{ row: { original: user } }">
                <UBadge v-if="deleted" color="neutral" variant="subtle">{{ t('roster.users.deleted') }}</UBadge>
                <RosterStatusBadge v-else :status="user.status" />
              </template>
              <template #created-cell="{ row: { original: user } }">{{ formatDate(user.created_at) ?? t('roster.users.none') }}</template>
              <template #actions-cell="{ row: { original: user } }">
                <div class="flex justify-end gap-2">
                  <template v-if="deleted">
                    <UButton size="sm" icon="i-lucide-undo-2" :loading="action.busy.value" :data-testid="`roster-user-restore-${user.id}`" @click="restore(user)">{{ t('roster.users.restore') }}</UButton>
                    <UButton size="sm" color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" :data-testid="`roster-user-purge-${user.id}`" @click="purge(user)">{{ t('roster.users.purge') }}</UButton>
                  </template>
                  <UButton
                    v-else-if="user.status === 'pending'"
                    size="sm"
                    icon="i-lucide-check"
                    :loading="action.busy.value"
                    :data-testid="`roster-user-approve-${user.id}`"
                    @click="approve(user)"
                  >
                    {{ t('roster.users.approve') }}
                  </UButton>
                </div>
              </template>
            </UTable>

            <RosterPager :current="paging.current" :last="paging.last" testid="roster-users" />
          </div>
        </RosterState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
