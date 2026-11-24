<script setup lang="ts">
import OrganizationInvitations from '../../../components/organizations/OrganizationInvitations.vue'
import OrganizationMembers from '../../../components/organizations/OrganizationMembers.vue'
import OrganizationSettings from '../../../components/organizations/OrganizationSettings.vue'
import OrganizationState from '../../../components/organizations/OrganizationState.vue'
import OrganizationTeams from '../../../components/organizations/OrganizationTeams.vue'
import {
  ORGANIZATION_TABS,
  organizationPath,
  organizationTab,
  type RosterOrganizationTab,
  type RosterQueued
} from '../../../components/organizations/organizations'
import RosterActionError from '../../../components/users/RosterActionError.vue'
import RosterTabs from '../../../components/users/RosterTabs.vue'
import {
  queryValue,
  rosterData,
  useRosterAction,
  useRosterLoad,
  type RosterOne,
  type RosterOrganization
} from '../../../components/users/users'

/**
 * `roster.organization`: one organization (`organizations.show`), ported
 * from v1's `organizations/show`, with v1's built-in tabs in `?tab=`:
 * members, teams, invitations and settings (update, links and delete). Its
 * role assignments moved to Guardian's `guardian.access` tab (AC 97, 99). Tabs other packages register come from `atrium.screens.show`; an
 * open one replaces the built-in content. After a delete the page offers
 * restore and purge, as Roster's `organizations.show` does not find a deleted
 * organization (D-032).
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const slug = computed(() => String(route.params.organization))
const tab = computed(() => organizationTab(queryValue(route.query.tab)))
const tabs = computed(() => ORGANIZATION_TABS.map(key => ({ key, label: t(`roster.organizations.tabs.${key}`) })))

const { client, data: organization, pending, failed, status, reload } = useRosterLoad(async roster =>
  rosterData<RosterOne<RosterOrganization>>(await roster.GET('/organizations/{organization}', {
    params: { path: { organization: slug.value } }
  })).data, slug)

const deleted = ref(false)
const danger = useRosterAction()

/** Members and teams are what an organization imports and exports. */
const transfers = computed(() => tab.value === 'members' || tab.value === 'teams')

function open(next: RosterOrganizationTab): void {
  router.replace({ query: { ...route.query, tab: next === 'members' ? undefined : next, page: undefined } })
}

async function updated(next: string): Promise<void> {
  if (next !== slug.value) {
    await router.replace({ path: organizationPath(next), query: { tab: 'settings' } })
    return
  }

  await reload()
}

async function restore(): Promise<void> {
  if (client && await danger.run(async () => rosterData(await client.POST('/organizations/{organization}/restore', {
    params: { path: { organization: slug.value } }
  })))) {
    deleted.value = false
    await reload()
  }
}

async function purge(): Promise<void> {
  if (!client || !window.confirm(t('roster.organizations.danger.confirm_purge'))) {
    return
  }

  // Roster queues the purge (202); the Deleted tab drops it once the queue runs.
  if (await danger.run(async () => rosterData<RosterQueued>(await client.DELETE('/organizations/{organization}/purge', {
    params: { path: { organization: slug.value } }
  })))) {
    await router.push({ path: '/roster/organizations', query: { trashed: 'only' } })
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-organization">
    <UPageHeader>
      <template #title>
        <span class="flex items-center gap-3">
          <UButton to="/roster/organizations" icon="i-lucide-arrow-left" color="neutral" variant="ghost" :aria-label="t('roster.organizations.title')" />
          <span class="truncate" data-testid="roster-organization-title">{{ organization?.name ?? t('roster.organizations.organization') }}</span>
          <UBadge v-if="deleted" color="neutral" variant="subtle">{{ t('roster.organizations.deleted') }}</UBadge>
        </span>
      </template>
      <template v-if="organization" #description>{{ organization.slug }}</template>
      <template v-if="transfers && organization && !deleted" #links>
        <UButton
          :to="{ path: '/roster/transfers', query: { organization: organization.slug } }"
          variant="outline"
          icon="i-lucide-arrow-up-down"
          data-testid="roster-organization-transfers"
        >
          {{ t('roster.organizations.import_export') }}
        </UButton>
      </template>
    </UPageHeader>

    <AtriumIntegrationScreen screen-key="roster.organization" :record="slug">
      <OrganizationState :ready="!pending && organization !== null" :failed="failed" :status="status">
        <div v-if="deleted && organization" class="flex max-w-3xl flex-col gap-4" data-testid="roster-organization-deleted">
          <RosterActionError :action="danger" />
          <UAlert color="warning" variant="subtle" icon="i-lucide-trash" :title="t('roster.organizations.danger.deleted')" :description="t('roster.organizations.danger.deleted_hint')" />
          <div>
            <UButton icon="i-lucide-undo-2" :loading="danger.busy.value" data-testid="roster-organization-restore" @click="restore">{{ t('roster.organizations.restore') }}</UButton>
          </div>
          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('roster.organizations.danger.title') }}</h2>
            </template>
            <p class="mb-4 text-sm">{{ t('roster.organizations.danger.purge_warning') }}</p>
            <UButton color="error" icon="i-lucide-trash-2" :loading="danger.busy.value" data-testid="roster-organization-purge" @click="purge">{{ t('roster.organizations.purge') }}</UButton>
          </UCard>
        </div>

        <div v-else-if="organization && client" class="space-y-4">
          <RosterTabs :tabs="tabs" :current="tab" :label="organization.name" testid="roster-organization-tab" data-testid="roster-organization-tabs" @select="open" />

          <OrganizationMembers v-if="tab === 'members'" :client="client" :organization="organization" @changed="reload" />
          <OrganizationTeams v-else-if="tab === 'teams'" :client="client" :organization="organization" @changed="reload" />
          <OrganizationInvitations v-else-if="tab === 'invitations'" :client="client" :organization="organization" />
          <OrganizationSettings v-else :client="client" :organization="organization" @updated="updated" @deleted="deleted = true" />
        </div>
      </OrganizationState>
    </AtriumIntegrationScreen>
  </div>
</template>
