import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterPager-CFRR10Qn.js";
import { t as n } from "./RosterState-4BrqaETn.js";
import { t as ee } from "./RosterStatusBadge-CjGfIXnt.js";
import { t as r } from "./RosterTabs-C9EsVdM2.js";
import { _ as te, c as i, g as a, h as o, i as s, l as c, m as l, r as u, s as d, t as f } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as p, AtriumPageHeader as ne, AtriumSelect as re } from "@refactor-circus/atrium";
import { UBadge as m, UButton as h, UEmpty as g, UForm as _, UFormField as v, UInput as y, ULink as ie, UTable as ae } from "@refactor-circus/atrium/ui";
import { Fragment as oe, computed as b, createBlock as x, createCommentVNode as se, createElementBlock as S, createElementVNode as C, createTextVNode as w, createVNode as T, defineComponent as E, openBlock as D, reactive as O, toDisplayString as k, unref as A, watch as j, withCtx as M } from "vue";
import { useI18n as N } from "vue-i18n";
import { useRoute as P, useRouter as F } from "vue-router";
// src/pages/users/index.vue?vue&type=script&setup=true&lang.ts
var I = {
	class: "space-y-4",
	"data-testid": "roster-users"
}, L = { class: "space-y-4" }, R = { class: "space-y-4" }, z = ["data-testid"], B = {
	key: 1,
	class: "font-medium"
}, V = { class: "flex justify-end gap-2" }, H = /* @__PURE__ */ E({
	__name: "index",
	setup(E) {
		let { t: H } = N(), U = P(), W = F(), G = l(), K = b(() => i(U.query.trashed) === "only"), q = b(() => {
			let e = i(U.query.status), t = i(U.query.search), n = d(U.query.page);
			return {
				...e ? { status: e } : {},
				...t ? { search: t } : {},
				...K.value ? { trashed: "only" } : {},
				...n > 1 ? { page: n } : {}
			};
		}), { client: J, data: Y, pending: ce, failed: le, status: ue, reload: X } = o(async (e) => c(await e.GET("/users", { params: { query: q.value } })), q), Z = b(() => Y.value?.data ?? []), Q = b(() => s(Y.value)), $ = O({
			status: "",
			search: ""
		}), de = b(() => f.map((e) => ({
			label: H(`roster.users.statuses.${e}`),
			value: e
		}))), fe = b(() => [{
			key: "active",
			label: H("roster.users.tabs.active")
		}, {
			key: "deleted",
			label: H("roster.users.tabs.deleted")
		}]), pe = b(() => [
			{
				id: "name",
				header: H("roster.users.fields.name")
			},
			{
				id: "email",
				header: H("roster.users.fields.email")
			},
			{
				id: "status",
				header: H("roster.users.fields.status")
			},
			{
				id: "created",
				header: H("roster.users.fields.created")
			},
			{
				id: "actions",
				header: H("roster.users.fields.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		j(() => [U.query.status, U.query.search], () => {
			$.status = i(U.query.status) ?? "", $.search = i(U.query.search) ?? "";
		}, { immediate: !0 });
		function me(e) {
			W.replace({ query: {
				...U.query,
				trashed: e ? "only" : void 0,
				page: void 0
			} });
		}
		function he() {
			W.replace({ query: {
				...U.query,
				status: $.status || void 0,
				search: $.search || void 0,
				page: void 0
			} });
		}
		function ge() {
			W.replace({ query: {
				...U.query,
				status: void 0,
				search: void 0,
				page: void 0
			} });
		}
		async function _e(e) {
			J && await G.run(async () => c(await J.POST("/users/{user}/approve", { params: { path: { user: String(e.id) } } }))) && await X();
		}
		async function ve(e) {
			J && await G.run(async () => c(await J.POST("/users/{user}/restore", { params: { path: { user: String(e.id) } } }))) && await X();
		}
		async function ye(e) {
			J && window.confirm(H("roster.users.danger.confirm_purge")) && await G.run(async () => c(await J.DELETE("/users/{user}/purge", { params: { path: { user: String(e.id) } } }))) && await X();
		}
		return (i, o) => {
			let s = h, c = ne, l = y, d = v, f = re, b = _, E = g, O = ie, j = m, N = ae, P = p;
			return D(), S("div", I, [T(c, { title: A(H)("roster.users.title") }, {
				default: M(() => [T(s, {
					to: "/roster/transfers",
					variant: "outline",
					icon: "i-lucide-arrow-up-down",
					"data-testid": "roster-users-transfers"
				}, {
					default: M(() => [w(k(A(H)("roster.users.import_export")), 1)]),
					_: 1
				}), T(s, {
					to: "/roster/users/create",
					icon: "i-lucide-plus",
					"data-testid": "roster-users-new"
				}, {
					default: M(() => [w(k(A(H)("roster.users.new")), 1)]),
					_: 1
				})]),
				_: 1
			}, 8, ["title"]), T(P, { "screen-key": "roster.users" }, {
				default: M(() => [C("div", L, [
					T(r, {
						tabs: A(fe),
						current: A(K) ? "deleted" : "active",
						label: A(H)("roster.users.title"),
						testid: "roster-users-tab",
						onSelect: o[0] ||= (e) => me(e === "deleted")
					}, null, 8, [
						"tabs",
						"current",
						"label"
					]),
					T(b, {
						state: A($),
						class: "flex flex-wrap items-end gap-3",
						"data-testid": "roster-users-filters",
						onSubmit: he
					}, {
						default: M(() => [
							T(d, {
								label: A(H)("roster.users.filters.search"),
								name: "search"
							}, {
								default: M(() => [T(l, {
									modelValue: A($).search,
									"onUpdate:modelValue": o[1] ||= (e) => A($).search = e,
									name: "search",
									class: "w-64",
									"data-testid": "roster-users-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							T(d, {
								label: A(H)("roster.users.fields.status"),
								name: "status"
							}, {
								default: M(() => [T(f, {
									modelValue: A($).status,
									"onUpdate:modelValue": o[2] ||= (e) => A($).status = e,
									items: A(de),
									blank: A(H)("roster.users.filters.all_statuses"),
									class: "w-48",
									"data-testid": "roster-users-status"
								}, null, 8, [
									"modelValue",
									"items",
									"blank"
								])]),
								_: 1
							}, 8, ["label"]),
							T(s, {
								type: "submit",
								icon: "i-lucide-filter",
								"data-testid": "roster-users-filter"
							}, {
								default: M(() => [w(k(A(H)("roster.users.filters.filter")), 1)]),
								_: 1
							}),
							T(s, {
								variant: "ghost",
								icon: "i-lucide-x",
								"data-testid": "roster-users-clear",
								onClick: ge
							}, {
								default: M(() => [w(k(A(H)("roster.users.filters.clear")), 1)]),
								_: 1
							})
						]),
						_: 1
					}, 8, ["state"]),
					T(n, {
						ready: !A(ce) && A(Y) !== null,
						failed: A(le),
						status: A(ue)
					}, {
						default: M(() => [C("div", R, [
							T(e, { action: A(G) }, null, 8, ["action"]),
							A(Z).length === 0 ? (D(), x(E, {
								key: 0,
								icon: "i-lucide-users",
								title: A(H)("roster.users.empty"),
								"data-testid": "roster-users-empty"
							}, null, 8, ["title"])) : (D(), x(N, {
								key: 1,
								data: A(Z),
								columns: A(pe),
								"data-testid": "roster-users-table"
							}, {
								"name-cell": M(({ row: { original: e } }) => [C("span", { "data-testid": `roster-user-row-${e.id}` }, [A(K) ? (D(), S("span", B, k(A(a)(e) ?? A(H)("roster.users.none")), 1)) : (D(), x(O, {
									key: 0,
									to: A(te)(e.id),
									class: "font-medium text-default hover:underline"
								}, {
									default: M(() => [w(k(A(a)(e) ?? A(H)("roster.users.none")), 1)]),
									_: 2
								}, 1032, ["to"]))], 8, z)]),
								"email-cell": M(({ row: { original: e } }) => [w(k(e.email ?? A(H)("roster.users.none")), 1)]),
								"status-cell": M(({ row: { original: e } }) => [A(K) ? (D(), x(j, {
									key: 0,
									color: "neutral",
									variant: "subtle"
								}, {
									default: M(() => [w(k(A(H)("roster.users.deleted")), 1)]),
									_: 1
								})) : (D(), x(ee, {
									key: 1,
									status: e.status
								}, null, 8, ["status"]))]),
								"created-cell": M(({ row: { original: e } }) => [w(k(A(u)(e.created_at) ?? A(H)("roster.users.none")), 1)]),
								"actions-cell": M(({ row: { original: e } }) => [C("div", V, [A(K) ? (D(), S(oe, { key: 0 }, [T(s, {
									size: "sm",
									icon: "i-lucide-undo-2",
									loading: A(G).busy.value,
									"data-testid": `roster-user-restore-${e.id}`,
									onClick: (t) => ve(e)
								}, {
									default: M(() => [w(k(A(H)("roster.users.restore")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								]), T(s, {
									size: "sm",
									color: "error",
									variant: "outline",
									icon: "i-lucide-trash-2",
									loading: A(G).busy.value,
									"data-testid": `roster-user-purge-${e.id}`,
									onClick: (t) => ye(e)
								}, {
									default: M(() => [w(k(A(H)("roster.users.purge")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])], 64)) : e.status === "pending" ? (D(), x(s, {
									key: 1,
									size: "sm",
									icon: "i-lucide-check",
									loading: A(G).busy.value,
									"data-testid": `roster-user-approve-${e.id}`,
									onClick: (t) => _e(e)
								}, {
									default: M(() => [w(k(A(H)("roster.users.approve")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])) : se("", !0)])]),
								_: 1
							}, 8, ["data", "columns"])),
							T(t, {
								current: A(Q).current,
								last: A(Q).last,
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
export { H as default };
