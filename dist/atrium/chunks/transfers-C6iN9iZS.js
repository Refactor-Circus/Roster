import { c as e, i as t, n, r, s as i, t as ee, u as te } from "./TransferStatusBadge-C80F4lj4.js";
import { t as a } from "./RosterActionError-DqFXfLSN.js";
import { t as ne } from "./RosterPager-CFRR10Qn.js";
import { c as o, d as s, h as c, i as l, l as u, m as d, n as f, r as re, s as p } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as ie } from "@refactor-circus/atrium";
import { UButton as ae, UCard as oe, UEmpty as se, UFileUpload as ce, UForm as m, UFormField as le, UInput as ue, ULink as h, UPageHeader as de, USelect as fe, UTable as pe } from "@refactor-circus/atrium/ui";
import { computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createSlots as me, createTextVNode as x, createVNode as S, defineComponent as C, isRef as he, openBlock as w, reactive as T, ref as ge, toDisplayString as E, unref as D, watch as O, withCtx as k } from "vue";
import { useI18n as A } from "vue-i18n";
import { useRoute as j, useRouter as M } from "vue-router";
// src/pages/transfers/index.vue?vue&type=script&setup=true&lang.ts
var N = {
	class: "space-y-4",
	"data-testid": "roster-transfers"
}, P = { "data-testid": "roster-transfers-organization" }, F = { class: "space-y-4" }, I = { class: "grid gap-4 lg:grid-cols-2" }, L = { class: "font-medium" }, _e = {
	class: "mb-4 flex flex-wrap items-end gap-3",
	"data-testid": "roster-import-templates"
}, ve = { class: "font-medium" }, R = /* @__PURE__ */ C({
	__name: "index",
	setup(C) {
		let { t: R } = A(), z = j(), ye = M(), B = d(), V = d(), H = d(), U = g(() => o(z.query.organization) ?? null), W = g(() => {
			let e = p(z.query.page);
			return {
				...U.value ? { organization: U.value } : {},
				...e > 1 ? { page: e } : {}
			};
		}), { client: G, data: K, pending: be, failed: xe, status: Se } = c(async (e) => u(await e.GET("/transfers", { params: { query: W.value } })), W), q = g(() => K.value?.data ?? []), J = g(() => l(K.value)), Y = ge(r[0]), X = T({
			type: r[0],
			organization: "",
			file: null
		}), Z = T({
			type: n[0],
			organization: "",
			externalSource: ""
		});
		O(U, (e) => {
			X.organization = e ?? "", Z.organization = e ?? "";
		}, { immediate: !0 });
		let Q = g(() => r.map((e) => ({
			label: R(`roster.transfers.types.${e}`),
			value: e
		}))), Ce = g(() => n.map((e) => ({
			label: R(`roster.transfers.types.${e}`),
			value: e
		}))), we = g(() => [
			{
				id: "type",
				header: R("roster.transfers.fields.type")
			},
			{
				id: "organization",
				header: R("roster.transfers.fields.organization")
			},
			{
				id: "requested_by",
				header: R("roster.transfers.fields.requested_by")
			},
			{
				id: "status",
				header: R("roster.transfers.fields.status")
			},
			{
				id: "rows",
				header: R("roster.transfers.fields.rows"),
				meta: { class: {
					th: "text-right",
					td: "text-right tabular-nums"
				} }
			},
			{
				id: "created",
				header: R("roster.transfers.fields.created")
			}
		]);
		async function Te() {
			if (!G) return;
			let e = Y.value;
			await B.run(async () => {
				let n = await G.GET("/imports/templates/{type}", {
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
			}) && r.transfer !== null && await ye.push(e(r.transfer.id));
		}
		async function Ee() {
			if (!G) return;
			let e = new FormData();
			e.append("type", X.type);
			let t = f(X.organization);
			t !== null && e.append("organization", t), X.file && e.append("file", X.file, X.file.name), await $(async () => u(await G.POST("/imports", { body: s(e) })).data, V);
		}
		async function De() {
			if (!G) return;
			let e = { type: Z.type };
			Z.type === "export_members" && (e.organization = f(Z.organization)), Z.type === "export_organizations" && (e.filters = { external_source: f(Z.externalSource) }), await $(async () => u(await G.POST("/exports", { body: e })).data, H);
		}
		return (t, n) => {
			let r = de, i = fe, o = le, s = ae, c = ue, l = ce, u = m, d = oe, f = se, p = h, g = pe, C = ie;
			return w(), y("div", N, [S(r, { title: D(R)("roster.transfers.title") }, me({ _: 2 }, [D(U) ? {
				name: "description",
				fn: k(() => [b("span", P, E(D(U)), 1)]),
				key: "0"
			} : void 0]), 1032, ["title"]), S(C, { "screen-key": "roster.transfers" }, {
				default: k(() => [S(te, {
					ready: !D(be) && D(K) !== null,
					failed: D(xe),
					status: D(Se)
				}, {
					default: k(() => [b("div", F, [
						b("div", I, [S(d, { "data-testid": "roster-import-card" }, {
							header: k(() => [b("h2", L, E(D(R)("roster.transfers.import.title")), 1)]),
							default: k(() => [
								b("div", _e, [S(o, { label: D(R)("roster.transfers.import.template") }, {
									default: k(() => [S(i, {
										modelValue: D(Y),
										"onUpdate:modelValue": n[0] ||= (e) => he(Y) ? Y.value = e : null,
										items: D(Q),
										class: "w-56",
										"data-testid": "roster-template-type"
									}, null, 8, ["modelValue", "items"])]),
									_: 1
								}, 8, ["label"]), S(s, {
									variant: "outline",
									icon: "i-lucide-download",
									loading: D(B).busy.value,
									"data-testid": "roster-template-download",
									onClick: Te
								}, {
									default: k(() => [x(E(D(R)("roster.transfers.import.download_template")), 1)]),
									_: 1
								}, 8, ["loading"])]),
								S(a, { action: D(B) }, null, 8, ["action"]),
								S(u, {
									state: D(X),
									class: "flex flex-col gap-3",
									"data-testid": "roster-import-form",
									onSubmit: Ee
								}, {
									default: k(() => [
										S(a, { action: D(V) }, null, 8, ["action"]),
										S(o, {
											label: D(R)("roster.transfers.fields.type"),
											error: D(V).error("type"),
											required: ""
										}, {
											default: k(() => [S(i, {
												modelValue: D(X).type,
												"onUpdate:modelValue": n[1] ||= (e) => D(X).type = e,
												items: D(Q),
												class: "w-full",
												"data-testid": "roster-import-type"
											}, null, 8, ["modelValue", "items"])]),
											_: 1
										}, 8, ["label", "error"]),
										S(o, {
											label: D(R)("roster.transfers.fields.organization"),
											hint: D(R)("roster.transfers.import.organization_hint"),
											error: D(V).error("organization")
										}, {
											default: k(() => [S(c, {
												modelValue: D(X).organization,
												"onUpdate:modelValue": n[2] ||= (e) => D(X).organization = e,
												class: "w-full",
												"data-testid": "roster-import-organization"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, [
											"label",
											"hint",
											"error"
										]),
										S(o, {
											label: D(R)("roster.transfers.import.file"),
											hint: D(R)("roster.transfers.import.file_hint"),
											error: D(V).error("file")
										}, {
											default: k(() => [S(l, {
												modelValue: D(X).file,
												"onUpdate:modelValue": n[3] ||= (e) => D(X).file = e,
												accept: ".csv,text/csv",
												icon: "i-lucide-file-spreadsheet",
												label: D(R)("roster.transfers.import.file"),
												description: D(R)("roster.transfers.import.file_hint"),
												layout: "list",
												class: "min-h-24 w-full",
												"data-testid": "roster-import-file"
											}, null, 8, [
												"modelValue",
												"label",
												"description"
											])]),
											_: 1
										}, 8, [
											"label",
											"hint",
											"error"
										]),
										b("div", null, [S(s, {
											type: "submit",
											icon: "i-lucide-upload",
											loading: D(V).busy.value,
											"data-testid": "roster-import-submit"
										}, {
											default: k(() => [x(E(D(R)("roster.transfers.import.submit")), 1)]),
											_: 1
										}, 8, ["loading"])])
									]),
									_: 1
								}, 8, ["state"])
							]),
							_: 1
						}), S(d, { "data-testid": "roster-export-card" }, {
							header: k(() => [b("h2", ve, E(D(R)("roster.transfers.export.title")), 1)]),
							default: k(() => [S(u, {
								state: D(Z),
								class: "flex flex-col gap-3",
								"data-testid": "roster-export-form",
								onSubmit: De
							}, {
								default: k(() => [
									S(a, { action: D(H) }, null, 8, ["action"]),
									S(o, {
										label: D(R)("roster.transfers.fields.type"),
										error: D(H).error("type"),
										required: ""
									}, {
										default: k(() => [S(i, {
											modelValue: D(Z).type,
											"onUpdate:modelValue": n[4] ||= (e) => D(Z).type = e,
											items: D(Ce),
											class: "w-full",
											"data-testid": "roster-export-type"
										}, null, 8, ["modelValue", "items"])]),
										_: 1
									}, 8, ["label", "error"]),
									D(Z).type === "export_members" ? (w(), _(o, {
										key: 0,
										label: D(R)("roster.transfers.fields.organization"),
										hint: D(R)("roster.transfers.export.organization_hint"),
										error: D(H).error("organization")
									}, {
										default: k(() => [S(c, {
											modelValue: D(Z).organization,
											"onUpdate:modelValue": n[5] ||= (e) => D(Z).organization = e,
											class: "w-full",
											"data-testid": "roster-export-organization"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									])) : v("", !0),
									D(Z).type === "export_organizations" ? (w(), _(o, {
										key: 1,
										label: D(R)("roster.transfers.export.external_source"),
										hint: D(R)("roster.transfers.export.external_source_hint"),
										error: D(H).error("filters.external_source")
									}, {
										default: k(() => [S(c, {
											modelValue: D(Z).externalSource,
											"onUpdate:modelValue": n[6] ||= (e) => D(Z).externalSource = e,
											class: "w-full",
											"data-testid": "roster-export-external-source"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									])) : v("", !0),
									b("div", null, [S(s, {
										type: "submit",
										icon: "i-lucide-download",
										loading: D(H).busy.value,
										"data-testid": "roster-export-submit"
									}, {
										default: k(() => [x(E(D(R)("roster.transfers.export.submit")), 1)]),
										_: 1
									}, 8, ["loading"])])
								]),
								_: 1
							}, 8, ["state"])]),
							_: 1
						})]),
						D(q).length === 0 ? (w(), _(f, {
							key: 0,
							icon: "i-lucide-arrow-up-down",
							title: D(R)("roster.transfers.empty"),
							"data-testid": "roster-transfers-empty"
						}, null, 8, ["title"])) : (w(), _(g, {
							key: 1,
							data: D(q),
							columns: D(we),
							class: "text-sm",
							"data-testid": "roster-transfers-table"
						}, {
							"type-cell": k(({ row: { original: t } }) => [S(p, {
								to: D(e)(t.id),
								class: "font-medium text-default hover:underline",
								"data-testid": `roster-transfer-row-${t.id}`
							}, {
								default: k(() => [x(E(D(R)(`roster.transfers.types.${t.type}`)), 1)]),
								_: 2
							}, 1032, ["to", "data-testid"])]),
							"organization-cell": k(({ row: { original: e } }) => [x(E(e.organization ?? D(R)("roster.transfers.none")), 1)]),
							"requested_by-cell": k(({ row: { original: e } }) => [x(E(e.requested_by?.name || e.requested_by?.email || D(R)("roster.transfers.none")), 1)]),
							"status-cell": k(({ row: { original: e } }) => [S(ee, { status: e.status }, null, 8, ["status"])]),
							"rows-cell": k(({ row: { original: e } }) => [x(E(e.row_count ?? 0), 1)]),
							"created-cell": k(({ row: { original: e } }) => [x(E(D(re)(e.created_at) ?? D(R)("roster.transfers.none")), 1)]),
							_: 1
						}, 8, ["data", "columns"])),
						S(ne, {
							current: D(J).current,
							last: D(J).last,
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
export { R as default };
