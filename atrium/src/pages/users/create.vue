<script setup lang="ts">
import RosterActionError from '../../components/users/RosterActionError.vue'
import RosterProfileFields from '../../components/users/RosterProfileFields.vue'
import {
  profileBody,
  profileForm,
  rosterData,
  untypedBody,
  userPath,
  useRoster,
  useRosterAction,
  type RosterOne,
  type RosterUser
} from '../../components/users/users'

/**
 * `roster.user-create`: v1's new-user form, posted to `roster.users.store`:
 * name, email, an optional password (left out when blank, so Roster sets a
 * random one), the initial status (active, or pending to wait for an
 * approver) and the profile fields. Then the new user's page.
 */
const { t } = useI18n()
const router = useRouter()
const client = useRoster()
const action = useRosterAction()

const form = reactive({ name: '', email: '', password: '', status: 'active' as 'active' | 'pending' })
const profile = ref(profileForm())
const statusItems = computed(() => (['active', 'pending'] as const).map(choice => ({ label: t(`roster.users.statuses.${choice}`), value: choice })))

async function create(): Promise<void> {
  if (!client) {
    return
  }

  const body: Record<string, unknown> = {}

  if (form.name.trim() !== '') {
    body.name = form.name
  }

  body.email = form.email

  if (form.password !== '') {
    body.password = form.password
  }

  body.status = form.status
  Object.assign(body, profileBody(profile.value))

  const created: { user: RosterUser | null } = { user: null }

  const ok = await action.run(async () => {
    created.user = rosterData<RosterOne<RosterUser>>(await client.POST('/users', { body: untypedBody(body) })).data
  })

  if (ok && created.user !== null) {
    await router.push(userPath(created.user.id))
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-user-create">
    <UPageHeader>
      <template #title>
        <span class="flex items-center gap-3">
          <UButton to="/roster/users" icon="i-lucide-arrow-left" color="neutral" variant="ghost" :aria-label="t('roster.users.title')" />
          <span class="truncate">{{ t('roster.users.new') }}</span>
        </span>
      </template>
    </UPageHeader>

    <AtriumIntegrationScreen screen-key="roster.user-create">
      <UCard class="max-w-2xl">
        <UForm :state="form" class="flex flex-col gap-4" data-testid="roster-user-create-form" @submit="create">
          <RosterActionError :action="action" />

          <UFormField :label="t('roster.users.fields.name')" :error="action.error('name')">
            <UInput v-model="form.name" class="w-full" data-testid="roster-user-name" />
          </UFormField>
          <UFormField :label="t('roster.users.fields.email')" :error="action.error('email')" required>
            <UInput v-model="form.email" type="email" class="w-full" data-testid="roster-user-email" />
          </UFormField>
          <UFormField :label="t('roster.users.fields.password')" :hint="t('roster.users.create.password_hint')" :error="action.error('password')">
            <UInput v-model="form.password" type="password" class="w-full" data-testid="roster-user-password" />
          </UFormField>
          <UFormField :label="t('roster.users.create.initial_status')" :error="action.error('status')">
            <USelect v-model="form.status" :items="statusItems" class="w-56" data-testid="roster-user-status" />
          </UFormField>

          <RosterProfileFields v-model="profile" :errors="action.errors.value" />

          <div>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="roster-user-create-submit">{{ t('roster.users.create.submit') }}</UButton>
          </div>
        </UForm>
      </UCard>
    </AtriumIntegrationScreen>
  </div>
</template>
