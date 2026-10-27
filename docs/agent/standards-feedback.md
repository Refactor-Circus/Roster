# Standards Feedback

Every time work has to break a standard, add a row.

| Date | Spec | Standard | Deviation | Why |
| --- | --- | --- | --- | --- |
| 2026-10-23 | standards cleanup | errors/exceptions | ScimException, SsoUnavailableException, InvalidInvitationException (and TransfersUnavailableException, SsoLoginRefused) extend RuntimeException directly; there is no RosterException base | follow-up: add RosterException and move these under it |
