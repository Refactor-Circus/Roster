<script setup lang="ts">
import RosterActionError from '../users/RosterActionError.vue'
import RosterPager from '../users/RosterPager.vue'
import {
  blankToNull,
  pageOf,
  queryPage,
  rosterData,
  untypedBody,
  useRosterAction,
  useRosterLoad,
  type RosterClient,
  type RosterOrganization,
  type RosterPage,
  type RosterTeam
} from '../users/users'
import OrganizationState from './OrganizationState.vue'
import { teamPath } from './organizations'
import type { TableColumn } from '@refactor-circus/atrium/ui'

/**
 * The organization page's Teams tab, as v1's: the new-team form (name and
 * slug, `teams.store`) and its teams (`teams.index`, paged), each linking to
 * its page with its member count.
 */
const props = defineProps<{ client: RosterClient, organization: RosterOrganization }>()

const emit = defineEmits<{ changed: [] }>()

const { t } = useI18n()
const route = useRoute()

const slug = computed(() => props.organization.slug)
const page = computed(() => queryPage(route.query.page))

const teams = useRosterLoad(async roster => rosterData<RosterPage<RosterTeam>>(await roster.GET('/organizations/{organization}/teams', {
  params: { path: { organization: slug.value }, query: page.value > 1 ? { page: page.value } : {} }
})), page)

const list = computed(() => teams.data.value?.data ?? [])
const paging = computed(() => pageOf(teams.data.value))

const columns = computed<TableColumn<RosterTeam>[]>(() => [
  { id: 'team', header: t('roster.organizations.teams.team') },
  { id: 'members', header: t('roster.organizations.fields.members'), meta: { class: { th: 'text-right', td: 'text-right tabular-nums' } } }
])

const create = useRosterAction()
const form = reactive({ name: '', slug: '' })

async function createTeam(): Promise<void> {
  if (await create.run(async () => rosterData(await props.client.POST('/organizations/{organization}/teams', {
    params: { path: { organization: slug.value } },
    // Scramble documents `teams.store` without its body.
    body: untypedBody({ name: form.name, slug: blankToNull(form.slug) })
  })))) {
    form.name = ''
    form.slug = ''
    await teams.reload()
    emit('changed')
  }
}
</script>

<template>
  <div class="flex flex-col gap-4" data-testid="roster-organization-teams">
    <UCard>
      <template #header>
        <h2 class="font-semibold">{{ t('roster.organizations.teams.new') }}</h2>
      </template>
      <UForm :state="form" class="flex flex-wrap items-start gap-2" data-testid="roster-team-create-form" @submit="createTeam">
        <RosterActionError :action="create" />
        <UFormField :label="t('roster.organizations.fields.name')" :error="create.error('name')" required>
          <UInput v-model="form.name" class="w-64" data-testid="roster-team-create-name" />
        </UFormField>
        <UFormField :label="t('roster.organizations.fields.slug')" :hint="t('roster.organizations.slug_hint')" :error="create.error('slug')">
          <UInput v-model="form.slug" class="w-64" data-testid="roster-team-create-slug" />
        </UFormField>
        <UButton type="submit" icon="i-lucide-plus" :loading="create.busy.value" class="mt-field-label" data-testid="roster-team-create">{{ t('roster.organizations.create') }}</UButton>
      </UForm>
    </UCard>

    <OrganizationState :ready="!teams.pending.value && teams.data.value !== null" :failed="teams.failed.value" :status="teams.status.value" compact>
      <div class="space-y-4">
        <UEmpty v-if="list.length === 0" icon="i-lucide-users-round" :title="t('roster.organizations.teams.empty')" data-testid="roster-teams-empty" />
        <UTable v-else :data="list" :columns="columns" data-testid="roster-teams-table">
          <template #team-cell="{ row: { original: team } }">
            <ULink :to="teamPath(organization.slug, team.slug)" class="font-medium text-default hover:underline" :data-testid="`roster-team-row-${team.slug}`">{{ team.name }}</ULink>
          </template>
          <template #members-cell="{ row: { original: team } }">{{ team.members_count ?? t('roster.organizations.none') }}</template>
        </UTable>

        <RosterPager :current="paging.current" :last="paging.last" testid="roster-teams" />
      </div>
    </OrganizationState>
  </div>
</template>
