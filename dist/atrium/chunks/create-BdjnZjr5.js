import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterProfileFields-BdSCb99p.js";
import { _ as n, a as r, d as i, l as a, m as o, o as s, p as c } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as l, AtriumPageHeader as u } from "@refactor-circus/atrium";
import { UButton as d, UCard as f, UForm as p, UFormField as m, UInput as h, USelect as g } from "@refactor-circus/atrium/ui";
import { computed as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, isRef as C, openBlock as w, reactive as T, ref as E, toDisplayString as D, unref as O, withCtx as k } from "vue";
import { useI18n as A } from "vue-i18n";
import { useRouter as j } from "vue-router";
// src/pages/users/create.vue?vue&type=script&setup=true&lang.ts
var M = {
	class: "space-y-4",
	"data-testid": "roster-user-create"
}, N = /* @__PURE__ */ S({
	__name: "create",
	setup(S) {
		let { t: N } = A(), P = j(), F = c(), I = o(), L = T({
			name: "",
			email: "",
			password: "",
			status: "active"
		}), R = E(s()), z = _(() => ["active", "pending"].map((e) => ({
			label: N(`roster.users.statuses.${e}`),
			value: e
		})));
		async function B() {
			if (!F) return;
			let e = {};
			L.name.trim() !== "" && (e.name = L.name), e.email = L.email, L.password !== "" && (e.password = L.password), e.status = L.status, Object.assign(e, r(R.value));
			let t = { user: null };
			await I.run(async () => {
				t.user = a(await F.POST("/users", { body: i(e) })).data;
			}) && t.user !== null && await P.push(n(t.user.id));
		}
		return (n, r) => {
			let i = u, a = h, o = m, s = g, c = d, _ = p, S = f, T = l;
			return w(), v("div", M, [x(i, {
				title: O(N)("roster.users.new"),
				back: "/roster/users",
				"back-label": O(N)("roster.users.title")
			}, null, 8, ["title", "back-label"]), x(T, { "screen-key": "roster.user-create" }, {
				default: k(() => [x(S, { class: "max-w-2xl" }, {
					default: k(() => [x(_, {
						state: O(L),
						class: "flex flex-col gap-4",
						"data-testid": "roster-user-create-form",
						onSubmit: B
					}, {
						default: k(() => [
							x(e, { action: O(I) }, null, 8, ["action"]),
							x(o, {
								label: O(N)("roster.users.fields.name"),
								error: O(I).error("name")
							}, {
								default: k(() => [x(a, {
									modelValue: O(L).name,
									"onUpdate:modelValue": r[0] ||= (e) => O(L).name = e,
									class: "w-full",
									"data-testid": "roster-user-name"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							x(o, {
								label: O(N)("roster.users.fields.email"),
								error: O(I).error("email"),
								required: ""
							}, {
								default: k(() => [x(a, {
									modelValue: O(L).email,
									"onUpdate:modelValue": r[1] ||= (e) => O(L).email = e,
									type: "email",
									class: "w-full",
									"data-testid": "roster-user-email"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							x(o, {
								label: O(N)("roster.users.fields.password"),
								hint: O(N)("roster.users.create.password_hint"),
								error: O(I).error("password")
							}, {
								default: k(() => [x(a, {
									modelValue: O(L).password,
									"onUpdate:modelValue": r[2] ||= (e) => O(L).password = e,
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
							x(o, {
								label: O(N)("roster.users.create.initial_status"),
								error: O(I).error("status")
							}, {
								default: k(() => [x(s, {
									modelValue: O(L).status,
									"onUpdate:modelValue": r[3] ||= (e) => O(L).status = e,
									items: O(z),
									class: "w-56",
									"data-testid": "roster-user-status"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							x(t, {
								modelValue: O(R),
								"onUpdate:modelValue": r[4] ||= (e) => C(R) ? R.value = e : null,
								errors: O(I).errors.value
							}, null, 8, ["modelValue", "errors"]),
							y("div", null, [x(c, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: O(I).busy.value,
								"data-testid": "roster-user-create-submit"
							}, {
								default: k(() => [b(D(O(N)("roster.users.create.submit")), 1)]),
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
export { N as default };
