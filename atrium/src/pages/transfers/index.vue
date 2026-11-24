<script setup lang="ts">
// Not ported (D-032)
// Roster's transfers screens (`roster.transfers`, `roster.transfer`) leave out these
// v1 features, which have no v2 API route:
// - Roster's impersonation banner (`roster::impersonation-banner`) on each screen.
//   It is Roster's own view, with no API route.
// - Offering only the import and export types the viewer may start, and the
//   transfer page's "may confirm" check. No API route reports them: every type is
//   offered and a 403 shows the no-access notice (D-031).
// - The "transfers need Impex" notice (v1's `Transfers::available()`). No API route
//   reports it: starting a transfer without Impex shows Roster's error message.
// - The transfers page's organization name in its header. The v2 list carries the
//   organization's slug only, so the slug is shown.
import AccessState from '../../components/access/AccessState.vue'
import TransferStatusBadge from '../../components/transfers/TransferStatusBadge.vue'
import {
  attachmentName,
  EXPORT_TYPES,
  IMPORT_TYPES,
  saveFile,
  transferPath,
  type RosterExportType,
  type RosterImportType,
  type RosterTransfer
} from '../../components/transfers/transfers'
import RosterActionError from '../../components/users/RosterActionError.vue'
import RosterPager from '../../components/users/RosterPager.vue'
import {
  blankToNull,
  formatDate,
  pageOf,
  queryPage,
  queryValue,
  rosterData,
  untypedBody,
  useRosterAction,
  useRosterLoad,
  type RosterOne,
  type RosterPage
} from '../../components/users/users'

/**
 * `roster.transfers`: the imports and exports (`roster.transfers.index`), a
 * page at a time and filtered to v1's `?organization=`, ported from v1's
 * `transfers/index`. The import card downloads a type's CSV template
 * (`roster.imports.templates.show`) and starts an import from v1's form
 * (type, organization and the CSV file, sent as multipart to
 * `roster.imports.store`). The export card starts an export
 * (`roster.exports.store`) with the type and the one field it uses: the
 * organization for members, the external source for organizations. Both
 * then open the new transfer.
 */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const templating = useRosterAction()
const importing = useRosterAction()
const exporting = useRosterAction()

const organization = computed(() => queryValue(route.query.organization) ?? null)

const query = computed(() => {
  const page = queryPage(route.query.page)

  return {
    ...(organization.value ? { organization: organization.value } : {}),
    ...(page > 1 ? { page } : {})
  }
})

const { client, data, pending, failed, status } = useRosterLoad(async roster =>
  rosterData<RosterPage<RosterTransfer>>(await roster.GET('/transfers', { params: { query: query.value } })), query)

const transfers = computed(() => data.value?.data ?? [])
const paging = computed(() => pageOf(data.value))

const template = ref<RosterImportType>(IMPORT_TYPES[0]!)
const importForm = reactive({ type: IMPORT_TYPES[0]! as RosterImportType, organization: '', file: null as File | null })
const exportForm = reactive({ type: EXPORT_TYPES[0]! as RosterExportType, organization: '', externalSource: '' })

watch(organization, (slug) => {
  importForm.organization = slug ?? ''
  exportForm.organization = slug ?? ''
}, { immediate: true })

function pickFile(event: Event): void {
  importForm.file = (event.target as HTMLInputElement).files?.[0] ?? null
}

async function downloadTemplate(): Promise<void> {
  if (!client) {
    return
  }

  const type = template.value

  await templating.run(async () => {
    const result = await client.GET('/imports/templates/{type}', { params: { path: { type }, query: { type } }, parseAs: 'blob' })
    const blob = rosterData<Blob>(result)

    saveFile(blob, attachmentName(result.response.headers.get('Content-Disposition'), `roster-${type}-template.csv`))
  })
}

async function open(start: () => Promise<RosterTransfer>, action: ReturnType<typeof useRosterAction>): Promise<void> {
  const started: { transfer: RosterTransfer | null } = { transfer: null }

  if (await action.run(async () => {
    started.transfer = await start()
  }) && started.transfer !== null) {
    await router.push(transferPath(started.transfer.id))
  }
}

async function startImport(): Promise<void> {
  if (!client) {
    return
  }

  const body = new FormData()
  body.append('type', importForm.type)

  const slug = blankToNull(importForm.organization)

  if (slug !== null) {
    body.append('organization', slug)
  }

  if (importForm.file) {
    body.append('file', importForm.file, importForm.file.name)
  }

  await open(async () => rosterData<RosterOne<RosterTransfer>>(await client.POST('/imports', { body: untypedBody(body) })).data, importing)
}

