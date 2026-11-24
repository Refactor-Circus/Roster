import { a as e, i as t, l as n, o as r, s as i, t as ee, u as te } from "./TransferStatusBadge-C80F4lj4.js";
import { t as ne } from "./RosterActionError-DqFXfLSN.js";
import { h as a, l as o, m as s, r as c, s as l } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as re } from "@refactor-circus/atrium";
import { UAlert as ie, UBadge as ae, UButton as oe, UCard as se, UPageHeader as ce, UProgress as le, UTable as ue } from "@refactor-circus/atrium/ui";
import { Fragment as u, computed as d, createBlock as f, createCommentVNode as p, createElementBlock as m, createElementVNode as h, createSlots as de, createTextVNode as g, createVNode as _, defineComponent as v, openBlock as y, renderList as b, toDisplayString as x, unref as S, withCtx as C } from "vue";
import { useI18n as w } from "vue-i18n";
import { useRoute as T, useRouter as fe } from "vue-router";
// src/pages/transfers/[transfer].vue?vue&type=script&setup=true&lang.ts
var pe = {
	class: "space-y-4",
	"data-testid": "roster-transfer"
}, me = { class: "flex items-center gap-3" }, he = {
	key: 0,
	class: "truncate",
	"data-testid": "roster-transfer-title"
}, ge = {
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
}, ve = ["data-testid"], ye = { class: "text-xs" }, be = { class: "ml-1 text-xs text-muted" }, xe = { class: "ml-1 text-xs text-muted" }, Se = {
	key: 0,
	class: "flex items-center gap-3 text-sm"
}, Ce = { class: "text-muted" }, R = /* @__PURE__ */ v({
	__name: "[transfer]",
	setup(v) {
		let { t: R, te: we } = w(), z = T(), Te = fe(), B = s(), V = d(() => String(z.params.transfer)), H = d(() => l(z.query.rows_page)), Ee = d(() => ({
			id: V.value,
			rowsPage: H.value
		})), { client: U, data: W, pending: De, failed: Oe, status: ke, reload: G } = a(async (e) => o(await e.GET("/transfers/{transfer}", { params: {
			path: { transfer: V.value },
			query: H.value > 1 ? { rows_page: H.value } : {}
		} })).data, Ee), K = d(() => W.value?.rows ?? []), q = d(() => [
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
		]), J = d(() => ({
			current: W.value?.rows_meta?.current_page ?? 1,
			last: W.value?.rows_meta?.last_page ?? 1
		})), Y = d(() => Object.entries(W.value?.summary ?? {})), X = d(() => Object.entries(W.value?.results ?? {})), Z = d(() => Object.values(B.errors.value).flat());
		function Q(e) {
			let t = `roster.transfers.outcomes.${e}`;
			return we(t) ? R(t) : e;
		}
		function $(e) {
			Te.replace({ query: {
				...z.query,
				rows_page: e > 1 ? String(e) : void 0
			} });
		}
		async function Ae() {
			U && await B.run(async () => o(await U.POST("/imports/{transfer}/confirm", { params: { path: { transfer: V.value } } }))) && await G();
		}
		async function je() {
			U && await B.run(async () => o(await U.DELETE("/transfers/{transfer}", { params: { path: { transfer: V.value } } }))) && await G();
		}
		async function Me() {
			let e = W.value;
			U && e && await B.run(async () => {
				let n = await U.GET("/transfers/{transfer}/download", {
					params: { path: { transfer: e.id } },
					parseAs: "blob"
				}), r = o(n);
				i(r, t(n.response.headers.get("Content-Disposition"), `roster-${e.type}-${e.id}.csv`));
			});
		}
		return (t, i) => {
			let a = oe, o = ce, s = ie, l = le, d = ae, v = se, w = ue, T = re;
			return y(), m("div", pe, [_(o, null, de({
				title: C(() => [h("span", me, [_(a, {
					to: S(n)(S(W)?.organization),
					icon: "i-lucide-arrow-left",
					color: "neutral",
					variant: "ghost",
					"aria-label": S(R)("roster.transfers.title"),
					"data-testid": "roster-transfer-back"
				}, null, 8, ["to", "aria-label"]), S(W) ? (y(), m("span", he, x(S(R)(`roster.transfers.types.${S(W).type}`)), 1)) : p("", !0)])]),
				_: 2
			}, [S(W)?.organization ? {
				name: "description",
				fn: C(() => [g(x(S(W).organization), 1)]),
				key: "0"
			} : void 0, S(W)?.download_url ? {
				name: "links",
				fn: C(() => [_(a, {
					icon: "i-lucide-download",
					loading: S(B).busy.value,
					"data-testid": "roster-transfer-download",
					onClick: Me
				}, {
					default: C(() => [g(x(S(R)("roster.transfers.download")), 1)]),
					_: 1
				}, 8, ["loading"])]),
				key: "1"
			} : void 0]), 1024), _(T, {
				"screen-key": "roster.transfer",
				record: S(V)
			}, {
				default: C(() => [_(te, {
					ready: !S(De) && S(W) !== null,
					failed: S(Oe),
					status: S(ke),
					"not-found": S(R)("roster.transfers.not_found")
				}, {
					default: C(() => [S(W) ? (y(), m("div", ge, [
						_(ne, { action: S(B) }, null, 8, ["action"]),
						S(Z).length > 0 ? (y(), f(s, {
							key: 0,
							color: "error",
							variant: "subtle",
							title: S(Z).join(" "),
							"data-testid": "roster-transfer-action-errors"
						}, null, 8, ["title"])) : p("", !0),
						_(v, null, {
							default: C(() => [
								h("dl", E, [
									h("div", null, [h("dt", D, x(S(R)("roster.transfers.fields.status")), 1), h("dd", null, [_(ee, {
										status: S(W).status,
										testid: "roster-transfer-status"
									}, null, 8, ["status"])])]),
									h("div", null, [h("dt", O, x(S(R)("roster.transfers.fields.requested_by")), 1), h("dd", null, x(S(W).requested_by?.name || S(W).requested_by?.email || S(R)("roster.transfers.none")), 1)]),
									h("div", null, [h("dt", k, x(S(R)("roster.transfers.fields.rows")), 1), h("dd", null, x(S(W).row_count ?? 0), 1)]),
									S(W).expires_at && S(W).status === "awaiting_confirmation" ? (y(), m("div", A, [h("dt", j, x(S(R)("roster.transfers.confirm_by")), 1), h("dd", null, x(S(c)(S(W).expires_at)), 1)])) : p("", !0),
									S(W).finished_at ? (y(), m("div", M, [h("dt", N, x(S(R)("roster.transfers.finished")), 1), h("dd", null, x(S(c)(S(W).finished_at)), 1)])) : p("", !0)
								]),
								S(W).error ? (y(), f(s, {
									key: 0,
									class: "mt-4",
									color: "error",
									variant: "subtle",
									title: S(W).error,
									"data-testid": "roster-transfer-error"
								}, null, 8, ["title"])) : p("", !0),
								S(W).progress && S(W).status === "running" ? (y(), m("div", P, [_(l, {
									"model-value": S(W).progress.done,
									max: Math.max(1, S(W).progress.total)
								}, null, 8, ["model-value", "max"]), h("p", F, x(S(R)("roster.transfers.rows_applied", {
									done: S(W).progress.done,
									total: S(W).progress.total
								})), 1)])) : p("", !0),
								S(Y).length > 0 ? (y(), m("div", I, [(y(!0), m(u, null, b(S(Y), ([e, t]) => (y(), f(d, {
									key: e,
									color: "neutral",
									variant: "subtle"
								}, {
									default: C(() => [g(x(Q(e)) + ": " + x(t), 1)]),
									_: 2
								}, 1024))), 128))])) : p("", !0),
								S(X).length > 0 ? (y(), m("div", L, [(y(!0), m(u, null, b(S(X), ([e, t]) => (y(), f(d, {
									key: e,
									color: "primary",
									variant: "subtle"
								}, {
									default: C(() => [g(x(Q(e)) + ": " + x(t), 1)]),
									_: 2
								}, 1024))), 128))])) : p("", !0),
								S(e)(S(W).status) ? p("", !0) : (y(), m("div", _e, [S(W).status === "awaiting_confirmation" ? (y(), f(a, {
									key: 0,
									icon: "i-lucide-check",
									loading: S(B).busy.value,
									"data-testid": "roster-transfer-confirm",
									onClick: Ae
								}, {
									default: C(() => [g(x(S(R)("roster.transfers.confirm")), 1)]),
									_: 1
								}, 8, ["loading"])) : p("", !0), _(a, {
									color: "error",
									variant: "outline",
									icon: "i-lucide-x",
									loading: S(B).busy.value,
									"data-testid": "roster-transfer-cancel",
									onClick: je
								}, {
									default: C(() => [g(x(S(R)("roster.transfers.cancel")), 1)]),
									_: 1
								}, 8, ["loading"])]))
							]),
							_: 1
						}),
						S(K).length > 0 ? (y(), m(u, { key: 1 }, [_(w, {
							data: S(K),
							columns: S(q),
							class: "text-sm",
							"data-testid": "roster-transfer-rows"
						}, {
							"line-cell": C(({ row: { original: e } }) => [h("span", { "data-testid": `roster-transfer-line-${e.line}` }, x(e.line), 9, ve)]),
							"row-cell": C(({ row: { original: e } }) => [h("span", ye, x(S(r)(e)), 1)]),
							"planned-cell": C(({ row: { original: e } }) => [_(d, {
								color: "neutral",
								variant: "subtle"
							}, {
								default: C(() => [g(x(Q(e.action ?? "skip")), 1)]),
								_: 2
							}, 1024), h("span", be, x((e.reasons ?? []).join(" ")), 1)]),
							"result-cell": C(({ row: { original: e } }) => [e.result ? (y(), m(u, { key: 0 }, [_(d, {
								color: "neutral",
								variant: "subtle"
							}, {
								default: C(() => [g(x(Q(e.result)), 1)]),
								_: 2
							}, 1024), h("span", xe, x((e.result_reasons ?? []).join(" ")), 1)], 64)) : p("", !0)]),
							_: 1
						}, 8, ["data", "columns"]), S(J).last > 1 ? (y(), m("div", Se, [
							_(a, {
								variant: "outline",
								size: "sm",
								icon: "i-lucide-chevron-left",
								disabled: S(J).current <= 1,
								"data-testid": "roster-transfer-rows-prev",
								onClick: i[0] ||= (e) => $(S(J).current - 1)
							}, {
								default: C(() => [g(x(S(R)("roster.users.pager.previous")), 1)]),
								_: 1
							}, 8, ["disabled"]),
							h("span", Ce, x(S(R)("roster.users.pager.page", {
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
								default: C(() => [g(x(S(R)("roster.users.pager.next")), 1)]),
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
export { R as default };
