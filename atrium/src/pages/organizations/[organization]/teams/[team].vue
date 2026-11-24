<script setup lang="ts">
import OrganizationState from '../../../../components/organizations/OrganizationState.vue'
import {
  organizationPath,
  summaryName,
  TEAM_STATUSES,
  teamPath,
  type RosterMember,
  type RosterTeamDetail
} from '../../../../components/organizations/organizations'
import RosterActionError from '../../../../components/users/RosterActionError.vue'
import {
  blankToNull,
  rosterData,
  untypedBody,
  useRosterAction,
  useRosterLoad,
  type RosterOne,
  type RosterOrganization,
  type RosterPage,
  type RosterUserSummary
} from '../../../../components/users/users'

/**
 * `roster.team`: one team (`teams.show`, with its members), ported from v1's
 * `teams/show`: its members, each removed (`teams.members.destroy`); adding
 * one of the organization's members who is not in it yet
 * (`teams.members.store`); its settings (name, slug, status and reason,
 * `teams.update`); and delete (`teams.destroy`), which returns to the
 * organization's Teams tab after a confirmation.
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const organizationSlug = computed(() => String(route.params.organization))
const teamSlug = computed(() => String(route.params.team))
const path = () => ({ params: { path: { organization: organizationSlug.value, team: teamSlug.value } } })

const { client, data: team, pending, failed, status, reload } = useRosterLoad(async roster =>
  rosterData<RosterOne<RosterTeamDetail>>(await roster.GET('/organizations/{organization}/teams/{team}', path())).data,
() => [organizationSlug.value, teamSlug.value])

const organization = useRosterLoad(async roster => rosterData<RosterOne<RosterOrganization>>(await roster.GET('/organizations/{organization}', {
  params: { path: { organization: organizationSlug.value } }
})).data, organizationSlug)

/** The organization's members, to pick the ones to add from. */
const organizationMembers = useRosterLoad(async roster => rosterData<RosterPage<RosterMember>>(await roster.GET('/organizations/{organization}/members', {
  // Roster pages its members, though the schema documents no query.
  params: { path: { organization: organizationSlug.value }, query: { per_page: 100 } as never }
})).data, organizationSlug)

const members = computed<RosterUserSummary[]>(() => team.value?.members ?? [])

const candidates = computed<RosterUserSummary[]>(() => {
  const inTeam = new Set(members.value.map(member => String(member.id)))

  return (organizationMembers.data.value ?? [])
    .map(member => member.user)
    .filter((user): user is RosterUserSummary => user !== null && !inTeam.has(String(user.id)))
})

const memberAction = useRosterAction()
const candidate = ref('')

watch(candidates, (list) => {
  if (!list.some(user => String(user.id) === candidate.value)) {
    candidate.value = list[0] ? String(list[0].id) : ''
  }
}, { immediate: true })

const settings = useRosterAction()
const form = reactive({ name: '', slug: '', status: 'active', status_reason: '' })

watch(team, (value) => {
  if (!value) {
    return
  }

  form.name = value.name
  form.slug = value.slug
  form.status = value.status
  form.status_reason = value.status_reason ?? ''
}, { immediate: true })

const danger = useRosterAction()

async function addMember(): Promise<void> {
  if (client && candidate.value !== '' && await memberAction.run(async () => rosterData(await client.POST('/organizations/{organization}/teams/{team}/members', {
    ...path(),
    body: { user: candidate.value }
  })))) {
    await reload()
  }
}

async function removeMember(member: RosterUserSummary): Promise<void> {
  if (client && await memberAction.run(async () => rosterData(await client.DELETE('/organizations/{organization}/teams/{team}/members/{user}', {
    params: { path: { organization: organizationSlug.value, team: teamSlug.value, user: String(member.id) } }
  })))) {
    await reload()
  }
}

