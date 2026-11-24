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
      <form class="flex max-w-2xl flex-col gap-3" data-testid="roster-invite-form" @submit.prevent="send">
        <RosterActionError :action="invite" />
        <UFormField :label="t('roster.organizations.fields.email')" :error="invite.error('email')" required>
          <UInput v-model="form.email" type="email" class="w-80" data-testid="roster-invite-email" />
        </UFormField>
        <fieldset v-if="teams.length > 0" class="flex flex-wrap gap-4">
          <legend class="mb-1 text-sm font-medium">{{ t('roster.organizations.fields.teams') }}</legend>
          <label v-for="team in teams" :key="team.slug" class="flex items-center gap-2 text-sm">
            <input
              type="checkbox"
              :value="team.slug"
              :checked="form.teams.includes(team.slug)"
              :data-testid="`roster-invite-team-${team.slug}`"
              @change="toggleTeam(team.slug, ($event.target as HTMLInputElement).checked)"
            >
            {{ team.name }}
          </label>
        </fieldset>
        <div>
          <UButton type="submit" icon="i-lucide-send" :loading="invite.busy.value" data-testid="roster-invite-send">{{ t('roster.organizations.invitations.invite') }}</UButton>
        </div>
      </form>
    </UCard>

    <RosterActionError :action="revoke" />

    <OrganizationState :ready="!invitations.pending.value && invitations.data.value !== null" :failed="invitations.failed.value" :status="invitations.status.value" compact>
      <div class="space-y-4">
        <p v-if="list.length === 0" class="atrium-empty" data-testid="roster-invitations-empty">{{ t('roster.organizations.invitations.empty') }}</p>
        <table v-else class="w-full text-left text-sm" data-testid="roster-invitations-table">
          <thead class="text-muted">
            <tr>
              <th class="py-2 pr-3 font-medium">{{ t('roster.organizations.fields.email') }}</th>
              <th class="py-2 pr-3 font-medium">{{ t('roster.organizations.fields.status') }}</th>
              <th class="py-2 pr-3 font-medium">{{ t('roster.organizations.invitations.expires') }}</th>
              <th class="py-2 text-right font-medium">{{ t('roster.organizations.fields.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-default">
            <tr v-for="invitation in list" :key="invitation.id" :data-testid="`roster-invitation-row-${invitation.id}`">
              <td class="py-2 pr-3">{{ invitation.email }}</td>
              <td class="py-2 pr-3">
                <UBadge :color="recordStatusColor(invitation.status)" variant="subtle" :data-status="invitation.status">
                  {{ te(`roster.organizations.invitations.statuses.${invitation.status}`) ? t(`roster.organizations.invitations.statuses.${invitation.status}`) : invitation.status }}
                </UBadge>
              </td>
              <td class="py-2 pr-3">{{ formatDate(invitation.expires_at) }}</td>
              <td class="py-2">
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
              </td>
            </tr>
          </tbody>
        </table>

        <RosterPager :current="paging.current" :last="paging.last" testid="roster-invitations" />
      </div>
    </OrganizationState>
  </div>
</template>
