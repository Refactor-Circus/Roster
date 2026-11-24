<script setup lang="ts">
import AccessState from '../../components/access/AccessState.vue'
import TransferStatusBadge from '../../components/transfers/TransferStatusBadge.vue'
import {
  attachmentName,
  isFinished,
  rowValues,
  saveFile,
  transfersPath,
  type RosterTransfer
} from '../../components/transfers/transfers'
import RosterActionError from '../../components/users/RosterActionError.vue'
import type { TableColumn } from '@nuxt/ui'
import {
  formatDate,
  queryPage,
  rosterData,
  useRosterAction,
  useRosterLoad,
  type RosterOne
} from '../../components/users/users'

/**
 * `roster.transfer`: one import or export (`roster.transfers.show`), ported
 * from v1's `transfers/show`: its status, requester, rows, confirm-by date,
 * progress, error and summary, then its rows a page at a time (`rows_page`).
 * An import awaiting confirmation is confirmed through
 * `roster.imports.confirm`; a transfer that has not finished is cancelled
 * through `roster.transfers.destroy`. A downloadable transfer (Roster sends
 * its `download_url`) is saved through `roster.transfers.download`.
 */
const { t, te } = useI18n()
const route = useRoute()
const router = useRouter()
const action = useRosterAction()

const id = computed(() => String(route.params.transfer))
const rowsPage = computed(() => queryPage(route.query.rows_page))

const source = computed(() => ({ id: id.value, rowsPage: rowsPage.value }))

const { client, data, pending, failed, status, reload } = useRosterLoad(async roster =>
  rosterData<RosterOne<RosterTransfer>>(await roster.GET('/transfers/{transfer}', {
    params: { path: { transfer: id.value }, query: rowsPage.value > 1 ? { rows_page: rowsPage.value } : {} }
  })).data, source)

const rows = computed(() => data.value?.rows ?? [])

type TransferRow = typeof rows.value[number]

const rowColumns = computed<TableColumn<TransferRow>[]>(() => [
  { id: 'line', header: t('roster.transfers.fields.line'), meta: { class: { th: 'text-right', td: 'text-right tabular-nums' } } },
  { id: 'row', header: t('roster.transfers.fields.row') },
  { id: 'planned', header: t('roster.transfers.fields.planned') },
  { id: 'result', header: t('roster.transfers.fields.result') }
])
const rowsPaging = computed(() => ({ current: data.value?.rows_meta?.current_page ?? 1, last: data.value?.rows_meta?.last_page ?? 1 }))
const summary = computed(() => Object.entries(data.value?.summary ?? {}))
const results = computed(() => Object.entries(data.value?.results ?? {}))
const fieldErrors = computed(() => Object.values(action.errors.value).flat())

/** A row outcome's label (`create`, `skip`, …); a key the layer does not know, such as an export's `exported`, shows as sent. */
function outcomeLabel(outcome: string): string {
  const key = `roster.transfers.outcomes.${outcome}`

  return te(key) ? t(key) : outcome
}

function showRows(page: number): void {
  router.replace({ query: { ...route.query, rows_page: page > 1 ? String(page) : undefined } })
}

async function confirmImport(): Promise<void> {
  if (client && await action.run(async () => rosterData(await client.POST('/imports/{transfer}/confirm', { params: { path: { transfer: id.value } } })))) {
    await reload()
  }
}

async function cancel(): Promise<void> {
  if (client && await action.run(async () => rosterData(await client.DELETE('/transfers/{transfer}', { params: { path: { transfer: id.value } } })))) {
    await reload()
  }
}

async function download(): Promise<void> {
  const transfer = data.value

  if (!client || !transfer) {
    return
  }

  await action.run(async () => {
    const result = await client.GET('/transfers/{transfer}/download', { params: { path: { transfer: transfer.id } }, parseAs: 'blob' })
    const blob = rosterData<Blob>(result)

    saveFile(blob, attachmentName(result.response.headers.get('Content-Disposition'), `roster-${transfer.type}-${transfer.id}.csv`))
  })
}
</script>

