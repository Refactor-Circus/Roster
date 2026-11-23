// openapi.json sha256: b78336e5371bcb44b63201acd01f0823b9b56004fa6ebca0c19b6a670f505e63
// Generated from openapi.json; do not edit. Regenerate with: composer client:generate
export interface paths {
    "/exports": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.exports.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/history": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.history.index"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/impersonations": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.impersonations.index"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/impersonations/{impersonation}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        delete: operations["roster.impersonations.destroy"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/imports": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.imports.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/imports/templates/{type}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.imports.templates.show"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/imports/{transfer}/confirm": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.imports.confirm"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/invitations/{token}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.invitations.lookup"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/invitations/{token}/accept": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.invitations.accept"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/invitations/{token}/decline": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.invitations.decline"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.organizations.index"];
        put?: never;
        post: operations["roster.organizations.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/external/{source}/{externalId}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put: operations["roster.organizations.sync"];
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/sync": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.organizations.sync-many"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.organizations.show"];
        put?: never;
        post?: never;
        delete: operations["roster.organizations.destroy"];
        options?: never;
        head?: never;
        patch: operations["roster.organizations.update"];
        trace?: never;
    };
    "/organizations/{organization}/invitations": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.organizations.invitations.index"];
        put?: never;
        post: operations["roster.organizations.invitations.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/invitations/{invitation}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        delete: operations["roster.organizations.invitations.revoke"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/links/{source}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put: operations["roster.organizations.links.update"];
        post?: never;
        delete: operations["roster.organizations.links.destroy"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/members": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.organizations.members.index"];
        put?: never;
        post: operations["roster.organizations.members.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/members/{user}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        delete: operations["roster.organizations.members.destroy"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/purge": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        delete: operations["roster.organizations.purge"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/restore": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.organizations.restore"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/role-assignments": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.organizations.role-assignments.index"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/teams": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.organizations.teams.index"];
        put?: never;
        post: operations["roster.organizations.teams.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/teams/{team}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.organizations.teams.show"];
        put?: never;
        post?: never;
        delete: operations["roster.organizations.teams.destroy"];
        options?: never;
        head?: never;
        patch: operations["roster.organizations.teams.update"];
        trace?: never;
    };
    "/organizations/{organization}/teams/{team}/members": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.organizations.teams.members.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/teams/{team}/members/{user}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        delete: operations["roster.organizations.teams.members.destroy"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/organizations/{organization}/transfer": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.organizations.transfer"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/permissions": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.permissions.index"];
        put?: never;
        post: operations["roster.permissions.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/permissions/{permission}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        delete: operations["roster.permissions.destroy"];
        options?: never;
        head?: never;
        patch: operations["roster.permissions.update"];
        trace?: never;
    };
    "/roles": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.roles.index"];
        put?: never;
        post: operations["roster.roles.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/roles/{role}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.roles.show"];
        put?: never;
        post?: never;
        delete: operations["roster.roles.destroy"];
        options?: never;
        head?: never;
        patch: operations["roster.roles.update"];
        trace?: never;
    };
    "/search/organizations": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.search.organizations"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/search/users": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.search.users"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/stats/organizations": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.stats.organizations"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/stats/users": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.stats.users"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/transfers": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.transfers.index"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/transfers/{transfer}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.transfers.show"];
        put?: never;
        post?: never;
        delete: operations["roster.transfers.destroy"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/transfers/{transfer}/download": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.transfers.download"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.users.index"];
        put?: never;
        post: operations["roster.users.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.users.show"];
        put?: never;
        post?: never;
        delete: operations["roster.users.destroy"];
        options?: never;
        head?: never;
        patch: operations["roster.users.update"];
        trace?: never;
    };
    "/users/{user}/approve": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.users.approve"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/context": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put: operations["roster.users.context.update"];
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/deactivate": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.users.deactivate"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/domain-join": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.users.domain-join"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/impersonate": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.users.impersonate"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/permissions": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.users.permissions"];
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/profile": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        delete?: never;
        options?: never;
        head?: never;
        patch: operations["roster.users.profile.update"];
        trace?: never;
    };
    "/users/{user}/purge": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        delete: operations["roster.users.purge"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/reactivate": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.users.reactivate"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/reject": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.users.reject"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/restore": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.users.restore"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/roles": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get: operations["roster.users.roles.index"];
        put?: never;
        post: operations["roster.users.roles.store"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/roles/{assignment}": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post?: never;
        delete: operations["roster.users.roles.destroy"];
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
    "/users/{user}/suspend": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        get?: never;
        put?: never;
        post: operations["roster.users.suspend"];
        delete?: never;
        options?: never;
        head?: never;
        patch?: never;
        trace?: never;
    };
}
export type webhooks = Record<string, never>;
export interface components {
    schemas: {
        /** AcceptInvitationRequest */
        AcceptInvitationRequest: {
            token: string;
        };
        /** AssignRoleRequest */
        AssignRoleRequest: {
            organization?: string | null;
            role: string;
            team?: string | null;
        };
        /** DeactivateUserRequest */
        DeactivateUserRequest: {
            reason?: string | null;
        };
        /** DeclineInvitationRequest */
        DeclineInvitationRequest: {
            token: string;
        };
        /** ImpersonationResource */
        ImpersonationResource: {
            active: boolean;
            end_reason: string | null;
            ended_at: string | null;
            expires_at: string | null;
            id: string;
            impersonator: unknown[] | null;
            link_expires_at: string;
            organization: string | null;
            reason: string;
            started_at: string | null;
            user: unknown[] | null;
        };
        /** InvitationLookupResource */
        InvitationLookupResource: {
            email: string;
            expires_at: string;
            organization: {
                name: string;
            };
            teams: string | {
                [key: string]: unknown;
            };
        };
        /** InvitationResource */
        InvitationResource: {
            accepted_at: string | null;
            created_at: string | null;
            declined_at: string | null;
            email: string;
            expires_at: string;
            id: string;
            organization: string;
            revoked_at: string | null;
            /** @enum {string} */
            status: "pending" | "accepted" | "declined" | "revoked" | "expired";
            teams: string | {
                [key: string]: unknown;
            };
        };
        /**
         * InvitationStatus
         * @enum {string}
         */
        InvitationStatus: "pending" | "accepted" | "declined" | "revoked" | "expired";
        /** LinkOrganizationRequest */
        LinkOrganizationRequest: {
            account_number?: string | null;
            external_id: string;
            source: string;
        };
        /** MemberResource */
        MemberResource: {
            joined_at: string | null;
            owner: boolean;
            source: string;
            status: string | null;
            teams?: string[];
            user: unknown[] | null;
        };
        /** OrganizationResource */
        OrganizationResource: {
            auto_join: boolean;
            created_at: string | null;
            domains?: string[];
            /** @description Its records in external systems (ERP, CRM, ...), one per source. */
            links: {
                account_number: string | null;
                external_id: string;
                source: string;
                synced_at: string | null;
            }[];
            members_count?: number;
            name: string;
            owner: unknown;
            personal: boolean;
            provisioned_status: string;
            slug: string;
            status: string;
            status_changed_at: string | null;
            status_reason: string | null;
            teams_count?: number;
        };
        /** OrganizationStatsResource */
        OrganizationStatsResource: {
            organizations: string;
            pending_invitations: string;
            teams: string;
        };
        /**
         * OrganizationStatus
         * @description Roster's default organization statuses (`roster.statuses.organizations`). Stored and shown only: Roster grants and refuses nothing by them.
         * @enum {string}
         */
        OrganizationStatus: "active" | "inactive";
        /** PermissionResource */
        PermissionResource: {
            description: string | null;
            name: string;
            system: boolean;
        };
        /** RejectUserRequest */
        RejectUserRequest: {
            reason?: string | null;
        };
        /** RoleAssignmentResource */
        RoleAssignmentResource: {
            assigned_at: string | null;
            id: string;
            organization: string | null;
            role: {
                id: string;
                name: string;
                /** @enum {string} */
                scope: "global" | "organization" | "team";
                slug: string;
            } | null;
            team: string | null;
            user: unknown[] | null;
        };
        /** RoleResource */
        RoleResource: {
            description: string | null;
            id: string;
            name: string;
            organization: string | null;
            permissions?: string[];
            /** @enum {string} */
            scope: "global" | "organization" | "team";
            slug: string;
            super: boolean;
            system: boolean;
        };
        /**
         * RoleScope
         * @description Where a role applies: everywhere, within one organization, or within one team.
         * @enum {string}
         */
        RoleScope: "global" | "organization" | "team";
        /** StartExportRequest */
        StartExportRequest: {
            filters?: {
                external_source?: string | null;
            };
            organization?: string | null;
            /** @enum {string} */
            type: "export_members" | "export_users" | "export_organizations";
        };
        /** StartImpersonationRequest */
        StartImpersonationRequest: {
            organization?: string | null;
            reason: string;
        };
        /** StartImportRequest */
        StartImportRequest: {
            content?: string | null;
            /**
             * Format: binary
             * @description Maximum file size: 5120 kilobytes.
             */
            file?: string;
            organization?: string | null;
            /** @enum {string} */
            type: "import_members" | "import_users" | "import_teams" | "import_organizations";
        };
        /** StoreInvitationRequest */
        StoreInvitationRequest: {
            /** Format: email */
            email: string;
            teams?: string[];
        };
        /** StoreMemberRequest */
        StoreMemberRequest: {
            user: string;
        };
        /** StoreOrganizationRequest */
        StoreOrganizationRequest: {
            auto_join?: boolean;
            domains?: string[];
            name: string;
            owner?: number | null;
            /** @enum {string} */
            provisioned_status?: "active" | "pending";
            slug?: string | null;
            status?: components["schemas"]["OrganizationStatus"];
            status_reason?: string | null;
        };
        /** StorePermissionRequest */
        StorePermissionRequest: {
            description?: string | null;
            name: string;
        };
        /** StoreRoleRequest */
        StoreRoleRequest: {
            description?: string | null;
            name: string;
            organization?: string | null;
            permissions?: string[];
            scope: components["schemas"]["RoleScope"];
            slug?: string | null;
        };
        /** StoreTeamMemberRequest */
        StoreTeamMemberRequest: {
            user: string;
        };
        /** StoreUserRequest */
        StoreUserRequest: {
            /** Format: uri */
            avatar_url?: string | null;
            bio?: string | null;
            display_name?: string | null;
            /** Format: email */
            email: string;
            locale?: string | null;
            meta?: string[] | null;
            name: string;
            password?: string | null;
            /** @enum {string} */
            status?: "active" | "pending";
            timezone?: string | null;
        };
        /** SuspendUserRequest */
        SuspendUserRequest: {
            reason?: string | null;
        };
        /** SwitchContextRequest */
        SwitchContextRequest: {
            organization: string;
            team?: string | null;
        };
        /**
         * SyncOrganizationRequest
         * @description Upsert one organization by its record in an external system, named in
         *     the URL: `PUT roster/organizations/external/{source}/{externalId}`.
         */
        SyncOrganizationRequest: {
            account_number?: string | null;
            auto_join?: boolean;
            domains?: string[];
            external_id: string;
            name?: string;
            organization?: string | null;
            owner?: string | null;
            slug?: string | null;
            source: string;
            status?: components["schemas"]["OrganizationStatus"];
            status_reason?: string | null;
        };
        /**
         * SyncOrganizationsRequest
         * @description Sync a batch of external records; each record succeeds or fails alone.
         */
        SyncOrganizationsRequest: {
            records: string[][];
        };
        /** TeamResource */
        TeamResource: {
            created_at: string | null;
            members?: unknown[][];
            members_count?: number;
            name: string;
            organization: string;
            slug: string;
            status: string;
            status_changed_at: string | null;
            status_reason: string | null;
        };
        /**
         * TeamStatus
         * @description Roster's default team statuses (`roster.statuses.teams`). Stored and shown only: Roster grants and refuses nothing by them.
         * @enum {string}
         */
        TeamStatus: "active" | "inactive";
        /** TransferOwnershipRequest */
        TransferOwnershipRequest: {
            user: string;
        };
        /** TransferResource */
        TransferResource: {
            confirmed_at: string | null;
            created_at: string | null;
            download_url: string | null;
            error: unknown;
            expires_at: string | null;
            filters: {
                [key: string]: unknown;
            } | null;
            finished_at: string | null;
            id: string;
            organization: string | null;
            progress: string;
            requested_by: unknown[] | null;
            results: string;
            row_count: number;
            rows?: unknown[];
            rows_meta?: {
                current_page: number;
                last_page: number;
                per_page: number;
                total: number;
            };
            /** @enum {string} */
            status: "validating" | "awaiting_confirmation" | "running" | "completed" | "failed" | "cancelled" | "expired";
            summary: {
                [key: string]: number;
            };
            /** @enum {string} */
            type: "import_members" | "import_users" | "import_teams" | "import_organizations" | "export_members" | "export_users" | "export_organizations";
        };
        /**
         * TransferStatus
         * @enum {string}
         */
        TransferStatus: "validating" | "awaiting_confirmation" | "running" | "completed" | "failed" | "cancelled" | "expired";
        /**
         * TransferType
         * @enum {string}
         */
        TransferType: "import_members" | "import_users" | "import_teams" | "import_organizations" | "export_members" | "export_users" | "export_organizations";
        /** UpdatePermissionRequest */
        UpdatePermissionRequest: {
            description: string | null;
        };
        /** UpdateProfileRequest */
        UpdateProfileRequest: {
            /** Format: uri */
            avatar_url?: string | null;
            bio?: string | null;
            display_name?: string | null;
            locale?: string | null;
            meta?: string[] | null;
            timezone?: string | null;
        };
        /** UpdateRoleRequest */
        UpdateRoleRequest: {
            description?: string | null;
            name?: string;
            permissions?: string[];
        };
        /** UserResource */
        UserResource: {
            created_at: string | null;
            current_organization: string | null;
            current_team: string | null;
            email: string;
            id: unknown;
            name: string;
            profile: unknown[];
            status: string;
            status_changed_at: string | null;
            status_reason: string;
        };
        /** UserStatsResource */
        UserStatsResource: {
            by_status: string;
            total: string;
        };
        /**
         * UserStatus
         * @description Roster's default user statuses (`roster.statuses.users`).
         * @enum {string}
         */
        UserStatus: "active" | "pending" | "suspended" | "deactivated";
        /** UserSummaryResource */
        UserSummaryResource: {
            email: string;
            id: unknown;
            name: string;
        };
    };
    responses: {
        /** @description Authorization error */
        AuthorizationException: {
            headers: {
                [name: string]: unknown;
            };
            content: {
                "application/json": {
                    /** @description Error overview. */
                    message: string;
                };
            };
        };
        /** @description Validation error */
        ValidationException: {
            headers: {
                [name: string]: unknown;
            };
            content: {
                "application/json": {
                    /** @description A detailed description of each field that failed validation. */
                    errors: {
                        [key: string]: string[];
                    };
                    /** @description Errors overview. */
                    message: string;
                };
            };
        };
    };
    parameters: never;
    requestBodies: never;
    headers: never;
    pathItems: never;
}
export type $defs = Record<string, never>;
export interface operations {
    "roster.exports.store": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["StartExportRequest"];
            };
        };
        responses: {
            /** @description `TransferResource` */
            202: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TransferResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.history.index": {
        parameters: {
            query?: {
                subject_type?: string | null;
                subject_id?: string | null;
                action?: string | null;
                per_page?: number | null;
                cursor?: string | null;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": string;
                };
            };
            403: components["responses"]["AuthorizationException"];
            404: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @constant */
                        message: "Auditing is turned off for this package.";
                    } | {
                        /** @constant */
                        message: "No audit log is installed. Install refactor-circus/keen to record history.";
                    };
                };
            };
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.impersonations.index": {
        parameters: {
            query?: {
                active?: boolean;
                user?: string | null;
                impersonator?: string | null;
                organization?: string | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `ImpersonationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["ImpersonationResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.impersonations.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                impersonation: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `ImpersonationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["ImpersonationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.imports.store": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "multipart/form-data": components["schemas"]["StartImportRequest"];
            };
        };
        responses: {
            /** @description `TransferResource` */
            202: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TransferResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.imports.templates.show": {
        parameters: {
            query: {
                type: "import_members" | "import_users" | "import_teams" | "import_organizations";
            };
            header?: never;
            path: {
                type: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            200: {
                headers: {
                    "Content-Disposition"?: string;
                    [name: string]: unknown;
                };
                content: {
                    "text/csv; charset=UTF-8": string;
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.imports.confirm": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                transfer: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `TransferResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TransferResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.invitations.lookup": {
        parameters: {
            query: {
                token: string;
            };
            header?: never;
            path: {
                token: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `InvitationLookupResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["InvitationLookupResource"];
                    };
                };
            };
            404: {
                headers: {
                    "Cache-Control"?: "no-store";
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @constant */
                        message: "Not found.";
                    };
                };
            };
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.invitations.accept": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                token: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["AcceptInvitationRequest"];
            };
        };
        responses: {
            /** @description `InvitationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["InvitationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.invitations.decline": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                token: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["DeclineInvitationRequest"];
            };
        };
        responses: {
            /** @description `InvitationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["InvitationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.index": {
        parameters: {
            query?: {
                search?: string | null;
                trashed?: "only" | "with" | null;
                user?: string | null;
                source?: string | null;
                external_id?: string | null;
                account_number?: string | null;
                status?: components["schemas"]["OrganizationStatus"] | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `OrganizationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.store": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["StoreOrganizationRequest"];
            };
        };
        responses: {
            /** @description `OrganizationResource` */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.sync": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                source: string;
                externalId: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["SyncOrganizationRequest"];
            };
        };
        responses: {
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": string;
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.sync-many": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["SyncOrganizationsRequest"];
            };
        };
        responses: {
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: unknown[];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.show": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `OrganizationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description No content */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.update": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `OrganizationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.invitations.index": {
        parameters: {
            query?: {
                status?: components["schemas"]["InvitationStatus"] | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `InvitationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["InvitationResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.invitations.store": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["StoreInvitationRequest"];
            };
        };
        responses: {
            /** @description `InvitationResource` */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["InvitationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.invitations.revoke": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
                invitation: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `InvitationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["InvitationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.links.update": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
                source: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["LinkOrganizationRequest"];
            };
        };
        responses: {
            /** @description `OrganizationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"] & Record<string, never>;
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.links.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
                source: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `OrganizationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.members.index": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `MemberResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["MemberResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.members.store": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["StoreMemberRequest"];
            };
        };
        responses: {
            /** @description `MemberResource` */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["MemberResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.members.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description No content */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.purge": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            202: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @constant */
                        message: "The organization is being deleted permanently. It disappears shortly.";
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.restore": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `OrganizationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.role-assignments.index": {
        parameters: {
            query?: {
                user?: string | null;
                organization?: string | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `RoleAssignmentResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["RoleAssignmentResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.teams.index": {
        parameters: {
            query?: {
                status?: components["schemas"]["TeamStatus"] | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `TeamResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TeamResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.teams.store": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `TeamResource` */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TeamResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.teams.show": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
                team: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `TeamResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TeamResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.teams.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
                team: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description No content */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.teams.update": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
                team: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `TeamResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TeamResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.teams.members.store": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
                team: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["StoreTeamMemberRequest"];
            };
        };
        responses: {
            /** @description `TeamResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TeamResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.teams.members.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
                team: string;
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description No content */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.organizations.transfer": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                organization: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["TransferOwnershipRequest"];
            };
        };
        responses: {
            /** @description `OrganizationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.permissions.index": {
        parameters: {
            query?: {
                search?: string | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `PermissionResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["PermissionResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.permissions.store": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["StorePermissionRequest"];
            };
        };
        responses: {
            /** @description `PermissionResource` */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["PermissionResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.permissions.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                permission: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description No content */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.permissions.update": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                permission: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["UpdatePermissionRequest"];
            };
        };
        responses: {
            /** @description `PermissionResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["PermissionResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.roles.index": {
        parameters: {
            query?: {
                scope?: components["schemas"]["RoleScope"] | null;
                organization?: string | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `RoleResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["RoleResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.roles.store": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["StoreRoleRequest"];
            };
        };
        responses: {
            /** @description `RoleResource` */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["RoleResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.roles.show": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                role: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `RoleResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["RoleResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.roles.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                role: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description No content */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.roles.update": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                role: string;
            };
            cookie?: never;
        };
        requestBody?: {
            content: {
                "application/json": components["schemas"]["UpdateRoleRequest"];
            };
        };
        responses: {
            /** @description `RoleResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["RoleResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.search.organizations": {
        parameters: {
            query: {
                q: string;
                limit?: number;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `OrganizationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.search.users": {
        parameters: {
            query: {
                q: string;
                limit?: number;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `UserSummaryResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserSummaryResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.stats.organizations": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `OrganizationStatsResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationStatsResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.stats.users": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `UserStatsResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserStatsResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.transfers.index": {
        parameters: {
            query?: {
                organization?: string | null;
                type?: components["schemas"]["TransferType"] | null;
                status?: components["schemas"]["TransferStatus"] | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `TransferResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TransferResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.transfers.show": {
        parameters: {
            query?: {
                rows_page?: number;
            };
            header?: never;
            path: {
                transfer: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `TransferResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TransferResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.transfers.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                transfer: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `TransferResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["TransferResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.transfers.download": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                transfer: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": Record<string, never>;
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.index": {
        parameters: {
            query?: {
                search?: string | null;
                status?: components["schemas"]["UserStatus"] | null;
                trashed?: "only" | "with" | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.store": {
        parameters: {
            query?: never;
            header?: never;
            path?: never;
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["StoreUserRequest"];
            };
        };
        responses: {
            /** @description `UserResource` */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.show": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description No content */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.update": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.approve": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.context.update": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["SwitchContextRequest"];
            };
        };
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.deactivate": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: {
            content: {
                "application/json": components["schemas"]["DeactivateUserRequest"];
            };
        };
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.domain-join": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `OrganizationResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["OrganizationResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.impersonate": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["StartImpersonationRequest"];
            };
        };
        responses: {
            /** @description `ImpersonationResource` */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["ImpersonationResource"];
                        url: string;
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.permissions": {
        parameters: {
            query?: {
                organization?: string | null;
                team?: string | null;
            };
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: string;
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.profile.update": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: {
            content: {
                "application/json": components["schemas"]["UpdateProfileRequest"];
            };
        };
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.purge": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description No content */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.reactivate": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.reject": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: {
            content: {
                "application/json": components["schemas"]["RejectUserRequest"];
            };
        };
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.restore": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.roles.index": {
        parameters: {
            query?: {
                user?: string | null;
                organization?: string | null;
                per_page?: number | null;
                page?: number | null;
            };
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description Array of `RoleAssignmentResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["RoleAssignmentResource"][];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.roles.store": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody: {
            content: {
                "application/json": components["schemas"]["AssignRoleRequest"];
            };
        };
        responses: {
            /** @description `RoleAssignmentResource` */
            201: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["RoleAssignmentResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.roles.destroy": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
                assignment: string;
            };
            cookie?: never;
        };
        requestBody?: never;
        responses: {
            /** @description No content */
            204: {
                headers: {
                    [name: string]: unknown;
                };
                content?: never;
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
    "roster.users.suspend": {
        parameters: {
            query?: never;
            header?: never;
            path: {
                user: string;
            };
            cookie?: never;
        };
        requestBody?: {
            content: {
                "application/json": components["schemas"]["SuspendUserRequest"];
            };
        };
        responses: {
            /** @description `UserResource` */
            200: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        data: components["schemas"]["UserResource"];
                    };
                };
            };
            403: components["responses"]["AuthorizationException"];
            422: components["responses"]["ValidationException"];
            /** @description Too many requests */
            429: {
                headers: {
                    [name: string]: unknown;
                };
                content: {
                    "application/json": {
                        /** @description Error overview. */
                        message: string;
                    };
                };
            };
        };
    };
}
