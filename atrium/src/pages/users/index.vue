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
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h1 class="text-xl font-semibold">{{ t('roster.users.title') }}</h1>
      <div class="flex gap-2">
        <UButton to="/roster/transfers" variant="outline" icon="i-lucide-arrow-up-down" data-testid="roster-users-transfers">{{ t('roster.users.import_export') }}</UButton>
        <UButton to="/roster/users/create" icon="i-lucide-plus" data-testid="roster-users-new">{{ t('roster.users.new') }}</UButton>
      </div>
    </div>

    <AtriumIntegrationScreen screen-key="roster.users">
      <div class="space-y-4">
        <nav class="flex gap-1 border-b border-default" :aria-label="t('roster.users.title')">
          <button
            type="button"
            class="-mb-px border-b-2 px-3 py-2 text-sm font-medium"
            :class="deleted ? 'border-transparent text-muted' : 'border-primary'"
            :aria-current="deleted ? undefined : 'page'"
            data-testid="roster-users-tab-active"
            @click="showTab(false)"
          >
            {{ t('roster.users.tabs.active') }}
          </button>
          <button
            type="button"
            class="-mb-px border-b-2 px-3 py-2 text-sm font-medium"
            :class="deleted ? 'border-primary' : 'border-transparent text-muted'"
            :aria-current="deleted ? 'page' : undefined"
            data-testid="roster-users-tab-deleted"
            @click="showTab(true)"
          >
            {{ t('roster.users.tabs.deleted') }}
          </button>
        </nav>

        <form class="atrium-querybar" data-testid="roster-users-filters" @submit.prevent="filter">
          <UFormField :label="t('roster.users.filters.search')">
            <UInput v-model="filters.search" name="search" class="w-64" data-testid="roster-users-search" />
          </UFormField>
          <UFormField :label="t('roster.users.fields.status')">
            <select v-model="filters.status" class="w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-users-status">
              <option value="">{{ t('roster.users.filters.all_statuses') }}</option>
              <option v-for="choice in USER_STATUSES" :key="choice" :value="choice">{{ t(`roster.users.statuses.${choice}`) }}</option>
            </select>
          </UFormField>
          <UButton type="submit" icon="i-lucide-filter" data-testid="roster-users-filter">{{ t('roster.users.filters.filter') }}</UButton>
          <UButton variant="ghost" icon="i-lucide-x" data-testid="roster-users-clear" @click="clear">{{ t('roster.users.filters.clear') }}</UButton>
        </form>

        <RosterState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <RosterActionError :action="action" />

            <p v-if="users.length === 0" class="atrium-empty" data-testid="roster-users-empty">{{ t('roster.users.empty') }}</p>
            <table v-else class="w-full text-left text-sm" data-testid="roster-users-table">
              <thead class="text-muted">
                <tr>
                  <th class="py-2 pr-3 font-medium">{{ t('roster.users.fields.name') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('roster.users.fields.email') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('roster.users.fields.status') }}</th>
                  <th class="py-2 pr-3 font-medium">{{ t('roster.users.fields.created') }}</th>
                  <th class="py-2 text-right font-medium">{{ t('roster.users.fields.actions') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-default">
                <tr v-for="user in users" :key="String(user.id)" :data-testid="`roster-user-row-${user.id}`">
                  <td class="py-2 pr-3">
                    <RouterLink v-if="!deleted" :to="userPath(user.id)" class="font-medium hover:underline">{{ userName(user) ?? t('roster.users.none') }}</RouterLink>
                    <span v-else class="font-medium">{{ userName(user) ?? t('roster.users.none') }}</span>
                  </td>
                  <td class="py-2 pr-3">{{ user.email ?? t('roster.users.none') }}</td>
                  <td class="py-2 pr-3">
                    <UBadge v-if="deleted" color="neutral" variant="subtle">{{ t('roster.users.deleted') }}</UBadge>
                    <RosterStatusBadge v-else :status="user.status" />
                  </td>
                  <td class="py-2 pr-3">{{ formatDate(user.created_at) ?? t('roster.users.none') }}</td>
                  <td class="py-2">
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
                  </td>
                </tr>
              </tbody>
            </table>

            <RosterPager :current="paging.current" :last="paging.last" testid="roster-users" />
          </div>
        </RosterState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
