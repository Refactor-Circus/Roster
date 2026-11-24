<script setup lang="ts">
import RosterActionError from '../users/RosterActionError.vue'
import RosterPager from '../users/RosterPager.vue'
import {
  formatDate,
  pageOf,
  queryPage,
  rosterData,
  useOrganizationTeams,
  useRosterAction,
  useRosterLoad,
  type RosterClient,
  type RosterOrganization,
  type RosterPage
} from '../users/users'
import OrganizationState from './OrganizationState.vue'
import { recordStatusColor, type RosterInvitation } from './organizations'
import type { TableColumn } from '@nuxt/ui'

/**
 * The organization page's Invitations tab, as v1's: the invite form (email
 * and the teams to join, `invitations.store`) and the invitations
 * (`invitations.index`, paged) with their status and expiry; a pending one is
 * revoked (`invitations.revoke`).
 */
const props = defineProps<{ client: RosterClient, organization: RosterOrganization }>()

const { t, te } = useI18n()
const route = useRoute()

const slug = computed(() => props.organization.slug)
const page = computed(() => queryPage(route.query.page))

const invitations = useRosterLoad(async roster => rosterData<RosterPage<RosterInvitation>>(await roster.GET('/organizations/{organization}/invitations', {
  params: { path: { organization: slug.value }, query: page.value > 1 ? { page: page.value } : {} }
})), page)

const list = computed(() => invitations.data.value?.data ?? [])
const paging = computed(() => pageOf(invitations.data.value))
const teams = useOrganizationTeams(() => slug.value)

const columns = computed<TableColumn<RosterInvitation>[]>(() => [
  { accessorKey: 'email', header: t('roster.organizations.fields.email') },
  { id: 'status', header: t('roster.organizations.fields.status') },
  { id: 'expires', header: t('roster.organizations.invitations.expires') },
  { id: 'actions', header: t('roster.organizations.fields.actions'), meta: { class: { th: 'text-right', td: 'text-right' } } }
])

const invite = useRosterAction()
const form = reactive<{ email: string, teams: string[] }>({ email: '', teams: [] })
const revoke = useRosterAction()

function toggleTeam(team: string, checked: boolean): void {
  form.teams = checked ? [...form.teams.filter(slug => slug !== team), team] : form.teams.filter(slug => slug !== team)
}

async function send(): Promise<void> {
  if (await invite.run(async () => rosterData(await props.client.POST('/organizations/{organization}/invitations', {
    params: { path: { organization: slug.value } },
    body: { email: form.email.trim(), teams: form.teams }
  })))) {
    form.email = ''
    form.teams = []
    await invitations.reload()
  }
}

async function revokeInvitation(invitation: RosterInvitation): Promise<void> {
  if (await revoke.run(async () => rosterData(await props.client.DELETE('/organizations/{organization}/invitations/{invitation}', {
    params: { path: { organization: slug.value, invitation: invitation.id } }
  })))) {
    await invitations.reload()
  }
}
</script>

<template>
  <div class="flex flex-col gap-4" data-testid="roster-organization-invitations">
    <UCard>
      <template #header>
        <h2 class="font-semibold">{{ t('roster.organizations.invitations.invite') }}</h2>
      </template>
      <UForm :state="form" class="flex max-w-2xl flex-col gap-3" data-testid="roster-invite-form" @submit="send">
        <RosterActionError :action="invite" />
        <UFormField :label="t('roster.organizations.fields.email')" :error="invite.error('email')" required>
          <UInput v-model="form.email" type="email" class="w-80" data-testid="roster-invite-email" />
        </UFormField>
        <UFormField v-if="teams.length > 0" :label="t('roster.organizations.fields.teams')" :error="invite.error('teams')">
          <div class="flex flex-wrap gap-4">
            <UCheckbox
              v-for="team in teams"
              :key="team.slug"
              :model-value="form.teams.includes(team.slug)"
              :label="team.name"
              :data-testid="`roster-invite-team-${team.slug}`"
              @update:model-value="(checked: boolean | 'indeterminate') => toggleTeam(team.slug, checked === true)"
            />
          </div>
        </UFormField>
        <div>
          <UButton type="submit" icon="i-lucide-send" :loading="invite.busy.value" data-testid="roster-invite-send">{{ t('roster.organizations.invitations.invite') }}</UButton>
        </div>
      </UForm>
    </UCard>

    <RosterActionError :action="revoke" />

    <OrganizationState :ready="!invitations.pending.value && invitations.data.value !== null" :failed="invitations.failed.value" :status="invitations.status.value" compact>
      <div class="space-y-4">
        <UEmpty v-if="list.length === 0" icon="i-lucide-mail" :title="t('roster.organizations.invitations.empty')" data-testid="roster-invitations-empty" />
        <UTable v-else :data="list" :columns="columns" class="text-sm" data-testid="roster-invitations-table">
          <template #email-cell="{ row: { original: invitation } }">
            <span :data-testid="`roster-invitation-row-${invitation.id}`">{{ invitation.email }}</span>
          </template>
          <template #status-cell="{ row: { original: invitation } }">
            <UBadge :color="recordStatusColor(invitation.status)" variant="subtle" :data-status="invitation.status">
              {{ te(`roster.organizations.invitations.statuses.${invitation.status}`) ? t(`roster.organizations.invitations.statuses.${invitation.status}`) : invitation.status }}
            </UBadge>
          </template>
          <template #expires-cell="{ row: { original: invitation } }">{{ formatDate(invitation.expires_at) }}</template>
          <template #actions-cell="{ row: { original: invitation } }">
            <div class="flex justify-end">
              <UButton
                v-if="invitation.status === 'pending'"
                size="sm"
                variant="ghost"
                icon="i-lucide-ban"
                :loading="revoke.busy.value"
                :data-testid="`roster-invitation-revoke-${invitation.id}`"
                @click="revokeInvitation(invitation)"
              >
                {{ t('roster.organizations.invitations.revoke') }}
              </UButton>
            </div>
          </template>
        </UTable>

        <RosterPager :current="paging.current" :last="paging.last" testid="roster-invitations" />
      </div>
    </OrganizationState>
  </div>
</template>
