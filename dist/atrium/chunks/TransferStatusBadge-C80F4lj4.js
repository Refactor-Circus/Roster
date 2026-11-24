import { AtriumErrorCard as e, AtriumNoAccess as t } from "@refactor-circus/atrium";
import { UAlert as n, UBadge as r, USkeleton as i } from "@refactor-circus/atrium/ui";
import { createBlock as a, createTextVNode as o, defineComponent as s, openBlock as c, renderSlot as l, toDisplayString as u, unref as d, withCtx as f } from "vue";
import { useI18n as p } from "vue-i18n";
// src/components/access/AccessState.vue
var m = /* @__PURE__ */ s({
	__name: "AccessState",
	props: {
		ready: { type: Boolean },
		failed: { type: Boolean },
		status: {},
		notFound: { default: void 0 }
	},
	setup(r) {
		let { t: o } = p();
		return (s, u) => {
			let f = t, p = n, m = e, h = i;
			return r.failed && r.status === 403 ? (c(), a(f, { key: 0 })) : r.failed && r.status === 404 ? (c(), a(p, {
				key: 1,
				color: "neutral",
				variant: "subtle",
				icon: "i-lucide-search-x",
				title: r.notFound ?? d(o)("roster.access.not_found"),
				"data-testid": "roster-not-found"
			}, null, 8, ["title"])) : r.failed ? (c(), a(m, {
				key: 2,
				message: d(o)("roster.access.load_failed")
			}, null, 8, ["message"])) : r.ready ? l(s.$slots, "default", {}, void 0, void 0, 3) : (c(), a(h, {
				key: 4,
				class: "h-32",
				"data-testid": "roster-loading"
			}));
		};
	}
}), h = [
	"import_members",
	"import_users",
	"import_teams",
	"import_organizations"
], g = [
	"export_members",
	"export_users",
	"export_organizations"
], _ = [
	"completed",
	"failed",
	"cancelled",
	"expired"
];
function v(e) {
	return `/roster/transfers/${encodeURIComponent(e)}`;
}
function y(e) {
	return e ? `/roster/transfers?organization=${encodeURIComponent(e)}` : "/roster/transfers";
}
function b(e) {
	return _.includes(e);
}
function x(e) {
	switch (e) {
		case "completed": return "success";
		case "awaiting_confirmation": return "warning";
		case "validating":
		case "running": return "info";
		case "failed": return "error";
		default: return "neutral";
	}
}
function S(e) {
	return Object.values(e.values ?? {}).filter((e) => e != null && e !== "").map(String).join(" · ");
}
function C(e, t) {
	let n = e ? /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(e) : null;
	return n?.[1] ? decodeURIComponent(n[1]) : t;
}
function w(e, t) {
	let n = URL.createObjectURL(e), r = document.createElement("a");
	r.href = n, r.download = t, r.rel = "noopener", document.body.appendChild(r);
	try {
		r.click();
	} finally {
		r.remove(), URL.revokeObjectURL(n);
	}
}
// src/components/transfers/TransferStatusBadge.vue
var T = /* @__PURE__ */ s({
	__name: "TransferStatusBadge",
	props: {
		status: {},
		testid: {}
	},
	setup(e) {
		let t = e, { t: n } = p();
		return (e, i) => {
			let s = r;
			return c(), a(s, {
				color: d(x)(t.status),
				variant: "subtle",
				"data-testid": t.testid
			}, {
				default: f(() => [o(u(d(n)(`roster.transfers.statuses.${t.status}`)), 1)]),
				_: 1
			}, 8, ["color", "data-testid"]);
		};
	}
});
export { b as a, v as c, C as i, y as l, g as n, S as o, h as r, w as s, T as t, m as u };
