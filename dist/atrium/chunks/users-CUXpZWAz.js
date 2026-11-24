import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterPager-CFRR10Qn.js";
import { t as n } from "./RosterState-4BrqaETn.js";
import { t as ee } from "./RosterStatusBadge-CjGfIXnt.js";
import { _ as te, c as r, g as i, h as a, i as o, l as s, m as c, r as ne, s as l, t as re } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as ie } from "@refactor-circus/atrium";
import { UBadge as ae, UButton as oe, UFormField as se, UInput as ce } from "@refactor-circus/atrium/ui";
import { Fragment as u, computed as d, createBlock as f, createCommentVNode as le, createElementBlock as p, createElementVNode as m, createTextVNode as h, createVNode as g, defineComponent as _, normalizeClass as v, openBlock as y, reactive as ue, renderList as b, resolveComponent as de, toDisplayString as x, unref as S, vModelSelect as fe, watch as pe, withCtx as C, withDirectives as me, withModifiers as w } from "vue";
import { useI18n as T } from "vue-i18n";
import { useRoute as E, useRouter as D } from "vue-router";
// src/pages/users/index.vue?vue&type=script&setup=true&lang.ts
var O = {
	class: "space-y-4",
	"data-testid": "roster-users"
}, k = { class: "flex flex-wrap items-center justify-between gap-3" }, A = { class: "text-xl font-semibold" }, j = { class: "flex gap-2" }, M = { class: "space-y-4" }, N = ["aria-label"], P = ["aria-current"], F = ["aria-current"], I = { value: "" }, L = ["value"], R = { class: "space-y-4" }, z = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "roster-users-empty"
}, he = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "roster-users-table"
}, ge = { class: "text-muted" }, B = { class: "py-2 pr-3 font-medium" }, _e = { class: "py-2 pr-3 font-medium" }, ve = { class: "py-2 pr-3 font-medium" }, ye = { class: "py-2 pr-3 font-medium" }, be = { class: "py-2 text-right font-medium" }, xe = { class: "divide-y divide-default" }, Se = ["data-testid"], Ce = { class: "py-2 pr-3" }, we = {
	key: 1,
	class: "font-medium"
}, Te = { class: "py-2 pr-3" }, Ee = { class: "py-2 pr-3" }, De = { class: "py-2 pr-3" }, Oe = { class: "py-2" }, ke = { class: "flex justify-end gap-2" }, V = /* @__PURE__ */ _({
	__name: "index",
	setup(_) {
		let { t: V } = T(), H = E(), U = D(), W = c(), G = d(() => r(H.query.trashed) === "only"), K = d(() => {
			let e = r(H.query.status), t = r(H.query.search), n = l(H.query.page);
			return {
				...e ? { status: e } : {},
				...t ? { search: t } : {},
				...G.value ? { trashed: "only" } : {},
				...n > 1 ? { page: n } : {}
			};
		}), { client: q, data: J, pending: Ae, failed: je, status: Me, reload: Y } = a(async (e) => s(await e.GET("/users", { params: { query: K.value } })), K), X = d(() => J.value?.data ?? []), Z = d(() => o(J.value)), Q = ue({
			status: "",
			search: ""
		});
		pe(() => [H.query.status, H.query.search], () => {
			Q.status = r(H.query.status) ?? "", Q.search = r(H.query.search) ?? "";
		}, { immediate: !0 });
		function $(e) {
			U.replace({ query: {
				...H.query,
				trashed: e ? "only" : void 0,
				page: void 0
			} });
		}
		function Ne() {
			U.replace({ query: {
				...H.query,
				status: Q.status || void 0,
				search: Q.search || void 0,
				page: void 0
			} });
		}
		function Pe() {
			U.replace({ query: {
				...H.query,
				status: void 0,
				search: void 0,
				page: void 0
			} });
		}
		async function Fe(e) {
			q && await W.run(async () => s(await q.POST("/users/{user}/approve", { params: { path: { user: String(e.id) } } }))) && await Y();
		}
		async function Ie(e) {
			q && await W.run(async () => s(await q.POST("/users/{user}/restore", { params: { path: { user: String(e.id) } } }))) && await Y();
		}
		async function Le(e) {
			q && window.confirm(V("roster.users.danger.confirm_purge")) && await W.run(async () => s(await q.DELETE("/users/{user}/purge", { params: { path: { user: String(e.id) } } }))) && await Y();
		}
		return (r, a) => {
			let o = oe, s = ce, c = se, l = de("RouterLink"), d = ae, _ = ie;
			return y(), p("div", O, [m("div", k, [m("h1", A, x(S(V)("roster.users.title")), 1), m("div", j, [g(o, {
				to: "/roster/transfers",
				variant: "outline",
				icon: "i-lucide-arrow-up-down",
				"data-testid": "roster-users-transfers"
			}, {
				default: C(() => [h(x(S(V)("roster.users.import_export")), 1)]),
				_: 1
			}), g(o, {
				to: "/roster/users/create",
				icon: "i-lucide-plus",
				"data-testid": "roster-users-new"
			}, {
				default: C(() => [h(x(S(V)("roster.users.new")), 1)]),
				_: 1
			})])]), g(_, { "screen-key": "roster.users" }, {
				default: C(() => [m("div", M, [
					m("nav", {
						class: "flex gap-1 border-b border-default",
						"aria-label": S(V)("roster.users.title")
					}, [m("button", {
						type: "button",
						class: v(["-mb-px border-b-2 px-3 py-2 text-sm font-medium", S(G) ? "border-transparent text-muted" : "border-primary"]),
						"aria-current": S(G) ? void 0 : "page",
						"data-testid": "roster-users-tab-active",
						onClick: a[0] ||= (e) => $(!1)
					}, x(S(V)("roster.users.tabs.active")), 11, P), m("button", {
						type: "button",
						class: v(["-mb-px border-b-2 px-3 py-2 text-sm font-medium", S(G) ? "border-primary" : "border-transparent text-muted"]),
						"aria-current": S(G) ? "page" : void 0,
						"data-testid": "roster-users-tab-deleted",
						onClick: a[1] ||= (e) => $(!0)
					}, x(S(V)("roster.users.tabs.deleted")), 11, F)], 8, N),
					m("form", {
						class: "atrium-querybar",
						"data-testid": "roster-users-filters",
						onSubmit: w(Ne, ["prevent"])
					}, [
						g(c, { label: S(V)("roster.users.filters.search") }, {
							default: C(() => [g(s, {
								modelValue: S(Q).search,
								"onUpdate:modelValue": a[2] ||= (e) => S(Q).search = e,
								name: "search",
								class: "w-64",
								"data-testid": "roster-users-search"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						g(c, { label: S(V)("roster.users.fields.status") }, {
							default: C(() => [me(m("select", {
								"onUpdate:modelValue": a[3] ||= (e) => S(Q).status = e,
								class: "w-48 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "roster-users-status"
							}, [m("option", I, x(S(V)("roster.users.filters.all_statuses")), 1), (y(!0), p(u, null, b(S(re), (e) => (y(), p("option", {
								key: e,
								value: e
							}, x(S(V)(`roster.users.statuses.${e}`)), 9, L))), 128))], 512), [[fe, S(Q).status]])]),
							_: 1
						}, 8, ["label"]),
						g(o, {
							type: "submit",
							icon: "i-lucide-filter",
							"data-testid": "roster-users-filter"
						}, {
							default: C(() => [h(x(S(V)("roster.users.filters.filter")), 1)]),
							_: 1
						}),
						g(o, {
							variant: "ghost",
							icon: "i-lucide-x",
							"data-testid": "roster-users-clear",
							onClick: Pe
						}, {
							default: C(() => [h(x(S(V)("roster.users.filters.clear")), 1)]),
							_: 1
						})
					], 32),
					g(n, {
						ready: !S(Ae) && S(J) !== null,
						failed: S(je),
						status: S(Me)
					}, {
						default: C(() => [m("div", R, [
							g(e, { action: S(W) }, null, 8, ["action"]),
							S(X).length === 0 ? (y(), p("p", z, x(S(V)("roster.users.empty")), 1)) : (y(), p("table", he, [m("thead", ge, [m("tr", null, [
								m("th", B, x(S(V)("roster.users.fields.name")), 1),
								m("th", _e, x(S(V)("roster.users.fields.email")), 1),
								m("th", ve, x(S(V)("roster.users.fields.status")), 1),
								m("th", ye, x(S(V)("roster.users.fields.created")), 1),
								m("th", be, x(S(V)("roster.users.fields.actions")), 1)
							])]), m("tbody", xe, [(y(!0), p(u, null, b(S(X), (e) => (y(), p("tr", {
								key: String(e.id),
								"data-testid": `roster-user-row-${e.id}`
							}, [
								m("td", Ce, [S(G) ? (y(), p("span", we, x(S(i)(e) ?? S(V)("roster.users.none")), 1)) : (y(), f(l, {
									key: 0,
									to: S(te)(e.id),
									class: "font-medium hover:underline"
								}, {
									default: C(() => [h(x(S(i)(e) ?? S(V)("roster.users.none")), 1)]),
									_: 2
								}, 1032, ["to"]))]),
								m("td", Te, x(e.email ?? S(V)("roster.users.none")), 1),
								m("td", Ee, [S(G) ? (y(), f(d, {
									key: 0,
									color: "neutral",
									variant: "subtle"
								}, {
									default: C(() => [h(x(S(V)("roster.users.deleted")), 1)]),
									_: 1
								})) : (y(), f(ee, {
									key: 1,
									status: e.status
								}, null, 8, ["status"]))]),
								m("td", De, x(S(ne)(e.created_at) ?? S(V)("roster.users.none")), 1),
								m("td", Oe, [m("div", ke, [S(G) ? (y(), p(u, { key: 0 }, [g(o, {
									size: "sm",
									icon: "i-lucide-undo-2",
									loading: S(W).busy.value,
									"data-testid": `roster-user-restore-${e.id}`,
									onClick: (t) => Ie(e)
								}, {
									default: C(() => [h(x(S(V)("roster.users.restore")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								]), g(o, {
									size: "sm",
									color: "error",
									variant: "outline",
									icon: "i-lucide-trash-2",
									loading: S(W).busy.value,
									"data-testid": `roster-user-purge-${e.id}`,
									onClick: (t) => Le(e)
								}, {
									default: C(() => [h(x(S(V)("roster.users.purge")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])], 64)) : e.status === "pending" ? (y(), f(o, {
									key: 1,
									size: "sm",
									icon: "i-lucide-check",
									loading: S(W).busy.value,
									"data-testid": `roster-user-approve-${e.id}`,
									onClick: (t) => Fe(e)
								}, {
									default: C(() => [h(x(S(V)("roster.users.approve")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])) : le("", !0)])])
							], 8, Se))), 128))])])),
							g(t, {
								current: S(Z).current,
								last: S(Z).last,
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
