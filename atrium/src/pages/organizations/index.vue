<script setup lang="ts">
// Not ported (D-032)
// Roster's organization, team and invitation screens (`roster.organizations`,
// `roster.organization-create`, `roster.organization`, `roster.team`,
// `roster.invitation`) leave out these v1 features, which have no v2 API route:
// - The deleted organization's page. v1's `organizations.show` rendered a deleted
//   organization, but v2's `roster.organizations.show` answers 404 for one. Restore
//   and purge are offered on the Deleted tab, and on the organization page right
//   after a delete.
// - The "I understand" checkbox before delete and purge (v1's `confirm` field).
//   The v2 routes take no confirmation field, so a browser confirmation replaces it.
// - An immediate purge. v2's `roster.organizations.purge` queues it (202), so the
//   page says so and the organization leaves the Deleted tab once the queue runs.
// - Roster's impersonation banner (`roster::impersonation-banner`) on each screen.
//   It is Roster's own view, with no API route.
// - Hiding each card and button by the viewer's permissions (v1's `@rosterCan`).
//   No API route reports them, so a 403 shows the no-access notice instead (D-031).
// - The screen extensions on the public invitation page. It loads neither the shell
//   nor `atrium.screens.show`, which need a signed-in user (D-038), so other
//   packages' tabs and sections do not render there.
// - The session-based accept and decline forms of the invitation page
//   (`roster.invitations.page.accept` / `.decline`). They map to the API's
//   `roster.invitations.accept` / `.decline`, which need a signed-in user.
import OrganizationState from '../../components/organizations/OrganizationState.vue'
import { organizationPath, type RosterQueued } from '../../components/organizations/organizations'
import RosterActionError from '../../components/users/RosterActionError.vue'
import RosterPager from '../../components/users/RosterPager.vue'
import RosterTabs from '../../components/users/RosterTabs.vue'
import {
  pageOf,
  queryPage,
  queryValue,
  rosterData,
  useRosterAction,
  useRosterLoad,
  type RosterOrganization,
  type RosterPage
} from '../../components/users/users'
import type { TableColumn } from '@nuxt/ui'

