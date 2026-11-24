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
import type { TableColumn } from '@refactor-circus/atrium/ui'

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

const importItems = computed(() => IMPORT_TYPES.map(type => ({ label: t(`roster.transfers.types.${type}`), value: type })))
const exportItems = computed(() => EXPORT_TYPES.map(type => ({ label: t(`roster.transfers.types.${type}`), value: type })))

const columns = computed<TableColumn<RosterTransfer>[]>(() => [
  { id: 'type', header: t('roster.transfers.fields.type') },
  { id: 'organization', header: t('roster.transfers.fields.organization') },
  { id: 'requested_by', header: t('roster.transfers.fields.requested_by') },
  { id: 'status', header: t('roster.transfers.fields.status') },
  { id: 'rows', header: t('roster.transfers.fields.rows'), meta: { class: { th: 'text-right', td: 'text-right tabular-nums' } } },
  { id: 'created', header: t('roster.transfers.fields.created') }
])

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
    <AtriumPageHeader :title="t('roster.transfers.title')">
      <template v-if="organization" #description>
        <span data-testid="roster-transfers-organization">{{ organization }}</span>
      </template>
    </AtriumPageHeader>

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
                  <USelect v-model="template" :items="importItems" class="w-56" data-testid="roster-template-type" />
                </UFormField>
                <UButton variant="outline" icon="i-lucide-download" :loading="templating.busy.value" data-testid="roster-template-download" @click="downloadTemplate">
                  {{ t('roster.transfers.import.download_template') }}
                </UButton>
              </div>
              <RosterActionError :action="templating" />

              <UForm :state="importForm" class="flex flex-col gap-3" data-testid="roster-import-form" @submit="startImport">
                <RosterActionError :action="importing" />
                <UFormField :label="t('roster.transfers.fields.type')" :error="importing.error('type')" required>
                  <USelect v-model="importForm.type" :items="importItems" class="w-full" data-testid="roster-import-type" />
                </UFormField>
                <UFormField :label="t('roster.transfers.fields.organization')" :hint="t('roster.transfers.import.organization_hint')" :error="importing.error('organization')">
                  <UInput v-model="importForm.organization" class="w-full" data-testid="roster-import-organization" />
                </UFormField>
                <UFormField :label="t('roster.transfers.import.file')" :hint="t('roster.transfers.import.file_hint')" :error="importing.error('file')">
                  <UFileUpload
                    v-model="importForm.file"
                    accept=".csv,text/csv"
                    icon="i-lucide-file-spreadsheet"
                    :label="t('roster.transfers.import.file')"
                    :description="t('roster.transfers.import.file_hint')"
                    layout="list"
                    class="min-h-24 w-full"
                    data-testid="roster-import-file"
                  />
                </UFormField>
                <div>
                  <UButton type="submit" icon="i-lucide-upload" :loading="importing.busy.value" data-testid="roster-import-submit">{{ t('roster.transfers.import.submit') }}</UButton>
                </div>
              </UForm>
            </UCard>

            <UCard data-testid="roster-export-card">
              <template #header>
                <h2 class="font-medium">{{ t('roster.transfers.export.title') }}</h2>
              </template>

              <UForm :state="exportForm" class="flex flex-col gap-3" data-testid="roster-export-form" @submit="startExport">
                <RosterActionError :action="exporting" />
                <UFormField :label="t('roster.transfers.fields.type')" :error="exporting.error('type')" required>
                  <USelect v-model="exportForm.type" :items="exportItems" class="w-full" data-testid="roster-export-type" />
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
              </UForm>
            </UCard>
          </div>

          <UEmpty v-if="transfers.length === 0" icon="i-lucide-arrow-up-down" :title="t('roster.transfers.empty')" data-testid="roster-transfers-empty" />
          <UTable v-else :data="transfers" :columns="columns" data-testid="roster-transfers-table">
            <template #type-cell="{ row: { original: transfer } }">
              <ULink :to="transferPath(transfer.id)" class="font-medium text-default hover:underline" :data-testid="`roster-transfer-row-${transfer.id}`">{{ t(`roster.transfers.types.${transfer.type}`) }}</ULink>
            </template>
            <template #organization-cell="{ row: { original: transfer } }">{{ transfer.organization ?? t('roster.transfers.none') }}</template>
            <template #requested_by-cell="{ row: { original: transfer } }">{{ transfer.requested_by?.name || transfer.requested_by?.email || t('roster.transfers.none') }}</template>
            <template #status-cell="{ row: { original: transfer } }"><TransferStatusBadge :status="transfer.status" /></template>
            <template #rows-cell="{ row: { original: transfer } }">{{ transfer.row_count ?? 0 }}</template>
            <template #created-cell="{ row: { original: transfer } }">{{ formatDate(transfer.created_at) ?? t('roster.transfers.none') }}</template>
          </UTable>

          <RosterPager :current="paging.current" :last="paging.last" testid="roster-transfers" />
        </div>
      </AccessState>
    </AtriumIntegrationScreen>
  </div>
</template>
