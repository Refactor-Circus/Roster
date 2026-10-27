# Soft Deletes — Shaping Notes

## Scope

Organization and user deletes become soft deletes ("both org and user deletes should be soft deletes"), keeping the Danger zone warning ("but keep the warning").

## Decisions

- **Users:** Laravel `SoftDeletes` on the user model. The bundled model and the workbench get it, and host apps add it.
  - A model without the trait keeps today's hard delete, at the user's choice.
- **Organizations:** `SoftDeletes`. Their slug and domains stay reserved while deleted.
- **Recovery:**
  - restore;
  - delete permanently, with its own permissions;
  - `roster:purge-deleted` with `roster.deletes.retention_days`, where null keeps records forever ("retention should have an indefinite option").
- **Defaults I chose:**
  - restore uses the delete permission, and force delete gets new `*.force-delete` permissions;
  - a trashed organization is inert (SCIM, SSO, auto-join and current context skip it);
  - trashed users are hidden from related lists;
  - owners of shared organizations still can't be deleted.

## Decisions made during implementation

- **Naming:** the permanent-delete Actions are `PurgeUserAction` / `PurgeOrganizationAction`, with permissions `roster.users.purge` / `roster.organizations.purge`. The audit recorder names entries after events, so "force-delete" would have been recorded as `user_force.deleted`; purge gives `user.purged` and `organization.purged`.
- **Personal organizations** follow their owner: soft-deleting a user moves their personal organization to Deleted, restoring brings it back, and purging removes it.
- **A deleted organization is switched off** by the soft-delete scope on slug lookups, plus guards on what's reached through relationships:
  - SCIM's token check (organization null → 401);
  - a global scope on `SsoConnection` that hides connections of deleted organizations;
  - invitations of a deleted organization are invalid;
  - `Roster::organization()` / `preload()` only count memberships of live organizations;
  - domain auto-join already queries live organizations.
- **Deleted users** are hidden from member, team and role-assignment lists (`whereHas('user')`) but keep their rows for a restore.
- **Atrium:** a deleted record's show page renders a small dedicated view (banner, Restore, and a Danger zone for Delete permanently with a warning and "I understand"). Deleting redirects to the Deleted list.
- **Delete warnings are kept** (the user's ask). They now say "Moves … to Deleted" and describe the restore. The permanent wording stays for user models without `SoftDeletes`.
- **`roster:purge-deleted`** uses an inclusive cutoff (`<=`), so `--days=0` purges everything deleted so far. It purges users first (taking their personal organizations), and skips, with a warning, deleted users who still own shared organizations.
- **Workbench and tests:** a workbench migration adds `deleted_at` to `users`, and the workbench `User` model soft-deletes. The traitless test mode (`PlainUser`) covers the hard-delete path.

## Context

- **Visuals:** None.
- **References:** `DeleteUserAction` (already soft-delete aware), `DeleteOrganizationAction`, `PruneTransfersCommand`, and the Danger zone partials.
- **Product alignment:** a Phase 3 follow-up.
