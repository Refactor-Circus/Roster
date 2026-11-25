import { AtriumIntegrationError as e, atriumStatus as t, useAtriumIntegration as n } from "@refactor-circus/atrium";
import { ref as r, shallowRef as i, watch as a } from "vue";
// src/components/users/users.ts
var o = [
	"active",
	"pending",
	"suspended",
	"deactivated"
];
function s(e) {
	return {
		display_name: e?.display_name ?? "",
		avatar_url: e?.avatar_url ?? "",
		timezone: e?.timezone ?? "",
		locale: e?.locale ?? "",
		bio: e?.bio ?? ""
	};
}
function c(e) {
	return {
		display_name: l(e.display_name),
		avatar_url: l(e.avatar_url),
		timezone: l(e.timezone),
		locale: l(e.locale),
		bio: l(e.bio)
	};
}
function l(e) {
	let t = (e ?? "").trim();
	return t === "" ? null : t;
}
function u(e) {
	return e && (("profile" in e ? e.profile?.display_name : null) || e.name || e.email) || null;
}
function d(e) {
	return `/roster/users/${encodeURIComponent(String(e))}`;
}
function f(e) {
	if (!e) return null;
	let t = new Date(e);
	return Number.isNaN(t.getTime()) ? e : t.toLocaleString();
}
function p(e) {
	let t = Array.isArray(e) ? e[0] : e;
	return typeof t == "string" && t !== "" ? t : void 0;
}
function m(e) {
	let t = Number(p(e));
	return Number.isInteger(t) && t > 1 ? t : 1;
}
function h(e) {
	switch (e) {
		case "active": return "success";
		case "pending": return "info";
		case "suspended": return "warning";
		case "deactivated": return "error";
		default: return "neutral";
	}
}
function g(e) {
	return e;
}
function _() {
	return n("roster").client;
}
function v(t) {
	if (!t.response.ok) throw new e(t.response.status, t.error);
	return t.data;
}
function y(e, n) {
	let o = _(), s = i(null), c = r(!0), l = r(!1), u = r(null);
	async function d() {
		if (!o) c.value = !1;
		else {
			c.value = s.value === null;
			try {
				s.value = await e(o), l.value = !1, u.value = null;
			} catch (e) {
				l.value = !0, u.value = t(e);
			} finally {
				c.value = !1;
			}
		}
	}
	return n && a(n, () => void d(), { deep: !0 }), d(), {
		client: o,
		data: s,
		pending: c,
		failed: l,
		status: u,
		reload: d
	};
}
function b(e) {
	let t = _(), n = i([]);
	async function r(e) {
		if (!t || !e) n.value = [];
		else try {
			n.value = v(await t.GET("/organizations/{organization}/teams", { params: {
				path: { organization: e },
				query: { per_page: 100 }
			} })).data;
		} catch {
			n.value = [];
		}
	}
	return a(e, (e) => void r(e), { immediate: !0 }), n;
}
function x(e) {
	return {
		current: e?.meta?.current_page ?? 1,
		last: e?.meta?.last_page ?? 1
	};
}
function S() {
	let n = r(!1), i = r({}), a = r(!1), o = r(null);
	async function s(r) {
		n.value = !0, i.value = {}, a.value = !1, o.value = null;
		try {
			return await r(), !0;
		} catch (n) {
			let r = t(n), s = n instanceof e ? n.body : null;
			return r === 403 ? a.value = !0 : r === 422 && s?.errors ? i.value = s.errors : o.value = typeof s?.message == "string" ? s.message : n instanceof Error ? n.message : String(n), !1;
		} finally {
			n.value = !1;
		}
	}
	function c(e) {
		return i.value[e]?.[0];
	}
	return {
		busy: n,
		errors: i,
		forbidden: a,
		message: o,
		run: s,
		error: c
	};
}
export { d as _, c as a, p as c, g as d, b as f, u as g, y as h, x as i, v as l, S as m, l as n, s as o, _ as p, f as r, m as s, o as t, h as u };
