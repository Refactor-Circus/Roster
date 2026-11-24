<script setup lang="ts">
import RosterActionError from './RosterActionError.vue'
import { rosterData, useOrganizationTeams, useRosterAction, type RosterClient, type RosterOrganization, type RosterUser } from './users'

/**
 * The user page's memberships card, as v1's: the organizations the user
 * belongs to (the current one marked), switching the user's current
 * organization and team (`users.context.update`), and the domain join
 * (`users.domain-join`), which says how many organizations it joined.
 */
const props = defineProps<{ client: RosterClient, user: RosterUser, memberships: RosterOrganization[] }>()

const emit = defineEmits<{ switched: [], joined: [] }>()

const { t } = useI18n()
const action = useRosterAction()
const notice = ref<string | null>(null)

const context = reactive({
  organization: props.user.current_organization ?? props.memberships[0]?.slug ?? '',
  team: props.user.current_team ?? ''
})

watch(() => [props.user.current_organization, props.user.current_team], () => {
  context.organization = props.user.current_organization ?? props.memberships[0]?.slug ?? ''
  context.team = props.user.current_team ?? ''
})

const teams = useOrganizationTeams(() => context.organization || null)
const id = computed(() => String(props.user.id))

async function switchContext(): Promise<void> {
  notice.value = null

  if (await action.run(async () => rosterData(await props.client.PUT('/users/{user}/context', {
    params: { path: { user: id.value } },
    body: { organization: context.organization, team: context.team || null }
  })))) {
    emit('switched')
  }
}

async function domainJoin(): Promise<void> {
  notice.value = null
  let joined = 0

  if (await action.run(async () => {
    joined = rosterData<{ data: RosterOrganization[] }>(await props.client.POST('/users/{user}/domain-join', { params: { path: { user: id.value } } })).data.length
  })) {
    notice.value = t('roster.users.memberships.joined', { count: joined })
    emit('joined')
  }
}
</script>

<template>
  <UCard data-testid="roster-user-memberships">
    <template #header>
      <h2 class="font-semibold">{{ t('roster.users.memberships.title') }}</h2>
    </template>

    <div class="flex flex-col gap-4">
      <RosterActionError :action="action" />
      <UAlert v-if="notice" color="success" variant="subtle" :title="notice" data-testid="roster-user-notice" />

      <p v-if="memberships.length === 0" class="text-sm text-muted">{{ t('roster.users.memberships.none') }}</p>
      <ul v-else class="flex flex-col gap-1 text-sm">
        <li v-for="organization in memberships" :key="organization.slug" :data-testid="`roster-membership-${organization.slug}`">
          <RouterLink :to="`/roster/organizations/${encodeURIComponent(organization.slug)}`" class="font-medium hover:underline">{{ organization.name }}</RouterLink>
          <UBadge v-if="organization.slug === user.current_organization" variant="subtle" class="ml-2">{{ t('roster.users.memberships.current') }}</UBadge>
        </li>
      </ul>

      <form v-if="memberships.length > 0" class="flex flex-wrap items-end gap-2" data-testid="roster-user-context-form" @submit.prevent="switchContext">
        <UFormField :label="t('roster.users.memberships.organization')" :error="action.error('organization')">
          <select v-model="context.organization" class="w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-user-context-organization">
            <option v-for="organization in memberships" :key="organization.slug" :value="organization.slug">{{ organization.name }}</option>
          </select>
        </UFormField>
        <UFormField :label="t('roster.users.memberships.team')" :error="action.error('team')">
          <select v-model="context.team" class="w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-user-context-team">
            <option value="">{{ t('roster.users.memberships.no_team') }}</option>
            <option v-for="team in teams" :key="team.slug" :value="team.slug">{{ team.name }}</option>
          </select>
        </UFormField>
        <UButton type="submit" icon="i-lucide-arrow-left-right" :loading="action.busy.value" data-testid="roster-user-context-switch">
          {{ t('roster.users.memberships.switch') }}
        </UButton>
      </form>

      <div>
        <UButton variant="ghost" icon="i-lucide-globe" :loading="action.busy.value" data-testid="roster-user-domain-join" @click="domainJoin">
          {{ t('roster.users.memberships.domain_join') }}
        </UButton>
      </div>
    </div>
  </UCard>
</template>
