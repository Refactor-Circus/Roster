import { UButton as e } from "@refactor-circus/atrium/ui";
import { createCommentVNode as t, createElementBlock as n, createElementVNode as r, createTextVNode as i, createVNode as a, defineComponent as o, openBlock as s, toDisplayString as c, unref as l, withCtx as u } from "vue";
import { useI18n as d } from "vue-i18n";
import { useRoute as f, useRouter as p } from "vue-router";
// src/components/users/RosterPager.vue?vue&type=script&setup=true&lang.ts
var m = {
	key: 0,
	class: "flex items-center gap-3 text-sm"
}, h = ["data-testid"], g = /* @__PURE__ */ o({
	__name: "RosterPager",
	props: {
		current: {},
		last: {},
		testid: {}
	},
	setup(o) {
		let g = o, { t: _ } = d(), v = f(), y = p();
		function b(e) {
			y.replace({ query: {
				...v.query,
				page: e > 1 ? String(e) : void 0
			} });
		}
		return (d, f) => {
			let p = e;
			return g.last > 1 ? (s(), n("div", m, [
				a(p, {
					variant: "outline",
					size: "sm",
					icon: "i-lucide-chevron-left",
					disabled: g.current <= 1,
					"data-testid": `${o.testid}-prev`,
					onClick: f[0] ||= (e) => b(g.current - 1)
				}, {
					default: u(() => [i(c(l(_)("roster.users.pager.previous")), 1)]),
					_: 1
				}, 8, ["disabled", "data-testid"]),
				r("span", {
					class: "text-muted",
					"data-testid": `${o.testid}-page`
				}, c(l(_)("roster.users.pager.page", {
					current: g.current,
					last: g.last
				})), 9, h),
				a(p, {
					variant: "outline",
					size: "sm",
					"trailing-icon": "i-lucide-chevron-right",
					disabled: g.current >= g.last,
					"data-testid": `${o.testid}-next`,
					onClick: f[1] ||= (e) => b(g.current + 1)
				}, {
					default: u(() => [i(c(l(_)("roster.users.pager.next")), 1)]),
					_: 1
				}, 8, ["disabled", "data-testid"])
			])) : t("", !0);
		};
	}
});
export { g as t };
