import { t as e } from "./OrganizationState-GrCgPjha.js";
import { o as t } from "./organizations-vLWlhIK9.js";
import { t as n } from "./RosterActionError-DqFXfLSN.js";
import { t as r } from "./RosterPager-CFRR10Qn.js";
import { t as i } from "./RosterTabs-C9EsVdM2.js";
import { c as a, h as o, i as s, l as c, m as l, s as u } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as d, AtriumPageHeader as f } from "@refactor-circus/atrium";
import { UAlert as p, UBadge as ee, UButton as te, UEmpty as ne, UForm as m, UFormField as re, UInput as ie, ULink as ae, UTable as oe } from "@refactor-circus/atrium/ui";
import { Fragment as h, computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, openBlock as w, reactive as T, ref as E, renderList as se, toDisplayString as D, unref as O, watch as k, withCtx as A } from "vue";
import { useI18n as j } from "vue-i18n";
import { useRoute as M, useRouter as ce } from "vue-router";
// src/pages/organizations/index.vue?vue&type=script&setup=true&lang.ts
var N = {
	class: "space-y-4",
	"data-testid": "roster-organizations"
}, P = { class: "space-y-4" }, F = { class: "space-y-4" }, I = ["data-testid"], L = {
	key: 1,
	class: "font-medium"
}, R = {
	key: 0,
	class: "flex justify-end gap-2"
}, z = /* @__PURE__ */ C({
	__name: "index",
	setup(C) {
		let { t: z } = j(), B = M(), V = ce(), H = l(), U = E(null), W = [
			"search",
			"source",
			"external_id",
			"account_number"
		], G = g(() => a(B.query.trashed) === "only"), K = g(() => {
			let e = u(B.query.page), t = {};
			for (let e of W) {
				let n = a(B.query[e]);
				n && (t[e] = n);
			}
			return {
				...t,
				...G.value ? { trashed: "only" } : {},
				...e > 1 ? { page: e } : {}
			};
		}), { client: q, data: J, pending: le, failed: ue, status: de, reload: Y } = o(async (e) => c(await e.GET("/organizations", { params: { query: K.value } })), K), X = g(() => J.value?.data ?? []), Z = g(() => s(J.value)), Q = T({
			search: "",
			source: "",
			external_id: "",
			account_number: ""
		}), fe = g(() => [{
			key: "active",
			label: z("roster.organizations.tabs.active")
		}, {
			key: "deleted",
			label: z("roster.organizations.tabs.deleted")
		}]), pe = g(() => [
			{
				id: "name",
				header: z("roster.organizations.fields.name")
			},
			{
				accessorKey: "slug",
				header: z("roster.organizations.fields.slug")
			},
			{
				id: "members",
				header: z("roster.organizations.fields.members"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			},
			{
				id: "teams",
				header: z("roster.organizations.fields.teams"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			},
			{
				id: "external",
				header: z("roster.organizations.fields.external")
			},
			{
				id: "actions",
				header: z("roster.organizations.fields.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]);
		k(() => W.map((e) => B.query[e]), () => {
			for (let e of W) Q[e] = a(B.query[e]) ?? "";
		}, { immediate: !0 });
		function $(e) {
			U.value = null, V.replace({ query: {
				...B.query,
				trashed: e ? "only" : void 0,
				page: void 0
			} });
		}
		function me() {
			let e = Object.fromEntries(W.map((e) => [e, Q[e].trim() || void 0]));
			V.replace({ query: {
				...B.query,
				...e,
				page: void 0
			} });
		}
		function he() {
			V.replace({ query: {
				...B.query,
				...Object.fromEntries(W.map((e) => [e, void 0])),
				page: void 0
			} });
		}
		async function ge(e) {
			U.value = null, q && await H.run(async () => c(await q.POST("/organizations/{organization}/restore", { params: { path: { organization: e.slug } } }))) && await Y();
		}
		async function _e(e) {
			if (!q || !window.confirm(z("roster.organizations.danger.confirm_purge"))) return;
			U.value = null;
			let t = null;
			await H.run(async () => {
				t = c(await q.DELETE("/organizations/{organization}/purge", { params: { path: { organization: e.slug } } })).message;
			}) && (U.value = t ?? z("roster.organizations.danger.purge_queued"), await Y());
		}
		return (a, o) => {
			let s = te, c = f, l = ie, u = re, g = m, C = p, T = ne, E = ae, k = ee, j = oe, M = d;
			return w(), y("div", N, [S(c, { title: O(z)("roster.organizations.title") }, {
				default: A(() => [S(s, {
					to: "/roster/transfers",
					variant: "outline",
					icon: "i-lucide-arrow-up-down",
					"data-testid": "roster-organizations-transfers"
				}, {
					default: A(() => [x(D(O(z)("roster.organizations.import_export")), 1)]),
					_: 1
				}), S(s, {
					to: "/roster/organizations/create",
					icon: "i-lucide-plus",
					"data-testid": "roster-organizations-new"
				}, {
					default: A(() => [x(D(O(z)("roster.organizations.new")), 1)]),
					_: 1
				})]),
				_: 1
			}, 8, ["title"]), S(M, { "screen-key": "roster.organizations" }, {
				default: A(() => [b("div", P, [
					S(i, {
						tabs: O(fe),
						current: O(G) ? "deleted" : "active",
						label: O(z)("roster.organizations.title"),
						testid: "roster-organizations-tab",
						onSelect: o[0] ||= (e) => $(e === "deleted")
					}, null, 8, [
						"tabs",
						"current",
						"label"
					]),
					S(g, {
						state: O(Q),
						class: "flex flex-wrap items-start gap-3",
						"data-testid": "roster-organizations-filters",
						onSubmit: me
					}, {
						default: A(() => [
							S(u, {
								label: O(z)("roster.organizations.filters.search"),
								name: "search"
							}, {
								default: A(() => [S(l, {
									modelValue: O(Q).search,
									"onUpdate:modelValue": o[1] ||= (e) => O(Q).search = e,
									name: "search",
									class: "w-64",
									"data-testid": "roster-organizations-search"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							S(u, {
								label: O(z)("roster.organizations.fields.source"),
								name: "source"
							}, {
								default: A(() => [S(l, {
									modelValue: O(Q).source,
									"onUpdate:modelValue": o[2] ||= (e) => O(Q).source = e,
									name: "source",
									class: "w-40",
									"data-testid": "roster-organizations-source"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							S(u, {
								label: O(z)("roster.organizations.fields.external_id"),
								name: "external_id"
							}, {
								default: A(() => [S(l, {
									modelValue: O(Q).external_id,
									"onUpdate:modelValue": o[3] ||= (e) => O(Q).external_id = e,
									name: "external_id",
									class: "w-48",
									"data-testid": "roster-organizations-external-id"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							S(u, {
								label: O(z)("roster.organizations.fields.account_number"),
								name: "account_number"
							}, {
								default: A(() => [S(l, {
									modelValue: O(Q).account_number,
									"onUpdate:modelValue": o[4] ||= (e) => O(Q).account_number = e,
									name: "account_number",
									class: "w-48",
									"data-testid": "roster-organizations-account-number"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label"]),
							S(s, {
								type: "submit",
								icon: "i-lucide-filter",
								class: "mt-field-label",
								"data-testid": "roster-organizations-filter"
							}, {
								default: A(() => [x(D(O(z)("roster.organizations.filters.filter")), 1)]),
								_: 1
							}),
							S(s, {
								variant: "ghost",
								icon: "i-lucide-x",
								class: "mt-field-label",
								"data-testid": "roster-organizations-clear",
								onClick: he
							}, {
								default: A(() => [x(D(O(z)("roster.organizations.filters.clear")), 1)]),
								_: 1
							})
						]),
						_: 1
					}, 8, ["state"]),
					S(e, {
						ready: !O(le) && O(J) !== null,
						failed: O(ue),
						status: O(de)
					}, {
						default: A(() => [b("div", F, [
							S(n, { action: O(H) }, null, 8, ["action"]),
							O(U) ? (w(), _(C, {
								key: 0,
								color: "success",
								variant: "subtle",
								title: O(U),
								"data-testid": "roster-organizations-notice"
							}, null, 8, ["title"])) : v("", !0),
							O(X).length === 0 ? (w(), _(T, {
								key: 1,
								icon: "i-lucide-building-2",
								title: O(z)("roster.organizations.empty"),
								"data-testid": "roster-organizations-empty"
							}, null, 8, ["title"])) : (w(), _(j, {
								key: 2,
								data: O(X),
								columns: O(pe),
								"data-testid": "roster-organizations-table"
							}, {
								"name-cell": A(({ row: { original: e } }) => [b("span", { "data-testid": `roster-organization-row-${e.slug}` }, [
									O(G) ? (w(), y("span", L, D(e.name), 1)) : (w(), _(E, {
										key: 0,
										to: O(t)(e.slug),
										class: "font-medium text-default hover:underline"
									}, {
										default: A(() => [x(D(e.name), 1)]),
										_: 2
									}, 1032, ["to"])),
									e.personal ? (w(), _(k, {
										key: 2,
										color: "neutral",
										variant: "subtle",
										class: "ml-2"
									}, {
										default: A(() => [x(D(O(z)("roster.organizations.personal")), 1)]),
										_: 1
									})) : v("", !0),
									O(G) ? (w(), _(k, {
										key: 3,
										color: "neutral",
										variant: "subtle",
										class: "ml-2"
									}, {
										default: A(() => [x(D(O(z)("roster.organizations.deleted")), 1)]),
										_: 1
									})) : v("", !0)
								], 8, I)]),
								"members-cell": A(({ row: { original: e } }) => [x(D(e.members_count ?? O(z)("roster.organizations.none")), 1)]),
								"teams-cell": A(({ row: { original: e } }) => [x(D(e.teams_count ?? O(z)("roster.organizations.none")), 1)]),
								"external-cell": A(({ row: { original: e } }) => [(w(!0), y(h, null, se(e.links, (e) => (w(), y("div", {
									key: e.source,
									class: "text-xs"
								}, [
									b("code", null, D(e.source), 1),
									x(" " + D(e.external_id), 1),
									e.account_number ? (w(), y(h, { key: 0 }, [x(" · " + D(e.account_number), 1)], 64)) : v("", !0)
								]))), 128))]),
								"actions-cell": A(({ row: { original: e } }) => [O(G) ? (w(), y("div", R, [S(s, {
									size: "sm",
									icon: "i-lucide-undo-2",
									loading: O(H).busy.value,
									"data-testid": `roster-organization-restore-${e.slug}`,
									onClick: (t) => ge(e)
								}, {
									default: A(() => [x(D(O(z)("roster.organizations.restore")), 1)]),
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
									loading: O(H).busy.value,
									"data-testid": `roster-organization-purge-${e.slug}`,
									onClick: (t) => _e(e)
								}, {
									default: A(() => [x(D(O(z)("roster.organizations.purge")), 1)]),
									_: 1
								}, 8, [
									"loading",
									"data-testid",
									"onClick"
								])])) : v("", !0)]),
								_: 1
							}, 8, ["data", "columns"])),
							S(r, {
								current: O(Z).current,
								last: O(Z).last,
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
