<script setup lang="ts">
import RosterActionError from '../users/RosterActionError.vue'
import {
  blankToNull,
  formatDate,
  rosterData,
  untypedBody,
  useRosterAction,
  type RosterClient,
  type RosterOne,
  type RosterOrganization
} from '../users/users'
import {
  domainsFrom,
  ORGANIZATION_STATUSES,
  PROVISIONED_STATUSES,
  type RosterOrganizationLink
} from './organizations'

/**
 * The organization page's Settings tab, as v1's: the settings form (name,
 * slug, domains, auto join, provisioned status, status and reason,
 * `organizations.update`); its external links, each unlinked by source
 * (`links.destroy`), and the link form (`links.update`); and, unless it is a
 * personal organization, the danger zone, which deletes it after a
 * confirmation (`organizations.destroy`).
 */
const props = defineProps<{ client: RosterClient, organization: RosterOrganization }>()

const emit = defineEmits<{ updated: [slug: string], deleted: [] }>()

const { t } = useI18n()

const slug = computed(() => props.organization.slug)
const path = () => ({ params: { path: { organization: slug.value } } })

const settings = useRosterAction()
const form = reactive({
  name: '',
  slug: '',
  domains: '',
  auto_join: false,
  provisioned_status: 'active',
  status: 'active',
  status_reason: ''
})

watch(() => props.organization, (organization) => {
  form.name = organization.name
  form.slug = organization.slug
  form.domains = (organization.domains ?? []).join('\n')
  form.auto_join = organization.auto_join
  form.provisioned_status = organization.provisioned_status
  form.status = organization.status
  form.status_reason = organization.status_reason ?? ''
}, { immediate: true })

const domainsError = computed(() => settings.error('domains')
  ?? Object.entries(settings.errors.value).find(([field]) => field.startsWith('domains.'))?.[1]?.[0])

const links = useRosterAction()
const linkForm = reactive({ source: '', external_id: '', account_number: '' })

const danger = useRosterAction()

async function save(): Promise<void> {
  const next = { slug: slug.value }

  if (await settings.run(async () => {
    next.slug = rosterData<RosterOne<RosterOrganization>>(await props.client.PATCH('/organizations/{organization}', {
      ...path(),
      // Scramble documents `organizations.update` without its body.
      body: untypedBody({
        name: form.name,
        slug: form.slug,
        domains: domainsFrom(form.domains),
        auto_join: form.auto_join,
        provisioned_status: form.provisioned_status,
        status: form.status,
        status_reason: blankToNull(form.status_reason)
      })
    })).data.slug
  })) {
    emit('updated', next.slug)
  }
}

async function unlink(link: RosterOrganizationLink): Promise<void> {
  if (await links.run(async () => rosterData(await props.client.DELETE('/organizations/{organization}/links/{source}', {
    params: { path: { organization: slug.value, source: link.source } }
  })))) {
    emit('updated', slug.value)
  }
}

async function link(): Promise<void> {
  const source = linkForm.source.trim()

  // Roster reads the source from the path, so a blank one has no route to go to.
  if (source === '') {
    links.errors.value = { source: [t('roster.organizations.links.source_required')] }
    return
  }

  if (await links.run(async () => rosterData(await props.client.PUT('/organizations/{organization}/links/{source}', {
    params: { path: { organization: slug.value, source } },
    body: { source, external_id: linkForm.external_id.trim(), account_number: blankToNull(linkForm.account_number) }
  })))) {
    linkForm.source = ''
    linkForm.external_id = ''
    linkForm.account_number = ''
    emit('updated', slug.value)
  }
}

async function destroy(): Promise<void> {
  if (!window.confirm(t('roster.organizations.danger.confirm_delete'))) {
    return
  }

  if (await danger.run(async () => rosterData(await props.client.DELETE('/organizations/{organization}', path())))) {
    emit('deleted')
  }
}
</script>