async function startExport(): Promise<void> {
  if (!client) {
    return
  }

  const body: { type: RosterExportType, organization?: string | null, filters?: { external_source?: string | null } } = { type: exportForm.type }

  if (exportForm.type === 'export_members') {
    body.organization = blankToNull(exportForm.organization)
  }

  if (exportForm.type === 'export_organizations') {
    body.filters = { external_source: blankToNull(exportForm.externalSource) }
  }

  await open(async () => rosterData<RosterOne<RosterTransfer>>(await client.POST('/exports', { body })).data, exporting)
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-transfers">
    <div>
      <h1 class="text-xl font-semibold">{{ t('roster.transfers.title') }}</h1>
      <p v-if="organization" class="text-sm text-muted" data-testid="roster-transfers-organization">{{ organization }}</p>
    </div>

    <AtriumIntegrationScreen screen-key="roster.transfers">
      <AccessState :ready="!pending && data !== null" :failed="failed" :status="status">
        <div class="space-y-4">
          <div class="grid gap-4 lg:grid-cols-2">
            <UCard data-testid="roster-import-card">
              <template #header>
                <h2 class="font-medium">{{ t('roster.transfers.import.title') }}</h2>
              </template>

              <div class="mb-4 flex flex-wrap items-end gap-3" data-testid="roster-import-templates">
                <UFormField :label="t('roster.transfers.import.template')">
                  <select v-model="template" class="w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-template-type">
                    <option v-for="type in IMPORT_TYPES" :key="type" :value="type">{{ t(`roster.transfers.types.${type}`) }}</option>
                  </select>
                </UFormField>
                <UButton variant="outline" icon="i-lucide-download" :loading="templating.busy.value" data-testid="roster-template-download" @click="downloadTemplate">
                  {{ t('roster.transfers.import.download_template') }}
                </UButton>
              </div>
              <RosterActionError :action="templating" />

              <form class="flex flex-col gap-3" data-testid="roster-import-form" @submit.prevent="startImport">
                <RosterActionError :action="importing" />
                <UFormField :label="t('roster.transfers.fields.type')" :error="importing.error('type')" required>
                  <select v-model="importForm.type" class="w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-import-type">
                    <option v-for="type in IMPORT_TYPES" :key="type" :value="type">{{ t(`roster.transfers.types.${type}`) }}</option>
                  </select>
                </UFormField>
                <UFormField :label="t('roster.transfers.fields.organization')" :hint="t('roster.transfers.import.organization_hint')" :error="importing.error('organization')">
                  <UInput v-model="importForm.organization" class="w-full" data-testid="roster-import-organization" />
                </UFormField>
                <UFormField :label="t('roster.transfers.import.file')" :hint="t('roster.transfers.import.file_hint')" :error="importing.error('file')">
                  <input type="file" accept=".csv,text/csv" class="block w-full text-sm" data-testid="roster-import-file" @change="pickFile">
                </UFormField>
                <div>
                  <UButton type="submit" icon="i-lucide-upload" :loading="importing.busy.value" data-testid="roster-import-submit">{{ t('roster.transfers.import.submit') }}</UButton>
                </div>
              </form>
            </UCard>

            <UCard data-testid="roster-export-card">
              <template #header>
                <h2 class="font-medium">{{ t('roster.transfers.export.title') }}</h2>
              </template>

              <form class="flex flex-col gap-3" data-testid="roster-export-form" @submit.prevent="startExport">
                <RosterActionError :action="exporting" />
                <UFormField :label="t('roster.transfers.fields.type')" :error="exporting.error('type')" required>
                  <select v-model="exportForm.type" class="w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm" data-testid="roster-export-type">
                    <option v-for="type in EXPORT_TYPES" :key="type" :value="type">{{ t(`roster.transfers.types.${type}`) }}</option>
                  </select>
                </UFormField>
                <UFormField
                  v-if="exportForm.type === 'export_members'"
                  :label="t('roster.transfers.fields.organization')"
                  :hint="t('roster.transfers.export.organization_hint')"
                  :error="exporting.error('organization')"
                >
                  <UInput v-model="exportForm.organization" class="w-full" data-testid="roster-export-organization" />
                </UFormField>
                <UFormField
                  v-if="exportForm.type === 'export_organizations'"
                  :label="t('roster.transfers.export.external_source')"
                  :hint="t('roster.transfers.export.external_source_hint')"
                  :error="exporting.error('filters.external_source')"
                >
                  <UInput v-model="exportForm.externalSource" class="w-full" data-testid="roster-export-external-source" />
                </UFormField>
                <div>
                  <UButton type="submit" icon="i-lucide-download" :loading="exporting.busy.value" data-testid="roster-export-submit">{{ t('roster.transfers.export.submit') }}</UButton>
                </div>
              </form>
            </UCard>
          </div>

          <p v-if="transfers.length === 0" class="atrium-empty" data-testid="roster-transfers-empty">{{ t('roster.transfers.empty') }}</p>
          <table v-else class="w-full text-left text-sm" data-testid="roster-transfers-table">
            <thead class="text-muted">
              <tr>
                <th class="py-2 pr-3 font-medium">{{ t('roster.transfers.fields.type') }}</th>
                <th class="py-2 pr-3 font-medium">{{ t('roster.transfers.fields.organization') }}</th>
                <th class="py-2 pr-3 font-medium">{{ t('roster.transfers.fields.requested_by') }}</th>
                <th class="py-2 pr-3 font-medium">{{ t('roster.transfers.fields.status') }}</th>
                <th class="py-2 pr-3 font-medium">{{ t('roster.transfers.fields.rows') }}</th>
                <th class="py-2 font-medium">{{ t('roster.transfers.fields.created') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-default">
              <tr v-for="transfer in transfers" :key="transfer.id" :data-testid="`roster-transfer-row-${transfer.id}`">
                <td class="py-2 pr-3">
                  <RouterLink :to="transferPath(transfer.id)" class="font-medium hover:underline">{{ t(`roster.transfers.types.${transfer.type}`) }}</RouterLink>
                </td>
                <td class="py-2 pr-3">{{ transfer.organization ?? t('roster.transfers.none') }}</td>
                <td class="py-2 pr-3">{{ transfer.requested_by?.name || transfer.requested_by?.email || t('roster.transfers.none') }}</td>
                <td class="py-2 pr-3"><TransferStatusBadge :status="transfer.status" /></td>
                <td class="py-2 pr-3">{{ transfer.row_count ?? 0 }}</td>
                <td class="py-2">{{ formatDate(transfer.created_at) ?? t('roster.transfers.none') }}</td>
              </tr>
            </tbody>
          </table>

          <RosterPager :current="paging.current" :last="paging.last" testid="roster-transfers" />
        </div>
      </AccessState>
    </AtriumIntegrationScreen>
  </div>
</template>
