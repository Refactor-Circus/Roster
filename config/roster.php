<?php

declare(strict_types=1);

use RefactorCircus\Roster\Atrium\Features\RosterSupportFeature;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Impersonation\Policies\ImpersonationPolicy;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Policies\InvitationPolicy;
use RefactorCircus\Roster\Domains\Organization\Enums\OrganizationStatus;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationDomainModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationLinkModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Policies\MembershipPolicy;
use RefactorCircus\Roster\Domains\Organization\Policies\OrganizationDomainPolicy;
use RefactorCircus\Roster\Domains\Organization\Policies\OrganizationLinkPolicy;
use RefactorCircus\Roster\Domains\Organization\Policies\OrganizationPolicy;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Policies\PermissionPolicy;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Role\Policies\RoleAssignmentPolicy;
use RefactorCircus\Roster\Domains\Role\Policies\RolePolicy;
use RefactorCircus\Roster\Domains\Team\Enums\TeamStatus;
use RefactorCircus\Roster\Domains\Team\Models\TeamMemberModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Team\Policies\TeamMemberPolicy;
use RefactorCircus\Roster\Domains\Team\Policies\TeamPolicy;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferRowModel;
use RefactorCircus\Roster\Domains\Transfer\Policies\TransferPolicy;
use RefactorCircus\Roster\Domains\Transfer\Policies\TransferRowPolicy;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Domains\User\Models\UserModel;
use RefactorCircus\Roster\Domains\User\Policies\ProfilePolicy;
use RefactorCircus\Roster\Domains\User\Policies\UserPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    |
    | Roster manages the host application's users rather than owning them.
    |
    | model:    The Eloquent user model. Three modes are supported:
    |           - your own model using the RefactorCircus\Roster\Domains\User\Concerns\HasRoster trait
    |           - your own model without the trait (Roster registers the
    |             `rosterProfile` relation on it dynamically)
    |           - RefactorCircus\Roster\Domains\User\Models\UserModel, for apps without a users table of
    |             their own; publish its migration with the
    |             `roster-users-migration` tag
    | key_type: The user model's primary key type - `int`, `ulid` or `uuid`.
    |           It decides the `user_id` column on roster_profiles, so set it
    |           before running the migrations.
    | columns:  Where the user's name, email and password live on the model,
    |           for schemas that use different column names. Set `name` to
    |           null if the users table has no name column.
    |
    | registration_status:
    |           `active`, or `pending` to make people who sign up through the
    |           app's own registration (Laravel's Registered event) wait for
    |           an approver. Admins choose per user when they create one, and
    |           each organization chooses for accounts its SSO or SCIM
    |           creates (its `provisioned_status`).
    | approvals.notify_approvers:
    |           Email everyone holding `roster.users.approve` when someone is
    |           waiting for approval.
    | approvals.notify_user:
    |           Email people when they're approved or rejected.
    |
    */

    'users' => [
        'model' => 'App\\Models\\User',
        'key_type' => 'int',
        'columns' => [
            'name' => 'name',
            'email' => 'email',
            'password' => 'password',
        ],
        'registration_status' => 'active',
        'approvals' => [
            'notify_approvers' => true,
            'notify_user' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | authorization: When true (the default), every Roster API route, MCP
    |                tool and Atrium screen requires a signed-in user holding
    |                the Action's permission. SECURITY: setting it to false
    |                makes the route middleware the only check - anyone who
    |                can reach the API could then manage every user.
    | super_admins:  Emails always treated as super-admins (every permission
    |                passes). Honoured only once the email is verified, and
    |                only on user models implementing MustVerifyEmail; prefer
    |                `php artisan roster:grant-super-admin {email}`.
    | roles.default_member:
    |                Slug of the organization role every new member gets.
    |                Null to give new members no role.
    | permission_cache.ttl:
    |                Seconds to keep the permission catalogue and each user's
    |                role grants in the cache store between requests, so a
    |                request's first permission check skips two queries. 0
    |                (the default) keeps them per request only. Roster's own
    |                Actions and model writes clear them; after writing roles,
    |                assignments or permissions some other way, call
    |                `Permissions::forgetCache()`.
    | permission_cache.store:
    |                The cache store to use; null for the default store.
    |
    */

    'authorization' => true,

    'super_admins' => [],

    'roles' => [
        'default_member' => 'member',
    ],

    'permission_cache' => [
        'ttl' => 0,
        'store' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditing
    |--------------------------------------------------------------------------
    |
    | Roster keeps no audit log of its own. With refactor-circus/keen
    | installed, Keen records every change Roster makes, and Roster shows the
    | history on its Atrium pages, its `GET /roster/history` route and its
    | history MCP tool. Without Keen none of that exists.
    |
    | enabled: Set to false to keep Roster out of the audit log even while
    |          Keen is installed: Keen records nothing of Roster's, and Roster
    |          offers no history.
    |
    */

    'audit' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Deleting
    |--------------------------------------------------------------------------
    |
    | Deleted organizations, and users when the user model uses Laravel's
    | SoftDeletes, can be restored until they're deleted permanently.
    |
    | retention_days: `php artisan roster:purge-deleted` permanently deletes
    |                 records deleted longer ago than this; schedule it. Null
    |                 keeps deleted records forever.
    |
    */

    'deletes' => [
        'retention_days' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Impersonation
    |--------------------------------------------------------------------------
    |
    | Holders of `roster.users.impersonate` can act as another user. Starting
    | issues a one-time link that only works in a browser already signed in
    | as the impersonator; it swaps the web session to the target user.
    |
    | ttl_minutes:      Impersonation ends on its own after this long.
    | link_minutes:     How long the one-time link stays valid.
    | blocked:          Permissions (and app abilities) refused while
    |                   impersonating, even to super-admins. `*` matches any
    |                   suffix, e.g. 'billing.*'. SECURITY: keep account- and
    |                   access-changing abilities here.
    | redirect:         Where entering impersonation lands.
    | return_to:        Where "return to my account" lands; null for the
    |                   user's Atrium page.
    | middleware_group: The route middleware group Roster adds its
    |                   `roster.impersonation` middleware to (which ends
    |                   expired or revoked sessions). Null to add it yourself.
    |
    */

    'impersonation' => [
        'ttl_minutes' => 30,
        'link_minutes' => 5,
        'blocked' => [
            'roster.users.impersonate',
            'roster.users.delete',
            'roster.roles.manage',
            'roster.roles.assign',
        ],
        'redirect' => '/',
        'return_to' => null,
        'middleware_group' => 'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Single Sign-On
    |--------------------------------------------------------------------------
    |
    | Organizations can sign their people in through their own identity
    | provider (OIDC, SAML or Microsoft Entra ID), configured per
    | organization in Atrium or via the API. Requires the optional package
    | laravel/socialite plus, per protocol, firebase/php-jwt (OIDC),
    | socialiteproviders/saml2 (SAML) or socialiteproviders/microsoft-azure
    | (Entra ID).
    |
    | redirect:                Where a successful sign-in lands.
    | discovery_cache_seconds: How long OIDC discovery documents and signing
    |                          keys are cached.
    | prefix / middleware:     Where the sign-in routes live.
    |
    | SECURITY: serve the callback over HTTPS. An identity provider is only
    | trusted for its organization's own domains: new accounts and automatic
    | linking happen only for those emails.
    |
    */

    'sso' => [
        'redirect' => '/',
        'discovery_cache_seconds' => 3600,
        'prefix' => 'roster/sso',
        'middleware' => ['web'],
    ],

    /*
    |--------------------------------------------------------------------------
    | SCIM Provisioning
    |--------------------------------------------------------------------------
    |
    | A SCIM 2.0 server per organization at /{prefix}/{organization}, so
    | identity providers (Okta, Microsoft Entra ID, ...) can create, update
    | and deprovision its members (Users) and teams (Groups).
    |
    | Each request needs one of the organization's SCIM tokens (issued in
    | Atrium or via the API, shown once, revocable). SECURITY: a token can
    | change the organization's membership; treat it like a password. It
    | only ever reaches its own organization, and only claims existing
    | accounts on the organization's own domains.
    |
    */

    'scim' => [
        'enabled' => true,
        'prefix' => 'scim/v2',
        'middleware' => ['api', 'throttle:roster'],
        'default_count' => 100,
        'max_count' => 500,
        'bulk' => [
            'max_operations' => 100,
            'max_payload_bytes' => 1048576,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CSV Import and Export
    |--------------------------------------------------------------------------
    |
    | Bulk imports (members, users, teams, organizations) and exports (members,
    | users, organizations), run as Impex flows. Requires the optional package refactor-circus/impex with
    | its migrations published, a queue worker and the scheduler.
    |
    | disk:                 Where uploads and exports are kept. SECURITY: use
    |                       a private disk; files hold personal data and are
    |                       only ever served through Roster's authorized route.
    | max_bytes / max_rows: Limits on an uploaded CSV.
    | confirm_within_hours: An import not confirmed in time is cancelled.
    | retention_days:       `php artisan roster:prune-transfers` deletes files
    |                       older than this; schedule it.
    | chunk:                Rows applied per batch chunk.
    |
    */

    'transfers' => [
        'disk' => 'local',
        'max_bytes' => 5242880,
        'max_rows' => 10000,
        'confirm_within_hours' => 24,
        'retention_days' => 7,
        'chunk' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Organizations
    |--------------------------------------------------------------------------
    |
    | personal: When true, every user created through Roster gets a personal
    |           organization they own. It is deleted with them and cannot be
    |           deleted on its own.
    |
    | sync_batch: The most records one bulk sync call may carry
    |             (SyncOrganizationsAction / POST roster/organizations/sync).
    |
    | Domain auto-join is configured per organization (its `auto_join` flag
    | and domain list). It only ever fires for users with a verified email,
    | so registering an address on someone else's domain is not enough.
    |
    | Organizations synced from external systems (an ERP, a CRM, ...) keep
    | their id and account number there per source, and may have no owner
    | until ownership is transferred to a member.
    |
    */

    'organizations' => [
        'personal' => false,
        'sync_batch' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Statuses
    |--------------------------------------------------------------------------
    |
    | The string-backed enum each record's `status` column holds. Point a key
    | at an enum of your own to rename, relabel or add statuses.
    |
    | users:         Implements RefactorCircus\Roster\Contracts\AccountStatus,
    |                naming the cases Roster's flows use (`active()`,
    |                `pending()`, `suspended()`, `deactivated()`) and, through
    |                `allowsAccess()`, which statuses let a user in.
    | organizations: Implement RefactorCircus\Roster\Contracts\Status. Roster
    | teams:         stores and shows these statuses only; it grants and
    |                refuses nothing by them.
    |
    | Every enum's `default()` is the status new records start with. Removing
    | a case that stored rows still hold makes reading those rows fail:
    | move the rows to another status first.
    |
    */

    'statuses' => [
        'users' => UserStatus::class,
        'organizations' => OrganizationStatus::class,
        'teams' => TeamStatus::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    |
    | expires_after_days: How long an invitation link stays valid.
    | redirect:           Where a user lands after accepting or declining.
    | accept_url:         Override the link in the invitation email, e.g. to a
    |                     page of your own. `{token}` is replaced with the
    |                     invitation token; your page should call
    |                     AcceptInvitationAction. Null uses Roster's own page.
    | middleware:         Wraps Roster's invitation page. It must authenticate
    |                     the user (only a signed-in user whose email matches
    |                     the invitation can accept it), so keep `auth` - or
    |                     your guard's equivalent - in the list.
    |
    */

    'invitations' => [
        'expires_after_days' => 7,
        'redirect' => '/',
        'accept_url' => null,
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP API Routes
    |--------------------------------------------------------------------------
    |
    | The JSON API for every Roster Action. Disabled by default.
    |
    | SECURITY: each route checks the caller's Roster permissions (see
    | Authorization above), but it needs an authenticated user to check: add
    | authentication middleware (e.g. `auth:sanctum`) before enabling it.
    |
    */

    'routes' => [
        'enabled' => false,
        'prefix' => 'roster',
        'middleware' => ['api', 'throttle:roster'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | The `throttle:roster` limiter in the default API and MCP middleware:
    | requests per minute, per signed-in user (or per IP for guests). Null
    | turns the limiter off; you can also drop it from the middleware lists.
    |
    */

    'rate_limit' => [
        'per_minute' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Server
    |--------------------------------------------------------------------------
    |
    | Exposes every Roster Action as an MCP tool. Both transports are
    | disabled by default.
    |
    | web:   An HTTP MCP endpoint at `route`, wrapped in `middleware`.
    |        SECURITY: tools check the caller's Roster permissions, so add
    |        authentication middleware (e.g. `auth:sanctum`) before enabling.
    | local: A stdio server started with `php artisan mcp:start <handle>`.
    |
    */

    'mcp' => [
        'web' => [
            'enabled' => false,
            'route' => 'mcp/roster',
            'middleware' => ['api', 'throttle:roster'],
        ],
        'local' => [
            'enabled' => false,
            'handle' => 'roster',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cortex
    |--------------------------------------------------------------------------
    |
    | When refactor-circus/cortex is installed, the MCP server is registered with it, so
    | its instructions can be overridden, and the tools join its registry,
    | so Cortex agents can manage users, organizations and roles. Agents act
    | as the signed-in user: Roster's permission checks apply, and a run
    | with no signed-in user is refused. Set `tools` to a list of tool
    | names, such as ['list-users-tool', 'show-user-tool'], to offer only
    | some of them.
    |
    */

    'cortex' => [
        'enabled' => true,
        'server' => 'roster',
        'tools' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Atrium
    |--------------------------------------------------------------------------
    |
    | features: Features that must all be on for Roster to appear in
    |           Atrium at all - its navigation, widgets, search, and pages
    |           (which answer 404 otherwise). Atrium asks its feature
    |           resolver, so Pennant (through refactor-circus/pennantplus) or any other
    |           flag system decides.
    |
    |           RosterSupportFeature is on until its global value is set, and
    |           only its global value counts. Swap in a subclass to change
    |           that, or your own feature names. Feature classes that do not
    |           exist (without refactor-circus/pennantplus) are skipped, so nothing is
    |           checked until Pennant is installed. Empty always shows Roster.
    |
    | Individual pages are still shown per Roster permission.
    |
    */

    'atrium' => [
        'features' => [
            RosterSupportFeature::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | The policy the Gate uses for each Roster model. Point a model at a class
    | of your own (extending the bundled one, or not) to change who may do
    | what; the bundled policies answer with Roster's permissions.
    |
    | Every check on Roster's JSON API, MCP tools and Atrium screens goes
    | through these: permission `roster.{resource}.{verb}` asks the policy of
    | the resource's model, by the camel-cased verb (`roster.users.manage-status`
    | asks UserPolicy::manageStatus()), with the model's class, the
    | organization or team the call is about, and the user it is about when
    | everyone may do it to themselves. Resources: users (UserModel),
    | organizations, members (MembershipModel), teams, invitations and roles.
    | The other policies answer your own `$user->can('view', $record)` checks.
    |
    | The SSO and SCIM models' policies are registered by those domains
    | themselves; add an entry here to point one of those models at a class
    | of your own, which wins over the bundled one.
    |
    | Lists that show only what falls within the caller's organizations still
    | work those out from Roster's permissions. Permissions blocked while
    | impersonating are refused before any policy is asked. Remove an entry to
    | check that resource by Roster's permissions alone.
    |
    */

    'policies' => [
        UserModel::class => UserPolicy::class,
        ProfileModel::class => ProfilePolicy::class,
        OrganizationModel::class => OrganizationPolicy::class,
        OrganizationDomainModel::class => OrganizationDomainPolicy::class,
        OrganizationLinkModel::class => OrganizationLinkPolicy::class,
        MembershipModel::class => MembershipPolicy::class,
        TeamModel::class => TeamPolicy::class,
        TeamMemberModel::class => TeamMemberPolicy::class,
        InvitationModel::class => InvitationPolicy::class,
        RoleModel::class => RolePolicy::class,
        PermissionModel::class => PermissionPolicy::class,
        RoleAssignmentModel::class => RoleAssignmentPolicy::class,
        ImpersonationModel::class => ImpersonationPolicy::class,
        TransferModel::class => TransferPolicy::class,
        TransferRowModel::class => TransferRowPolicy::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | The table each of Roster's records is stored in. Set these before running
    | the migrations: the migrations, models, validation rules and queries all
    | read them. Keys left out keep their default.
    |
    | users:         Only used by RefactorCircus\Roster\Domains\User\Models\UserModel
    |                and its opt-in migration; your own user model keeps its table.
    |
    */

    'tables' => [
        'users' => 'users',
        'profiles' => 'roster_profiles',
        'organizations' => 'roster_organizations',
        'organization_domains' => 'roster_organization_domains',
        'organization_links' => 'roster_organization_links',
        'memberships' => 'roster_memberships',
        'teams' => 'roster_teams',
        'team_members' => 'roster_team_members',
        'invitations' => 'roster_invitations',
        'permissions' => 'roster_permissions',
        'roles' => 'roster_roles',
        'permission_role' => 'roster_permission_role',
        'role_assignments' => 'roster_role_assignments',
        'impersonations' => 'roster_impersonations',
        'sso_connections' => 'roster_sso_connections',
        'sso_identities' => 'roster_sso_identities',
        'scim_tokens' => 'roster_scim_tokens',
        'scim_users' => 'roster_scim_users',
        'scim_groups' => 'roster_scim_groups',
        'transfers' => 'roster_transfers',
        'transfer_rows' => 'roster_transfer_rows',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Roster registers two middleware aliases:
    |
    | roster.active:       suspended and deactivated users get a 403.
    | roster.organization: users who belong to no organization get a 403.
    |
    */

];
