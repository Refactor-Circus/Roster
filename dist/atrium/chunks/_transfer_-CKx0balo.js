import { a as e, i as t, l as n, o as r, s as i, t as ee, u as te } from "./TransferStatusBadge-C80F4lj4.js";
import { t as ne } from "./RosterActionError-DqFXfLSN.js";
import { h as a, l as o, m as s, r as c, s as l } from "./users-JqW-WBif.js";
import { AtriumDetails as re, AtriumIntegrationScreen as ie, AtriumPageHeader as ae } from "@refactor-circus/atrium";
import { UAlert as oe, UBadge as se, UButton as ce, UCard as u, UProgress as d, UTable as le } from "@refactor-circus/atrium/ui";
import { Fragment as f, computed as p, createBlock as m, createCommentVNode as h, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as b, openBlock as x, renderList as S, toDisplayString as C, unref as w, withCtx as T } from "vue";
import { useI18n as E } from "vue-i18n";
import { useRoute as D, useRouter as ue } from "vue-router";
// src/pages/transfers/[transfer].vue?vue&type=script&setup=true&lang.ts
var de = {
	class: "space-y-4",
	"data-testid": "roster-transfer"
}, fe = {
	key: 0,
	class: "truncate",
	"data-testid": "roster-transfer-title"
}, pe = {
	key: 0,
	class: "space-y-4"
}, me = {
	key: 1,
	class: "mt-4 space-y-1",
	"data-testid": "roster-transfer-progress"
}, O = { class: "text-sm text-muted" }, k = {
	key: 2,
	class: "mt-4 flex flex-wrap gap-2",
	"data-testid": "roster-transfer-summary"
}, A = {
	key: 3,
	class: "mt-2 flex flex-wrap gap-2",
	"data-testid": "roster-transfer-results"
}, j = {
	key: 4,
	class: "mt-4 flex gap-2"
}, M = ["data-testid"], N = { class: "text-xs" }, P = { class: "ml-1 text-xs text-muted" }, F = { class: "ml-1 text-xs text-muted" }, I = {
	key: 0,
	class: "flex items-center gap-3 text-sm"
}, L = { class: "text-muted" }, R = /* @__PURE__ */ b({
	__name: "[transfer]",
	setup(b) {
		let { t: R, te: z } = E(), B = D(), he = ue(), V = s(), H = p(() => String(B.params.transfer)), U = p(() => l(B.query.rows_page)), ge = p(() => ({
			id: H.value,
			rowsPage: U.value
		})), { client: W, data: G, pending: _e, failed: ve, status: ye, reload: K } = a(async (e) => o(await e.GET("/transfers/{transfer}", { params: {
			path: { transfer: H.value },
			query: U.value > 1 ? { rows_page: U.value } : {}
		} })).data, ge), q = p(() => G.value?.rows ?? []), be = p(() => [
			{
				id: "line",
				header: R("roster.transfers.fields.line"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			},
			{
				id: "row",
				header: R("roster.transfers.fields.row")
			},
			{
				id: "planned",
				header: R("roster.transfers.fields.planned")
			},
			{
				id: "result",
				header: R("roster.transfers.fields.result")
			}
		]), J = p(() => ({
			current: G.value?.rows_meta?.current_page ?? 1,
			last: G.value?.rows_meta?.last_page ?? 1
		})), Y = p(() => Object.entries(G.value?.summary ?? {})), X = p(() => Object.entries(G.value?.results ?? {})), Z = p(() => Object.values(V.errors.value).flat()), xe = p(() => {
			let e = G.value;
			return e ? [
				{
					key: "status",
					label: R("roster.transfers.fields.status")
				},
				{
					label: R("roster.transfers.fields.requested_by"),
					value: e.requested_by?.name || e.requested_by?.email || R("roster.transfers.none")
				},
				{
					label: R("roster.transfers.fields.rows"),
					value: e.row_count ?? 0
				},
				...e.expires_at && e.status === "awaiting_confirmation" ? [{
					label: R("roster.transfers.confirm_by"),
					value: c(e.expires_at),
					testid: "roster-transfer-confirm-by"
				}] : [],
				...e.finished_at ? [{
					label: R("roster.transfers.finished"),
					value: c(e.finished_at)
				}] : []
			] : [];
		});
		function Q(e) {
			let t = `roster.transfers.outcomes.${e}`;
			return z(t) ? R(t) : e;
		}
		function $(e) {
			he.replace({ query: {
				...B.query,
				rows_page: e > 1 ? String(e) : void 0
			} });
		}
		async function Se() {
			W && await V.run(async () => o(await W.POST("/imports/{transfer}/confirm", { params: { path: { transfer: H.value } } }))) && await K();
		}
		async function Ce() {
			W && await V.run(async () => o(await W.DELETE("/transfers/{transfer}", { params: { path: { transfer: H.value } } }))) && await K();
		}
		async function we() {
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
			let a = ce, o = ae, s = oe, c = re, l = d, p = se, b = u, E = le, D = ie;
			return x(), g("div", de, [y(o, {
				back: w(n)(w(G)?.organization),
				"back-label": w(R)("roster.transfers.title"),
				description: w(G)?.organization
			}, {
				title: T(() => [w(G) ? (x(), g("span", fe, C(w(R)(`roster.transfers.types.${w(G).type}`)), 1)) : h("", !0)]),
				default: T(() => [w(G)?.download_url ? (x(), m(a, {
					key: 0,
					icon: "i-lucide-download",
					loading: w(V).busy.value,
					"data-testid": "roster-transfer-download",
					onClick: we
				}, {
					default: T(() => [v(C(w(R)("roster.transfers.download")), 1)]),
					_: 1
				}, 8, ["loading"])) : h("", !0)]),
				_: 1
			}, 8, [
				"back",
				"back-label",
				"description"
			]), y(D, {
				"screen-key": "roster.transfer",
				record: w(H)
			}, {
				default: T(() => [y(te, {
					ready: !w(_e) && w(G) !== null,
					failed: w(ve),
					status: w(ye),
					"not-found": w(R)("roster.transfers.not_found")
				}, {
					default: T(() => [w(G) ? (x(), g("div", pe, [
						y(ne, { action: w(V) }, null, 8, ["action"]),
						w(Z).length > 0 ? (x(), m(s, {
							key: 0,
							color: "error",
							variant: "subtle",
							title: w(Z).join(" "),
							"data-testid": "roster-transfer-action-errors"
						}, null, 8, ["title"])) : h("", !0),
						y(b, null, {
							default: T(() => [
								y(c, {
									items: w(xe),
									columns: 2
								}, {
									status: T(() => [y(ee, {
										status: w(G).status,
										testid: "roster-transfer-status"
									}, null, 8, ["status"])]),
									_: 1
								}, 8, ["items"]),
								w(G).error ? (x(), m(s, {
									key: 0,
									class: "mt-4",
									color: "error",
									variant: "subtle",
									title: w(G).error,
									"data-testid": "roster-transfer-error"
								}, null, 8, ["title"])) : h("", !0),
								w(G).progress && w(G).status === "running" ? (x(), g("div", me, [y(l, {
									"model-value": w(G).progress.done,
									max: Math.max(1, w(G).progress.total)
								}, null, 8, ["model-value", "max"]), _("p", O, C(w(R)("roster.transfers.rows_applied", {
									done: w(G).progress.done,
									total: w(G).progress.total
								})), 1)])) : h("", !0),
								w(Y).length > 0 ? (x(), g("div", k, [(x(!0), g(f, null, S(w(Y), ([e, t]) => (x(), m(p, {
									key: e,
									color: "neutral",
									variant: "subtle"
								}, {
									default: T(() => [v(C(Q(e)) + ": " + C(t), 1)]),
									_: 2
								}, 1024))), 128))])) : h("", !0),
								w(X).length > 0 ? (x(), g("div", A, [(x(!0), g(f, null, S(w(X), ([e, t]) => (x(), m(p, {
									key: e,
									color: "primary",
									variant: "subtle"
								}, {
									default: T(() => [v(C(Q(e)) + ": " + C(t), 1)]),
									_: 2
								}, 1024))), 128))])) : h("", !0),
								w(e)(w(G).status) ? h("", !0) : (x(), g("div", j, [w(G).status === "awaiting_confirmation" ? (x(), m(a, {
									key: 0,
									icon: "i-lucide-check",
									loading: w(V).busy.value,
									"data-testid": "roster-transfer-confirm",
									onClick: Se
								}, {
									default: T(() => [v(C(w(R)("roster.transfers.confirm")), 1)]),
									_: 1
								}, 8, ["loading"])) : h("", !0), y(a, {
									color: "error",
									variant: "outline",
									icon: "i-lucide-x",
									loading: w(V).busy.value,
									"data-testid": "roster-transfer-cancel",
									onClick: Ce
								}, {
									default: T(() => [v(C(w(R)("roster.transfers.cancel")), 1)]),
									_: 1
								}, 8, ["loading"])]))
							]),
							_: 1
						}),
						w(q).length > 0 ? (x(), g(f, { key: 1 }, [y(E, {
							data: w(q),
							columns: w(be),
							"data-testid": "roster-transfer-rows"
						}, {
							"line-cell": T(({ row: { original: e } }) => [_("span", { "data-testid": `roster-transfer-line-${e.line}` }, C(e.line), 9, M)]),
							"row-cell": T(({ row: { original: e } }) => [_("span", N, C(w(r)(e)), 1)]),
							"planned-cell": T(({ row: { original: e } }) => [y(p, {
								color: "neutral",
								variant: "subtle"
							}, {
								default: T(() => [v(C(Q(e.action ?? "skip")), 1)]),
								_: 2
							}, 1024), _("span", P, C((e.reasons ?? []).join(" ")), 1)]),
							"result-cell": T(({ row: { original: e } }) => [e.result ? (x(), g(f, { key: 0 }, [y(p, {
								color: "neutral",
								variant: "subtle"
							}, {
								default: T(() => [v(C(Q(e.result)), 1)]),
								_: 2
							}, 1024), _("span", F, C((e.result_reasons ?? []).join(" ")), 1)], 64)) : h("", !0)]),
							_: 1
						}, 8, ["data", "columns"]), w(J).last > 1 ? (x(), g("div", I, [
							y(a, {
								variant: "outline",
								size: "sm",
								icon: "i-lucide-chevron-left",
								disabled: w(J).current <= 1,
								"data-testid": "roster-transfer-rows-prev",
								onClick: i[0] ||= (e) => $(w(J).current - 1)
							}, {
								default: T(() => [v(C(w(R)("roster.users.pager.previous")), 1)]),
								_: 1
							}, 8, ["disabled"]),
							_("span", L, C(w(R)("roster.users.pager.page", {
								current: w(J).current,
								last: w(J).last
							})), 1),
							y(a, {
								variant: "outline",
								size: "sm",
								"trailing-icon": "i-lucide-chevron-right",
								disabled: w(J).current >= w(J).last,
								"data-testid": "roster-transfer-rows-next",
								onClick: i[1] ||= (e) => $(w(J).current + 1)
							}, {
								default: T(() => [v(C(w(R)("roster.users.pager.next")), 1)]),
								_: 1
							}, 8, ["disabled"])
						])) : h("", !0)], 64)) : h("", !0)
					])) : h("", !0)]),
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
export { R as default };
