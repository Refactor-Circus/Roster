import { a as e, o as t } from "./organizations-vLWlhIK9.js";
import { t as n } from "./RosterActionError-DqFXfLSN.js";
import { d as r, l as i, m as a, n as o, p as s } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as c } from "@refactor-circus/atrium";
import { UButton as l, UCard as u, UCheckbox as d, UForm as f, UFormField as p, UInput as m, UPageHeader as h, UTextarea as g } from "@refactor-circus/atrium/ui";
import { computed as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, openBlock as C, reactive as w, toDisplayString as T, unref as E, withCtx as D } from "vue";
import { useI18n as O } from "vue-i18n";
import { useRouter as k } from "vue-router";
// src/pages/organizations/create.vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "roster-organization-create"
}, j = { class: "flex items-center gap-3" }, M = { class: "truncate" }, N = /* @__PURE__ */ S({
	__name: "create",
	setup(S) {
		let { t: N } = O(), P = k(), F = s(), I = a(), L = w({
			name: "",
			slug: "",
			owner: "",
			domains: "",
			auto_join: !1
		}), R = _(() => I.error("domains") ?? Object.entries(I.errors.value).find(([e]) => e.startsWith("domains."))?.[1]?.[0]);
		async function z() {
			if (!F) return;
			let n = { slug: null };
			await I.run(async () => {
				n.slug = i(await F.POST("/organizations", { body: r({
					name: L.name,
					slug: o(L.slug),
					owner: o(L.owner),
					domains: e(L.domains),
					auto_join: L.auto_join
				}) })).data.slug;
			}) && n.slug && await P.push(t(n.slug));
		}
		return (e, t) => {
			let r = l, i = h, a = m, o = p, s = g, _ = d, S = f, w = u, O = c;
			return C(), v("div", A, [x(i, null, {
				title: D(() => [y("span", j, [x(r, {
					to: "/roster/organizations",
					icon: "i-lucide-arrow-left",
					color: "neutral",
					variant: "ghost",
					"aria-label": E(N)("roster.organizations.title")
				}, null, 8, ["aria-label"]), y("span", M, T(E(N)("roster.organizations.new")), 1)])]),
				_: 1
			}), x(O, { "screen-key": "roster.organization-create" }, {
				default: D(() => [x(w, { class: "max-w-2xl" }, {
					default: D(() => [x(S, {
						state: E(L),
						class: "flex flex-col gap-4",
						"data-testid": "roster-organization-create-form",
						onSubmit: z
					}, {
						default: D(() => [
							x(n, { action: E(I) }, null, 8, ["action"]),
							x(o, {
								label: E(N)("roster.organizations.fields.name"),
								error: E(I).error("name"),
								required: ""
							}, {
								default: D(() => [x(a, {
									modelValue: E(L).name,
									"onUpdate:modelValue": t[0] ||= (e) => E(L).name = e,
									class: "w-full",
									"data-testid": "roster-organization-name"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							x(o, {
								label: E(N)("roster.organizations.fields.slug"),
								hint: E(N)("roster.organizations.slug_hint"),
								error: E(I).error("slug")
							}, {
								default: D(() => [x(a, {
									modelValue: E(L).slug,
									"onUpdate:modelValue": t[1] ||= (e) => E(L).slug = e,
									class: "w-full",
									"data-testid": "roster-organization-slug"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							x(o, {
								label: E(N)("roster.organizations.fields.owner"),
								hint: E(N)("roster.organizations.owner_hint"),
								error: E(I).error("owner")
							}, {
								default: D(() => [x(a, {
									modelValue: E(L).owner,
									"onUpdate:modelValue": t[2] ||= (e) => E(L).owner = e,
									class: "w-full",
									"data-testid": "roster-organization-owner"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							x(o, {
								label: E(N)("roster.organizations.fields.domains"),
								hint: E(N)("roster.organizations.domains_hint"),
								error: E(R)
							}, {
								default: D(() => [x(s, {
									modelValue: E(L).domains,
									"onUpdate:modelValue": t[3] ||= (e) => E(L).domains = e,
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
							x(_, {
								modelValue: E(L).auto_join,
								"onUpdate:modelValue": t[4] ||= (e) => E(L).auto_join = e,
								label: E(N)("roster.organizations.fields.auto_join"),
								"data-testid": "roster-organization-auto-join"
							}, null, 8, ["modelValue", "label"]),
							y("div", null, [x(r, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: E(I).busy.value,
								"data-testid": "roster-organization-create-submit"
							}, {
								default: D(() => [b(T(E(N)("roster.organizations.create")), 1)]),
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
