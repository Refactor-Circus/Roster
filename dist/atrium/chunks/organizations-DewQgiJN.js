import { t as e } from "./OrganizationState-GrCgPjha.js";
import { o as t } from "./organizations-vLWlhIK9.js";
import { t as n } from "./RosterActionError-DqFXfLSN.js";
import { t as ee } from "./RosterPager-CFRR10Qn.js";
import { c as r, h as i, i as a, l as o, m as s, s as c } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as te } from "@refactor-circus/atrium";
import { UAlert as ne, UBadge as re, UButton as ie, UFormField as ae, UInput as oe } from "@refactor-circus/atrium/ui";
import { Fragment as l, computed as u, createBlock as d, createCommentVNode as f, createElementBlock as p, createElementVNode as m, createTextVNode as h, createVNode as g, defineComponent as _, normalizeClass as v, openBlock as y, reactive as b, ref as se, renderList as x, resolveComponent as ce, toDisplayString as S, unref as C, watch as le, withCtx as w, withModifiers as ue } from "vue";
import { useI18n as de } from "vue-i18n";
import { useRoute as fe, useRouter as pe } from "vue-router";
// src/pages/organizations/index.vue?vue&type=script&setup=true&lang.ts
var me = {
	class: "space-y-4",
	"data-testid": "roster-organizations"
}, he = { class: "flex flex-wrap items-center justify-between gap-3" }, ge = { class: "text-xl font-semibold" }, T = { class: "flex gap-2" }, E = { class: "space-y-4" }, D = ["aria-label"], O = ["aria-current"], k = ["aria-current"], A = { class: "space-y-4" }, j = {
	key: 1,
	class: "atrium-empty",
	"data-testid": "roster-organizations-empty"
}, M = {
	key: 2,
	class: "w-full text-left text-sm",
	"data-testid": "roster-organizations-table"
}, N = { class: "text-muted" }, P = { class: "py-2 pr-3 font-medium" }, F = { class: "py-2 pr-3 font-medium" }, I = { class: "py-2 pr-3 font-medium" }, L = { class: "py-2 pr-3 font-medium" }, _e = { class: "py-2 pr-3 font-medium" }, ve = { class: "py-2 text-right font-medium" }, ye = { class: "divide-y divide-default" }, be = ["data-testid"], xe = { class: "py-2 pr-3" }, R = {
	key: 1,
	class: "font-medium"
}, Se = { class: "py-2 pr-3" }, Ce = { class: "py-2 pr-3" }, we = { class: "py-2 pr-3" }, Te = { class: "py-2 pr-3 text-xs" }, Ee = { class: "py-2" }, De = {
	key: 0,
	class: "flex justify-end gap-2"
}, z = /* @__PURE__ */ _({
	__name: "index",
	setup(_) {
		let { t: z } = de(), B = fe(), V = pe(), H = s(), U = se(null), W = [
			"search",
			"source",
			"external_id",
			"account_number"
		], G = u(() => r(B.query.trashed) === "only"), K = u(() => {
			let e = c(B.query.page), t = {};
			for (let e of W) {
				let n = r(B.query[e]);
				n && (t[e] = n);
			}
			return {
				...t,
				...G.value ? { trashed: "only" } : {},
				...e > 1 ? { page: e } : {}
			};
		}), { client: q, data: J, pending: Oe, failed: ke, status: Ae, reload: Y } = i(async (e) => o(await e.GET("/organizations", { params: { query: K.value } })), K), X = u(() => J.value?.data ?? []), Z = u(() => a(J.value)), Q = b({
			search: "",
			source: "",
			external_id: "",
			account_number: ""
		});
		le(() => W.map((e) => B.query[e]), () => {
			for (let e of W) Q[e] = r(B.query[e]) ?? "";
		}, { immediate: !0 });
		function $(e) {
			U.value = null, V.replace({ query: {
				...B.query,
				trashed: e ? "only" : void 0,
				page: void 0
			} });
		}
		function je() {
			let e = Object.fromEntries(W.map((e) => [e, Q[e].trim() || void 0]));
			V.replace({ query: {
				...B.query,
				...e,
				page: void 0
			} });
		}
		function Me() {
			V.replace({ query: {
				...B.query,
				...Object.fromEntries(W.map((e) => [e, void 0])),
				page: void 0
			} });
		}
		async function Ne(e) {
			U.value = null, q && await H.run(async () => o(await q.POST("/organizations/{organization}/restore", { params: { path: { organization: e.slug } } }))) && await Y();
		}
		async function Pe(e) {
			if (!q || !window.confirm(z("roster.organizations.danger.confirm_purge"))) return;
			U.value = null;
			let t = null;
			await H.run(async () => {
				t = o(await q.DELETE("/organizations/{organization}/purge", { params: { path: { organization: e.slug } } })).message;
			}) && (U.value = t ?? z("roster.organizations.danger.purge_queued"), await Y());
		}
		return (r, i) => {
			let a = ie, o = oe, s = ae, c = ne, u = ce("RouterLink"), _ = re, b = te;
			return y(), p("div", me, [m("div", he, [m("h1", ge, S(C(z)("roster.organizations.title")), 1), m("div", T, [g(a, {
				to: "/roster/transfers",
				variant: "outline",
				icon: "i-lucide-arrow-up-down",
				"data-testid": "roster-organizations-transfers"
			}, {
				default: w(() => [h(S(C(z)("roster.organizations.import_export")), 1)]),
				_: 1
			}), g(a, {
				to: "/roster/organizations/create",
				icon: "i-lucide-plus",
				"data-testid": "roster-organizations-new"
			}, {
				default: w(() => [h(S(C(z)("roster.organizations.new")), 1)]),
				_: 1
			})])]), g(b, { "screen-key": "roster.organizations" }, {
				default: w(() => [m("div", E, [
					m("nav", {
						class: "flex gap-1 border-b border-default",
						"aria-label": C(z)("roster.organizations.title")
					}, [m("button", {
						type: "button",
						class: v(["-mb-px border-b-2 px-3 py-2 text-sm font-medium", C(G) ? "border-transparent text-muted" : "border-primary"]),
						"aria-current": C(G) ? void 0 : "page",
						"data-testid": "roster-organizations-tab-active",
						onClick: i[0] ||= (e) => $(!1)
					}, S(C(z)("roster.organizations.tabs.active")), 11, O), m("button", {
						type: "button",
						class: v(["-mb-px border-b-2 px-3 py-2 text-sm font-medium", C(G) ? "border-primary" : "border-transparent text-muted"]),
						"aria-current": C(G) ? "page" : void 0,
						"data-testid": "roster-organizations-tab-deleted",
						onClick: i[1] ||= (e) => $(!0)
					}, S(C(z)("roster.organizations.tabs.deleted")), 11, k)], 8, D),
					m("form", {
						class: "atrium-querybar",
						"data-testid": "roster-organizations-filters",
						onSubmit: ue(je, ["prevent"])
					}, [
						g(s, { label: C(z)("roster.organizations.filters.search") }, {
							default: w(() => [g(o, {
								modelValue: C(Q).search,
								"onUpdate:modelValue": i[2] ||= (e) => C(Q).search = e,
								name: "search",
								class: "w-64",
								"data-testid": "roster-organizations-search"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						g(s, { label: C(z)("roster.organizations.fields.source") }, {
							default: w(() => [g(o, {
								modelValue: C(Q).source,
								"onUpdate:modelValue": i[3] ||= (e) => C(Q).source = e,
								name: "source",
								class: "w-40",
								"data-testid": "roster-organizations-source"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						g(s, { label: C(z)("roster.organizations.fields.external_id") }, {
							default: w(() => [g(o, {
								modelValue: C(Q).external_id,
								"onUpdate:modelValue": i[4] ||= (e) => C(Q).external_id = e,
								name: "external_id",
								class: "w-48",
								"data-testid": "roster-organizations-external-id"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						g(s, { label: C(z)("roster.organizations.fields.account_number") }, {
							default: w(() => [g(o, {
								modelValue: C(Q).account_number,
								"onUpdate:modelValue": i[5] ||= (e) => C(Q).account_number = e,
								name: "account_number",
								class: "w-48",
								"data-testid": "roster-organizations-account-number"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						g(a, {
							type: "submit",
							icon: "i-lucide-filter",
							"data-testid": "roster-organizations-filter"
						}, {
							default: w(() => [h(S(C(z)("roster.organizations.filters.filter")), 1)]),
							_: 1
						}),
						g(a, {
							variant: "ghost",
							icon: "i-lucide-x",
							"data-testid": "roster-organizations-clear",
							onClick: Me
						}, {
							default: w(() => [h(S(C(z)("roster.organizations.filters.clear")), 1)]),
							_: 1
						})
					], 32),
					g(e, {
						ready: !C(Oe) && C(J) !== null,
						failed: C(ke),
						status: C(Ae)
					}, {
						default: w(() => [m("div", A, [
							g(n, { action: C(H) }, null, 8, ["action"]),
							C(U) ? (y(), d(c, {
								key: 0,
								color: "success",
								variant: "subtle",
								title: C(U),
								"data-testid": "roster-organizations-notice"
							}, null, 8, ["title"])) : f("", !0),
							C(X).length === 0 ? (y(), p("p", j, S(C(z)("roster.organizations.empty")), 1)) : (y(), p("table", M, [m("thead", N, [m("tr", null, [
								m("th", P, S(C(z)("roster.organizations.fields.name")), 1),
								m("th", F, S(C(z)("roster.organizations.fields.slug")), 1),
								m("th", I, S(C(z)("roster.organizations.fields.members")), 1),
								m("th", L, S(C(z)("roster.organizations.fields.teams")), 1),
								m("th", _e, S(C(z)("roster.organizations.fields.external")), 1),
								m("th", ve, S(C(z)("roster.organizations.fields.actions")), 1)
							])]), m("tbody", ye, [(y(!0), p(l, null, x(C(X), (e) => (y(), p("tr", {
								key: e.slug,
								"data-testid": `roster-organization-row-${e.slug}`
							}, [
								m("td", xe, [
									C(G) ? (y(), p("span", R, S(e.name), 1)) : (y(), d(u, {
										key: 0,
										to: C(t)(e.slug),
										class: "font-medium hover:underline"
									}, {
										default: w(() => [h(S(e.name), 1)]),
										_: 2
									}, 1032, ["to"])),
									e.personal ? (y(), d(_, {
										key: 2,
										color: "neutral",
										variant: "subtle",
										class: "ml-2"
									}, {
										default: w(() => [h(S(C(z)("roster.organizations.personal")), 1)]),
										_: 1
									})) : f("", !0),
									C(G) ? (y(), d(_, {
										key: 3,
										color: "neutral",
										variant: "subtle",
										class: "ml-2"
									}, {
										default: w(() => [h(S(C(z)("roster.organizations.deleted")), 1)]),
										_: 1
									})) : f("", !0)
								]),
								m("td", Se, S(e.slug), 1),
								m("td", Ce, S(e.members_count ?? C(z)("roster.organizations.none")), 1),
								m("td", we, S(e.teams_count ?? C(z)("roster.organizations.none")), 1),
								m("td", Te, [(y(!0), p(l, null, x(e.links, (e) => (y(), p("div", { key: e.source }, [
									m("code", null, S(e.source), 1),
									h(" " + S(e.external_id), 1),
									e.account_number ? (y(), p(l, { key: 0 }, [h(" · " + S(e.account_number), 1)], 64)) : f("", !0)
								]))), 128))]),
								m("td", Ee, [C(G) ? (y(), p("div", De, [g(a, {
									size: "sm",
									icon: "i-lucide-undo-2",
									loading: C(H).busy.value,
									"data-testid": `roster-organization-restore-${e.slug}`,
									onClick: (t) => Ne(e)
								}, {
									default: w(() => [h(S(C(z)("roster.organizations.restore")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								]), g(a, {
									size: "sm",
									color: "error",
									variant: "outline",
									icon: "i-lucide-trash-2",
									loading: C(H).busy.value,
									"data-testid": `roster-organization-purge-${e.slug}`,
									onClick: (t) => Pe(e)
								}, {
									default: w(() => [h(S(C(z)("roster.organizations.purge")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])])) : f("", !0)])
							], 8, be))), 128))])])),
							g(ee, {
								current: C(Z).current,
								last: C(Z).last,
								testid: "roster-organizations"
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
export { z as default };
