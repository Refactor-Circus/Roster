<script setup lang="ts">
import { domainsFrom, organizationPath } from '../../components/organizations/organizations'
import RosterActionError from '../../components/users/RosterActionError.vue'
import {
  blankToNull,
  rosterData,
  untypedBody,
  useRoster,
  useRosterAction,
  type RosterOne,
  type RosterOrganization
} from '../../components/users/users'

/**
 * `roster.organization-create`: v1's `organizations/create` form (name,
 * slug, owner, domains one per line, auto join), posted to
 * `roster.organizations.store`; the new organization's page opens next. A
 * 422 shows the field errors.
 */
const { t } = useI18n()
const router = useRouter()
const client = useRoster()
const action = useRosterAction()

const form = reactive({ name: '', slug: '', owner: '', domains: '', auto_join: false })

/** A domain's errors arrive per line (`domains.0`), and show under the textarea. */
const domainsError = computed(() => action.error('domains')
  ?? Object.entries(action.errors.value).find(([field]) => field.startsWith('domains.'))?.[1]?.[0])

async function create(): Promise<void> {
  if (!client) {
    return
  }

  const created: { slug: string | null } = { slug: null }

  if (await action.run(async () => {
    created.slug = rosterData<RosterOne<RosterOrganization>>(await client.POST('/organizations', {
      // The owner is a user key, which the schema types as a number.
      body: untypedBody({
        name: form.name,
        slug: blankToNull(form.slug),
        owner: blankToNull(form.owner),
        domains: domainsFrom(form.domains),
        auto_join: form.auto_join
      })
    })).data.slug
  }) && created.slug) {
    await router.push(organizationPath(created.slug))
  }
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-organization-create">
    <AtriumPageHeader :title="t('roster.organizations.new')" back="/roster/organizations" :back-label="t('roster.organizations.title')" />

    <AtriumIntegrationScreen screen-key="roster.organization-create">
      <UCard class="max-w-2xl">
        <UForm :state="form" class="flex flex-col gap-4" data-testid="roster-organization-create-form" @submit="create">
          <RosterActionError :action="action" />
          <UFormField :label="t('roster.organizations.fields.name')" :error="action.error('name')" required>
            <UInput v-model="form.name" class="w-full" data-testid="roster-organization-name" />
          </UFormField>
          <UFormField :label="t('roster.organizations.fields.slug')" :hint="t('roster.organizations.slug_hint')" :error="action.error('slug')">
            <UInput v-model="form.slug" class="w-full" data-testid="roster-organization-slug" />
          </UFormField>
          <UFormField :label="t('roster.organizations.fields.owner')" :hint="t('roster.organizations.owner_hint')" :error="action.error('owner')">
            <UInput v-model="form.owner" class="w-full" data-testid="roster-organization-owner" />
          </UFormField>
          <UFormField :label="t('roster.organizations.fields.domains')" :hint="t('roster.organizations.domains_hint')" :error="domainsError">
            <UTextarea v-model="form.domains" :rows="3" class="w-full" data-testid="roster-organization-domains" />
          </UFormField>
          <UCheckbox v-model="form.auto_join" :label="t('roster.organizations.fields.auto_join')" data-testid="roster-organization-auto-join" />
          <div>
            <UButton type="submit" icon="i-lucide-plus" :loading="action.busy.value" data-testid="roster-organization-create-submit">{{ t('roster.organizations.create') }}</UButton>
          </div>
        </UForm>
      </UCard>
    </AtriumIntegrationScreen>
  </div>
</template>
