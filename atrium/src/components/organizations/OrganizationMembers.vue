<script setup lang="ts">
import RosterActionError from '../users/RosterActionError.vue'
import RosterPager from '../users/RosterPager.vue'
import RosterStatusBadge from '../users/RosterStatusBadge.vue'
import {
  pageOf,
  queryPage,
  rosterData,
  userPath,
  useRosterAction,
  useRosterLoad,
  type RosterClient,
  type RosterOrganization,
  type RosterPage
} from '../users/users'
import OrganizationState from './OrganizationState.vue'
import { summaryName, type RosterMember } from './organizations'
import type { TableColumn } from '@nuxt/ui'

/**
 * The organization page's Members tab, as v1's: a warning while it has no
 * owner, the add-member form (`members.store`, by user key), and its members
 * (`members.index`, paged), each with its status, teams and source. A pending
 * member is approved (`users.approve`); a member other than the owner is made
 * the owner (`organizations.transfer`, not for a personal organization) or
 * removed (`members.destroy`).
 */
const props = defineProps<{ client: RosterClient, organization: RosterOrganization }>()

const emit = defineEmits<{ changed: [] }>()

const { t } = useI18n()
const route = useRoute()

const slug = computed(() => props.organization.slug)
const page = computed(() => queryPage(route.query.page))

const members = useRosterLoad(async roster => rosterData<RosterPage<RosterMember>>(await roster.GET('/organizations/{organization}/members', {
  // Roster pages its members, though the schema documents no query.
  params: { path: { organization: slug.value }, query: (page.value > 1 ? { page: page.value } : {}) as never }
})), page)

const list = computed(() => members.data.value?.data ?? [])
const paging = computed(() => pageOf(members.data.value))

const columns = computed<TableColumn<RosterMember>[]>(() => [
  { id: 'member', header: t('roster.organizations.members.member') },
  { id: 'status', header: t('roster.organizations.fields.status') },
  { id: 'teams', header: t('roster.organizations.fields.teams') },
  { accessorKey: 'source', header: t('roster.organizations.members.source') },
  { id: 'actions', header: t('roster.organizations.fields.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])

const add = useRosterAction()
const newMember = ref('')
const action = useRosterAction()

async function addMember(): Promise<void> {
  if (await add.run(async () => rosterData(await props.client.POST('/organizations/{organization}/members', {
    params: { path: { organization: slug.value } },
    body: { user: newMember.value.trim() }
  })))) {
    newMember.value = ''
    await members.reload()
    emit('changed')
  }
}

async function approve(member: RosterMember): Promise<void> {
  if (member.user && await action.run(async () => rosterData(await props.client.POST('/users/{user}/approve', {
    params: { path: { user: String(member.user!.id) } }
  })))) {
    await members.reload()
  }
}

async function makeOwner(member: RosterMember): Promise<void> {
  if (member.user && await action.run(async () => rosterData(await props.client.POST('/organizations/{organization}/transfer', {
    params: { path: { organization: slug.value } },
    body: { user: String(member.user!.id) }
  })))) {
    await members.reload()
    emit('changed')
  }
}

async function remove(member: RosterMember): Promise<void> {
  if (member.user && await action.run(async () => rosterData(await props.client.DELETE('/organizations/{organization}/members/{user}', {
    params: { path: { organization: slug.value, user: String(member.user!.id) } }
  })))) {
    await members.reload()
    emit('changed')
  }
}
</script>

<template>
  <div class="flex flex-col gap-4" data-testid="roster-organization-members">
    <UAlert v-if="organization.owner === null" color="warning" variant="subtle" icon="i-lucide-triangle-alert" :title="t('roster.organizations.members.no_owner')" data-testid="roster-organization-no-owner" />

    <UCard>
      <template #header>
        <h2 class="font-semibold">{{ t('roster.organizations.members.add') }}</h2>
      </template>
      <UForm class="flex flex-wrap items-end gap-2" data-testid="roster-member-add-form" @submit="addMember">
        <RosterActionError :action="add" />
        <UFormField :label="t('roster.organizations.members.user')" :hint="t('roster.organizations.members.user_hint')" :error="add.error('user')" required>
          <UInput v-model="newMember" class="w-64" data-testid="roster-member-add-user" />
        </UFormField>
        <UButton type="submit" icon="i-lucide-user-plus" :loading="add.busy.value" data-testid="roster-member-add">{{ t('roster.organizations.members.add') }}</UButton>
      </UForm>
    </UCard>

    <RosterActionError :action="action" />

    <OrganizationState :ready="!members.pending.value && members.data.value !== null" :failed="members.failed.value" :status="members.status.value" compact>
      <div class="space-y-4">
        <UEmpty v-if="list.length === 0" icon="i-lucide-users" :title="t('roster.organizations.members.empty')" data-testid="roster-members-empty" />
        <UTable v-else :data="list" :columns="columns" class="text-sm" data-testid="roster-members-table">
          <template #member-cell="{ row: { original: member } }">
            <span :data-testid="`roster-member-row-${member.user?.id}`">
              <template v-if="member.user">
                <ULink :to="userPath(member.user.id)" class="font-medium text-default hover:underline">{{ summaryName(member.user) }}</ULink>
                <UBadge v-if="member.owner" variant="subtle" class="ml-2" data-testid="roster-member-owner">{{ t('roster.organizations.fields.owner') }}</UBadge>
              </template>
            </span>
          </template>
          <template #status-cell="{ row: { original: member } }">
            <RosterStatusBadge v-if="member.status" :status="member.status" />
          </template>
          <template #teams-cell="{ row: { original: member } }">{{ member.teams?.length ? member.teams.join(', ') : t('roster.organizations.none') }}</template>
          <template #actions-cell="{ row: { original: member } }">
            <div v-if="member.user" class="flex justify-end gap-2">
              <UButton
                v-if="member.status === 'pending'"
                size="sm"
                icon="i-lucide-check"
                :loading="action.busy.value"
                :data-testid="`roster-member-approve-${member.user.id}`"
                @click="approve(member)"
              >
                {{ t('roster.organizations.members.approve') }}
              </UButton>
              <template v-if="!member.owner">
                <UButton
                  v-if="!organization.personal"
                  size="sm"
                  variant="ghost"
                  icon="i-lucide-key-round"
                  :loading="action.busy.value"
                  :data-testid="`roster-member-make-owner-${member.user.id}`"
                  @click="makeOwner(member)"
                >
                  {{ t('roster.organizations.members.make_owner') }}
                </UButton>
                <UButton
                  size="sm"
                  color="error"
                  variant="outline"
                  icon="i-lucide-user-minus"
                  :loading="action.busy.value"
                  :data-testid="`roster-member-remove-${member.user.id}`"
                  @click="remove(member)"
                >
                  {{ t('roster.organizations.members.remove') }}
                </UButton>
              </template>
            </div>
          </template>
        </UTable>

        <RosterPager :current="paging.current" :last="paging.last" testid="roster-members" />
      </div>
    </OrganizationState>
  </div>
</template>
