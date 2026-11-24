import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterProfileFields-BdSCb99p.js";
import { _ as n, a as r, d as i, l as a, m as o, o as s, p as c } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as l } from "@refactor-circus/atrium";
import { UButton as u, UCard as d, UForm as f, UFormField as p, UInput as m, UPageHeader as h, USelect as g } from "@refactor-circus/atrium/ui";
import { computed as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, isRef as C, openBlock as w, reactive as T, ref as E, toDisplayString as D, unref as O, withCtx as k } from "vue";
import { useI18n as A } from "vue-i18n";
import { useRouter as j } from "vue-router";
// src/pages/users/create.vue?vue&type=script&setup=true&lang.ts
var M = {
	class: "space-y-4",
	"data-testid": "roster-user-create"
}, N = { class: "flex items-center gap-3" }, P = { class: "truncate" }, F = /* @__PURE__ */ S({
	__name: "create",
	setup(S) {
		let { t: F } = A(), I = j(), L = c(), R = o(), z = T({
			name: "",
			email: "",
			password: "",
			status: "active"
		}), B = E(s()), V = _(() => ["active", "pending"].map((e) => ({
			label: F(`roster.users.statuses.${e}`),
			value: e
		})));
		async function H() {
			if (!L) return;
			let e = {};
			z.name.trim() !== "" && (e.name = z.name), e.email = z.email, z.password !== "" && (e.password = z.password), e.status = z.status, Object.assign(e, r(B.value));
			let t = { user: null };
			await R.run(async () => {
				t.user = a(await L.POST("/users", { body: i(e) })).data;
			}) && t.user !== null && await I.push(n(t.user.id));
		}
		return (n, r) => {
			let i = u, a = h, o = m, s = p, c = g, _ = f, S = d, T = l;
			return w(), v("div", M, [x(a, null, {
				title: k(() => [y("span", N, [x(i, {
					to: "/roster/users",
					icon: "i-lucide-arrow-left",
					color: "neutral",
					variant: "ghost",
					"aria-label": O(F)("roster.users.title")
				}, null, 8, ["aria-label"]), y("span", P, D(O(F)("roster.users.new")), 1)])]),
				_: 1
			}), x(T, { "screen-key": "roster.user-create" }, {
				default: k(() => [x(S, { class: "max-w-2xl" }, {
					default: k(() => [x(_, {
						state: O(z),
						class: "flex flex-col gap-4",
						"data-testid": "roster-user-create-form",
						onSubmit: H
					}, {
						default: k(() => [
							x(e, { action: O(R) }, null, 8, ["action"]),
							x(s, {
								label: O(F)("roster.users.fields.name"),
								error: O(R).error("name")
							}, {
								default: k(() => [x(o, {
									modelValue: O(z).name,
									"onUpdate:modelValue": r[0] ||= (e) => O(z).name = e,
									class: "w-full",
									"data-testid": "roster-user-name"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							x(s, {
								label: O(F)("roster.users.fields.email"),
								error: O(R).error("email"),
								required: ""
							}, {
								default: k(() => [x(o, {
									modelValue: O(z).email,
									"onUpdate:modelValue": r[1] ||= (e) => O(z).email = e,
									type: "email",
									class: "w-full",
									"data-testid": "roster-user-email"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							x(s, {
								label: O(F)("roster.users.fields.password"),
								hint: O(F)("roster.users.create.password_hint"),
								error: O(R).error("password")
							}, {
								default: k(() => [x(o, {
									modelValue: O(z).password,
									"onUpdate:modelValue": r[2] ||= (e) => O(z).password = e,
									type: "password",
									class: "w-full",
									"data-testid": "roster-user-password"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							x(s, {
								label: O(F)("roster.users.create.initial_status"),
								error: O(R).error("status")
							}, {
								default: k(() => [x(c, {
									modelValue: O(z).status,
									"onUpdate:modelValue": r[3] ||= (e) => O(z).status = e,
									items: O(V),
									class: "w-56",
									"data-testid": "roster-user-status"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							x(t, {
								modelValue: O(B),
								"onUpdate:modelValue": r[4] ||= (e) => C(B) ? B.value = e : null,
								errors: O(R).errors.value
							}, null, 8, ["modelValue", "errors"]),
							y("div", null, [x(i, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: O(R).busy.value,
								"data-testid": "roster-user-create-submit"
							}, {
								default: k(() => [b(D(O(F)("roster.users.create.submit")), 1)]),
								_: 1
							}, 8, ["loading"])])
						]),
						_: 1
					}, 8, ["state"])]),
					_: 1
				})]),
				_: 1
			})]);
		};
	}
});
export { F as default };
