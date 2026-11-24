/**
 * What `roster.stats.users` and `roster.stats.organizations` answer, as
 * Roster's resources send it. Roster's vendored schema types these counts as
 * strings (its Scramble export reads them loosely), so the widgets read them
 * with `countOf()`, which takes either.
 */
export interface RosterUserStats {
  total: number | string
  by_status: Record<string, number | string>
}

export interface RosterOrganizationStats {
  organizations: number | string
  teams: number | string
  pending_invitations: number | string
}

/** A count as a whole number, 0 for anything that is not one. */
export function countOf(value: unknown): number {
  const count = typeof value === 'number' ? value : Number(value)

  return Number.isFinite(count) ? count : 0
}
