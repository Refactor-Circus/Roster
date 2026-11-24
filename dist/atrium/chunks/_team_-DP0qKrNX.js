import { t as e } from "./OrganizationState-GrCgPjha.js";
import { i as t, l as n, o as r, u as i } from "./organizations-vLWlhIK9.js";
import { t as a } from "./RosterActionError-DqFXfLSN.js";
import { d as o, h as s, l as c, m as l, n as u } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as ee, AtriumPageHeader as te } from "@refactor-circus/atrium";
import { UButton as d, UCard as f, UForm as p, UFormField as m, UInput as ne, USelect as re } from "@refactor-circus/atrium/ui";
import { Fragment as ie, computed as h, createCommentVNode as ae, createElementBlock as g, createElementVNode as _, createTextVNode as v, createVNode as y, defineComponent as b, isRef as x, openBlock as S, reactive as C, ref as oe, renderList as se, toDisplayString as w, unref as T, watch as E, withCtx as D } from "vue";
import { useI18n as ce } from "vue-i18n";
import { useRoute as le, useRouter as ue } from "vue-router";
// src/pages/organizations/[organization]/teams/[team].vue?vue&type=script&setup=true&lang.ts
var de = {
	class: "space-y-4",
	"data-testid": "roster-team"
}, fe = {
	class: "truncate",
	"data-testid": "roster-team-title"
}, pe = { "data-testid": "roster-team-organization" }, O = {
	key: 0,
	class: "grid gap-4 lg:grid-cols-2"
}, k = { class: "font-semibold" }, A = { class: "flex flex-col gap-4" }, j = {
	key: 0,
	class: "text-sm text-muted",
	"data-testid": "roster-team-members-empty"
}, M = {
	key: 1,
	class: "flex flex-col gap-2"
}, N = ["data-testid"], P = { class: "font-semibold" }, F = { class: "mt-4 flex flex-col gap-2" }, I = /* @__PURE__ */ b({
	__name: "[team]",
	setup(b) {
		let { t: I } = ce(), L = le(), R = ue(), z = h(() => String(L.params.organization)), B = h(() => String(L.params.team)), V = () => ({ params: { path: {
			organization: z.value,
			team: B.value
		} } }), { client: H, data: U, pending: me, failed: he, status: ge, reload: W } = s(async (e) => c(await e.GET("/organizations/{organization}/teams/{team}", V())).data, () => [z.value, B.value]), G = s(async (e) => c(await e.GET("/organizations/{organization}", { params: { path: { organization: z.value } } })).data, z), _e = s(async (e) => c(await e.GET("/organizations/{organization}/members", { params: {
			path: { organization: z.value },
			query: { per_page: 100 }
		} })).data, z), K = h(() => U.value?.members ?? []), q = h(() => {
			let e = new Set(K.value.map((e) => String(e.id)));
			return (_e.data.value ?? []).map((e) => e.user).filter((t) => t !== null && !e.has(String(t.id)));
		}), J = l(), Y = oe("");
		E(q, (e) => {
			e.some((e) => String(e.id) === Y.value) || (Y.value = e[0] ? String(e[0].id) : "");
		}, { immediate: !0 });
		let X = l(), Z = C({
			name: "",
			slug: "",
			status: "active",
			status_reason: ""
		}), Q = h(() => t.map((e) => ({
			label: I(`roster.organizations.statuses.${e}`),
			value: e
		}))), ve = h(() => q.value.map((e) => ({
			label: n(e) ?? String(e.id),
			value: String(e.id)
		})));
		E(U, (e) => {
			e && (Z.name = e.name, Z.slug = e.slug, Z.status = e.status, Z.status_reason = e.status_reason ?? "");
		}, { immediate: !0 });
		let $ = l();
		async function ye() {
			H && Y.value !== "" && await J.run(async () => c(await H.POST("/organizations/{organization}/teams/{team}/members", {
				...V(),
				body: { user: Y.value }
			}))) && await W();
		}
		async function be(e) {
			H && await J.run(async () => c(await H.DELETE("/organizations/{organization}/teams/{team}/members/{user}", { params: { path: {
				organization: z.value,
				team: B.value,
				user: String(e.id)
			} } }))) && await W();
		}
		async function xe() {
			if (!H) return;
			let e = { slug: B.value };
			if (await X.run(async () => {
				e.slug = c(await H.PATCH("/organizations/{organization}/teams/{team}", {
					...V(),
					body: o({
						name: Z.name,
						slug: Z.slug,
						status: Z.status,
						status_reason: u(Z.status_reason)
					})
				})).data.slug;
			})) {
				if (e.slug !== B.value) {
					await R.replace(i(z.value, e.slug));
					return;
				}
				await W();
			}
		}
		async function Se() {
			H && window.confirm(I("roster.organizations.teams.confirm_delete")) && await $.run(async () => c(await H.DELETE("/organizations/{organization}/teams/{team}", V()))) && await R.push(r(z.value, "teams"));
		}
		return (t, i) => {
			let o = te, s = d, c = re, l = m, u = p, h = f, b = ne, C = ee;
			return S(), g("div", de, [y(o, {
				back: T(r)(T(z), "teams"),
				"back-label": T(G).data.value?.name ?? T(z)
			}, {
				title: D(() => [_("span", fe, w(T(U)?.name ?? T(I)("roster.organizations.teams.team")), 1)]),
				description: D(() => [_("span", pe, w(T(G).data.value?.name ?? T(z)), 1)]),
				_: 1
			}, 8, ["back", "back-label"]), y(C, {
				"screen-key": "roster.team",
				record: T(B)
			}, {
				default: D(() => [y(e, {
					ready: !T(me) && T(U) !== null,
					failed: T(he),
					status: T(ge),
					"not-found": T(I)("roster.organizations.teams.not_found")
				}, {
					default: D(() => [T(U) ? (S(), g("div", O, [y(h, { "data-testid": "roster-team-members" }, {
						header: D(() => [_("h2", k, w(T(I)("roster.organizations.fields.members")), 1)]),
						default: D(() => [_("div", A, [
							y(a, { action: T(J) }, null, 8, ["action"]),
							T(K).length === 0 ? (S(), g("p", j, w(T(I)("roster.organizations.members.empty")), 1)) : (S(), g("ul", M, [(S(!0), g(ie, null, se(T(K), (e) => (S(), g("li", {
								key: String(e.id),
								class: "flex items-center justify-between gap-2",
								"data-testid": `roster-team-member-${e.id}`
							}, [_("span", null, w(T(n)(e)), 1), y(s, {
								size: "sm",
								variant: "ghost",
								icon: "i-lucide-user-minus",
								loading: T(J).busy.value,
								"data-testid": `roster-team-member-remove-${e.id}`,
								onClick: (t) => be(e)
							}, {
								default: D(() => [v(w(T(I)("roster.organizations.members.remove")), 1)]),
								_: 1
							}, 8, [
								"loading",
								"data-testid",
								"onClick"
							])], 8, N))), 128))])),
							y(u, {
								class: "flex flex-wrap items-end gap-2",
								"data-testid": "roster-team-member-add-form",
								onSubmit: ye
							}, {
								default: D(() => [y(l, {
									label: T(I)("roster.organizations.members.add"),
									error: T(J).error("user")
								}, {
									default: D(() => [y(c, {
										modelValue: T(Y),
										"onUpdate:modelValue": i[0] ||= (e) => x(Y) ? Y.value = e : null,
										items: T(ve),
										placeholder: T(I)("roster.organizations.teams.no_candidates"),
										disabled: T(q).length === 0,
										class: "w-64",
										"data-testid": "roster-team-member-candidate"
									}, null, 8, [
										"modelValue",
										"items",
										"placeholder",
										"disabled"
									])]),
									_: 1
								}, 8, ["label", "error"]), y(s, {
									type: "submit",
									icon: "i-lucide-user-plus",
									disabled: T(Y) === "",
									loading: T(J).busy.value,
									"data-testid": "roster-team-member-add"
								}, {
									default: D(() => [v(w(T(I)("roster.organizations.members.add")), 1)]),
									_: 1
								}, 8, ["disabled", "loading"])]),
								_: 1
							})
						])]),
						_: 1
					}), y(h, { "data-testid": "roster-team-settings" }, {
						header: D(() => [_("h2", P, w(T(I)("roster.organizations.settings.title")), 1)]),
						default: D(() => [y(u, {
							state: T(Z),
							class: "flex flex-col gap-4",
							"data-testid": "roster-team-settings-form",
							onSubmit: xe
						}, {
							default: D(() => [
								y(a, { action: T(X) }, null, 8, ["action"]),
								y(l, {
									label: T(I)("roster.organizations.fields.name"),
									error: T(X).error("name"),
									required: ""
								}, {
									default: D(() => [y(b, {
										modelValue: T(Z).name,
										"onUpdate:modelValue": i[1] ||= (e) => T(Z).name = e,
										class: "w-full",
										"data-testid": "roster-team-name"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								y(l, {
									label: T(I)("roster.organizations.fields.slug"),
									error: T(X).error("slug"),
									required: ""
								}, {
									default: D(() => [y(b, {
										modelValue: T(Z).slug,
										"onUpdate:modelValue": i[2] ||= (e) => T(Z).slug = e,
										class: "w-full",
										"data-testid": "roster-team-slug"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								y(l, {
									label: T(I)("roster.organizations.fields.status"),
									error: T(X).error("status")
								}, {
									default: D(() => [y(c, {
										modelValue: T(Z).status,
										"onUpdate:modelValue": i[3] ||= (e) => T(Z).status = e,
										items: T(Q),
										class: "w-72",
										"data-testid": "roster-team-status"
									}, null, 8, ["modelValue", "items"])]),
									_: 1
								}, 8, ["label", "error"]),
								y(l, {
									label: T(I)("roster.organizations.fields.reason"),
									error: T(X).error("status_reason")
								}, {
									default: D(() => [y(b, {
										modelValue: T(Z).status_reason,
										"onUpdate:modelValue": i[4] ||= (e) => T(Z).status_reason = e,
										class: "w-80",
										"data-testid": "roster-team-status-reason"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								_("div", null, [y(s, {
									type: "submit",
									icon: "i-lucide-check",
									loading: T(X).busy.value,
									"data-testid": "roster-team-save"
								}, {
									default: D(() => [v(w(T(I)("roster.organizations.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							]),
							_: 1
						}, 8, ["state"]), _("div", F, [y(a, { action: T($) }, null, 8, ["action"]), _("div", null, [y(s, {
							color: "error",
							icon: "i-lucide-trash",
							loading: T($).busy.value,
							"data-testid": "roster-team-delete",
							onClick: Se
						}, {
							default: D(() => [v(w(T(I)("roster.organizations.teams.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])])]),
						_: 1
					})])) : ae("", !0)]),
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
