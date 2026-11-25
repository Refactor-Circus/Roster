VERDICT: PASS

Compat review, round 1. Every public-surface break in the diff is listed in the spec (AC 14-20, 26) and recorded in CHANGELOG v2.0.0 under Breaking: the Atrium classes, the atrium.roster.* routes, the ui views, the roster.atrium config, @rosterCan, the viewAtrium gate and atrium.view permission, RosterSupportFeature, the STATUS_COUNTS_CACHE key rename, and the changed links for the approval email and impersonation return.

Additions (6 routes, 2 MCP tools, 3 config keys, 2 event pairs per action) are backwards compatible. No schema or migration change. No finding at or above minor.
