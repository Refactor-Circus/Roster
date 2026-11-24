<script setup lang="ts">
import {
  useInvitationClient,
  type RosterAnsweredInvitation,
  type RosterInvitationLookup
} from '../../components/invitation/invitation'
import { formatDate, rosterData } from '../../components/users/users'
import { useAtrium } from '@refactor-circus/atrium'
import { AtriumIntegrationError } from '@refactor-circus/atrium'
import { atriumStatus, type AtriumDetailsItem } from '@refactor-circus/atrium'

/**
 * `roster.invitation`: the public invitation page (AC 70, D-038), ported
 * from v1's `invitations/show`. It is declared public, so it renders in the
 * public layout and loads no shell, and nothing on it calls the auth
 * adapter's `onUnauthorized()` by itself.
 *
 * It looks the token up (`roster.invitations.lookup`, which needs no
 * sign-in) and shows the email, the organization, the teams and the expiry.
 * Any 404 shows one generic "not available" state, which tells nothing about
 * why; a 429 shows "too many attempts". Accept and decline
 * (`roster.invitations.accept` / `.decline`) act as the signed-in visitor:
 * a visitor who is not signed in (Roster answers 401) is sent to sign in
 * through `onUnauthorized()` before anything is accepted or declined.
 *
 * The token stays in the URL alone: it is never put in the title, the
 * console or storage.
 */

type State = 'loading' | 'ready' | 'unavailable' | 'throttled' | 'failed' | 'accepted' | 'declined'

const { t } = useI18n()
const route = useRoute()
const atrium = useAtrium()
const client = useInvitationClient()

useHead({ title: () => t('roster.organizations.invitation.title') })

const state = ref<State>('loading')
const invitation = shallowRef<RosterInvitationLookup | null>(null)
const busy = ref(false)
const message = ref<string | null>(null)

/** The token, read from the route each time it is needed rather than kept. */
function token(): string {
  return String(route.params.token ?? '')
}

/** The invitation's facts: the email, the organization, its teams (when it names any) and the expiry. */
function details(found: RosterInvitationLookup): AtriumDetailsItem[] {
  return [
    { label: t('roster.organizations.fields.email'), value: found.email, testid: 'roster-invitation-email' },
    { label: t('roster.organizations.invitation.organization'), value: found.organization.name, testid: 'roster-invitation-organization' },
    ...(found.teams.length > 0 ? [{ label: t('roster.organizations.fields.teams'), value: found.teams.join(', '), testid: 'roster-invitation-teams' }] : []),
    { label: t('roster.organizations.invitation.expires'), value: formatDate(found.expires_at), testid: 'roster-invitation-expires' }
  ]
}

/** The state a failed request leaves the page in, or null to stay and show `message`. */
function failure(status: number | null): State | null {
  switch (status) {
    case 404:
      return 'unavailable'
    case 429:
      return 'throttled'
    default:
      return null
  }
}

async function lookup(): Promise<void> {
  state.value = 'loading'

  try {
    invitation.value = rosterData<{ data: RosterInvitationLookup }>(await client.GET('/invitations/{token}', {
      // Roster reads the token from the path; Scramble also lists it as a query.
      params: { path: { token: token() } } as never
    })).data
    state.value = 'ready'
  } catch (error) {
    const status = atriumStatus(error)

    state.value = failure(status) ?? (status === 422 ? 'unavailable' : 'failed')
  }
}

async function answer(verb: 'accept' | 'decline'): Promise<void> {
  busy.value = true
  message.value = null

  try {
    const body = { token: token() }
    const result = verb === 'accept'
      ? await client.POST('/invitations/{token}/accept', { params: { path: { token: token() } }, body })
      : await client.POST('/invitations/{token}/decline', { params: { path: { token: token() } }, body })

    if (result.response.status === 401) {
      // Not signed in: nothing was answered. Send the visitor to sign in first.
      await atrium.auth().onUnauthorized?.()
      return
    }

    rosterData<{ data: RosterAnsweredInvitation }>(result)
    state.value = verb === 'accept' ? 'accepted' : 'declined'
  } catch (error) {
    const status = atriumStatus(error)
    const next = failure(status)

    if (next) {
      state.value = next
      return
    }

    const body = (error instanceof AtriumIntegrationError ? error.body : null) as { message?: unknown, errors?: Record<string, string[]> } | null
    const first = body?.errors ? Object.values(body.errors)[0]?.[0] : undefined

    message.value = first ?? (typeof body?.message === 'string' ? body.message : t('roster.organizations.invitation.answer_failed'))
  } finally {
    busy.value = false
  }
}

onMounted(lookup)
</script>

<template>
  <UCard data-testid="roster-invitation">
    <template #header>
      <h1 class="text-lg font-semibold" data-testid="roster-invitation-heading">
        {{ invitation && state !== 'unavailable' && state !== 'throttled'
          ? t('roster.organizations.invitation.heading', { organization: invitation.organization.name })
          : t('roster.organizations.invitation.title') }}
      </h1>
    </template>

    <USkeleton v-if="state === 'loading'" class="h-24" data-testid="roster-invitation-loading" />

    <UAlert
      v-else-if="state === 'unavailable'"
      color="neutral"
      variant="subtle"
      icon="i-lucide-mail-x"
      :title="t('roster.organizations.invitation.unavailable')"
      :description="t('roster.organizations.invitation.unavailable_hint')"
      data-testid="roster-invitation-unavailable"
    />

    <UAlert
      v-else-if="state === 'throttled'"
      color="warning"
      variant="subtle"
      icon="i-lucide-timer"
      :title="t('roster.organizations.invitation.throttled')"
      :description="t('roster.organizations.invitation.throttled_hint')"
      data-testid="roster-invitation-throttled"
    />

    <AtriumErrorCard v-else-if="state === 'failed'" :message="t('roster.organizations.invitation.failed')" />

    <UAlert
      v-else-if="state === 'accepted'"
      color="success"
      variant="subtle"
      icon="i-lucide-check"
      :title="t('roster.organizations.invitation.accepted', { organization: invitation?.organization.name ?? '' })"
      data-testid="roster-invitation-accepted"
    />

    <UAlert
      v-else-if="state === 'declined'"
      color="neutral"
      variant="subtle"
      icon="i-lucide-x"
      :title="t('roster.organizations.invitation.declined')"
      data-testid="roster-invitation-declined"
    />

    <div v-else-if="invitation" class="flex flex-col gap-4" data-testid="roster-invitation-details">
      <p>{{ t('roster.organizations.invitation.prompt', { organization: invitation.organization.name }) }}</p>
      <AtriumDetails :items="details(invitation)" />

      <UAlert v-if="message" color="error" variant="subtle" :title="message" data-testid="roster-invitation-error" />

      <div class="flex flex-wrap gap-3">
        <UButton icon="i-lucide-check" :loading="busy" data-testid="roster-invitation-accept" @click="answer('accept')">{{ t('roster.organizations.invitation.accept') }}</UButton>
        <UButton variant="outline" icon="i-lucide-x" :loading="busy" data-testid="roster-invitation-decline" @click="answer('decline')">{{ t('roster.organizations.invitation.decline') }}</UButton>
      </div>
    </div>
  </UCard>
</template>