<template>
  <div class="space-y-4" data-testid="roster-transfer">
    <UPageHeader>
      <template #title>
        <span class="flex items-center gap-3">
          <UButton
            :to="transfersPath(data?.organization)"
            icon="i-lucide-arrow-left"
            color="neutral"
            variant="ghost"
            :aria-label="t('roster.transfers.title')"
            data-testid="roster-transfer-back"
          />
          <span v-if="data" class="truncate" data-testid="roster-transfer-title">{{ t(`roster.transfers.types.${data.type}`) }}</span>
        </span>
      </template>
      <template v-if="data?.organization" #description>{{ data.organization }}</template>
      <template v-if="data?.download_url" #links>
        <UButton
          icon="i-lucide-download"
          :loading="action.busy.value"
          data-testid="roster-transfer-download"
          @click="download"
        >
          {{ t('roster.transfers.download') }}
        </UButton>
      </template>
    </UPageHeader>

    <AtriumIntegrationScreen screen-key="roster.transfer" :record="id">
      <AccessState :ready="!pending && data !== null" :failed="failed" :status="status" :not-found="t('roster.transfers.not_found')">
        <div v-if="data" class="space-y-4">
          <RosterActionError :action="action" />
          <UAlert
            v-if="fieldErrors.length > 0"
            color="error"
            variant="subtle"
            :title="fieldErrors.join(' ')"
            data-testid="roster-transfer-action-errors"
          />

          <UCard>
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
              <div>
                <dt class="text-muted">{{ t('roster.transfers.fields.status') }}</dt>
                <dd><TransferStatusBadge :status="data.status" testid="roster-transfer-status" /></dd>
              </div>
              <div>
                <dt class="text-muted">{{ t('roster.transfers.fields.requested_by') }}</dt>
                <dd>{{ data.requested_by?.name || data.requested_by?.email || t('roster.transfers.none') }}</dd>
              </div>
              <div>
                <dt class="text-muted">{{ t('roster.transfers.fields.rows') }}</dt>
                <dd>{{ data.row_count ?? 0 }}</dd>
              </div>
              <div v-if="data.expires_at && data.status === 'awaiting_confirmation'" data-testid="roster-transfer-confirm-by">
                <dt class="text-muted">{{ t('roster.transfers.confirm_by') }}</dt>
                <dd>{{ formatDate(data.expires_at) }}</dd>
              </div>
              <div v-if="data.finished_at">
                <dt class="text-muted">{{ t('roster.transfers.finished') }}</dt>
                <dd>{{ formatDate(data.finished_at) }}</dd>
              </div>
            </dl>

            <UAlert v-if="data.error" class="mt-4" color="error" variant="subtle" :title="data.error" data-testid="roster-transfer-error" />

            <div v-if="data.progress && data.status === 'running'" class="mt-4 space-y-1" data-testid="roster-transfer-progress">
              <UProgress :model-value="data.progress.done" :max="Math.max(1, data.progress.total)" />
              <p class="text-sm text-muted">{{ t('roster.transfers.rows_applied', { done: data.progress.done, total: data.progress.total }) }}</p>
            </div>

            <div v-if="summary.length > 0" class="mt-4 flex flex-wrap gap-2" data-testid="roster-transfer-summary">
              <UBadge v-for="[outcome, count] in summary" :key="outcome" color="neutral" variant="subtle">{{ outcomeLabel(outcome) }}: {{ count }}</UBadge>
            </div>
            <div v-if="results.length > 0" class="mt-2 flex flex-wrap gap-2" data-testid="roster-transfer-results">
              <UBadge v-for="[outcome, count] in results" :key="outcome" color="primary" variant="subtle">{{ outcomeLabel(outcome) }}: {{ count }}</UBadge>
            </div>

            <div v-if="!isFinished(data.status)" class="mt-4 flex gap-2">
              <UButton
                v-if="data.status === 'awaiting_confirmation'"
                icon="i-lucide-check"
                :loading="action.busy.value"
                data-testid="roster-transfer-confirm"
                @click="confirmImport"
              >
                {{ t('roster.transfers.confirm') }}
              </UButton>
              <UButton
                color="error"
                variant="outline"
                icon="i-lucide-x"
                :loading="action.busy.value"
                data-testid="roster-transfer-cancel"
                @click="cancel"
              >
                {{ t('roster.transfers.cancel') }}
              </UButton>
            </div>
          </UCard>

          <template v-if="rows.length > 0">
            <UTable :data="rows" :columns="rowColumns" class="text-sm" data-testid="roster-transfer-rows">
              <template #line-cell="{ row: { original: row } }">
                <span :data-testid="`roster-transfer-line-${row.line}`">{{ row.line }}</span>
              </template>
              <template #row-cell="{ row: { original: row } }">
                <span class="text-xs">{{ rowValues(row) }}</span>
              </template>
              <template #planned-cell="{ row: { original: row } }">
                <UBadge color="neutral" variant="subtle">{{ outcomeLabel(row.action ?? 'skip') }}</UBadge>
                <span class="ml-1 text-xs text-muted">{{ (row.reasons ?? []).join(' ') }}</span>
              </template>
              <template #result-cell="{ row: { original: row } }">
                <template v-if="row.result">
                  <UBadge color="neutral" variant="subtle">{{ outcomeLabel(row.result) }}</UBadge>
                  <span class="ml-1 text-xs text-muted">{{ (row.result_reasons ?? []).join(' ') }}</span>
                </template>
              </template>
            </UTable>

            <div v-if="rowsPaging.last > 1" class="flex items-center gap-3 text-sm">
              <UButton
                variant="outline"
                size="sm"
                icon="i-lucide-chevron-left"
                :disabled="rowsPaging.current <= 1"
                data-testid="roster-transfer-rows-prev"
                @click="showRows(rowsPaging.current - 1)"
              >
                {{ t('roster.users.pager.previous') }}
              </UButton>
              <span class="text-muted">{{ t('roster.users.pager.page', { current: rowsPaging.current, last: rowsPaging.last }) }}</span>
              <UButton
                variant="outline"
                size="sm"
                trailing-icon="i-lucide-chevron-right"
                :disabled="rowsPaging.current >= rowsPaging.last"
                data-testid="roster-transfer-rows-next"
                @click="showRows(rowsPaging.current + 1)"
              >
                {{ t('roster.users.pager.next') }}
              </UButton>
            </div>
          </template>
        </div>
      </AccessState>
    </AtriumIntegrationScreen>
  </div>
</template>