async function save(): Promise<void> {
  if (!client) {
    return
  }

  const next = { slug: teamSlug.value }

  if (await settings.run(async () => {
    next.slug = rosterData<RosterOne<RosterTeamDetail>>(await client.PATCH('/organizations/{organization}/teams/{team}', {
      ...path(),
      // Scramble documents `teams.update` without its body.
      body: untypedBody({ name: form.name, slug: form.slug, status: form.status, status_reason: blankToNull(form.status_reason) })
    })).data.slug
  })) {
    if (next.slug !== teamSlug.value) {
      await router.replace(teamPath(organizationSlug.value, next.slug))
      return
    }

    await reload()
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('roster.organizations.teams.confirm_delete'))) {
    return
  }

  if (await danger.run(async () => rosterData(await client.DELETE('/organizations/{organization}/teams/{team}', path())))) {
    await router.push(organizationPath(organizationSlug.value, 'teams'))
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-team">
    <div class="flex flex-wrap items-center gap-3">
      <UButton
        :to="organizationPath(organizationSlug, 'teams')"
        icon="i-lucide-arrow-left"
        color="neutral"
        variant="ghost"
        :aria-label="organization.data.value?.name ?? organizationSlug"
        data-testid="roster-team-back"
      />
      <div class="min-w-0">
        <h1 class="text-xl font-semibold" data-testid="roster-team-title">{{ team?.name ?? t('roster.organizations.teams.team') }}</h1>
        <p class="text-sm text-muted" data-testid="roster-team-organization">{{ organization.data.value?.name ?? organizationSlug }}</p>
      </div>
    </div>

    <AtriumIntegrationScreen screen-key="roster.team" :record="teamSlug">
      <OrganizationState :ready="!pending && team !== null" :failed="failed" :status="status" :not-found="t('roster.organizations.teams.not_found')">
        <div v-if="team" class="grid gap-4 lg:grid-cols-2">
          <UCard data-testid="roster-team-members">
            <template #header>
              <h2 class="font-semibold">{{ t('roster.organizations.fields.members') }}</h2>
            </template>
            <div class="flex flex-col gap-4">
              <RosterActionError :action="memberAction" />
              <p v-if="members.length === 0" class="text-sm" data-testid="roster-team-members-empty">{{ t('roster.organizations.members.empty') }}</p>
              <ul v-else class="flex flex-col gap-2">
                <li v-for="member in members" :key="String(member.id)" class="flex items-center justify-between gap-2" :data-testid="`roster-team-member-${member.id}`">
                  <span>{{ summaryName(member) }}</span>
                  <UButton
                    size="sm"
                    variant="ghost"
                    icon="i-lucide-user-minus"
                    :loading="memberAction.busy.value"
                    :data-testid="`roster-team-member-remove-${member.id}`"
                    @click="removeMember(member)"
                  >
                    {{ t('roster.organizations.members.remove') }}
                  </UButton>
                </li>
              </ul>

              <form class="flex flex-wrap items-end gap-2" data-testid="roster-team-member-add-form" @submit.prevent="addMember">
                <UFormField :label="t('roster.organizations.members.add')" :error="memberAction.error('user')">
                  <select v-model="candidate" class="w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-team-member-candidate">
                    <option v-if="candidates.length === 0" value="" disabled>{{ t('roster.organizations.teams.no_candidates') }}</option>
                    <option v-for="user in candidates" :key="String(user.id)" :value="String(user.id)">{{ summaryName(user) }}</option>
                  </select>
                </UFormField>
                <UButton type="submit" icon="i-lucide-user-plus" :disabled="candidate === ''" :loading="memberAction.busy.value" data-testid="roster-team-member-add">
                  {{ t('roster.organizations.members.add') }}
                </UButton>
              </form>
            </div>
          </UCard>

          <UCard data-testid="roster-team-settings">
            <template #header>
              <h2 class="font-semibold">{{ t('roster.organizations.settings.title') }}</h2>
            </template>
            <form class="flex flex-col gap-4" data-testid="roster-team-settings-form" @submit.prevent="save">
              <RosterActionError :action="settings" />
              <UFormField :label="t('roster.organizations.fields.name')" :error="settings.error('name')" required>
                <UInput v-model="form.name" class="w-full" data-testid="roster-team-name" />
              </UFormField>
              <UFormField :label="t('roster.organizations.fields.slug')" :error="settings.error('slug')" required>
                <UInput v-model="form.slug" class="w-full" data-testid="roster-team-slug" />
              </UFormField>
              <UFormField :label="t('roster.organizations.fields.status')" :error="settings.error('status')">
                <select v-model="form.status" class="w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-team-status">
                  <option v-for="choice in TEAM_STATUSES" :key="choice" :value="choice">{{ t(`roster.organizations.statuses.${choice}`) }}</option>
                </select>
              </UFormField>
              <UFormField :label="t('roster.organizations.fields.reason')" :error="settings.error('status_reason')">
                <UInput v-model="form.status_reason" class="w-80" data-testid="roster-team-status-reason" />
              </UFormField>
              <div>
                <UButton type="submit" icon="i-lucide-check" :loading="settings.busy.value" data-testid="roster-team-save">{{ t('roster.organizations.save') }}</UButton>
              </div>
            </form>

            <div class="mt-4 flex flex-col gap-2">
              <RosterActionError :action="danger" />
              <div>
                <UButton color="error" icon="i-lucide-trash" :loading="danger.busy.value" data-testid="roster-team-delete" @click="destroy">{{ t('roster.organizations.teams.delete') }}</UButton>
              </div>
            </div>
          </UCard>
        </div>
      </OrganizationState>
    </AtriumIntegrationScreen>
  </div>
</template>
