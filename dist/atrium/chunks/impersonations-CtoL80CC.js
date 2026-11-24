import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterPager-CFRR10Qn.js";
import { t as n } from "./RosterState-4BrqaETn.js";
import { g as r, h as i, i as a, l as o, m as s, r as c, s as l } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as u } from "@refactor-circus/atrium";
import { UBadge as d, UButton as ee } from "@refactor-circus/atrium/ui";
import { Fragment as f, computed as p, createBlock as m, createCommentVNode as h, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as te, openBlock as b, renderList as ne, toDisplayString as x, unref as S, withCtx as C } from "vue";
import { useI18n as w } from "vue-i18n";
import { useRoute as T } from "vue-router";
// src/pages/impersonations/index.vue?vue&type=script&setup=true&lang.ts
var E = {
	class: "space-y-4",
	"data-testid": "roster-impersonations"
}, D = { class: "text-xl font-semibold" }, O = { class: "space-y-4" }, k = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "roster-impersonations-empty"
}, A = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "roster-impersonations-table"
}, j = { class: "text-muted" }, M = { class: "py-2 pr-3 font-medium" }, N = { class: "py-2 pr-3 font-medium" }, P = { class: "py-2 pr-3 font-medium" }, F = { class: "py-2 pr-3 font-medium" }, I = { class: "py-2 text-right font-medium" }, L = { class: "divide-y divide-default" }, R = ["data-testid"], z = { class: "py-2 pr-3" }, re = { class: "py-2 pr-3" }, B = { class: "py-2 pr-3" }, V = { class: "py-2 pr-3" }, H = { key: 1 }, U = { key: 2 }, W = { class: "py-2" }, G = { class: "flex justify-end" }, K = /* @__PURE__ */ te({
	__name: "index",
	setup(te) {
		let { t: K } = w(), q = T(), J = s(), Y = p(() => {
			let e = l(q.query.page);
			return e > 1 ? { page: e } : {};
		}), { client: X, data: Z, pending: ie, failed: ae, status: oe, reload: se } = i(async (e) => o(await e.GET("/impersonations", { params: { query: Y.value } })), Y), Q = p(() => Z.value?.data ?? []), $ = p(() => a(Z.value));
		async function ce(e) {
			X && await J.run(async () => o(await X.DELETE("/impersonations/{impersonation}", { params: { path: { impersonation: e.id } } }))) && await se();
		}
		return (i, a) => {
			let o = d, s = ee, l = u;
			return b(), g("div", E, [_("h1", D, x(S(K)("roster.impersonations.title")), 1), y(l, { "screen-key": "roster.impersonations" }, {
				default: C(() => [y(n, {
					ready: !S(ie) && S(Z) !== null,
					failed: S(ae),
					status: S(oe)
				}, {
					default: C(() => [_("div", O, [
						y(e, { action: S(J) }, null, 8, ["action"]),
						S(Q).length === 0 ? (b(), g("p", k, x(S(K)("roster.impersonations.empty")), 1)) : (b(), g("table", A, [_("thead", j, [_("tr", null, [
							_("th", M, x(S(K)("roster.impersonations.impersonator")), 1),
							_("th", N, x(S(K)("roster.impersonations.user")), 1),
							_("th", P, x(S(K)("roster.users.fields.reason")), 1),
							_("th", F, x(S(K)("roster.users.fields.status")), 1),
							_("th", I, x(S(K)("roster.users.fields.actions")), 1)
						])]), _("tbody", L, [(b(!0), g(f, null, ne(S(Q), (e) => (b(), g("tr", {
							key: e.id,
							"data-testid": `roster-impersonation-${e.id}`
						}, [
							_("td", z, x(S(r)(e.impersonator) ?? S(K)("roster.users.none")), 1),
							_("td", re, x(S(r)(e.user) ?? S(K)("roster.users.none")), 1),
							_("td", B, x(e.reason), 1),
							_("td", V, [e.active ? (b(), m(o, {
								key: 0,
								color: "warning",
								variant: "subtle",
								"data-testid": "roster-impersonation-active"
							}, {
								default: C(() => [v(x(S(K)("roster.impersonations.active")), 1)]),
								_: 1
							})) : e.ended_at ? (b(), g("span", H, x(e.end_reason ?? S(K)("roster.impersonations.ended")) + " · " + x(S(c)(e.ended_at)), 1)) : (b(), g("span", U, x(S(K)("roster.impersonations.link_issued")), 1))]),
							_("td", W, [_("div", G, [e.ended_at ? h("", !0) : (b(), m(s, {
								key: 0,
								size: "sm",
								color: "error",
								icon: "i-lucide-square",
								loading: S(J).busy.value,
								"data-testid": "roster-impersonation-stop",
								onClick: (t) => ce(e)
							}, {
								default: C(() => [v(x(S(K)("roster.impersonations.stop")), 1)]),
								_: 1
							}, 8, ["loading", "onClick"]))])])
						], 8, R))), 128))])])),
						y(t, {
							current: S($).current,
							last: S($).last,
							testid: "roster-impersonations"
						}, null, 8, ["current", "last"])
					])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			})]);
		};
	}
});
export { K as default };