/**
 * `roster.organizations`: every organization (`roster.organizations.index`),
 * ported from v1's `organizations/index`. The Active and Deleted tabs
 * (`trashed=only`), the search and the external-record filters (`source`,
 * `external_id`, `account_number`) live in the URL and go to the endpoint as
 * its query, a page at a time. A deleted organization is restored, or purged
 * after a confirmation, from the Deleted tab.
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useRosterAction()
const notice = ref<string | null>(null)

const FILTERS = ['search', 'source', 'external_id', 'account_number'] as const

type Filter = typeof FILTERS[number]

const deleted = computed(() => queryValue(route.query.trashed) === 'only')

const query = computed(() => {
  const page = queryPage(route.query.page)
  const filters: Partial<Record<Filter, string>> = {}

  for (const name of FILTERS) {
    const value = queryValue(route.query[name])

    if (value) {
      filters[name] = value
    }
  }

  return {
    ...filters,
    ...(deleted.value ? { trashed: 'only' as const } : {}),
    ...(page > 1 ? { page } : {})
  }
})

const { client, data, pending, failed, status, reload } = useRosterLoad(async roster =>
  rosterData<RosterPage<RosterOrganization>>(await roster.GET('/organizations', { params: { query: query.value } })), query)

const organizations = computed(() => data.value?.data ?? [])
const paging = computed(() => pageOf(data.value))

const filters = reactive<Record<Filter, string>>({ search: '', source: '', external_id: '', account_number: '' })

const tabs = computed(() => [
  { key: 'active' as const, label: t('roster.organizations.tabs.active') },
  { key: 'deleted' as const, label: t('roster.organizations.tabs.deleted') }
])

const columns = computed<TableColumn<RosterOrganization>[]>(() => [
  { id: 'name', header: t('roster.organizations.fields.name') },
  { accessorKey: 'slug', header: t('roster.organizations.fields.slug') },
  { id: 'members', header: t('roster.organizations.fields.members'), meta: { class: { th: 'text-right', td: 'text-right tabular-nums' } } },
  { id: 'teams', header: t('roster.organizations.fields.teams'), meta: { class: { th: 'text-right', td: 'text-right tabular-nums' } } },
  { id: 'external', header: t('roster.organizations.fields.external') },
  { id: 'actions', header: t('roster.organizations.fields.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])

watch(() => FILTERS.map(name => route.query[name]), () => {
  for (const name of FILTERS) {
    filters[name] = queryValue(route.query[name]) ?? ''
  }
}, { immediate: true })

function showTab(trashed: boolean): void {
  notice.value = null
  router.replace({ query: { ...route.query, trashed: trashed ? 'only' : undefined, page: undefined } })
}

function filter(): void {
  const values = Object.fromEntries(FILTERS.map(name => [name, filters[name].trim() || undefined]))

  router.replace({ query: { ...route.query, ...values, page: undefined } })
}

function clear(): void {
  router.replace({ query: { ...route.query, ...Object.fromEntries(FILTERS.map(name => [name, undefined])), page: undefined } })
}

async function restore(organization: RosterOrganization): Promise<void> {
  notice.value = null

  if (client && await action.run(async () => rosterData(await client.POST('/organizations/{organization}/restore', {
    params: { path: { organization: organization.slug } }
  })))) {
    await reload()
  }
}

async function purge(organization: RosterOrganization): Promise<void> {
  if (!client || !window.confirm(t('roster.organizations.danger.confirm_purge'))) {
    return
  }

  notice.value = null
  let queued: string | null = null

  if (await action.run(async () => {
    queued = rosterData<RosterQueued>(await client.DELETE('/organizations/{organization}/purge', {
      params: { path: { organization: organization.slug } }
    })).message
  })) {
    notice.value = queued ?? t('roster.organizations.danger.purge_queued')
    await reload()
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-organizations">
    <UPageHeader :title="t('roster.organizations.title')">
      <template #links>
        <UButton to="/roster/transfers" variant="outline" icon="i-lucide-arrow-up-down" data-testid="roster-organizations-transfers">{{ t('roster.organizations.import_export') }}</UButton>
        <UButton to="/roster/organizations/create" icon="i-lucide-plus" data-testid="roster-organizations-new">{{ t('roster.organizations.new') }}</UButton>
      </template>
    </UPageHeader>

    <AtriumIntegrationScreen screen-key="roster.organizations">
      <div class="space-y-4">
        <RosterTabs :tabs="tabs" :current="deleted ? 'deleted' : 'active'" :label="t('roster.organizations.title')" testid="roster-organizations-tab" @select="key => showTab(key === 'deleted')" />

        <UForm :state="filters" class="flex flex-wrap items-end gap-3" data-testid="roster-organizations-filters" @submit="filter">
          <UFormField :label="t('roster.organizations.filters.search')" name="search">
            <UInput v-model="filters.search" name="search" class="w-64" data-testid="roster-organizations-search" />
          </UFormField>
          <UFormField :label="t('roster.organizations.fields.source')" name="source">
            <UInput v-model="filters.source" name="source" class="w-40" data-testid="roster-organizations-source" />
          </UFormField>
          <UFormField :label="t('roster.organizations.fields.external_id')" name="external_id">
            <UInput v-model="filters.external_id" name="external_id" class="w-48" data-testid="roster-organizations-external-id" />
          </UFormField>
          <UFormField :label="t('roster.organizations.fields.account_number')" name="account_number">
            <UInput v-model="filters.account_number" name="account_number" class="w-48" data-testid="roster-organizations-account-number" />
          </UFormField>
          <UButton type="submit" icon="i-lucide-filter" data-testid="roster-organizations-filter">{{ t('roster.organizations.filters.filter') }}</UButton>
          <UButton variant="ghost" icon="i-lucide-x" data-testid="roster-organizations-clear" @click="clear">{{ t('roster.organizations.filters.clear') }}</UButton>
        </UForm>

        <OrganizationState :ready="!pending && data !== null" :failed="failed" :status="status">
          <div class="space-y-4">
            <RosterActionError :action="action" />
            <UAlert v-if="notice" color="success" variant="subtle" :title="notice" data-testid="roster-organizations-notice" />

            <UEmpty v-if="organizations.length === 0" icon="i-lucide-building-2" :title="t('roster.organizations.empty')" data-testid="roster-organizations-empty" />
            <UTable v-else :data="organizations" :columns="columns" class="text-sm" data-testid="roster-organizations-table">
              <template #name-cell="{ row: { original: organization } }">
                <span :data-testid="`roster-organization-row-${organization.slug}`">
                  <ULink v-if="!deleted" :to="organizationPath(organization.slug)" class="font-medium text-default hover:underline">{{ organization.name }}</ULink>
                  <span v-else class="font-medium">{{ organization.name }}</span>
                  <UBadge v-if="organization.personal" color="neutral" variant="subtle" class="ml-2">{{ t('roster.organizations.personal') }}</UBadge>
                  <UBadge v-if="deleted" color="neutral" variant="subtle" class="ml-2">{{ t('roster.organizations.deleted') }}</UBadge>
                </span>
              </template>
              <template #members-cell="{ row: { original: organization } }">{{ organization.members_count ?? t('roster.organizations.none') }}</template>
              <template #teams-cell="{ row: { original: organization } }">{{ organization.teams_count ?? t('roster.organizations.none') }}</template>
              <template #external-cell="{ row: { original: organization } }">
                <div v-for="link in organization.links" :key="link.source" class="text-xs">
                  <code>{{ link.source }}</code> {{ link.external_id }}<template v-if="link.account_number"> · {{ link.account_number }}</template>
                </div>
              </template>
              <template #actions-cell="{ row: { original: organization } }">
                <div v-if="deleted" class="flex justify-end gap-2">
                  <UButton size="sm" icon="i-lucide-undo-2" :loading="action.busy.value" :data-testid="`roster-organization-restore-${organization.slug}`" @click="restore(organization)">{{ t('roster.organizations.restore') }}</UButton>
                  <UButton size="sm" color="error" variant="outline" icon="i-lucide-trash-2" :loading="action.busy.value" :data-testid="`roster-organization-purge-${organization.slug}`" @click="purge(organization)">{{ t('roster.organizations.purge') }}</UButton>
                </div>
              </template>
            </UTable>

            <RosterPager :current="paging.current" :last="paging.last" testid="roster-organizations" />
          </div>
        </OrganizationState>
      </div>
    </AtriumIntegrationScreen>
  </div>
</template>
