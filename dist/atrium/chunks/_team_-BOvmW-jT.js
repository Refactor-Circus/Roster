import { t as e } from "./OrganizationState-GrCgPjha.js";
import { i as t, l as n, o as r, u as ee } from "./organizations-vLWlhIK9.js";
import { t as i } from "./RosterActionError-DqFXfLSN.js";
import { d as a, h as o, l as s, m as c, n as l } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as te } from "@refactor-circus/atrium";
import { UButton as ne, UCard as re, UFormField as ie, UInput as ae } from "@refactor-circus/atrium/ui";
import { Fragment as u, computed as d, createCommentVNode as f, createElementBlock as p, createElementVNode as m, createTextVNode as h, createVNode as g, defineComponent as oe, isRef as se, openBlock as _, reactive as ce, ref as le, renderList as v, toDisplayString as y, unref as b, vModelSelect as x, watch as S, withCtx as C, withDirectives as w, withModifiers as T } from "vue";
import { useI18n as ue } from "vue-i18n";
import { useRoute as de, useRouter as fe } from "vue-router";
// src/pages/organizations/[organization]/teams/[team].vue?vue&type=script&setup=true&lang.ts
var pe = {
	class: "space-y-4",
	"data-testid": "roster-team"
}, me = { class: "flex flex-wrap items-center gap-3" }, he = { class: "min-w-0" }, ge = {
	class: "text-xl font-semibold",
	"data-testid": "roster-team-title"
}, _e = {
	class: "text-sm text-muted",
	"data-testid": "roster-team-organization"
}, ve = {
	key: 0,
	class: "grid gap-4 lg:grid-cols-2"
}, E = { class: "font-semibold" }, D = { class: "flex flex-col gap-4" }, O = {
	key: 0,
	class: "text-sm",
	"data-testid": "roster-team-members-empty"
}, k = {
	key: 1,
	class: "flex flex-col gap-2"
}, A = ["data-testid"], j = {
	key: 0,
	value: "",
	disabled: ""
}, M = ["value"], N = { class: "font-semibold" }, P = ["value"], F = { class: "mt-4 flex flex-col gap-2" }, I = /* @__PURE__ */ oe({
	__name: "[team]",
	setup(oe) {
		let { t: I } = ue(), L = de(), R = fe(), z = d(() => String(L.params.organization)), B = d(() => String(L.params.team)), V = () => ({ params: { path: {
			organization: z.value,
			team: B.value
		} } }), { client: H, data: U, pending: ye, failed: be, status: xe, reload: W } = o(async (e) => s(await e.GET("/organizations/{organization}/teams/{team}", V())).data, () => [z.value, B.value]), G = o(async (e) => s(await e.GET("/organizations/{organization}", { params: { path: { organization: z.value } } })).data, z), K = o(async (e) => s(await e.GET("/organizations/{organization}/members", { params: {
			path: { organization: z.value },
			query: { per_page: 100 }
		} })).data, z), q = d(() => U.value?.members ?? []), J = d(() => {
			let e = new Set(q.value.map((e) => String(e.id)));
			return (K.data.value ?? []).map((e) => e.user).filter((t) => t !== null && !e.has(String(t.id)));
		}), Y = c(), X = le("");
		S(J, (e) => {
			e.some((e) => String(e.id) === X.value) || (X.value = e[0] ? String(e[0].id) : "");
		}, { immediate: !0 });
		let Z = c(), Q = ce({
			name: "",
			slug: "",
			status: "active",
			status_reason: ""
		});
		S(U, (e) => {
			e && (Q.name = e.name, Q.slug = e.slug, Q.status = e.status, Q.status_reason = e.status_reason ?? "");
		}, { immediate: !0 });
		let $ = c();
		async function Se() {
			H && X.value !== "" && await Y.run(async () => s(await H.POST("/organizations/{organization}/teams/{team}/members", {
				...V(),
				body: { user: X.value }
			}))) && await W();
		}
		async function Ce(e) {
			H && await Y.run(async () => s(await H.DELETE("/organizations/{organization}/teams/{team}/members/{user}", { params: { path: {
				organization: z.value,
				team: B.value,
				user: String(e.id)
			} } }))) && await W();
		}
		async function we() {
			if (!H) return;
			let e = { slug: B.value };
			if (await Z.run(async () => {
				e.slug = s(await H.PATCH("/organizations/{organization}/teams/{team}", {
					...V(),
					body: a({
						name: Q.name,
						slug: Q.slug,
						status: Q.status,
						status_reason: l(Q.status_reason)
					})
				})).data.slug;
			})) {
				if (e.slug !== B.value) {
					await R.replace(ee(z.value, e.slug));
					return;
				}
				await W();
			}
		}
		async function Te() {
			H && window.confirm(I("roster.organizations.teams.confirm_delete")) && await $.run(async () => s(await H.DELETE("/organizations/{organization}/teams/{team}", V()))) && await R.push(r(z.value, "teams"));
		}
		return (ee, a) => {
			let o = ne, s = ie, c = re, l = ae, d = te;
			return _(), p("div", pe, [m("div", me, [g(o, {
				to: b(r)(b(z), "teams"),
				icon: "i-lucide-arrow-left",
				color: "neutral",
				variant: "ghost",
				"aria-label": b(G).data.value?.name ?? b(z),
				"data-testid": "roster-team-back"
			}, null, 8, ["to", "aria-label"]), m("div", he, [m("h1", ge, y(b(U)?.name ?? b(I)("roster.organizations.teams.team")), 1), m("p", _e, y(b(G).data.value?.name ?? b(z)), 1)])]), g(d, {
				"screen-key": "roster.team",
				record: b(B)
			}, {
				default: C(() => [g(e, {
					ready: !b(ye) && b(U) !== null,
					failed: b(be),
					status: b(xe),
					"not-found": b(I)("roster.organizations.teams.not_found")
				}, {
					default: C(() => [b(U) ? (_(), p("div", ve, [g(c, { "data-testid": "roster-team-members" }, {
						header: C(() => [m("h2", E, y(b(I)("roster.organizations.fields.members")), 1)]),
						default: C(() => [m("div", D, [
							g(i, { action: b(Y) }, null, 8, ["action"]),
							b(q).length === 0 ? (_(), p("p", O, y(b(I)("roster.organizations.members.empty")), 1)) : (_(), p("ul", k, [(_(!0), p(u, null, v(b(q), (e) => (_(), p("li", {
								key: String(e.id),
								class: "flex items-center justify-between gap-2",
								"data-testid": `roster-team-member-${e.id}`
							}, [m("span", null, y(b(n)(e)), 1), g(o, {
								size: "sm",
								variant: "ghost",
								icon: "i-lucide-user-minus",
								loading: b(Y).busy.value,
								"data-testid": `roster-team-member-remove-${e.id}`,
								onClick: (t) => Ce(e)
							}, {
								default: C(() => [h(y(b(I)("roster.organizations.members.remove")), 1)]),
								_: 1
							}, 8, [
								"loading",
								"data-testid",
								"onClick"
							])], 8, A))), 128))])),
							m("form", {
								class: "flex flex-wrap items-end gap-2",
								"data-testid": "roster-team-member-add-form",
								onSubmit: T(Se, ["prevent"])
							}, [g(s, {
								label: b(I)("roster.organizations.members.add"),
								error: b(Y).error("user")
							}, {
								default: C(() => [w(m("select", {
									"onUpdate:modelValue": a[0] ||= (e) => se(X) ? X.value = e : null,
									class: "w-64 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "roster-team-member-candidate"
								}, [b(J).length === 0 ? (_(), p("option", j, y(b(I)("roster.organizations.teams.no_candidates")), 1)) : f("", !0), (_(!0), p(u, null, v(b(J), (e) => (_(), p("option", {
									key: String(e.id),
									value: String(e.id)
								}, y(b(n)(e)), 9, M))), 128))], 512), [[x, b(X)]])]),
								_: 1
							}, 8, ["label", "error"]), g(o, {
								type: "submit",
								icon: "i-lucide-user-plus",
								disabled: b(X) === "",
								loading: b(Y).busy.value,
								"data-testid": "roster-team-member-add"
							}, {
								default: C(() => [h(y(b(I)("roster.organizations.members.add")), 1)]),
								_: 1
							}, 8, ["disabled", "loading"])], 32)
						])]),
						_: 1
					}), g(c, { "data-testid": "roster-team-settings" }, {
						header: C(() => [m("h2", N, y(b(I)("roster.organizations.settings.title")), 1)]),
						default: C(() => [m("form", {
							class: "flex flex-col gap-4",
							"data-testid": "roster-team-settings-form",
							onSubmit: T(we, ["prevent"])
						}, [
							g(i, { action: b(Z) }, null, 8, ["action"]),
							g(s, {
								label: b(I)("roster.organizations.fields.name"),
								error: b(Z).error("name"),
								required: ""
							}, {
								default: C(() => [g(l, {
									modelValue: b(Q).name,
									"onUpdate:modelValue": a[1] ||= (e) => b(Q).name = e,
									class: "w-full",
									"data-testid": "roster-team-name"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							g(s, {
								label: b(I)("roster.organizations.fields.slug"),
								error: b(Z).error("slug"),
								required: ""
							}, {
								default: C(() => [g(l, {
									modelValue: b(Q).slug,
									"onUpdate:modelValue": a[2] ||= (e) => b(Q).slug = e,
									class: "w-full",
									"data-testid": "roster-team-slug"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							g(s, {
								label: b(I)("roster.organizations.fields.status"),
								error: b(Z).error("status")
							}, {
								default: C(() => [w(m("select", {
									"onUpdate:modelValue": a[3] ||= (e) => b(Q).status = e,
									class: "w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "roster-team-status"
								}, [(_(!0), p(u, null, v(b(t), (e) => (_(), p("option", {
									key: e,
									value: e
								}, y(b(I)(`roster.organizations.statuses.${e}`)), 9, P))), 128))], 512), [[x, b(Q).status]])]),
								_: 1
							}, 8, ["label", "error"]),
							g(s, {
								label: b(I)("roster.organizations.fields.reason"),
								error: b(Z).error("status_reason")
							}, {
								default: C(() => [g(l, {
									modelValue: b(Q).status_reason,
									"onUpdate:modelValue": a[4] ||= (e) => b(Q).status_reason = e,
									class: "w-80",
									"data-testid": "roster-team-status-reason"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							m("div", null, [g(o, {
								type: "submit",
								icon: "i-lucide-check",
								loading: b(Z).busy.value,
								"data-testid": "roster-team-save"
							}, {
								default: C(() => [h(y(b(I)("roster.organizations.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32), m("div", F, [g(i, { action: b($) }, null, 8, ["action"]), m("div", null, [g(o, {
							color: "error",
							icon: "i-lucide-trash",
							loading: b($).busy.value,
							"data-testid": "roster-team-delete",
							onClick: Te
						}, {
							default: C(() => [h(y(b(I)("roster.organizations.teams.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])])]),
						_: 1
					})])) : f("", !0)]),
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
export { I as default };
