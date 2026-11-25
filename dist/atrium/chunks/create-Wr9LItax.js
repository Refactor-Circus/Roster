import { a as e, o as t } from "./organizations-vLWlhIK9.js";
import { t as n } from "./RosterActionError-DqFXfLSN.js";
import { d as r, l as i, m as a, n as o, p as s } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as c, AtriumPageHeader as l } from "@refactor-circus/atrium";
import { UButton as u, UCard as d, UCheckbox as f, UForm as p, UFormField as m, UInput as h, UTextarea as g } from "@refactor-circus/atrium/ui";
import { computed as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, openBlock as C, reactive as w, toDisplayString as T, unref as E, withCtx as D } from "vue";
import { useI18n as O } from "vue-i18n";
import { useRouter as k } from "vue-router";
// src/pages/organizations/create.vue?vue&type=script&setup=true&lang.ts
var A = {
	class: "space-y-4",
	"data-testid": "roster-organization-create"
}, j = /* @__PURE__ */ S({
	__name: "create",
	setup(S) {
		let { t: j } = O(), M = k(), N = s(), P = a(), F = w({
			name: "",
			slug: "",
			owner: "",
			domains: "",
			auto_join: !1
		}), I = _(() => P.error("domains") ?? Object.entries(P.errors.value).find(([e]) => e.startsWith("domains."))?.[1]?.[0]);
		async function L() {
			if (!N) return;
			let n = { slug: null };
			await P.run(async () => {
				n.slug = i(await N.POST("/organizations", { body: r({
					name: F.name,
					slug: o(F.slug),
					owner: o(F.owner),
					domains: e(F.domains),
					auto_join: F.auto_join
				}) })).data.slug;
			}) && n.slug && await M.push(t(n.slug));
		}
		return (e, t) => {
			let r = l, i = h, a = m, o = g, s = f, _ = u, S = p, w = d, O = c;
			return C(), v("div", A, [x(r, {
				title: E(j)("roster.organizations.new"),
				back: "/roster/organizations",
				"back-label": E(j)("roster.organizations.title")
			}, null, 8, ["title", "back-label"]), x(O, { "screen-key": "roster.organization-create" }, {
				default: D(() => [x(w, { class: "max-w-2xl" }, {
					default: D(() => [x(S, {
						state: E(F),
						class: "flex flex-col gap-4",
						"data-testid": "roster-organization-create-form",
						onSubmit: L
					}, {
						default: D(() => [
							x(n, { action: E(P) }, null, 8, ["action"]),
							x(a, {
								label: E(j)("roster.organizations.fields.name"),
								error: E(P).error("name"),
								required: ""
							}, {
								default: D(() => [x(i, {
									modelValue: E(F).name,
									"onUpdate:modelValue": t[0] ||= (e) => E(F).name = e,
									class: "w-full",
									"data-testid": "roster-organization-name"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							x(a, {
								label: E(j)("roster.organizations.fields.slug"),
								hint: E(j)("roster.organizations.slug_hint"),
								error: E(P).error("slug")
							}, {
								default: D(() => [x(i, {
									modelValue: E(F).slug,
									"onUpdate:modelValue": t[1] ||= (e) => E(F).slug = e,
									class: "w-full",
									"data-testid": "roster-organization-slug"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							x(a, {
								label: E(j)("roster.organizations.fields.owner"),
								hint: E(j)("roster.organizations.owner_hint"),
								error: E(P).error("owner")
							}, {
								default: D(() => [x(i, {
									modelValue: E(F).owner,
									"onUpdate:modelValue": t[2] ||= (e) => E(F).owner = e,
									class: "w-full",
									"data-testid": "roster-organization-owner"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							x(a, {
								label: E(j)("roster.organizations.fields.domains"),
								hint: E(j)("roster.organizations.domains_hint"),
								error: E(I)
							}, {
								default: D(() => [x(o, {
									modelValue: E(F).domains,
									"onUpdate:modelValue": t[3] ||= (e) => E(F).domains = e,
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
							x(s, {
								modelValue: E(F).auto_join,
								"onUpdate:modelValue": t[4] ||= (e) => E(F).auto_join = e,
								label: E(j)("roster.organizations.fields.auto_join"),
								"data-testid": "roster-organization-auto-join"
							}, null, 8, ["modelValue", "label"]),
							y("div", null, [x(_, {
								type: "submit",
								icon: "i-lucide-plus",
								loading: E(P).busy.value,
								"data-testid": "roster-organization-create-submit"
							}, {
								default: D(() => [b(T(E(j)("roster.organizations.create")), 1)]),
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
export { j as default };
