import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterPager-CFRR10Qn.js";
import { t as n } from "./RosterState-4BrqaETn.js";
import { t as r } from "./RosterStatusBadge-CjGfIXnt.js";
import { t as ee } from "./RosterTabs-C9EsVdM2.js";
import { _ as te, c as i, g as a, h as o, i as s, l as c, m as l, r as ne, s as u, t as d } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as re } from "@refactor-circus/atrium";
import { UBadge as ie, UButton as ae, UEmpty as f, UForm as p, UFormField as m, UInput as h, ULink as g, UPageHeader as oe, USelect as se, UTable as ce } from "@refactor-circus/atrium/ui";
import { Fragment as le, computed as _, createBlock as v, createCommentVNode as ue, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, isRef as w, openBlock as T, reactive as E, toDisplayString as D, unref as O, watch as k, withCtx as A } from "vue";
import { useI18n as j } from "vue-i18n";
import { useRoute as M, useRouter as N } from "vue-router";
// src/pages/users/index.vue?vue&type=script&setup=true&lang.ts
var P = {
	class: "space-y-4",
	"data-testid": "roster-users"
}, F = { class: "space-y-4" }, I = { class: "space-y-4" }, L = ["data-testid"], R = {
	key: 1,
	class: "font-medium"
}, z = { class: "flex justify-end gap-2" }, B = "*", V = /* @__PURE__ */ C({
	__name: "index",
	setup(C) {
		let { t: V } = j(), H = M(), U = N(), W = l(), G = _(() => i(H.query.trashed) === "only"), K = _(() => {
			let e = i(H.query.status), t = i(H.query.search), n = u(H.query.page);
			return {
				...e ? { status: e } : {},
				...t ? { search: t } : {},
				...G.value ? { trashed: "only" } : {},
				...n > 1 ? { page: n } : {}
			};
		}), { client: q, data: J, pending: de, failed: fe, status: pe, reload: Y } = o(async (e) => c(await e.GET("/users", { params: { query: K.value } })), K), X = _(() => J.value?.data ?? []), Z = _(() => s(J.value)), Q = E({
			status: "",
			search: ""
		}), $ = _({
			get: () => Q.status || B,
			set: (e) => {
				Q.status = e === B ? "" : e;
			}
		}), me = _(() => [{
			label: V("roster.users.filters.all_statuses"),
			value: B
		}, ...d.map((e) => ({
			label: V(`roster.users.statuses.${e}`),
			value: e
		}))]), he = _(() => [{
			key: "active",
			label: V("roster.users.tabs.active")
		}, {
			key: "deleted",
			label: V("roster.users.tabs.deleted")
		}]), ge = _(() => [
			{
				id: "name",
				header: V("roster.users.fields.name")
			},
			{
				id: "email",
				header: V("roster.users.fields.email")
			},
			{
				id: "status",
				header: V("roster.users.fields.status")
			},
			{
				id: "created",
				header: V("roster.users.fields.created")
			},
			{
				id: "actions",
				header: V("roster.users.fields.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		k(() => [H.query.status, H.query.search], () => {
			Q.status = i(H.query.status) ?? "", Q.search = i(H.query.search) ?? "";
		}, { immediate: !0 });
		function _e(e) {
			U.replace({ query: {
				...H.query,
				trashed: e ? "only" : void 0,
				page: void 0
			} });
		}
		function ve() {
			U.replace({ query: {
				...H.query,
				status: Q.status || void 0,
				search: Q.search || void 0,
				page: void 0
			} });
		}
		function ye() {
			U.replace({ query: {
				...H.query,
				status: void 0,
				search: void 0,
				page: void 0
			} });
		}
		async function be(e) {
			q && await W.run(async () => c(await q.POST("/users/{user}/approve", { params: { path: { user: String(e.id) } } }))) && await Y();
		}
		async function xe(e) {
			q && await W.run(async () => c(await q.POST("/users/{user}/restore", { params: { path: { user: String(e.id) } } }))) && await Y();
		}
		async function Se(e) {
			q && window.confirm(V("roster.users.danger.confirm_purge")) && await W.run(async () => c(await q.DELETE("/users/{user}/purge", { params: { path: { user: String(e.id) } } }))) && await Y();
		}
		return (i, o) => {
			let s = ae, c = oe, l = h, u = m, d = se, _ = p, C = f, E = g, k = ie, j = ce, M = re;
			return T(), y("div", P, [S(c, { title: O(V)("roster.users.title") }, {
				links: A(() => [S(s, {
					to: "/roster/transfers",
					variant: "outline",
					icon: "i-lucide-arrow-up-down",
					"data-testid": "roster-users-transfers"
				}, {
					default: A(() => [x(D(O(V)("roster.users.import_export")), 1)]),
					_: 1
				}), S(s, {
					to: "/roster/users/create",
					icon: "i-lucide-plus",
					"data-testid": "roster-users-new"
				}, {
					default: A(() => [x(D(O(V)("roster.users.new")), 1)]),
					_: 1
				})]),
				_: 1
			}, 8, ["title"]), S(M, { "screen-key": "roster.users" }, {
				default: A(() => [b("div", F, [
					S(ee, {
						tabs: O(he),
						current: O(G) ? "deleted" : "active",
						label: O(V)("roster.users.title"),
						testid: "roster-users-tab",
						onSelect: o[0] ||= (e) => _e(e === "deleted")
					}, null, 8, [
						"tabs",
						"current",
						"label"
					]),
					S(_, {
						state: O(Q),
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "roster-users-filters",
						onSubmit: ve
					}, {
						default: A(() => [
							S(u, {
								label: O(V)("roster.users.filters.search"),
								name: "search"
							}, {
								default: A(() => [S(l, {
									modelValue: O(Q).search,
									"onUpdate:modelValue": o[1] ||= (e) => O(Q).search = e,
									name: "search",
									class: "w-64",
									"data-testid": "roster-users-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							S(u, {
								label: O(V)("roster.users.fields.status"),
								name: "status"
							}, {
								default: A(() => [S(d, {
									modelValue: O($),
									"onUpdate:modelValue": o[2] ||= (e) => w($) ? $.value = e : null,
									items: O(me),
									class: "w-48",
									"data-testid": "roster-users-status"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label"]),
							S(s, {
								type: "submit",
								icon: "i-lucide-filter",
								"data-testid": "roster-users-filter"
							}, {
								default: A(() => [x(D(O(V)("roster.users.filters.filter")), 1)]),
								_: 1
							}),
							S(s, {
								variant: "ghost",
								icon: "i-lucide-x",
								"data-testid": "roster-users-clear",
								onClick: ye
							}, {
								default: A(() => [x(D(O(V)("roster.users.filters.clear")), 1)]),
								_: 1
							})
						]),
						_: 1
					}, 8, ["state"]),
					S(n, {
						ready: !O(de) && O(J) !== null,
						failed: O(fe),
						status: O(pe)
					}, {
						default: A(() => [b("div", I, [
							S(e, { action: O(W) }, null, 8, ["action"]),
							O(X).length === 0 ? (T(), v(C, {
								key: 0,
								icon: "i-lucide-users",
								title: O(V)("roster.users.empty"),
								"data-testid": "roster-users-empty"
							}, null, 8, ["title"])) : (T(), v(j, {
								key: 1,
								data: O(X),
								columns: O(ge),
								class: "text-sm",
								"data-testid": "roster-users-table"
							}, {
								"name-cell": A(({ row: { original: e } }) => [b("span", { "data-testid": `roster-user-row-${e.id}` }, [O(G) ? (T(), y("span", R, D(O(a)(e) ?? O(V)("roster.users.none")), 1)) : (T(), v(E, {
									key: 0,
									to: O(te)(e.id),
									class: "font-medium text-default hover:underline"
								}, {
									default: A(() => [x(D(O(a)(e) ?? O(V)("roster.users.none")), 1)]),
									_: 2
								}, 1032, ["to"]))], 8, L)]),
								"email-cell": A(({ row: { original: e } }) => [x(D(e.email ?? O(V)("roster.users.none")), 1)]),
								"status-cell": A(({ row: { original: e } }) => [O(G) ? (T(), v(k, {
									key: 0,
									color: "neutral",
									variant: "subtle"
								}, {
									default: A(() => [x(D(O(V)("roster.users.deleted")), 1)]),
									_: 1
								})) : (T(), v(r, {
									key: 1,
									status: e.status
								}, null, 8, ["status"]))]),
								"created-cell": A(({ row: { original: e } }) => [x(D(O(ne)(e.created_at) ?? O(V)("roster.users.none")), 1)]),
								"actions-cell": A(({ row: { original: e } }) => [b("div", z, [O(G) ? (T(), y(le, { key: 0 }, [S(s, {
									size: "sm",
									icon: "i-lucide-undo-2",
									loading: O(W).busy.value,
									"data-testid": `roster-user-restore-${e.id}`,
									onClick: (t) => xe(e)
								}, {
									default: A(() => [x(D(O(V)("roster.users.restore")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								]), S(s, {
									size: "sm",
									color: "error",
									variant: "outline",
									icon: "i-lucide-trash-2",
									loading: O(W).busy.value,
									"data-testid": `roster-user-purge-${e.id}`,
									onClick: (t) => Se(e)
								}, {
									default: A(() => [x(D(O(V)("roster.users.purge")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])], 64)) : e.status === "pending" ? (T(), v(s, {
									key: 1,
									size: "sm",
									icon: "i-lucide-check",
									loading: O(W).busy.value,
									"data-testid": `roster-user-approve-${e.id}`,
									onClick: (t) => be(e)
								}, {
									default: A(() => [x(D(O(V)("roster.users.approve")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])) : ue("", !0)])]),
								_: 1
							}, 8, ["data", "columns"])),
							S(t, {
								current: O(Z).current,
								last: O(Z).last,
								testid: "roster-users"
							}, null, 8, ["current", "last"])
						])]),
						_: 1
					}, 8, [
						"ready",
						"failed",
						"status"
					])
				])]),
				_: 1
			})]);
		};
	}
});
export { V as default };
