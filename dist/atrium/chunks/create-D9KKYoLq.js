import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterProfileFields-BdSCb99p.js";
import { _ as n, a as r, d as i, l as a, m as o, o as s, p as c } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as l } from "@refactor-circus/atrium";
import { UButton as u, UCard as d, UFormField as f, UInput as p } from "@refactor-circus/atrium/ui";
import { createElementBlock as m, createElementVNode as h, createTextVNode as g, createVNode as _, defineComponent as v, isRef as y, openBlock as b, reactive as x, ref as S, toDisplayString as C, unref as w, vModelSelect as T, withCtx as E, withDirectives as D, withModifiers as O } from "vue";
import { useI18n as k } from "vue-i18n";
import { useRouter as A } from "vue-router";
// src/pages/users/create.vue?vue&type=script&setup=true&lang.ts
var j = {
	class: "space-y-4",
	"data-testid": "roster-user-create"
}, M = { class: "flex items-center gap-3" }, N = { class: "text-xl font-semibold" }, P = { value: "active" }, F = { value: "pending" }, I = /* @__PURE__ */ v({
	__name: "create",
	setup(v) {
		let { t: I } = k(), L = A(), R = c(), z = o(), B = x({
			name: "",
			email: "",
			password: "",
			status: "active"
		}), V = S(s());
		async function H() {
			if (!R) return;
			let e = {};
			B.name.trim() !== "" && (e.name = B.name), e.email = B.email, B.password !== "" && (e.password = B.password), e.status = B.status, Object.assign(e, r(V.value));
			let t = { user: null };
			await z.run(async () => {
				t.user = a(await R.POST("/users", { body: i(e) })).data;
			}) && t.user !== null && await L.push(n(t.user.id));
		}
		return (n, r) => {
			let i = u, a = p, o = f, s = d, c = l;
			return b(), m("div", j, [h("div", M, [_(i, {
				to: "/roster/users",
				icon: "i-lucide-arrow-left",
				color: "neutral",
				variant: "ghost",
				"aria-label": w(I)("roster.users.title")
			}, null, 8, ["aria-label"]), h("h1", N, C(w(I)("roster.users.new")), 1)]), _(c, { "screen-key": "roster.user-create" }, {
				default: E(() => [_(s, { class: "max-w-2xl" }, {
					default: E(() => [h("form", {
						class: "flex flex-col gap-4",
						"data-testid": "roster-user-create-form",
						onSubmit: O(H, ["prevent"])
					}, [
						_(e, { action: w(z) }, null, 8, ["action"]),
						_(o, {
							label: w(I)("roster.users.fields.name"),
							error: w(z).error("name")
						}, {
							default: E(() => [_(a, {
								modelValue: w(B).name,
								"onUpdate:modelValue": r[0] ||= (e) => w(B).name = e,
								class: "w-full",
								"data-testid": "roster-user-name"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						_(o, {
							label: w(I)("roster.users.fields.email"),
							error: w(z).error("email"),
							required: ""
						}, {
							default: E(() => [_(a, {
								modelValue: w(B).email,
								"onUpdate:modelValue": r[1] ||= (e) => w(B).email = e,
								type: "email",
								class: "w-full",
								"data-testid": "roster-user-email"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						_(o, {
							label: w(I)("roster.users.fields.password"),
							hint: w(I)("roster.users.create.password_hint"),
							error: w(z).error("password")
						}, {
							default: E(() => [_(a, {
								modelValue: w(B).password,
								"onUpdate:modelValue": r[2] ||= (e) => w(B).password = e,
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
						_(o, {
							label: w(I)("roster.users.create.initial_status"),
							error: w(z).error("status")
						}, {
							default: E(() => [D(h("select", {
								"onUpdate:modelValue": r[3] ||= (e) => w(B).status = e,
								class: "w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "roster-user-status"
							}, [h("option", P, C(w(I)("roster.users.statuses.active")), 1), h("option", F, C(w(I)("roster.users.statuses.pending")), 1)], 512), [[T, w(B).status]])]),
							_: 1
						}, 8, ["label", "error"]),
						_(t, {
							modelValue: w(V),
							"onUpdate:modelValue": r[4] ||= (e) => y(V) ? V.value = e : null,
							errors: w(z).errors.value
						}, null, 8, ["modelValue", "errors"]),
						h("div", null, [_(i, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: w(z).busy.value,
							"data-testid": "roster-user-create-submit"
						}, {
							default: E(() => [g(C(w(I)("roster.users.create.submit")), 1)]),
							_: 1
						}, 8, ["loading"])])
					], 32)]),
					_: 1
				})]),
				_: 1
			})]);
		};
	}
});
export { I as default };
