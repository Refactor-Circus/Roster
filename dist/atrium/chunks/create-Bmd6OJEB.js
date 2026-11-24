import { a as e, o as t } from "./organizations-vLWlhIK9.js";
import { t as n } from "./RosterActionError-DqFXfLSN.js";
import { d as r, l as i, m as a, n as o, p as s } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as c } from "@refactor-circus/atrium";
import { UButton as l, UCard as u, UFormField as d, UInput as f, UTextarea as p } from "@refactor-circus/atrium/ui";
import { computed as m, createElementBlock as h, createElementVNode as g, createTextVNode as _, createVNode as v, defineComponent as y, openBlock as b, reactive as x, toDisplayString as S, unref as C, vModelCheckbox as w, withCtx as T, withDirectives as E, withModifiers as D } from "vue";
import { useI18n as O } from "vue-i18n";
import { useRouter as k } from "vue-router";
// src/pages/organizations/create.vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "roster-organization-create"
}, j = { class: "flex items-center gap-3" }, M = { class: "text-xl font-semibold" }, N = { class: "flex items-center gap-2 text-sm" }, P = /* @__PURE__ */ y({
	__name: "create",
	setup(y) {
		let { t: P } = O(), F = k(), I = s(), L = a(), R = x({
			name: "",
			slug: "",
			owner: "",
			domains: "",
			auto_join: !1
		}), z = m(() => L.error("domains") ?? Object.entries(L.errors.value).find(([e]) => e.startsWith("domains."))?.[1]?.[0]);
		async function B() {
			if (!I) return;
			let n = { slug: null };
			await L.run(async () => {
				n.slug = i(await I.POST("/organizations", { body: r({
					name: R.name,
					slug: o(R.slug),
					owner: o(R.owner),
					domains: e(R.domains),
					auto_join: R.auto_join
				}) })).data.slug;
			}) && n.slug && await F.push(t(n.slug));
		}
		return (e, t) => {
			let r = l, i = f, a = d, o = p, s = u, m = c;
			return b(), h("div", A, [g("div", j, [v(r, {
				to: "/roster/organizations",
				icon: "i-lucide-arrow-left",
				color: "neutral",
				variant: "ghost",
				"aria-label": C(P)("roster.organizations.title")
			}, null, 8, ["aria-label"]), g("h1", M, S(C(P)("roster.organizations.new")), 1)]), v(m, { "screen-key": "roster.organization-create" }, {
				default: T(() => [v(s, { class: "max-w-2xl" }, {
					default: T(() => [g("form", {
						class: "flex flex-col gap-4",
						"data-testid": "roster-organization-create-form",
						onSubmit: D(B, ["prevent"])
					}, [
						v(n, { action: C(L) }, null, 8, ["action"]),
						v(a, {
							label: C(P)("roster.organizations.fields.name"),
							error: C(L).error("name"),
							required: ""
						}, {
							default: T(() => [v(i, {
								modelValue: C(R).name,
								"onUpdate:modelValue": t[0] ||= (e) => C(R).name = e,
								class: "w-full",
								"data-testid": "roster-organization-name"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						v(a, {
							label: C(P)("roster.organizations.fields.slug"),
							hint: C(P)("roster.organizations.slug_hint"),
							error: C(L).error("slug")
						}, {
							default: T(() => [v(i, {
								modelValue: C(R).slug,
								"onUpdate:modelValue": t[1] ||= (e) => C(R).slug = e,
								class: "w-full",
								"data-testid": "roster-organization-slug"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						v(a, {
							label: C(P)("roster.organizations.fields.owner"),
							hint: C(P)("roster.organizations.owner_hint"),
							error: C(L).error("owner")
						}, {
							default: T(() => [v(i, {
								modelValue: C(R).owner,
								"onUpdate:modelValue": t[2] ||= (e) => C(R).owner = e,
								class: "w-full",
								"data-testid": "roster-organization-owner"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						v(a, {
							label: C(P)("roster.organizations.fields.domains"),
							hint: C(P)("roster.organizations.domains_hint"),
							error: C(z)
						}, {
							default: T(() => [v(o, {
								modelValue: C(R).domains,
								"onUpdate:modelValue": t[3] ||= (e) => C(R).domains = e,
								rows: 3,
								class: "w-full",
								"data-testid": "roster-organization-domains"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						g("label", N, [E(g("input", {
							"onUpdate:modelValue": t[4] ||= (e) => C(R).auto_join = e,
							type: "checkbox",
							"data-testid": "roster-organization-auto-join"
						}, null, 512), [[w, C(R).auto_join]]), _(" " + S(C(P)("roster.organizations.fields.auto_join")), 1)]),
						g("div", null, [v(r, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: C(L).busy.value,
							"data-testid": "roster-organization-create-submit"
						}, {
							default: T(() => [_(S(C(P)("roster.organizations.create")), 1)]),
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
export { P as default };
