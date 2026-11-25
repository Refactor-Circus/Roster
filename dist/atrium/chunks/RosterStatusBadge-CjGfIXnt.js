import { u as e } from "./users-JqW-WBif.js";
import { UBadge as t } from "@refactor-circus/atrium/ui";
import { createBlock as n, createTextVNode as r, defineComponent as i, openBlock as a, toDisplayString as o, unref as s, withCtx as c } from "vue";
import { useI18n as l } from "vue-i18n";
// src/components/users/RosterStatusBadge.vue
var u = /* @__PURE__ */ i({
	__name: "RosterStatusBadge",
	props: { status: {} },
	setup(i) {
		let { t: u, te: d } = l();
		return (l, f) => {
			let p = t;
			return a(), n(p, {
				color: s(e)(i.status),
				variant: "subtle",
				"data-status": i.status
			}, {
				default: c(() => [r(o(s(d)(`roster.users.statuses.${i.status}`) ? s(u)(`roster.users.statuses.${i.status}`) : i.status), 1)]),
				_: 1
			}, 8, ["color", "data-status"]);
		};
	}
});
export { u as t };
