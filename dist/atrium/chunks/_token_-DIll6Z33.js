import { l as e, r as t } from "./users-JqW-WBif.js";
import { AtriumErrorCard as n, AtriumIntegrationError as r, atriumFetch as i, atriumStatus as a, resolveIntegrationBase as o, useAtrium as s, useAtriumBoot as c, useAtriumShellStore as l, useHead as u } from "@refactor-circus/atrium";
import { UAlert as d, UButton as f, UCard as p, USkeleton as m } from "@refactor-circus/atrium/ui";
import { Fragment as h, createBlock as g, createCommentVNode as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, onMounted as C, openBlock as w, ref as T, shallowRef as E, toDisplayString as D, unref as O, withCtx as k } from "vue";
import { useI18n as A } from "vue-i18n";
import { useRoute as j } from "vue-router";
import M from "openapi-fetch";
function N(e, t, n, r) {
	return o(e || t || "/roster", n, r);
}
function P() {
	let e = c(), t = l().integration("roster")?.api_base;
	return M({
		baseUrl: N(e.integrations.roster?.api_base, t, e.api_base, window.location.origin),
		fetch: i
	});
}
// src/pages/invitations/[token].vue?vue&type=script&setup=true&lang.ts
var F = {
	class: "text-lg font-semibold",
	"data-testid": "roster-invitation-heading"
}, I = {
	key: 6,
	class: "flex flex-col gap-4",
	"data-testid": "roster-invitation-details"
}, L = { class: "grid grid-cols-[auto_1fr] gap-x-4 gap-y-1 text-sm" }, R = { class: "text-muted" }, z = { "data-testid": "roster-invitation-email" }, B = { class: "text-muted" }, V = { "data-testid": "roster-invitation-organization" }, H = { class: "text-muted" }, U = { "data-testid": "roster-invitation-teams" }, W = { class: "text-muted" }, G = { "data-testid": "roster-invitation-expires" }, K = { class: "flex flex-wrap gap-3" }, q = /* @__PURE__ */ S({
	__name: "[token]",
	setup(i) {
		let { t: o } = A(), c = j(), l = s(), S = P();
		u({ title: () => o("roster.organizations.invitation.title") });
		let M = T("loading"), N = E(null), q = T(!1), J = T(null);
		function Y() {
			return String(c.params.token ?? "");
		}
		function X(e) {
			switch (e) {
				case 404: return "unavailable";
				case 429: return "throttled";
				default: return null;
			}
		}
		async function Z() {
			M.value = "loading";
			try {
				N.value = e(await S.GET("/invitations/{token}", { params: { path: { token: Y() } } })).data, M.value = "ready";
			} catch (e) {
				let t = a(e);
				M.value = X(t) ?? (t === 422 ? "unavailable" : "failed");
			}
		}
		async function Q(t) {
			q.value = !0, J.value = null;
			try {
				let n = { token: Y() }, r = t === "accept" ? await S.POST("/invitations/{token}/accept", {
					params: { path: { token: Y() } },
					body: n
				}) : await S.POST("/invitations/{token}/decline", {
					params: { path: { token: Y() } },
					body: n
				});
				if (r.response.status === 401) {
					await l.auth().onUnauthorized?.();
					return;
				}
				e(r), M.value = t === "accept" ? "accepted" : "declined";
			} catch (e) {
				let t = X(a(e));
				if (t) {
					M.value = t;
					return;
				}
				let n = e instanceof r ? e.body : null, i = n?.errors ? Object.values(n.errors)[0]?.[0] : void 0;
				J.value = i ?? (typeof n?.message == "string" ? n.message : o("roster.organizations.invitation.answer_failed"));
			} finally {
				q.value = !1;
			}
		}
		return C(Z), (e, r) => {
			let i = m, a = d, s = n, c = f, l = p;
			return w(), g(l, { "data-testid": "roster-invitation" }, {
				header: k(() => [y("h1", F, D(O(N) && O(M) !== "unavailable" && O(M) !== "throttled" ? O(o)("roster.organizations.invitation.heading", { organization: O(N).organization.name }) : O(o)("roster.organizations.invitation.title")), 1)]),
				default: k(() => [O(M) === "loading" ? (w(), g(i, {
					key: 0,
					class: "h-24",
					"data-testid": "roster-invitation-loading"
				})) : O(M) === "unavailable" ? (w(), g(a, {
					key: 1,
					color: "neutral",
					variant: "subtle",
					icon: "i-lucide-mail-x",
					title: O(o)("roster.organizations.invitation.unavailable"),
					description: O(o)("roster.organizations.invitation.unavailable_hint"),
					"data-testid": "roster-invitation-unavailable"
				}, null, 8, ["title", "description"])) : O(M) === "throttled" ? (w(), g(a, {
					key: 2,
					color: "warning",
					variant: "subtle",
					icon: "i-lucide-timer",
					title: O(o)("roster.organizations.invitation.throttled"),
					description: O(o)("roster.organizations.invitation.throttled_hint"),
					"data-testid": "roster-invitation-throttled"
				}, null, 8, ["title", "description"])) : O(M) === "failed" ? (w(), g(s, {
					key: 3,
					message: O(o)("roster.organizations.invitation.failed")
				}, null, 8, ["message"])) : O(M) === "accepted" ? (w(), g(a, {
					key: 4,
					color: "success",
					variant: "subtle",
					icon: "i-lucide-check",
					title: O(o)("roster.organizations.invitation.accepted", { organization: O(N)?.organization.name ?? "" }),
					"data-testid": "roster-invitation-accepted"
				}, null, 8, ["title"])) : O(M) === "declined" ? (w(), g(a, {
					key: 5,
					color: "neutral",
					variant: "subtle",
					icon: "i-lucide-x",
					title: O(o)("roster.organizations.invitation.declined"),
					"data-testid": "roster-invitation-declined"
				}, null, 8, ["title"])) : O(N) ? (w(), v("div", I, [
					y("p", null, D(O(o)("roster.organizations.invitation.prompt", { organization: O(N).organization.name })), 1),
					y("dl", L, [
						y("dt", R, D(O(o)("roster.organizations.fields.email")), 1),
						y("dd", z, D(O(N).email), 1),
						y("dt", B, D(O(o)("roster.organizations.invitation.organization")), 1),
						y("dd", V, D(O(N).organization.name), 1),
						O(N).teams.length > 0 ? (w(), v(h, { key: 0 }, [y("dt", H, D(O(o)("roster.organizations.fields.teams")), 1), y("dd", U, D(O(N).teams.join(", ")), 1)], 64)) : _("", !0),
						y("dt", W, D(O(o)("roster.organizations.invitation.expires")), 1),
						y("dd", G, D(O(t)(O(N).expires_at)), 1)
					]),
					O(J) ? (w(), g(a, {
						key: 0,
						color: "error",
						variant: "subtle",
						title: O(J),
						"data-testid": "roster-invitation-error"
					}, null, 8, ["title"])) : _("", !0),
					y("div", K, [x(c, {
						icon: "i-lucide-check",
						loading: O(q),
						"data-testid": "roster-invitation-accept",
						onClick: r[0] ||= (e) => Q("accept")
					}, {
						default: k(() => [b(D(O(o)("roster.organizations.invitation.accept")), 1)]),
						_: 1
					}, 8, ["loading"]), x(c, {
						variant: "outline",
						icon: "i-lucide-x",
						loading: O(q),
						"data-testid": "roster-invitation-decline",
						onClick: r[1] ||= (e) => Q("decline")
					}, {
						default: k(() => [b(D(O(o)("roster.organizations.invitation.decline")), 1)]),
						_: 1
					}, 8, ["loading"])])
				])) : _("", !0)]),
				_: 1
			});
		};
	}
});
export { q as default };
