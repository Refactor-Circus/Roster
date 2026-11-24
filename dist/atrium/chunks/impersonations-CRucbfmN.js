import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterPager-CFRR10Qn.js";
import { t as n } from "./RosterState-4BrqaETn.js";
import { g as r, h as i, i as a, l as o, m as s, r as c, s as l } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as u } from "@refactor-circus/atrium";
import { UBadge as d, UButton as f, UEmpty as p, UPageHeader as m, UTable as h } from "@refactor-circus/atrium/ui";
import { computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, openBlock as w, toDisplayString as T, unref as E, withCtx as D } from "vue";
import { useI18n as O } from "vue-i18n";
import { useRoute as k } from "vue-router";
// src/pages/impersonations/index.vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "roster-impersonations"
}, j = { class: "space-y-4" }, M = ["data-testid"], N = { key: 1 }, P = { key: 2 }, F = { class: "flex justify-end" }, I = /* @__PURE__ */ C({
	__name: "index",
	setup(C) {
		let { t: I } = O(), L = k(), R = s(), z = g(() => {
			let e = l(L.query.page);
			return e > 1 ? { page: e } : {};
		}), { client: B, data: V, pending: H, failed: U, status: W, reload: G } = i(async (e) => o(await e.GET("/impersonations", { params: { query: z.value } })), z), K = g(() => V.value?.data ?? []), q = g(() => a(V.value)), J = g(() => [
			{
				id: "impersonator",
				header: I("roster.impersonations.impersonator")
			},
			{
				id: "user",
				header: I("roster.impersonations.user")
			},
			{
				accessorKey: "reason",
				header: I("roster.users.fields.reason")
			},
			{
				id: "status",
				header: I("roster.users.fields.status")
			},
			{
				id: "actions",
				header: I("roster.users.fields.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		async function Y(e) {
			B && await R.run(async () => o(await B.DELETE("/impersonations/{impersonation}", { params: { path: { impersonation: e.id } } }))) && await G();
		}
		return (i, a) => {
			let o = m, s = p, l = d, g = f, C = h, O = u;
			return w(), y("div", A, [S(o, { title: E(I)("roster.impersonations.title") }, null, 8, ["title"]), S(O, { "screen-key": "roster.impersonations" }, {
				default: D(() => [S(n, {
					ready: !E(H) && E(V) !== null,
					failed: E(U),
					status: E(W)
				}, {
					default: D(() => [b("div", j, [
						S(e, { action: E(R) }, null, 8, ["action"]),
						E(K).length === 0 ? (w(), _(s, {
							key: 0,
							icon: "i-lucide-venetian-mask",
							title: E(I)("roster.impersonations.empty"),
							"data-testid": "roster-impersonations-empty"
						}, null, 8, ["title"])) : (w(), _(C, {
							key: 1,
							data: E(K),
							columns: E(J),
							class: "text-sm",
							"data-testid": "roster-impersonations-table"
						}, {
							"impersonator-cell": D(({ row: { original: e } }) => [b("span", { "data-testid": `roster-impersonation-${e.id}` }, T(E(r)(e.impersonator) ?? E(I)("roster.users.none")), 9, M)]),
							"user-cell": D(({ row: { original: e } }) => [x(T(E(r)(e.user) ?? E(I)("roster.users.none")), 1)]),
							"status-cell": D(({ row: { original: e } }) => [e.active ? (w(), _(l, {
								key: 0,
								color: "warning",
								variant: "subtle",
								"data-testid": "roster-impersonation-active"
							}, {
								default: D(() => [x(T(E(I)("roster.impersonations.active")), 1)]),
								_: 1
							})) : e.ended_at ? (w(), y("span", N, T(e.end_reason ?? E(I)("roster.impersonations.ended")) + " · " + T(E(c)(e.ended_at)), 1)) : (w(), y("span", P, T(E(I)("roster.impersonations.link_issued")), 1))]),
							"actions-cell": D(({ row: { original: e } }) => [b("div", F, [e.ended_at ? v("", !0) : (w(), _(g, {
								key: 0,
								size: "sm",
								color: "error",
								icon: "i-lucide-square",
								loading: E(R).busy.value,
								"data-testid": "roster-impersonation-stop",
								onClick: (t) => Y(e)
							}, {
								default: D(() => [x(T(E(I)("roster.impersonations.stop")), 1)]),
								_: 1
							}, 8, ["loading", "onClick"]))])]),
							_: 1
						}, 8, ["data", "columns"])),
						S(t, {
							current: E(q).current,
							last: E(q).last,
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
export { I as default };
