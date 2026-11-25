import { l as e, r as t } from "./users-JqW-WBif.js";
import { AtriumDetails as n, AtriumErrorCard as r, AtriumIntegrationError as i, atriumFetch as a, atriumStatus as o, resolveIntegrationBase as s, useAtrium as c, useAtriumBoot as l, useAtriumShellStore as u, useHead as d } from "@refactor-circus/atrium";
import { UAlert as f, UButton as p, UCard as m, USkeleton as h } from "@refactor-circus/atrium/ui";
import { createBlock as g, createCommentVNode as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, onMounted as C, openBlock as w, ref as T, shallowRef as E, toDisplayString as D, unref as O, withCtx as k } from "vue";
import { useI18n as A } from "vue-i18n";
import { useRoute as j } from "vue-router";
import M from "openapi-fetch";
function N(e, t, n, r) {
	return s(e || t || "/roster", n, r);
}
function P() {
	let e = l(), t = u().integration("roster")?.api_base;
	return M({
		baseUrl: N(e.integrations.roster?.api_base, t, e.api_base, window.location.origin),
		fetch: a
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
}, L = { class: "flex flex-wrap gap-3" }, R = /* @__PURE__ */ S({
	__name: "[token]",
	setup(a) {
		let { t: s } = A(), l = j(), u = c(), S = P();
		d({ title: () => s("roster.organizations.invitation.title") });
		let M = T("loading"), N = E(null), R = T(!1), z = T(null);
		function B() {
			return String(l.params.token ?? "");
		}
		function V(e) {
			return [
				{
					label: s("roster.organizations.fields.email"),
					value: e.email,
					testid: "roster-invitation-email"
				},
				{
					label: s("roster.organizations.invitation.organization"),
					value: e.organization.name,
					testid: "roster-invitation-organization"
				},
				...e.teams.length > 0 ? [{
					label: s("roster.organizations.fields.teams"),
					value: e.teams.join(", "),
					testid: "roster-invitation-teams"
				}] : [],
				{
					label: s("roster.organizations.invitation.expires"),
					value: t(e.expires_at),
					testid: "roster-invitation-expires"
				}
			];
		}
		function H(e) {
			switch (e) {
				case 404: return "unavailable";
				case 429: return "throttled";
				default: return null;
			}
		}
		async function U() {
			M.value = "loading";
			try {
				N.value = e(await S.GET("/invitations/{token}", { params: { path: { token: B() } } })).data, M.value = "ready";
			} catch (e) {
				let t = o(e);
				M.value = H(t) ?? (t === 422 ? "unavailable" : "failed");
			}
		}
		async function W(t) {
			R.value = !0, z.value = null;
			try {
				let n = { token: B() }, r = t === "accept" ? await S.POST("/invitations/{token}/accept", {
					params: { path: { token: B() } },
					body: n
				}) : await S.POST("/invitations/{token}/decline", {
					params: { path: { token: B() } },
					body: n
				});
				if (r.response.status === 401) {
					await u.auth().onUnauthorized?.();
					return;
				}
				e(r), M.value = t === "accept" ? "accepted" : "declined";
			} catch (e) {
				let t = H(o(e));
				if (t) {
					M.value = t;
					return;
				}
				let n = e instanceof i ? e.body : null, r = n?.errors ? Object.values(n.errors)[0]?.[0] : void 0;
				z.value = r ?? (typeof n?.message == "string" ? n.message : s("roster.organizations.invitation.answer_failed"));
			} finally {
				R.value = !1;
			}
		}
		return C(U), (e, t) => {
			let i = h, a = f, o = r, c = n, l = p, u = m;
			return w(), g(u, { "data-testid": "roster-invitation" }, {
				header: k(() => [y("h1", F, D(O(N) && O(M) !== "unavailable" && O(M) !== "throttled" ? O(s)("roster.organizations.invitation.heading", { organization: O(N).organization.name }) : O(s)("roster.organizations.invitation.title")), 1)]),
				default: k(() => [O(M) === "loading" ? (w(), g(i, {
					key: 0,
					class: "h-24",
					"data-testid": "roster-invitation-loading"
				})) : O(M) === "unavailable" ? (w(), g(a, {
					key: 1,
					color: "neutral",
					variant: "subtle",
					icon: "i-lucide-mail-x",
					title: O(s)("roster.organizations.invitation.unavailable"),
					description: O(s)("roster.organizations.invitation.unavailable_hint"),
					"data-testid": "roster-invitation-unavailable"
				}, null, 8, ["title", "description"])) : O(M) === "throttled" ? (w(), g(a, {
					key: 2,
					color: "warning",
					variant: "subtle",
					icon: "i-lucide-timer",
					title: O(s)("roster.organizations.invitation.throttled"),
					description: O(s)("roster.organizations.invitation.throttled_hint"),
					"data-testid": "roster-invitation-throttled"
				}, null, 8, ["title", "description"])) : O(M) === "failed" ? (w(), g(o, {
					key: 3,
					message: O(s)("roster.organizations.invitation.failed")
				}, null, 8, ["message"])) : O(M) === "accepted" ? (w(), g(a, {
					key: 4,
					color: "success",
					variant: "subtle",
					icon: "i-lucide-check",
					title: O(s)("roster.organizations.invitation.accepted", { organization: O(N)?.organization.name ?? "" }),
					"data-testid": "roster-invitation-accepted"
				}, null, 8, ["title"])) : O(M) === "declined" ? (w(), g(a, {
					key: 5,
					color: "neutral",
					variant: "subtle",
					icon: "i-lucide-x",
					title: O(s)("roster.organizations.invitation.declined"),
					"data-testid": "roster-invitation-declined"
				}, null, 8, ["title"])) : O(N) ? (w(), v("div", I, [
					y("p", null, D(O(s)("roster.organizations.invitation.prompt", { organization: O(N).organization.name })), 1),
					x(c, { items: V(O(N)) }, null, 8, ["items"]),
					O(z) ? (w(), g(a, {
						key: 0,
						color: "error",
						variant: "subtle",
						title: O(z),
						"data-testid": "roster-invitation-error"
					}, null, 8, ["title"])) : _("", !0),
					y("div", L, [x(l, {
						icon: "i-lucide-check",
						loading: O(R),
						"data-testid": "roster-invitation-accept",
						onClick: t[0] ||= (e) => W("accept")
					}, {
						default: k(() => [b(D(O(s)("roster.organizations.invitation.accept")), 1)]),
						_: 1
					}, 8, ["loading"]), x(l, {
						variant: "outline",
						icon: "i-lucide-x",
						loading: O(R),
						"data-testid": "roster-invitation-decline",
						onClick: t[1] ||= (e) => W("decline")
					}, {
						default: k(() => [b(D(O(s)("roster.organizations.invitation.decline")), 1)]),
						_: 1
					}, 8, ["loading"])])
				])) : _("", !0)]),
				_: 1
			});
		};
	}
});
export { R as default };
