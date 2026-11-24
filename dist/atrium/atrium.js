import { AtriumErrorCard as e, AtriumNoAccess as t, defineAtriumModule as n, integrationData as r, mergeAtriumMessages as i, useAtriumIntegration as a, useIntegrationQuery as o } from "@refactor-circus/atrium";
import { USkeleton as s } from "@refactor-circus/atrium/ui";
import { Fragment as c, computed as l, createBlock as u, createElementBlock as d, createElementVNode as f, createVNode as p, defineComponent as m, openBlock as h, renderList as g, toDisplayString as _, unref as v } from "vue";
import { useI18n as y } from "vue-i18n";
// src/components/search/sources.ts
function b(e) {
	return encodeURIComponent(String(e));
}
var x = async (e, { limit: t }) => {
	let { client: n } = a("roster");
	if (!n) return [];
	let { data: i } = r(await n.GET("/search/users", { params: { query: {
		q: e,
		limit: t
	} } }));
	return i.map((e) => ({
		title: e.name || e.email || String(e.id),
		subtitle: e.email || null,
		icon: null,
		group: null,
		link: {
			path: `/roster/users/${b(e.id)}`,
			external: !1
		}
	}));
}, S = async (e, { limit: t }) => {
	let { client: n } = a("roster");
	if (!n) return [];
	let { data: i } = r(await n.GET("/search/organizations", { params: { query: {
		q: e,
		limit: t
	} } }));
	return i.map((e) => ({
		title: e.name,
		subtitle: e.slug,
		icon: null,
		group: null,
		link: {
			path: `/roster/organizations/${b(e.slug)}`,
			external: !1
		}
	}));
};
// src/components/widgets/stats.ts
function C(e) {
	let t = typeof e == "number" ? e : Number(e);
	return Number.isFinite(t) ? t : 0;
}
// src/components/widgets/OrganizationsWidget.vue?vue&type=script&setup=true&lang.ts
var w = {
	class: "h-full",
	"data-testid": "roster-organizations"
}, T = {
	key: 2,
	class: "grid h-full grid-cols-3 gap-3"
}, E = ["data-count"], D = { class: "text-sm text-muted" }, O = { class: "text-2xl font-semibold" }, k = {
	key: 3,
	class: "grid grid-cols-3 gap-3"
}, A = /* @__PURE__ */ m({
	__name: "OrganizationsWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(n) {
		let { t: i } = y(), { data: a, status: m, forbidden: b } = o("roster", "roster.stats.organizations", async (e) => r(await e.GET("/stats/organizations")).data), x = l(() => a.value === null ? [] : [
			{
				key: "organizations",
				label: i("roster.widgets.organizations.organizations"),
				count: C(a.value.organizations)
			},
			{
				key: "teams",
				label: i("roster.widgets.organizations.teams"),
				count: C(a.value.teams)
			},
			{
				key: "pending_invitations",
				label: i("roster.widgets.organizations.pending_invitations"),
				count: C(a.value.pending_invitations)
			}
		]);
		return (n, r) => {
			let o = t, l = e, y = s;
			return h(), d("div", w, [v(b) ? (h(), u(o, {
				key: 0,
				compact: ""
			})) : v(m) === "error" ? (h(), u(l, {
				key: 1,
				message: v(i)("atrium.widget.failed")
			}, null, 8, ["message"])) : v(a) ? (h(), d("dl", T, [(h(!0), d(c, null, g(v(x), (e) => (h(), d("div", {
				key: e.key,
				class: "flex flex-col-reverse",
				"data-testid": "roster-organizations-count",
				"data-count": e.key
			}, [f("dt", D, _(e.label), 1), f("dd", O, _(e.count), 1)], 8, E))), 128))])) : (h(), d("div", k, [(h(), d(c, null, g(3, (e) => p(y, {
				key: e,
				class: "h-10"
			})), 64))]))]);
		};
	}
}), j = {
	class: "h-full",
	"data-testid": "roster-user-status"
}, M = {
	key: 2,
	class: "flex h-full flex-col gap-3"
}, N = {
	class: "text-3xl font-semibold",
	"data-testid": "roster-user-status-total"
}, P = { class: "text-sm text-muted" }, F = { class: "grid grid-cols-2 gap-2 text-sm" }, I = ["data-status"], L = { class: "text-muted" }, R = { class: "font-medium" }, z = {
	key: 3,
	class: "space-y-2"
}, B = /* @__PURE__ */ m({
	__name: "UserStatusWidget",
	props: {
		data: {},
		settings: {}
	},
	setup(n) {
		let { t: i, te: a } = y(), { data: m, status: b, forbidden: x } = o("roster", "roster.stats.users", async (e) => r(await e.GET("/stats/users")).data), S = l(() => Object.entries(m.value?.by_status ?? {}).map(([e, t]) => ({
			key: e,
			label: a(`roster.widgets.user_status.statuses.${e}`) ? i(`roster.widgets.user_status.statuses.${e}`) : e,
			count: C(t)
		})));
		return (n, r) => {
			let a = t, o = e, l = s;
			return h(), d("div", j, [v(x) ? (h(), u(a, {
				key: 0,
				compact: ""
			})) : v(b) === "error" ? (h(), u(o, {
				key: 1,
				message: v(i)("atrium.widget.failed")
			}, null, 8, ["message"])) : v(m) ? (h(), d("div", M, [f("div", null, [f("p", N, _(v(C)(v(m).total)), 1), f("p", P, _(v(i)("roster.widgets.user_status.total")), 1)]), f("ul", F, [(h(!0), d(c, null, g(v(S), (e) => (h(), d("li", {
				key: e.key,
				class: "flex items-center justify-between gap-2",
				"data-testid": "roster-user-status-row",
				"data-status": e.key
			}, [f("span", L, _(e.label), 1), f("span", R, _(e.count), 1)], 8, I))), 128))])])) : (h(), d("div", z, [p(l, { class: "h-8 w-1/3" }), p(l, { class: "h-4 w-2/3" })]))]);
		};
	}
}), V = { roster: {
	access: {
		not_found: "This record was not found.",
		load_failed: "Roster could not be loaded."
	},
	transfers: {
		title: "Imports & exports",
		empty: "No imports or exports yet.",
		not_found: "This transfer was not found.",
		none: "—",
		download: "Download CSV",
		confirm: "Confirm import",
		cancel: "Cancel",
		confirm_by: "Confirm by",
		finished: "Finished",
		rows_applied: "{done} of {total} rows applied",
		fields: {
			type: "Type",
			organization: "Organization",
			requested_by: "Requested by",
			status: "Status",
			rows: "Rows",
			created: "Created",
			line: "Line",
			row: "Row",
			planned: "Planned",
			result: "Result"
		},
		import: {
			title: "New import",
			template: "Template",
			download_template: "Download template",
			organization_hint: "Organization slug; required for member and team imports.",
			file: "CSV file",
			file_hint: "Members: email, name, display_name, teams, role. Users: email, name, display_name. Teams: name, slug, members. Separate lists with ; or |.",
			submit: "Upload and preview"
		},
		export: {
			title: "New export",
			organization_hint: "Organization slug; required for member exports.",
			external_source: "Source",
			external_source_hint: "Organizations export: only records from this system, e.g. erp.",
			submit: "Start export"
		},
		types: {
			import_members: "Import members",
			import_users: "Import users",
			import_teams: "Import teams",
			import_organizations: "Import organizations",
			export_members: "Export members",
			export_users: "Export users",
			export_organizations: "Export organizations"
		},
		statuses: {
			validating: "Validating",
			awaiting_confirmation: "Awaiting confirmation",
			running: "Running",
			completed: "Completed",
			failed: "Failed",
			cancelled: "Cancelled",
			expired: "Expired"
		},
		outcomes: {
			create: "Create",
			link: "Link",
			invite: "Invite",
			update: "Update",
			skip: "Skip",
			error: "Error"
		}
	}
} }, H = { roster: { organizations: {
	title: "Organizations",
	organization: "Organization",
	new: "New organization",
	create: "Create",
	save: "Save",
	import_export: "Import & export",
	empty: "No organizations found.",
	none: "—",
	deleted: "Deleted",
	personal: "Personal",
	restore: "Restore",
	purge: "Delete permanently",
	slug_hint: "Leave blank to derive it from the name.",
	owner_hint: "The owner's user ID or email. Leave blank for none.",
	domains_hint: "One domain per line.",
	tabs: {
		active: "Active",
		deleted: "Deleted",
		members: "Members",
		teams: "Teams",
		invitations: "Invitations",
		settings: "Settings"
	},
	filters: {
		search: "Search",
		filter: "Filter",
		clear: "Clear"
	},
	fields: {
		name: "Name",
		slug: "Slug",
		owner: "Owner",
		domains: "Domains",
		auto_join: "Users with a matching email domain join automatically",
		email: "Email",
		members: "Members",
		teams: "Teams",
		status: "Status",
		reason: "Reason",
		source: "Source",
		external: "External records",
		external_id: "External ID",
		account_number: "Account number",
		actions: "Actions"
	},
	statuses: {
		active: "Active",
		inactive: "Inactive"
	},
	state: {
		not_found: "This organization was not found.",
		load_failed: "Roster could not be loaded."
	},
	members: {
		add: "Add member",
		user: "User",
		user_hint: "The user's ID or email.",
		member: "Member",
		source: "Source",
		empty: "No members yet.",
		no_owner: "This organization has no owner.",
		approve: "Approve",
		make_owner: "Make owner",
		remove: "Remove"
	},
	teams: {
		team: "Team",
		new: "New team",
		empty: "No teams yet.",
		delete: "Delete team",
		confirm_delete: "Delete this team? Its members stay in the organization.",
		no_candidates: "Every member is already in this team.",
		not_found: "This team was not found."
	},
	invitations: {
		invite: "Invite",
		empty: "No invitations yet.",
		expires: "Expires",
		revoke: "Revoke",
		statuses: {
			pending: "Pending",
			accepted: "Accepted",
			declined: "Declined",
			revoked: "Revoked",
			expired: "Expired"
		}
	},
	settings: {
		title: "Settings",
		auto_join_hint: "Matched against the domains above.",
		provisioned_status: "Status of provisioned accounts",
		provisioned_status_hint: "The status a user created by a sync or an import starts with."
	},
	links: {
		title: "External links",
		empty: "No external links.",
		synced: "Last synced",
		never: "Never",
		link: "Link",
		unlink: "Unlink",
		source_hint: "The external system, e.g. crm.",
		source_required: "Enter a source."
	},
	danger: {
		title: "Danger zone",
		delete: "Delete organization",
		delete_title: "Delete this organization",
		delete_warning: "It can be restored from the Deleted tab until it is deleted permanently.",
		confirm_delete: "Delete this organization?",
		confirm_purge: "Delete this organization permanently? This cannot be undone.",
		purge_queued: "The organization will be deleted permanently shortly.",
		purge_warning: "Deleting permanently removes the organization, its teams, memberships and invitations. This cannot be undone.",
		deleted: "This organization is deleted.",
		deleted_hint: "Restore it, or delete it permanently."
	},
	invitation: {
		title: "Invitation",
		heading: "Join {organization}",
		prompt: "You have been invited to join {organization}.",
		organization: "Organization",
		expires: "Expires",
		accept: "Accept",
		decline: "Decline",
		accepted: "You have joined {organization}.",
		declined: "You declined the invitation.",
		unavailable: "This invitation is not available.",
		unavailable_hint: "It may have expired or already been used. Ask for a new invitation.",
		throttled: "Too many attempts.",
		throttled_hint: "Wait a moment, then try again.",
		failed: "The invitation could not be loaded.",
		answer_failed: "That did not work. Try again."
	}
} } }, U = { roster: {
	users: {
		title: "Users",
		user: "User",
		new: "New user",
		import_export: "Import & export",
		empty: "No users found.",
		none: "—",
		deleted: "Deleted",
		save: "Save",
		approve: "Approve",
		restore: "Restore",
		purge: "Delete permanently",
		tabs: {
			active: "Active",
			deleted: "Deleted"
		},
		filters: {
			search: "Search",
			all_statuses: "All statuses",
			filter: "Filter",
			clear: "Clear"
		},
		fields: {
			name: "Name",
			email: "Email",
			password: "Password",
			reason: "Reason",
			status: "Status",
			created: "Created",
			actions: "Actions"
		},
		statuses: {
			active: "Active",
			pending: "Pending approval",
			suspended: "Suspended",
			deactivated: "Deactivated"
		},
		state: {
			not_found: "This user was not found.",
			load_failed: "Roster could not be loaded.",
			action_forbidden: "You may not do that."
		},
		pager: {
			previous: "Previous",
			next: "Next",
			page: "Page {current} of {last}"
		},
		create: {
			password_hint: "Leave blank to set a random password.",
			initial_status: "Initial status",
			submit: "Create"
		},
		account: {
			title: "Account",
			password_hint: "Leave blank to keep the current password."
		},
		profile: {
			title: "Profile",
			display_name: "Display name",
			avatar_url: "Avatar URL",
			timezone: "Time zone",
			locale: "Locale",
			bio: "Bio"
		},
		approval: {
			title: "Awaiting approval",
			hint: "This account waits for an approver before it can sign in.",
			reject: "Reject"
		},
		status: {
			title: "Status",
			change_to: "Change status to",
			reason_hint: "Shown to the user and kept in the history.",
			update: "Update status"
		},
		memberships: {
			title: "Memberships",
			none: "Not a member of any organization.",
			current: "Current",
			organization: "Organization",
			team: "Team",
			no_team: "No team",
			switch: "Switch",
			domain_join: "Run domain join",
			joined: "Domain join finished: {count} organization(s) joined."
		},
		impersonate: {
			title: "Impersonate",
			reason_hint: "Why you need to act as this user. It is kept with the impersonation.",
			start: "Impersonate",
			unsafe_link: "Roster answered with a link Atrium will not follow."
		},
		danger: {
			title: "Danger zone",
			delete: "Delete user",
			delete_warning: "Deleting removes the user's access. Where users are soft-deleted, the user can be restored.",
			confirm_delete: "Delete this user?",
			confirm_purge: "Delete this user permanently? This cannot be undone.",
			deleted: "This user was deleted.",
			deleted_hint: "Restore the user, or delete them permanently.",
			purge_warning: "Deleting permanently removes the user and everything Roster keeps about them."
		}
	},
	impersonations: {
		title: "Impersonations",
		empty: "No impersonations.",
		impersonator: "Impersonator",
		user: "User",
		active: "Active",
		ended: "Ended",
		link_issued: "Link issued",
		stop: "End"
	}
} }, W = { roster: { widgets: {
	user_status: {
		total: "Users",
		statuses: {
			active: "Active",
			pending: "Pending approval",
			suspended: "Suspended",
			deactivated: "Deactivated"
		}
	},
	organizations: {
		organizations: "Organizations",
		teams: "Teams",
		pending_invitations: "Pending invitations"
	}
} } }, G = [
	{
		path: "/roster/impersonations",
		name: "roster-impersonations",
		component: () => import("./chunks/impersonations-DQHMWqxA.js")
	},
	{
		path: "/roster/invitations/:token",
		name: "roster-invitations-token",
		component: () => import("./chunks/_token_-X7UrBmDc.js"),
		meta: { atrium: { public: !0 } }
	},
	{
		path: "/roster/organizations/:organization",
		name: "roster-organizations-organization",
		component: () => import("./chunks/_organization_-DqnMRyOo.js")
	},
	{
		path: "/roster/organizations/:organization/teams/:team",
		name: "roster-organizations-organization-teams-team",
		component: () => import("./chunks/_team_-DP0qKrNX.js")
	},
	{
		path: "/roster/organizations/create",
		name: "roster-organizations-create",
		component: () => import("./chunks/create-Wr9LItax.js")
	},
	{
		path: "/roster/organizations",
		name: "roster-organizations",
		component: () => import("./chunks/organizations-CbsWH3Ms.js")
	},
	{
		path: "/roster/transfers/:transfer",
		name: "roster-transfers-transfer",
		component: () => import("./chunks/_transfer_-CKx0balo.js")
	},
	{
		path: "/roster/transfers",
		name: "roster-transfers",
		component: () => import("./chunks/transfers-BCqdB9kW.js")
	},
	{
		path: "/roster/users/:user",
		name: "roster-users-user",
		component: () => import("./chunks/_user_-BceZxBlJ.js")
	},
	{
		path: "/roster/users/create",
		name: "roster-users-create",
		component: () => import("./chunks/create-BdjnZjr5.js")
	},
	{
		path: "/roster/users",
		name: "roster-users",
		component: () => import("./chunks/users-D2FmdciT.js")
	}
], K = {
	"roster.users": "roster-users",
	"roster.user-create": "roster-users-create",
	"roster.user": "roster-users-user",
	"roster.organizations": "roster-organizations",
	"roster.organization-create": "roster-organizations-create",
	"roster.organization": "roster-organizations-organization",
	"roster.team": "roster-organizations-organization-teams-team",
	"roster.impersonations": "roster-impersonations",
	"roster.transfers": "roster-transfers",
	"roster.transfer": "roster-transfers-transfer",
	"roster.invitation": "roster-invitations-token"
}, q = n({
	key: "roster",
	routes: G,
	screens: K,
	widgets: {
		"roster.user-status": B,
		"roster.organizations": A
	},
	searchSources: {
		"roster-users": x,
		"roster-organizations": S
	},
	messages: { en: i(V, H, U, W) }
});
export { q as default, K as screens };
