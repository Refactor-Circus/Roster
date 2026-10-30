# Standards Feedback

Every time work has to break a standard, add a row.

| Date | Spec | Standard | Deviation | Why |
| --- | --- | --- | --- | --- |
| 2026-10-23 | standards cleanup | errors/exceptions | ScimException, SsoUnavailableException, InvalidInvitationException (and TransfersUnavailableException, SsoLoginRefused) extend RuntimeException directly; there is no RosterException base | follow-up: add RosterException and move these under it |
| 2026-10-30 | 2026-10-29-2038-adopt-atrium-screen-registry | roster/atrium-views | `resources/views/invitations/show.blade.php` and `resources/views/partials/impersonation-banner-plain.blade.php` are plain HTML with inline `style=` attributes, not Atrium components; `StylesTest` excludes exactly these two files | Atrium is optional (D-027): the invitation page and impersonation banner must render without it, where no Atrium component or stylesheet exists. With Atrium installed, Roster renders their Atrium versions under `ui/`. Follow-up: the standard should name these two plain fallbacks |
