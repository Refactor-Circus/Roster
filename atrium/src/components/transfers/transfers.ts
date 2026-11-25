import type { components } from '../../../../client/src/schema'
import type { RosterUserSummary } from '../users/users'

/*
 * What the Roster transfers screens share (AC 54, 69): the transfer types
 * and statuses, the transfer payload, and saving a CSV the API answers with.
 *
 * Scramble types a transfer's `progress` and `results` as `string`, its
 * `error` as `unknown` and its `requested_by` as `unknown[]`, so the
 * transfer is declared here as Roster's `TransferResource` builds it; the
 * gaps are Roster's to fix in its `schema()` declaration.
 */

type Schemas = components['schemas']

export type RosterTransferType = Schemas['TransferType']
export type RosterTransferStatus = Schemas['TransferStatus']
export type RosterImportType = Schemas['StartImportRequest']['type']
export type RosterExportType = Schemas['StartExportRequest']['type']

/** The import types, in Roster's order (`TransferType`). */
export const IMPORT_TYPES: readonly RosterImportType[] = ['import_members', 'import_users', 'import_teams', 'import_organizations']

/** The export types, in Roster's order (`TransferType`). */
export const EXPORT_TYPES: readonly RosterExportType[] = ['export_members', 'export_users', 'export_organizations']

/** The statuses after which a transfer neither confirms nor cancels. */
export const FINISHED_STATUSES: readonly RosterTransferStatus[] = ['completed', 'failed', 'cancelled', 'expired']

/** One row of a transfer, as `TransferRowModel::toReport()` builds it. */
export interface RosterTransferRow {
  line: number
  values?: Record<string, unknown>
  action?: string
  reasons?: string[]
  result?: string
  result_reasons?: string[]
}

/** `TransferResource`. */
export interface RosterTransfer {
  id: string
  type: RosterTransferType
  status: RosterTransferStatus
  organization: string | null
  requested_by: RosterUserSummary | null
  row_count: number | null
  /** Rows applied of the total, while an import runs */
  progress: { done: number, total: number } | null
  /** Planned rows per outcome */
  summary: Record<string, number>
  /** Applied rows per outcome */
  results: Record<string, number>
  error: string | null
  filters: Record<string, unknown> | null
  /** One page of rows, on a single transfer */
  rows?: RosterTransferRow[]
  rows_meta?: { current_page: number, last_page: number, per_page: number, total: number }
  download_url: string | null
  expires_at: string | null
  confirmed_at: string | null
  finished_at: string | null
  created_at: string | null
}

/** The layer path of a transfer's page. */
export function transferPath(id: string): string {
  return `/roster/transfers/${encodeURIComponent(id)}`
}

/** The layer path of the transfers list, filtered to an organization when one is given. */
export function transfersPath(organization?: string | null): string {
  return organization ? `/roster/transfers?organization=${encodeURIComponent(organization)}` : '/roster/transfers'
}

/** Whether a transfer has finished: it can be neither confirmed nor cancelled. */
export function isFinished(status: RosterTransferStatus): boolean {
  return FINISHED_STATUSES.includes(status)
}

/** The badge colour of a transfer status. */
export function transferStatusColor(status: string | null | undefined): 'success' | 'warning' | 'error' | 'info' | 'neutral' {
  switch (status) {
    case 'completed':
      return 'success'
    case 'awaiting_confirmation':
      return 'warning'
    case 'validating':
    case 'running':
      return 'info'
    case 'failed':
      return 'error'
    default:
      return 'neutral'
  }
}

/** The values of a transfer row, as v1 showed them: the filled ones, joined. */
export function rowValues(row: RosterTransferRow): string {
  return Object.values(row.values ?? {})
    .filter(value => value !== null && value !== undefined && value !== '')
    .map(String)
    .join(' · ')
}

/**
 * The file name a `Content-Disposition` header gives (`attachment;
 * filename="…"`), else `fallback`.
 */
export function attachmentName(header: string | null, fallback: string): string {
  const match = header ? /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(header) : null

  return match?.[1] ? decodeURIComponent(match[1]) : fallback
}

/**
 * Saves a file the API answered with (a template or an export). It comes
 * through the signed-in Roster client, so a plain link, which would carry
 * none of the auth adapter's headers, is not used.
 */
export function saveFile(blob: Blob, name: string): void {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = name
  link.rel = 'noopener'
  document.body.appendChild(link)

  try {
    link.click()
  } finally {
    link.remove()
    URL.revokeObjectURL(url)
  }
}
