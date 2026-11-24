<script setup lang="ts">
import RosterActionError from '../../components/users/RosterActionError.vue'
import RosterProfileFields from '../../components/users/RosterProfileFields.vue'
import RosterState from '../../components/users/RosterState.vue'
import RosterStatusBadge from '../../components/users/RosterStatusBadge.vue'
import RosterUserMemberships from '../../components/users/RosterUserMemberships.vue'
import {
  blankToNull,
  profileBody,
  profileForm,
  rosterData,
  untypedBody,
  userName,
  useRosterAction,
  useRosterLoad,
  type RosterImpersonationStarted,
  type RosterOne,
  type RosterOrganization,
  type RosterPage,
  type RosterUser,
  type RosterUserStatus
} from '../../components/users/users'

/**
 * `roster.user`: one user (`users.show`), ported from v1's `users/show`.
 * Its account (`users.update`) and profile (`users.profile.update`); approve
 * and reject while it awaits approval; v1's single status form, which maps
 * the new status to `users.suspend`, `users.deactivate` or `users.reactivate`
 * (Roster AC 6); its memberships with the context switch and domain join;
 * impersonation, which follows the one-time link Roster answers with;
 * and delete, after which the page offers restore and purge (Roster's
 * `users.show` does not find a deleted user, D-032).
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const id = computed(() => String(route.params.user))
const path = () => ({ params: { path: { user: id.value } } })

const { client, data: user, pending, failed, status, reload } = useRosterLoad(async roster =>
  rosterData<RosterOne<RosterUser>>(await roster.GET('/users/{user}', path())).data)

const memberships = useRosterLoad(async roster =>
  rosterData<RosterPage<RosterOrganization>>(await roster.GET('/organizations', { params: { query: { user: id.value, per_page: 100 } } })).data)

const title = computed(() => userName(user.value) ?? t('roster.users.user'))
const deleted = ref(false)

const account = useRosterAction()
const accountForm = reactive({ name: '', email: '', password: '' })

const profile = useRosterAction()
const profileFields = ref(profileForm())

const approval = useRosterAction()
const rejectReason = ref('')

const statusAction = useRosterAction()
const statusForm = reactive<{ status: RosterUserStatus | undefined, reason: string }>({ status: undefined, reason: '' })

const impersonate = useRosterAction()
const impersonationReason = ref('')

const danger = useRosterAction()

/** v1's status choices: the other ones of active, suspended and deactivated; a pending user is activated by approval. */
const statusChoices = computed<RosterUserStatus[]>(() => {
  const current = user.value?.status

  return (['active', 'suspended', 'deactivated'] as const).filter(choice => choice !== current && !(current === 'pending' && choice === 'active'))
})

const statusItems = computed(() => statusChoices.value.map(choice => ({ label: t(`roster.users.statuses.${choice}`), value: choice })))

watch(user, (value) => {
  if (!value) {
    return
  }

  accountForm.name = value.name ?? ''
  accountForm.email = value.email ?? ''
  accountForm.password = ''
  profileFields.value = profileForm(value.profile)
  statusForm.status = statusChoices.value[0]
  statusForm.reason = ''
}, { immediate: true })

async function saveAccount(): Promise<void> {
  const body: Record<string, string> = {}

  if (accountForm.name.trim() !== '') {
    body.name = accountForm.name
  }

  body.email = accountForm.email

  if (accountForm.password !== '') {
    body.password = accountForm.password
  }

  if (client && await account.run(async () => rosterData(await client.PATCH('/users/{user}', { ...path(), body: untypedBody(body) })))) {
    await reload()
  }
}

async function saveProfile(): Promise<void> {
  if (client && await profile.run(async () => rosterData(await client.PATCH('/users/{user}/profile', { ...path(), body: profileBody(profileFields.value) })))) {
    await reload()
  }
}

async function approve(): Promise<void> {
  if (client && await approval.run(async () => rosterData(await client.POST('/users/{user}/approve', path())))) {
    await reload()
  }
}

async function reject(): Promise<void> {
  if (client && await approval.run(async () => rosterData(await client.POST('/users/{user}/reject', { ...path(), body: { reason: blankToNull(rejectReason.value) } })))) {
    rejectReason.value = ''
    await reload()
  }
}

/** v1's status form: the chosen status picks the route (Roster AC 6). */
async function changeStatus(): Promise<void> {
  if (!client || statusForm.status === undefined) {
    return
  }

  const reason = blankToNull(statusForm.reason)
  const target = statusForm.status

  if (await statusAction.run(async () => {
    switch (target) {
      case 'suspended':
        return rosterData(await client.POST('/users/{user}/suspend', { ...path(), body: { reason } }))
      case 'deactivated':
        return rosterData(await client.POST('/users/{user}/deactivate', { ...path(), body: { reason } }))
      default:
        return rosterData(await client.POST('/users/{user}/reactivate', path()))
    }
  })) {
    await reload()
  }
}

