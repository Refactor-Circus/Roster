import { UButton as e } from "@refactor-circus/atrium/ui";
import { Fragment as t, createBlock as n, createElementBlock as r, createTextVNode as i, defineComponent as a, normalizeClass as o, openBlock as s, renderList as c, toDisplayString as l, withCtx as u } from "vue";
// src/components/users/RosterTabs.vue?vue&type=script&setup=true&lang.ts
var d = ["aria-label"], f = /* @__PURE__ */ a({
	__name: "RosterTabs",
	props: {
		tabs: {},
		current: {},
		label: {},
		testid: {}
	},
	emits: ["select"],
	setup(a, { emit: f }) {
		let p = f;
		return (f, m) => {
			let h = e;
			return s(), r("nav", {
				class: "flex flex-wrap gap-1 border-b border-default",
				"aria-label": a.label
			}, [(s(!0), r(t, null, c(a.tabs, (e) => (s(), n(h, {
				key: e.key,
				color: "neutral",
				variant: "link",
				class: o(["-mb-px rounded-none border-b-2", e.key === a.current ? "border-primary text-highlighted" : "border-transparent text-muted"]),
				"aria-current": e.key === a.current ? "page" : void 0,
				"data-testid": `${a.testid}-${e.key}`,
				onClick: (t) => p("select", e.key)
			}, {
				default: u(() => [i(l(e.label), 1)]),
				_: 2
			}, 1032, [
				"class",
				"aria-current",
				"data-testid",
				"onClick"
			]))), 128))], 8, d);
		};
	}
});
export { f as t };
