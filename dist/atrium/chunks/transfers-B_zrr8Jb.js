import { c as e, i as t, n, r, s as i, t as ee, u as te } from "./TransferStatusBadge-C80F4lj4.js";
import { t as a } from "./RosterActionError-DqFXfLSN.js";
import { t as ne } from "./RosterPager-CFRR10Qn.js";
import { c as o, d as s, h as c, i as l, l as u, m as d, n as f, r as re, s as ie } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as ae } from "@refactor-circus/atrium";
import { UButton as oe, UCard as se, UFormField as ce, UInput as le } from "@refactor-circus/atrium/ui";
import { Fragment as p, computed as m, createBlock as h, createCommentVNode as g, createElementBlock as _, createElementVNode as v, createTextVNode as y, createVNode as b, defineComponent as ue, isRef as de, openBlock as x, reactive as S, ref as fe, renderList as C, resolveComponent as pe, toDisplayString as w, unref as T, vModelSelect as E, watch as me, withCtx as D, withDirectives as O, withModifiers as k } from "vue";
import { useI18n as he } from "vue-i18n";
import { useRoute as ge, useRouter as A } from "vue-router";
// src/pages/transfers/index.vue?vue&type=script&setup=true&lang.ts
var j = {
	class: "space-y-4",
	"data-testid": "roster-transfers"
}, M = { class: "text-xl font-semibold" }, N = {
	key: 0,
	class: "text-sm text-muted",
	"data-testid": "roster-transfers-organization"
}, P = { class: "space-y-4" }, F = { class: "grid gap-4 lg:grid-cols-2" }, I = { class: "font-medium" }, L = {
	class: "mb-4 flex flex-wrap items-end gap-3",
	"data-testid": "roster-import-templates"
}, _e = ["value"], ve = ["value"], ye = { class: "font-medium" }, be = ["value"], xe = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "roster-transfers-empty"
}, Se = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "roster-transfers-table"
}, Ce = { class: "text-muted" }, we = { class: "py-2 pr-3 font-medium" }, Te = { class: "py-2 pr-3 font-medium" }, Ee = { class: "py-2 pr-3 font-medium" }, De = { class: "py-2 pr-3 font-medium" }, R = { class: "py-2 pr-3 font-medium" }, Oe = { class: "py-2 font-medium" }, ke = { class: "divide-y divide-default" }, Ae = ["data-testid"], je = { class: "py-2 pr-3" }, Me = { class: "py-2 pr-3" }, Ne = { class: "py-2 pr-3" }, Pe = { class: "py-2 pr-3" }, Fe = { class: "py-2 pr-3" }, Ie = { class: "py-2" }, z = /* @__PURE__ */ ue({
	__name: "index",
	setup(ue) {
		let { t: z } = he(), B = ge(), Le = A(), V = d(), H = d(), U = d(), W = m(() => o(B.query.organization) ?? null), G = m(() => {
			let e = ie(B.query.page);
			return {
				...W.value ? { organization: W.value } : {},
				...e > 1 ? { page: e } : {}
			};
		}), { client: K, data: q, pending: Re, failed: ze, status: Be } = c(async (e) => u(await e.GET("/transfers", { params: { query: G.value } })), G), J = m(() => q.value?.data ?? []), Y = m(() => l(q.value)), X = fe(r[0]), Z = S({
			type: r[0],
			organization: "",
			file: null
		}), Q = S({
			type: n[0],
			organization: "",
			externalSource: ""
		});
		me(W, (e) => {
			Z.organization = e ?? "", Q.organization = e ?? "";
		}, { immediate: !0 });
		function Ve(e) {
			Z.file = e.target.files?.[0] ?? null;
		}
		async function He() {
			if (!K) return;
			let e = X.value;
			await V.run(async () => {
				let n = await K.GET("/imports/templates/{type}", {
					params: {
						path: { type: e },
						query: { type: e }
					},
					parseAs: "blob"
				}), r = u(n);
				i(r, t(n.response.headers.get("Content-Disposition"), `roster-${e}-template.csv`));
			});
		}
		async function $(t, n) {
			let r = { transfer: null };
			await n.run(async () => {
				r.transfer = await t();
			}) && r.transfer !== null && await Le.push(e(r.transfer.id));
		}
		async function Ue() {
			if (!K) return;
			let e = new FormData();
			e.append("type", Z.type);
			let t = f(Z.organization);
			t !== null && e.append("organization", t), Z.file && e.append("file", Z.file, Z.file.name), await $(async () => u(await K.POST("/imports", { body: s(e) })).data, H);
		}
		async function We() {
			if (!K) return;
			let e = { type: Q.type };
			Q.type === "export_members" && (e.organization = f(Q.organization)), Q.type === "export_organizations" && (e.filters = { external_source: f(Q.externalSource) }), await $(async () => u(await K.POST("/exports", { body: e })).data, U);
		}
		return (t, i) => {
			let o = ce, s = oe, c = le, l = se, u = pe("RouterLink"), d = ae;
			return x(), _("div", j, [v("div", null, [v("h1", M, w(T(z)("roster.transfers.title")), 1), T(W) ? (x(), _("p", N, w(T(W)), 1)) : g("", !0)]), b(d, { "screen-key": "roster.transfers" }, {
				default: D(() => [b(te, {
					ready: !T(Re) && T(q) !== null,
					failed: T(ze),
					status: T(Be)
				}, {
					default: D(() => [v("div", P, [
						v("div", F, [b(l, { "data-testid": "roster-import-card" }, {
							header: D(() => [v("h2", I, w(T(z)("roster.transfers.import.title")), 1)]),
							default: D(() => [
								v("div", L, [b(o, { label: T(z)("roster.transfers.import.template") }, {
									default: D(() => [O(v("select", {
										"onUpdate:modelValue": i[0] ||= (e) => de(X) ? X.value = e : null,
										class: "w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
										"data-testid": "roster-template-type"
									}, [(x(!0), _(p, null, C(T(r), (e) => (x(), _("option", {
										key: e,
										value: e
									}, w(T(z)(`roster.transfers.types.${e}`)), 9, _e))), 128))], 512), [[E, T(X)]])]),
									_: 1
								}, 8, ["label"]), b(s, {
									variant: "outline",
									icon: "i-lucide-download",
									loading: T(V).busy.value,
									"data-testid": "roster-template-download",
									onClick: He
								}, {
									default: D(() => [y(w(T(z)("roster.transfers.import.download_template")), 1)]),
									_: 1
								}, 8, ["loading"])]),
								b(a, { action: T(V) }, null, 8, ["action"]),
								v("form", {
									class: "flex flex-col gap-3",
									"data-testid": "roster-import-form",
									onSubmit: k(Ue, ["prevent"])
								}, [
									b(a, { action: T(H) }, null, 8, ["action"]),
									b(o, {
										label: T(z)("roster.transfers.fields.type"),
										error: T(H).error("type"),
										required: ""
									}, {
										default: D(() => [O(v("select", {
											"onUpdate:modelValue": i[1] ||= (e) => T(Z).type = e,
											class: "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
											"data-testid": "roster-import-type"
										}, [(x(!0), _(p, null, C(T(r), (e) => (x(), _("option", {
											key: e,
											value: e
										}, w(T(z)(`roster.transfers.types.${e}`)), 9, ve))), 128))], 512), [[E, T(Z).type]])]),
										_: 1
									}, 8, ["label", "error"]),
									b(o, {
										label: T(z)("roster.transfers.fields.organization"),
										hint: T(z)("roster.transfers.import.organization_hint"),
										error: T(H).error("organization")
									}, {
										default: D(() => [b(c, {
											modelValue: T(Z).organization,
											"onUpdate:modelValue": i[2] ||= (e) => T(Z).organization = e,
											class: "w-full",
											"data-testid": "roster-import-organization"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									b(o, {
										label: T(z)("roster.transfers.import.file"),
										hint: T(z)("roster.transfers.import.file_hint"),
										error: T(H).error("file")
									}, {
										default: D(() => [v("input", {
											type: "file",
											accept: ".csv,text/csv",
											class: "block w-full text-sm",
											"data-testid": "roster-import-file",
											onChange: Ve
										}, null, 32)]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									v("div", null, [b(s, {
										type: "submit",
										icon: "i-lucide-upload",
										loading: T(H).busy.value,
										"data-testid": "roster-import-submit"
									}, {
										default: D(() => [y(w(T(z)("roster.transfers.import.submit")), 1)]),
										_: 1
									}, 8, ["loading"])])
								], 32)
							]),
							_: 1
						}), b(l, { "data-testid": "roster-export-card" }, {
							header: D(() => [v("h2", ye, w(T(z)("roster.transfers.export.title")), 1)]),
							default: D(() => [v("form", {
								class: "flex flex-col gap-3",
								"data-testid": "roster-export-form",
								onSubmit: k(We, ["prevent"])
							}, [
								b(a, { action: T(U) }, null, 8, ["action"]),
								b(o, {
									label: T(z)("roster.transfers.fields.type"),
									error: T(U).error("type"),
									required: ""
								}, {
									default: D(() => [O(v("select", {
										"onUpdate:modelValue": i[3] ||= (e) => T(Q).type = e,
										class: "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
										"data-testid": "roster-export-type"
									}, [(x(!0), _(p, null, C(T(n), (e) => (x(), _("option", {
										key: e,
										value: e
									}, w(T(z)(`roster.transfers.types.${e}`)), 9, be))), 128))], 512), [[E, T(Q).type]])]),
									_: 1
								}, 8, ["label", "error"]),
								T(Q).type === "export_members" ? (x(), h(o, {
									key: 0,
									label: T(z)("roster.transfers.fields.organization"),
									hint: T(z)("roster.transfers.export.organization_hint"),
									error: T(U).error("organization")
								}, {
									default: D(() => [b(c, {
										modelValue: T(Q).organization,
										"onUpdate:modelValue": i[4] ||= (e) => T(Q).organization = e,
										class: "w-full",
										"data-testid": "roster-export-organization"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								])) : g("", !0),
								T(Q).type === "export_organizations" ? (x(), h(o, {
									key: 1,
									label: T(z)("roster.transfers.export.external_source"),
									hint: T(z)("roster.transfers.export.external_source_hint"),
									error: T(U).error("filters.external_source")
								}, {
									default: D(() => [b(c, {
										modelValue: T(Q).externalSource,
										"onUpdate:modelValue": i[5] ||= (e) => T(Q).externalSource = e,
										class: "w-full",
										"data-testid": "roster-export-external-source"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								])) : g("", !0),
								v("div", null, [b(s, {
									type: "submit",
									icon: "i-lucide-download",
									loading: T(U).busy.value,
									"data-testid": "roster-export-submit"
								}, {
									default: D(() => [y(w(T(z)("roster.transfers.export.submit")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						})]),
						T(J).length === 0 ? (x(), _("p", xe, w(T(z)("roster.transfers.empty")), 1)) : (x(), _("table", Se, [v("thead", Ce, [v("tr", null, [
							v("th", we, w(T(z)("roster.transfers.fields.type")), 1),
							v("th", Te, w(T(z)("roster.transfers.fields.organization")), 1),
							v("th", Ee, w(T(z)("roster.transfers.fields.requested_by")), 1),
							v("th", De, w(T(z)("roster.transfers.fields.status")), 1),
							v("th", R, w(T(z)("roster.transfers.fields.rows")), 1),
							v("th", Oe, w(T(z)("roster.transfers.fields.created")), 1)
						])]), v("tbody", ke, [(x(!0), _(p, null, C(T(J), (t) => (x(), _("tr", {
							key: t.id,
							"data-testid": `roster-transfer-row-${t.id}`
						}, [
							v("td", je, [b(u, {
								to: T(e)(t.id),
								class: "font-medium hover:underline"
							}, {
								default: D(() => [y(w(T(z)(`roster.transfers.types.${t.type}`)), 1)]),
								_: 2
							}, 1032, ["to"])]),
							v("td", Me, w(t.organization ?? T(z)("roster.transfers.none")), 1),
							v("td", Ne, w(t.requested_by?.name || t.requested_by?.email || T(z)("roster.transfers.none")), 1),
							v("td", Pe, [b(ee, { status: t.status }, null, 8, ["status"])]),
							v("td", Fe, w(t.row_count ?? 0), 1),
							v("td", Ie, w(T(re)(t.created_at) ?? T(z)("roster.transfers.none")), 1)
						], 8, Ae))), 128))])])),
						b(ne, {
							current: T(Y).current,
							last: T(Y).last,
							testid: "roster-transfers"
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
export { z as default };