<template>
  <div class="flex flex-col gap-4" data-testid="roster-organization-settings">
    <UCard>
      <template #header>
        <h2 class="font-semibold">{{ t('roster.organizations.settings.title') }}</h2>
      </template>
      <form class="flex max-w-2xl flex-col gap-4" data-testid="roster-organization-settings-form" @submit.prevent="save">
        <RosterActionError :action="settings" />
        <UFormField :label="t('roster.organizations.fields.name')" :error="settings.error('name')" required>
          <UInput v-model="form.name" class="w-full" data-testid="roster-settings-name" />
        </UFormField>
        <UFormField :label="t('roster.organizations.fields.slug')" :error="settings.error('slug')" required>
          <UInput v-model="form.slug" class="w-full" data-testid="roster-settings-slug" />
        </UFormField>
        <UFormField :label="t('roster.organizations.fields.domains')" :hint="t('roster.organizations.domains_hint')" :error="domainsError">
          <UTextarea v-model="form.domains" :rows="3" class="w-full" data-testid="roster-settings-domains" />
        </UFormField>
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.auto_join" type="checkbox" data-testid="roster-settings-auto-join">
          {{ t('roster.organizations.fields.auto_join') }}
        </label>
        <p class="-mt-3 text-xs text-muted">{{ t('roster.organizations.settings.auto_join_hint') }}</p>
        <UFormField :label="t('roster.organizations.settings.provisioned_status')" :hint="t('roster.organizations.settings.provisioned_status_hint')" :error="settings.error('provisioned_status')">
          <select v-model="form.provisioned_status" class="w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-settings-provisioned-status">
            <option v-for="choice in PROVISIONED_STATUSES" :key="choice" :value="choice">{{ t(`roster.users.statuses.${choice}`) }}</option>
          </select>
        </UFormField>
        <UFormField :label="t('roster.organizations.fields.status')" :error="settings.error('status')">
          <select v-model="form.status" class="w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-settings-status">
            <option v-for="choice in ORGANIZATION_STATUSES" :key="choice" :value="choice">{{ t(`roster.organizations.statuses.${choice}`) }}</option>
          </select>
        </UFormField>
        <UFormField :label="t('roster.organizations.fields.reason')" :error="settings.error('status_reason')">
          <UInput v-model="form.status_reason" class="w-80" data-testid="roster-settings-status-reason" />
        </UFormField>
        <div>
          <UButton type="submit" icon="i-lucide-check" :loading="settings.busy.value" data-testid="roster-settings-save">{{ t('roster.organizations.save') }}</UButton>
        </div>
      </form>
    </UCard>

    <UCard data-testid="roster-organization-links">
      <template #header>
        <h2 class="font-semibold">{{ t('roster.organizations.links.title') }}</h2>
      </template>
      <div class="flex flex-col gap-4">
        <RosterActionError :action="links" />
        <p v-if="organization.links.length === 0" class="text-sm" data-testid="roster-links-empty">{{ t('roster.organizations.links.empty') }}</p>
        <table v-else class="w-full text-left text-sm">
          <thead class="text-muted">
            <tr>
              <th class="py-2 pr-3 font-medium">{{ t('roster.organizations.fields.source') }}</th>
              <th class="py-2 pr-3 font-medium">{{ t('roster.organizations.fields.external_id') }}</th>
              <th class="py-2 pr-3 font-medium">{{ t('roster.organizations.fields.account_number') }}</th>
              <th class="py-2 pr-3 font-medium">{{ t('roster.organizations.links.synced') }}</th>
              <th class="py-2 text-right font-medium">{{ t('roster.organizations.fields.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-default">
            <tr v-for="item in organization.links" :key="item.source" :data-testid="`roster-link-${item.source}`">
              <td class="py-2 pr-3"><code>{{ item.source }}</code></td>
              <td class="py-2 pr-3">{{ item.external_id }}</td>
              <td class="py-2 pr-3">{{ item.account_number ?? t('roster.organizations.none') }}</td>
              <td class="py-2 pr-3">{{ formatDate(item.synced_at) ?? t('roster.organizations.links.never') }}</td>
              <td class="py-2">
                <div class="flex justify-end">
                  <UButton size="sm" variant="ghost" icon="i-lucide-unlink" :loading="links.busy.value" :data-testid="`roster-unlink-${item.source}`" @click="unlink(item)">
                    {{ t('roster.organizations.links.unlink') }}
                  </UButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <form class="flex flex-wrap items-end gap-3" data-testid="roster-link-form" @submit.prevent="link">
          <UFormField :label="t('roster.organizations.fields.source')" :hint="t('roster.organizations.links.source_hint')" :error="links.error('source')" required>
            <UInput v-model="linkForm.source" class="w-40" data-testid="roster-link-source" />
          </UFormField>
          <UFormField :label="t('roster.organizations.fields.external_id')" :error="links.error('external_id')" required>
            <UInput v-model="linkForm.external_id" class="w-56" data-testid="roster-link-external-id" />
          </UFormField>
          <UFormField :label="t('roster.organizations.fields.account_number')" :error="links.error('account_number')">
            <UInput v-model="linkForm.account_number" class="w-48" data-testid="roster-link-account-number" />
          </UFormField>
          <UButton type="submit" icon="i-lucide-link" :loading="links.busy.value" data-testid="roster-link-submit">{{ t('roster.organizations.links.link') }}</UButton>
        </form>
      </div>
    </UCard>

    <UCard v-if="!organization.personal" data-testid="roster-organization-danger">
      <template #header>
        <h2 class="font-semibold">{{ t('roster.organizations.danger.title') }}</h2>
      </template>
      <div class="flex flex-col gap-3">
        <RosterActionError :action="danger" />
        <UAlert color="warning" variant="subtle" :title="t('roster.organizations.danger.delete_title')" :description="t('roster.organizations.danger.delete_warning')" />
        <div>
          <UButton color="error" icon="i-lucide-trash" :loading="danger.busy.value" data-testid="roster-organization-delete" @click="destroy">{{ t('roster.organizations.danger.delete') }}</UButton>
        </div>
      </div>
    </UCard>
  </div>
</template>
