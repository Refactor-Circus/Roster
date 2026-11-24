import { a as e, i as t, l as n, o as r, s as i, t as ee, u as te } from "./TransferStatusBadge-C80F4lj4.js";
import { t as ne } from "./RosterActionError-DqFXfLSN.js";
import { h as a, l as o, m as s, r as c, s as l } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as re } from "@refactor-circus/atrium";
import { UAlert as ie, UBadge as ae, UButton as oe, UCard as se, UProgress as ce } from "@refactor-circus/atrium/ui";
import { Fragment as u, computed as d, createBlock as f, createCommentVNode as p, createElementBlock as m, createElementVNode as h, createTextVNode as g, createVNode as _, defineComponent as v, openBlock as y, renderList as b, toDisplayString as x, unref as S, withCtx as C } from "vue";
import { useI18n as le } from "vue-i18n";
import { useRoute as ue, useRouter as de } from "vue-router";
// src/pages/transfers/[transfer].vue?vue&type=script&setup=true&lang.ts
var fe = {
	class: "space-y-4",
	"data-testid": "roster-transfer"
}, pe = { class: "flex flex-wrap items-center justify-between gap-3" }, me = { class: "flex items-center gap-3" }, he = { key: 0 }, ge = {
	class: "text-xl font-semibold",
	"data-testid": "roster-transfer-title"
}, w = {
	key: 0,
	class: "text-sm text-muted"
}, T = {
	key: 0,
	class: "space-y-4"
}, E = { class: "grid gap-3 text-sm sm:grid-cols-2" }, D = { class: "text-muted" }, O = { class: "text-muted" }, k = { class: "text-muted" }, A = {
	key: 0,
	"data-testid": "roster-transfer-confirm-by"
}, j = { class: "text-muted" }, M = { key: 1 }, N = { class: "text-muted" }, P = {
	key: 1,
	class: "mt-4 space-y-1",
	"data-testid": "roster-transfer-progress"
}, F = { class: "text-sm text-muted" }, I = {
	key: 2,
	class: "mt-4 flex flex-wrap gap-2",
	"data-testid": "roster-transfer-summary"
}, L = {
	key: 3,
	class: "mt-2 flex flex-wrap gap-2",
	"data-testid": "roster-transfer-results"
}, _e = {
	key: 4,
	class: "mt-4 flex gap-2"
}, ve = {
	class: "w-full text-left text-sm",
	"data-testid": "roster-transfer-rows"
}, ye = { class: "text-muted" }, be = { class: "py-2 pr-3 font-medium" }, xe = { class: "py-2 pr-3 font-medium" }, Se = { class: "py-2 pr-3 font-medium" }, Ce = { class: "py-2 font-medium" }, we = { class: "divide-y divide-default" }, Te = ["data-testid"], Ee = { class: "py-2 pr-3" }, De = { class: "py-2 pr-3 text-xs" }, R = { class: "py-2 pr-3" }, Oe = { class: "ml-1 text-xs text-muted" }, ke = { class: "py-2" }, Ae = { class: "ml-1 text-xs text-muted" }, je = {
	key: 0,
	class: "flex items-center gap-3 text-sm"
}, Me = { class: "text-muted" }, z = /* @__PURE__ */ v({
	__name: "[transfer]",
	setup(v) {
		let { t: z, te: Ne } = le(), B = ue(), Pe = de(), V = s(), H = d(() => String(B.params.transfer)), U = d(() => l(B.query.rows_page)), Fe = d(() => ({
			id: H.value,
			rowsPage: U.value
		})), { client: W, data: G, pending: Ie, failed: Le, status: Re, reload: K } = a(async (e) => o(await e.GET("/transfers/{transfer}", { params: {
			path: { transfer: H.value },
			query: U.value > 1 ? { rows_page: U.value } : {}
		} })).data, Fe), q = d(() => G.value?.rows ?? []), J = d(() => ({
			current: G.value?.rows_meta?.current_page ?? 1,
			last: G.value?.rows_meta?.last_page ?? 1
		})), Y = d(() => Object.entries(G.value?.summary ?? {})), X = d(() => Object.entries(G.value?.results ?? {})), Z = d(() => Object.values(V.errors.value).flat());
		function Q(e) {
			let t = `roster.transfers.outcomes.${e}`;
			return Ne(t) ? z(t) : e;
		}
		function $(e) {
			Pe.replace({ query: {
				...B.query,
				rows_page: e > 1 ? String(e) : void 0
			} });
		}
		async function ze() {
			W && await V.run(async () => o(await W.POST("/imports/{transfer}/confirm", { params: { path: { transfer: H.value } } }))) && await K();
		}
		async function Be() {
			W && await V.run(async () => o(await W.DELETE("/transfers/{transfer}", { params: { path: { transfer: H.value } } }))) && await K();
		}
		async function Ve() {
			let e = G.value;
			W && e && await V.run(async () => {
				let n = await W.GET("/transfers/{transfer}/download", {
					params: { path: { transfer: e.id } },
					parseAs: "blob"
				}), r = o(n);
				i(r, t(n.response.headers.get("Content-Disposition"), `roster-${e.type}-${e.id}.csv`));
			});
		}
		return (t, i) => {
			let a = oe, o = ie, s = ce, l = ae, d = se, v = re;
			return y(), m("div", fe, [h("div", pe, [h("div", me, [_(a, {
				to: S(n)(S(G)?.organization),
				icon: "i-lucide-arrow-left",
				color: "neutral",
				variant: "ghost",
				"aria-label": S(z)("roster.transfers.title"),
				"data-testid": "roster-transfer-back"
			}, null, 8, ["to", "aria-label"]), S(G) ? (y(), m("div", he, [h("h1", ge, x(S(z)(`roster.transfers.types.${S(G).type}`)), 1), S(G).organization ? (y(), m("p", w, x(S(G).organization), 1)) : p("", !0)])) : p("", !0)]), S(G)?.download_url ? (y(), f(a, {
				key: 0,
				icon: "i-lucide-download",
				loading: S(V).busy.value,
				"data-testid": "roster-transfer-download",
				onClick: Ve
			}, {
				default: C(() => [g(x(S(z)("roster.transfers.download")), 1)]),
				_: 1
			}, 8, ["loading"])) : p("", !0)]), _(v, {
				"screen-key": "roster.transfer",
				record: S(H)
			}, {
				default: C(() => [_(te, {
					ready: !S(Ie) && S(G) !== null,
					failed: S(Le),
					status: S(Re),
					"not-found": S(z)("roster.transfers.not_found")
				}, {
					default: C(() => [S(G) ? (y(), m("div", T, [
						_(ne, { action: S(V) }, null, 8, ["action"]),
						S(Z).length > 0 ? (y(), f(o, {
							key: 0,
							color: "error",
							variant: "subtle",
							title: S(Z).join(" "),
							"data-testid": "roster-transfer-action-errors"
						}, null, 8, ["title"])) : p("", !0),
						_(d, null, {
							default: C(() => [
								h("dl", E, [
									h("div", null, [h("dt", D, x(S(z)("roster.transfers.fields.status")), 1), h("dd", null, [_(ee, {
										status: S(G).status,
										testid: "roster-transfer-status"
									}, null, 8, ["status"])])]),
									h("div", null, [h("dt", O, x(S(z)("roster.transfers.fields.requested_by")), 1), h("dd", null, x(S(G).requested_by?.name || S(G).requested_by?.email || S(z)("roster.transfers.none")), 1)]),
									h("div", null, [h("dt", k, x(S(z)("roster.transfers.fields.rows")), 1), h("dd", null, x(S(G).row_count ?? 0), 1)]),
									S(G).expires_at && S(G).status === "awaiting_confirmation" ? (y(), m("div", A, [h("dt", j, x(S(z)("roster.transfers.confirm_by")), 1), h("dd", null, x(S(c)(S(G).expires_at)), 1)])) : p("", !0),
									S(G).finished_at ? (y(), m("div", M, [h("dt", N, x(S(z)("roster.transfers.finished")), 1), h("dd", null, x(S(c)(S(G).finished_at)), 1)])) : p("", !0)
								]),
								S(G).error ? (y(), f(o, {
									key: 0,
									class: "mt-4",
									color: "error",
									variant: "subtle",
									title: S(G).error,
									"data-testid": "roster-transfer-error"
								}, null, 8, ["title"])) : p("", !0),
								S(G).progress && S(G).status === "running" ? (y(), m("div", P, [_(s, {
									"model-value": S(G).progress.done,
									max: Math.max(1, S(G).progress.total)
								}, null, 8, ["model-value", "max"]), h("p", F, x(S(z)("roster.transfers.rows_applied", {
									done: S(G).progress.done,
									total: S(G).progress.total
								})), 1)])) : p("", !0),
								S(Y).length > 0 ? (y(), m("div", I, [(y(!0), m(u, null, b(S(Y), ([e, t]) => (y(), f(l, {
									key: e,
									color: "neutral",
									variant: "subtle"
								}, {
									default: C(() => [g(x(Q(e)) + ": " + x(t), 1)]),
									_: 2
								}, 1024))), 128))])) : p("", !0),
								S(X).length > 0 ? (y(), m("div", L, [(y(!0), m(u, null, b(S(X), ([e, t]) => (y(), f(l, {
									key: e,
									color: "primary",
									variant: "subtle"
								}, {
									default: C(() => [g(x(Q(e)) + ": " + x(t), 1)]),
									_: 2
								}, 1024))), 128))])) : p("", !0),
								S(e)(S(G).status) ? p("", !0) : (y(), m("div", _e, [S(G).status === "awaiting_confirmation" ? (y(), f(a, {
									key: 0,
									icon: "i-lucide-check",
									loading: S(V).busy.value,
									"data-testid": "roster-transfer-confirm",
									onClick: ze
								}, {
									default: C(() => [g(x(S(z)("roster.transfers.confirm")), 1)]),
									_: 1
								}, 8, ["loading"])) : p("", !0), _(a, {
									color: "error",
									variant: "outline",
									icon: "i-lucide-x",
									loading: S(V).busy.value,
									"data-testid": "roster-transfer-cancel",
									onClick: Be
								}, {
									default: C(() => [g(x(S(z)("roster.transfers.cancel")), 1)]),
									_: 1
								}, 8, ["loading"])]))
							]),
							_: 1
						}),
						S(q).length > 0 ? (y(), m(u, { key: 1 }, [h("table", ve, [h("thead", ye, [h("tr", null, [
							h("th", be, x(S(z)("roster.transfers.fields.line")), 1),
							h("th", xe, x(S(z)("roster.transfers.fields.row")), 1),
							h("th", Se, x(S(z)("roster.transfers.fields.planned")), 1),
							h("th", Ce, x(S(z)("roster.transfers.fields.result")), 1)
						])]), h("tbody", we, [(y(!0), m(u, null, b(S(q), (e) => (y(), m("tr", {
							key: e.line,
							"data-testid": `roster-transfer-line-${e.line}`
						}, [
							h("td", Ee, x(e.line), 1),
							h("td", De, x(S(r)(e)), 1),
							h("td", R, [_(l, {
								color: "neutral",
								variant: "subtle"
							}, {
								default: C(() => [g(x(Q(e.action ?? "skip")), 1)]),
								_: 2
							}, 1024), h("span", Oe, x((e.reasons ?? []).join(" ")), 1)]),
							h("td", ke, [e.result ? (y(), m(u, { key: 0 }, [_(l, {
								color: "neutral",
								variant: "subtle"
							}, {
								default: C(() => [g(x(Q(e.result)), 1)]),
								_: 2
							}, 1024), h("span", Ae, x((e.result_reasons ?? []).join(" ")), 1)], 64)) : p("", !0)])
						], 8, Te))), 128))])]), S(J).last > 1 ? (y(), m("div", je, [
							_(a, {
								variant: "outline",
								size: "sm",
								icon: "i-lucide-chevron-left",
								disabled: S(J).current <= 1,
								"data-testid": "roster-transfer-rows-prev",
								onClick: i[0] ||= (e) => $(S(J).current - 1)
							}, {
								default: C(() => [g(x(S(z)("roster.users.pager.previous")), 1)]),
								_: 1
							}, 8, ["disabled"]),
							h("span", Me, x(S(z)("roster.users.pager.page", {
								current: S(J).current,
								last: S(J).last
							})), 1),
							_(a, {
								variant: "outline",
								size: "sm",
								"trailing-icon": "i-lucide-chevron-right",
								disabled: S(J).current >= S(J).last,
								"data-testid": "roster-transfer-rows-next",
								onClick: i[1] ||= (e) => $(S(J).current + 1)
							}, {
								default: C(() => [g(x(S(z)("roster.users.pager.next")), 1)]),
								_: 1
							}, 8, ["disabled"])
						])) : p("", !0)], 64)) : p("", !0)
					])) : p("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status",
					"not-found"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { z as default };
