import { vi } from 'vitest'
import type { RosterTransfer } from '../../src/components/transfers/transfers'

/*
 * Shared payloads of the Roster transfers suites
 * (AC 54, 69), typed with `satisfies` against the shape the page reads
 * (`transfers.ts`), as Scramble types a transfer's `progress`, `results`,
 * `error` and `requested_by` loosely. The page mounter, the screen answer and the call filters
 * come from the users area's `../users/support`.
 */

export const adaSummary = { id: 7, name: 'Ada Lovelace', email: 'ada@example.com' }

export const pendingImport = {
  id: '01JTRANSFERIMPORT',
  type: 'import_users',
  status: 'awaiting_confirmation',
  organization: 'acme',
  requested_by: adaSummary,
  row_count: 2,
  progress: null,
  summary: { create: 1, skip: 1 },
  results: {},
  error: null,
  filters: null,
  rows: [
    { line: 2, values: { email: 'new@example.com', name: 'New' }, action: 'create', reasons: [] },
    { line: 3, values: { email: 'ada@example.com', name: 'Ada' }, action: 'skip', reasons: ['Already a user.'] }
  ],
  rows_meta: { current_page: 1, last_page: 2, per_page: 100, total: 2 },
  download_url: null,
  expires_at: '2026-11-24T10:00:00+00:00',
  confirmed_at: null,
  finished_at: null,
  created_at: '2026-11-23T10:00:00+00:00'
} satisfies RosterTransfer

export const finishedExport = {
  ...pendingImport,
  id: '01JTRANSFEREXPORT',
  type: 'export_users',
  status: 'completed',
  organization: null,
  row_count: 12,
  summary: {},
  results: { exported: 12 },
  rows: [],
  rows_meta: { current_page: 1, last_page: 1, per_page: 100, total: 0 },
  download_url: 'http://localhost/roster/api/transfers/01JTRANSFEREXPORT/download',
  expires_at: null,
  finished_at: '2026-11-23T10:05:00+00:00'
} satisfies RosterTransfer

export const failedImport = {
  ...pendingImport,
  id: '01JTRANSFERFAILED',
  status: 'failed',
  error: 'The file has no email column.',
  rows: [],
  rows_meta: { current_page: 1, last_page: 1, per_page: 100, total: 0 },
  finished_at: '2026-11-23T10:02:00+00:00'
} satisfies RosterTransfer

export const runningImport = {
  ...pendingImport,
  id: '01JTRANSFERRUNNING',
  status: 'running',
  progress: { done: 1, total: 2 },
  confirmed_at: '2026-11-23T10:01:00+00:00'
} satisfies RosterTransfer

/** The answer of a CSV route (a template or an export), as the mocked API sends it. */
export const CSV = 'email,name\r\nada@example.com,Ada\r\n'

export interface SavedFile {
  href: string
  download: string
}

/**
 * Catches the files the page saves: `URL.createObjectURL()` hands out a
 * fake object URL, and each click of a download anchor is recorded instead
 * of followed. `vi.restoreAllMocks()` undoes it.
 */
export function catchDownloads(): { saved: SavedFile[], blobs: Blob[] } {
  const saved: SavedFile[] = []
  const blobs: Blob[] = []

  vi.spyOn(URL, 'createObjectURL').mockImplementation((blob) => {
    blobs.push(blob as Blob)

    return `blob:roster-${blobs.length}`
  })
  vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined)
  vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(function (this: HTMLAnchorElement) {
    saved.push({ href: this.getAttribute('href') ?? '', download: this.download })
  })

  return { saved, blobs }
}
