import { UFormField as e, UInput as t, UTextarea as n } from "@refactor-circus/atrium/ui";
import { Fragment as r, createElementBlock as i, createVNode as a, defineComponent as o, mergeModels as s, openBlock as c, renderList as l, unref as u, useModel as d, withCtx as f } from "vue";
import { useI18n as p } from "vue-i18n";
// src/components/users/RosterProfileFields.vue
var m = /* @__PURE__ */ o({
	__name: "RosterProfileFields",
	props: /*@__PURE__*/ s({ errors: {} }, {
		modelValue: { required: !0 },
		modelModifiers: {}
	}),
	emits: ["update:modelValue"],
	setup(o) {
		let s = d(o, "modelValue"), { t: m } = p(), h = [
			"display_name",
			"avatar_url",
			"timezone",
			"locale"
		];
		return (d, p) => {
			let g = t, _ = e, v = n;
			return c(), i(r, null, [(c(), i(r, null, l(h, (e) => a(_, {
				key: e,
				label: u(m)(`roster.users.profile.${e}`),
				error: o.errors[e]?.[0]
			}, {
				default: f(() => [a(g, {
					modelValue: s.value[e],
					"onUpdate:modelValue": (t) => s.value[e] = t,
					type: e === "avatar_url" ? "url" : "text",
					class: "w-full",
					"data-testid": `roster-profile-${e}`
				}, null, 8, [
					"modelValue",
					"onUpdate:modelValue",
					"type",
					"data-testid"
				])]),
				_: 2
			}, 1032, ["label", "error"])), 64)), a(_, {
				label: u(m)("roster.users.profile.bio"),
				error: o.errors.bio?.[0]
			}, {
				default: f(() => [a(v, {
					modelValue: s.value.bio,
					"onUpdate:modelValue": p[0] ||= (e) => s.value.bio = e,
					class: "w-full",
					"data-testid": "roster-profile-bio"
				}, null, 8, ["modelValue"])]),
				_: 1
			}, 8, ["label", "error"])], 64);
		};
	}
});
export { m as t };