/**
 * Starts impersonating and follows the one-time link, as v1 redirected to it.
 * Like every server-supplied URL (atriumLink), the link is followed only when
 * it is an absolute http(s) URL; anything else is shown as an error.
 */
async function startImpersonation(): Promise<void> {
  if (!client) {
    return
  }

  const started = { url: '' }

  if (await impersonate.run(async () => {
    const url = rosterData<RosterImpersonationStarted>(await client.POST('/users/{user}/impersonate', { ...path(), body: { reason: impersonationReason.value } })).url

    if (typeof url !== 'string' || !isExternalUrl(url)) {
      throw new Error(t('roster.users.impersonate.unsafe_link'))
    }

    started.url = url
  })) {
    await navigateTo(started.url, { external: true })
  }
}

async function destroy(): Promise<void> {
  if (!client || !window.confirm(t('roster.users.danger.confirm_delete'))) {
    return
  }

  if (await danger.run(async () => rosterData(await client.DELETE('/users/{user}', path())))) {
    deleted.value = true
  }
}

async function restore(): Promise<void> {
  if (client && await danger.run(async () => rosterData(await client.POST('/users/{user}/restore', path())))) {
    deleted.value = false
    await reload()
  }
}

async function purge(): Promise<void> {
  if (!client || !window.confirm(t('roster.users.danger.confirm_purge'))) {
    return
  }

  if (await danger.run(async () => rosterData(await client.DELETE('/users/{user}/purge', path())))) {
    await router.push({ path: '/roster/users', query: { trashed: 'only' } })
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-user">
    <AtriumPageHeader back="/roster/users" :back-label="t('roster.users.title')" :description="user?.email">
      <template #title>
        <span class="truncate" data-testid="roster-user-title">{{ title }}</span>
        <RosterStatusBadge v-if="user && !deleted" :status="user.status" data-testid="roster-user-status-badge" />
      </template>
    </AtriumPageHeader>

    <AtriumIntegrationScreen screen-key="roster.user" :record="id">
      <RosterState :ready="!pending && user !== null" :failed="failed" :status="status">
        <div v-if="deleted && user" class="flex max-w-3xl flex-col gap-4" data-testid="roster-user-deleted">
          <RosterActionError :action="danger" />
          <UAlert color="warning" variant="subtle" icon="i-lucide-trash" :title="t('roster.users.danger.deleted')" :description="t('roster.users.danger.deleted_hint')" />
          <div>
            <UButton icon="i-lucide-undo-2" :loading="danger.busy.value" data-testid="roster-user-restore" @click="restore">{{ t('roster.users.restore') }}</UButton>
          </div>
          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('roster.users.danger.title') }}</h2>
            </template>
            <p class="mb-4 text-sm">{{ t('roster.users.danger.purge_warning') }}</p>
            <UButton color="error" icon="i-lucide-trash-2" :loading="danger.busy.value" data-testid="roster-user-purge" @click="purge">{{ t('roster.users.purge') }}</UButton>
          </UCard>
        </div>

        <div v-else-if="user" class="grid gap-4 lg:grid-cols-2">
          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('roster.users.account.title') }}</h2>
            </template>
            <UForm :state="accountForm" class="flex flex-col gap-4" data-testid="roster-user-account-form" @submit="saveAccount">
              <RosterActionError :action="account" />
              <UFormField :label="t('roster.users.fields.name')" :error="account.error('name')">
                <UInput v-model="accountForm.name" class="w-full" data-testid="roster-account-name" />
              </UFormField>
              <UFormField :label="t('roster.users.fields.email')" :error="account.error('email')" required>
                <UInput v-model="accountForm.email" type="email" class="w-full" data-testid="roster-account-email" />
              </UFormField>
              <UFormField :label="t('roster.users.fields.password')" :hint="t('roster.users.account.password_hint')" :error="account.error('password')">
                <UInput v-model="accountForm.password" type="password" class="w-full" data-testid="roster-account-password" />
              </UFormField>
              <div>
                <UButton type="submit" icon="i-lucide-check" :loading="account.busy.value" data-testid="roster-account-save">{{ t('roster.users.save') }}</UButton>
              </div>
            </UForm>
          </UCard>

          <UCard>
            <template #header>
              <h2 class="font-semibold">{{ t('roster.users.profile.title') }}</h2>
            </template>
            <UForm class="flex flex-col gap-4" data-testid="roster-user-profile-form" @submit="saveProfile">
              <RosterActionError :action="profile" />
              <RosterProfileFields v-model="profileFields" :errors="profile.errors.value" />
              <div>
                <UButton type="submit" icon="i-lucide-check" :loading="profile.busy.value" data-testid="roster-profile-save">{{ t('roster.users.save') }}</UButton>
              </div>
            </UForm>
          </UCard>

          <UCard v-if="user.status === 'pending'" class="lg:col-span-2" data-testid="roster-user-approval">
            <template #header>
              <h2 class="font-semibold">{{ t('roster.users.approval.title') }}</h2>
            </template>
            <div class="flex flex-col gap-3">
              <p class="text-sm">{{ t('roster.users.approval.hint') }}</p>
              <RosterActionError :action="approval" />
              <div class="flex flex-wrap items-start gap-3">
                <UButton icon="i-lucide-check" :loading="approval.busy.value" class="mt-field-label" data-testid="roster-user-approve" @click="approve">{{ t('roster.users.approve') }}</UButton>
                <UForm class="flex items-start gap-2" data-testid="roster-user-reject-form" @submit="reject">
                  <UFormField :label="t('roster.users.fields.reason')" :error="approval.error('reason')">
                    <UInput v-model="rejectReason" class="w-64" data-testid="roster-user-reject-reason" />
                  </UFormField>
                  <UButton type="submit" color="error" icon="i-lucide-x" :loading="approval.busy.value" class="mt-field-label" data-testid="roster-user-reject">{{ t('roster.users.approval.reject') }}</UButton>
                </UForm>
              </div>
            </div>
          </UCard>

          <UCard class="lg:col-span-2">
            <template #header>
              <h2 class="font-semibold">{{ t('roster.users.status.title') }}</h2>
            </template>
            <div class="flex flex-col gap-3">
              <p v-if="user.status_reason" class="text-sm">{{ t('roster.users.fields.reason') }}: {{ user.status_reason }}</p>
              <RosterActionError :action="statusAction" />
              <UForm :state="statusForm" class="flex flex-wrap items-start gap-3" data-testid="roster-user-status-form" @submit="changeStatus">
                <UFormField :label="t('roster.users.status.change_to')">
                  <USelect v-model="statusForm.status" :items="statusItems" class="w-56" data-testid="roster-user-new-status" />
                </UFormField>
                <UFormField :label="t('roster.users.fields.reason')" :hint="t('roster.users.status.reason_hint')" :error="statusAction.error('reason')">
                  <UInput v-model="statusForm.reason" class="w-80" data-testid="roster-user-status-reason" />
                </UFormField>
                <UButton type="submit" variant="outline" icon="i-lucide-refresh-cw" :loading="statusAction.busy.value" class="mt-field-label" data-testid="roster-user-change-status">
                  {{ t('roster.users.status.update') }}
                </UButton>
              </UForm>
            </div>
          </UCard>

          <div class="lg:col-span-2">
            <RosterUserMemberships
              v-if="client"
              :client="client"
              :user="user"
              :memberships="memberships.data.value ?? []"
              @switched="reload"
              @joined="memberships.reload"
            />
          </div>

          <UCard class="lg:col-span-2" data-testid="roster-user-impersonate">
            <template #header>
              <h2 class="font-semibold">{{ t('roster.users.impersonate.title') }}</h2>
            </template>
            <div class="flex flex-col gap-3">
              <RosterActionError :action="impersonate" />
              <UForm class="flex flex-wrap items-start gap-2" data-testid="roster-user-impersonate-form" @submit="startImpersonation">
                <UFormField :label="t('roster.users.fields.reason')" :hint="t('roster.users.impersonate.reason_hint')" :error="impersonate.error('reason')" required>
                  <UInput v-model="impersonationReason" class="w-80" data-testid="roster-user-impersonation-reason" />
                </UFormField>
                <UButton type="submit" color="warning" icon="i-lucide-eye" :loading="impersonate.busy.value" class="mt-field-label" data-testid="roster-user-impersonate-submit">
                  {{ t('roster.users.impersonate.start') }}
                </UButton>
              </UForm>
            </div>
          </UCard>

          <UCard class="lg:col-span-2" data-testid="roster-user-danger">
            <template #header>
              <h2 class="font-semibold">{{ t('roster.users.danger.title') }}</h2>
            </template>
            <div class="flex flex-col gap-3">
              <RosterActionError :action="danger" />
              <p class="text-sm">{{ t('roster.users.danger.delete_warning') }}</p>
              <div>
                <UButton color="error" icon="i-lucide-trash" :loading="danger.busy.value" data-testid="roster-user-delete" @click="destroy">{{ t('roster.users.danger.delete') }}</UButton>
              </div>
            </div>
          </UCard>
        </div>
      </RosterState>
    </AtriumIntegrationScreen>
  </div>
</template>
