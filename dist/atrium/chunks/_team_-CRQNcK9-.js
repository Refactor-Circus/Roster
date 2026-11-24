import { t as e } from "./OrganizationState-GrCgPjha.js";
import { i as t, l as n, o as r, u as i } from "./organizations-vLWlhIK9.js";
import { t as a } from "./RosterActionError-DqFXfLSN.js";
import { d as o, h as s, l as c, m as l, n as u } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as d } from "@refactor-circus/atrium";
import { UButton as f, UCard as ee, UForm as te, UFormField as ne, UInput as re, UPageHeader as p, USelect as ie } from "@refactor-circus/atrium/ui";
import { Fragment as ae, computed as m, createCommentVNode as oe, createElementBlock as h, createElementVNode as g, createTextVNode as _, createVNode as v, defineComponent as y, isRef as se, openBlock as b, reactive as x, ref as S, renderList as C, toDisplayString as w, unref as T, watch as E, withCtx as D } from "vue";
import { useI18n as ce } from "vue-i18n";
import { useRoute as le, useRouter as ue } from "vue-router";
// src/pages/organizations/[organization]/teams/[team].vue?vue&type=script&setup=true&lang.ts
var de = {
	class: "space-y-4",
	"data-testid": "roster-team"
}, fe = { class: "flex items-center gap-3" }, pe = {
	class: "truncate",
	"data-testid": "roster-team-title"
}, O = { "data-testid": "roster-team-organization" }, k = {
	key: 0,
	class: "grid gap-4 lg:grid-cols-2"
}, A = { class: "font-semibold" }, j = { class: "flex flex-col gap-4" }, M = {
	key: 0,
	class: "text-sm text-muted",
	"data-testid": "roster-team-members-empty"
}, N = {
	key: 1,
	class: "flex flex-col gap-2"
}, P = ["data-testid"], F = { class: "font-semibold" }, I = { class: "mt-4 flex flex-col gap-2" }, L = /* @__PURE__ */ y({
	__name: "[team]",
	setup(y) {
		let { t: L } = ce(), R = le(), z = ue(), B = m(() => String(R.params.organization)), V = m(() => String(R.params.team)), H = () => ({ params: { path: {
			organization: B.value,
			team: V.value
		} } }), { client: U, data: W, pending: me, failed: he, status: ge, reload: G } = s(async (e) => c(await e.GET("/organizations/{organization}/teams/{team}", H())).data, () => [B.value, V.value]), K = s(async (e) => c(await e.GET("/organizations/{organization}", { params: { path: { organization: B.value } } })).data, B), _e = s(async (e) => c(await e.GET("/organizations/{organization}/members", { params: {
			path: { organization: B.value },
			query: { per_page: 100 }
		} })).data, B), q = m(() => W.value?.members ?? []), J = m(() => {
			let e = new Set(q.value.map((e) => String(e.id)));
			return (_e.data.value ?? []).map((e) => e.user).filter((t) => t !== null && !e.has(String(t.id)));
		}), Y = l(), X = S("");
		E(J, (e) => {
			e.some((e) => String(e.id) === X.value) || (X.value = e[0] ? String(e[0].id) : "");
		}, { immediate: !0 });
		let Z = l(), Q = x({
			name: "",
			slug: "",
			status: "active",
			status_reason: ""
		}), ve = m(() => t.map((e) => ({
			label: L(`roster.organizations.statuses.${e}`),
			value: e
		}))), ye = m(() => J.value.map((e) => ({
			label: n(e) ?? String(e.id),
			value: String(e.id)
		})));
		E(W, (e) => {
			e && (Q.name = e.name, Q.slug = e.slug, Q.status = e.status, Q.status_reason = e.status_reason ?? "");
		}, { immediate: !0 });
		let $ = l();
		async function be() {
			U && X.value !== "" && await Y.run(async () => c(await U.POST("/organizations/{organization}/teams/{team}/members", {
				...H(),
				body: { user: X.value }
			}))) && await G();
		}
		async function xe(e) {
			U && await Y.run(async () => c(await U.DELETE("/organizations/{organization}/teams/{team}/members/{user}", { params: { path: {
				organization: B.value,
				team: V.value,
				user: String(e.id)
			} } }))) && await G();
		}
		async function Se() {
			if (!U) return;
			let e = { slug: V.value };
			if (await Z.run(async () => {
				e.slug = c(await U.PATCH("/organizations/{organization}/teams/{team}", {
					...H(),
					body: o({
						name: Q.name,
						slug: Q.slug,
						status: Q.status,
						status_reason: u(Q.status_reason)
					})
				})).data.slug;
			})) {
				if (e.slug !== V.value) {
					await z.replace(i(B.value, e.slug));
					return;
				}
				await G();
			}
		}
		async function Ce() {
			U && window.confirm(L("roster.organizations.teams.confirm_delete")) && await $.run(async () => c(await U.DELETE("/organizations/{organization}/teams/{team}", H()))) && await z.push(r(B.value, "teams"));
		}
		return (t, i) => {
			let o = f, s = p, c = ie, l = ne, u = te, m = ee, y = re, x = d;
			return b(), h("div", de, [v(s, null, {
				title: D(() => [g("span", fe, [v(o, {
					to: T(r)(T(B), "teams"),
					icon: "i-lucide-arrow-left",
					color: "neutral",
					variant: "ghost",
					"aria-label": T(K).data.value?.name ?? T(B),
					"data-testid": "roster-team-back"
				}, null, 8, ["to", "aria-label"]), g("span", pe, w(T(W)?.name ?? T(L)("roster.organizations.teams.team")), 1)])]),
				description: D(() => [g("span", O, w(T(K).data.value?.name ?? T(B)), 1)]),
				_: 1
			}), v(x, {
				"screen-key": "roster.team",
				record: T(V)
			}, {
				default: D(() => [v(e, {
					ready: !T(me) && T(W) !== null,
					failed: T(he),
					status: T(ge),
					"not-found": T(L)("roster.organizations.teams.not_found")
				}, {
					default: D(() => [T(W) ? (b(), h("div", k, [v(m, { "data-testid": "roster-team-members" }, {
						header: D(() => [g("h2", A, w(T(L)("roster.organizations.fields.members")), 1)]),
						default: D(() => [g("div", j, [
							v(a, { action: T(Y) }, null, 8, ["action"]),
							T(q).length === 0 ? (b(), h("p", M, w(T(L)("roster.organizations.members.empty")), 1)) : (b(), h("ul", N, [(b(!0), h(ae, null, C(T(q), (e) => (b(), h("li", {
								key: String(e.id),
								class: "flex items-center justify-between gap-2",
								"data-testid": `roster-team-member-${e.id}`
							}, [g("span", null, w(T(n)(e)), 1), v(o, {
								size: "sm",
								variant: "ghost",
								icon: "i-lucide-user-minus",
								loading: T(Y).busy.value,
								"data-testid": `roster-team-member-remove-${e.id}`,
								onClick: (t) => xe(e)
							}, {
								default: D(() => [_(w(T(L)("roster.organizations.members.remove")), 1)]),
								_: 1
							}, 8, [
								"loading",
								"data-testid",
								"onClick"
							])], 8, P))), 128))])),
							v(u, {
								class: "flex flex-wrap items-end gap-2",
								"data-testid": "roster-team-member-add-form",
								onSubmit: be
							}, {
								default: D(() => [v(l, {
									label: T(L)("roster.organizations.members.add"),
									error: T(Y).error("user")
								}, {
									default: D(() => [v(c, {
										modelValue: T(X),
										"onUpdate:modelValue": i[0] ||= (e) => se(X) ? X.value = e : null,
										items: T(ye),
										placeholder: T(L)("roster.organizations.teams.no_candidates"),
										disabled: T(J).length === 0,
										class: "w-64",
										"data-testid": "roster-team-member-candidate"
									}, null, 8, [
										"modelValue",
										"items",
										"placeholder",
										"disabled"
									])]),
									_: 1
								}, 8, ["label", "error"]), v(o, {
									type: "submit",
									icon: "i-lucide-user-plus",
									disabled: T(X) === "",
									loading: T(Y).busy.value,
									"data-testid": "roster-team-member-add"
								}, {
									default: D(() => [_(w(T(L)("roster.organizations.members.add")), 1)]),
									_: 1
								}, 8, ["disabled", "loading"])]),
								_: 1
							})
						])]),
						_: 1
					}), v(m, { "data-testid": "roster-team-settings" }, {
						header: D(() => [g("h2", F, w(T(L)("roster.organizations.settings.title")), 1)]),
						default: D(() => [v(u, {
							state: T(Q),
							class: "flex flex-col gap-4",
							"data-testid": "roster-team-settings-form",
							onSubmit: Se
						}, {
							default: D(() => [
								v(a, { action: T(Z) }, null, 8, ["action"]),
								v(l, {
									label: T(L)("roster.organizations.fields.name"),
									error: T(Z).error("name"),
									required: ""
								}, {
									default: D(() => [v(y, {
										modelValue: T(Q).name,
										"onUpdate:modelValue": i[1] ||= (e) => T(Q).name = e,
										class: "w-full",
										"data-testid": "roster-team-name"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								v(l, {
									label: T(L)("roster.organizations.fields.slug"),
									error: T(Z).error("slug"),
									required: ""
								}, {
									default: D(() => [v(y, {
										modelValue: T(Q).slug,
										"onUpdate:modelValue": i[2] ||= (e) => T(Q).slug = e,
										class: "w-full",
										"data-testid": "roster-team-slug"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								v(l, {
									label: T(L)("roster.organizations.fields.status"),
									error: T(Z).error("status")
								}, {
									default: D(() => [v(c, {
										modelValue: T(Q).status,
										"onUpdate:modelValue": i[3] ||= (e) => T(Q).status = e,
										items: T(ve),
										class: "w-72",
										"data-testid": "roster-team-status"
									}, null, 8, ["modelValue", "items"])]),
									_: 1
								}, 8, ["label", "error"]),
								v(l, {
									label: T(L)("roster.organizations.fields.reason"),
									error: T(Z).error("status_reason")
								}, {
									default: D(() => [v(y, {
										modelValue: T(Q).status_reason,
										"onUpdate:modelValue": i[4] ||= (e) => T(Q).status_reason = e,
										class: "w-80",
										"data-testid": "roster-team-status-reason"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								g("div", null, [v(o, {
									type: "submit",
									icon: "i-lucide-check",
									loading: T(Z).busy.value,
									"data-testid": "roster-team-save"
								}, {
									default: D(() => [_(w(T(L)("roster.organizations.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							]),
							_: 1
						}, 8, ["state"]), g("div", I, [v(a, { action: T($) }, null, 8, ["action"]), g("div", null, [v(o, {
							color: "error",
							icon: "i-lucide-trash",
							loading: T($).busy.value,
							"data-testid": "roster-team-delete",
							onClick: Ce
						}, {
							default: D(() => [_(w(T(L)("roster.organizations.teams.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])])]),
						_: 1
					})])) : oe("", !0)]),
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
export { L as default };
