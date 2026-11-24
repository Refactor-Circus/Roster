import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterPager-CFRR10Qn.js";
import { _ as n, c as r, d as i, f as a, h as o, i as s, l as c, m as l, n as u, r as d, s as f } from "./users-JqW-WBif.js";
import { t as p } from "./OrganizationState-GrCgPjha.js";
import { a as m, c as h, l as g, n as _, o as v, r as y, s as b, t as x, u as S } from "./organizations-vLWlhIK9.js";
import { t as C } from "./RosterStatusBadge-CjGfIXnt.js";
import { AtriumIntegrationScreen as w } from "@refactor-circus/atrium";
import { UAlert as T, UBadge as E, UButton as D, UCard as O, UFormField as k, UInput as A, UTextarea as j } from "@refactor-circus/atrium/ui";
import { Fragment as M, computed as N, createBlock as P, createCommentVNode as F, createElementBlock as I, createElementVNode as L, createTextVNode as R, createVNode as z, defineComponent as B, isRef as ee, normalizeClass as V, openBlock as H, reactive as U, ref as W, renderList as G, resolveComponent as K, toDisplayString as q, unref as J, vModelCheckbox as te, vModelSelect as ne, watch as re, withCtx as Y, withDirectives as X, withModifiers as Z } from "vue";
import { useI18n as Q } from "vue-i18n";
import { useRoute as $, useRouter as ie } from "vue-router";
// src/components/organizations/OrganizationInvitations.vue?vue&type=script&setup=true&lang.ts
var ae = {
	class: "flex flex-col gap-4",
	"data-testid": "roster-organization-invitations"
}, oe = { class: "font-semibold" }, se = {
	key: 0,
	class: "flex flex-wrap gap-4"
}, ce = { class: "mb-1 text-sm font-medium" }, le = [
	"value",
	"checked",
	"data-testid",
	"onChange"
], ue = { class: "space-y-4" }, de = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "roster-invitations-empty"
}, fe = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "roster-invitations-table"
}, pe = { class: "text-muted" }, me = { class: "py-2 pr-3 font-medium" }, he = { class: "py-2 pr-3 font-medium" }, ge = { class: "py-2 pr-3 font-medium" }, _e = { class: "py-2 text-right font-medium" }, ve = { class: "divide-y divide-default" }, ye = ["data-testid"], be = { class: "py-2 pr-3" }, xe = { class: "py-2 pr-3" }, Se = { class: "py-2 pr-3" }, Ce = { class: "py-2" }, we = { class: "flex justify-end" }, Te = /* @__PURE__ */ B({
	__name: "OrganizationInvitations",
	props: {
		client: {},
		organization: {}
	},
	setup(n) {
		let r = n, { t: i, te: u } = Q(), m = $(), g = N(() => r.organization.slug), _ = N(() => f(m.query.page)), v = o(async (e) => c(await e.GET("/organizations/{organization}/invitations", { params: {
			path: { organization: g.value },
			query: _.value > 1 ? { page: _.value } : {}
		} })), _), y = N(() => v.data.value?.data ?? []), b = N(() => s(v.data.value)), x = a(() => g.value), S = l(), C = U({
			email: "",
			teams: []
		}), w = l();
		function T(e, t) {
			C.teams = t ? [...C.teams.filter((t) => t !== e), e] : C.teams.filter((t) => t !== e);
		}
		async function j() {
			await S.run(async () => c(await r.client.POST("/organizations/{organization}/invitations", {
				params: { path: { organization: g.value } },
				body: {
					email: C.email.trim(),
					teams: C.teams
				}
			}))) && (C.email = "", C.teams = [], await v.reload());
		}
		async function B(e) {
			await w.run(async () => c(await r.client.DELETE("/organizations/{organization}/invitations/{invitation}", { params: { path: {
				organization: g.value,
				invitation: e.id
			} } }))) && await v.reload();
		}
		return (n, r) => {
			let a = A, o = k, s = D, c = O, l = E;
			return H(), I("div", ae, [
				z(c, null, {
					header: Y(() => [L("h2", oe, q(J(i)("roster.organizations.invitations.invite")), 1)]),
					default: Y(() => [L("form", {
						class: "flex max-w-2xl flex-col gap-3",
						"data-testid": "roster-invite-form",
						onSubmit: Z(j, ["prevent"])
					}, [
						z(e, { action: J(S) }, null, 8, ["action"]),
						z(o, {
							label: J(i)("roster.organizations.fields.email"),
							error: J(S).error("email"),
							required: ""
						}, {
							default: Y(() => [z(a, {
								modelValue: J(C).email,
								"onUpdate:modelValue": r[0] ||= (e) => J(C).email = e,
								type: "email",
								class: "w-80",
								"data-testid": "roster-invite-email"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						J(x).length > 0 ? (H(), I("fieldset", se, [L("legend", ce, q(J(i)("roster.organizations.fields.teams")), 1), (H(!0), I(M, null, G(J(x), (e) => (H(), I("label", {
							key: e.slug,
							class: "flex items-center gap-2 text-sm"
						}, [L("input", {
							type: "checkbox",
							value: e.slug,
							checked: J(C).teams.includes(e.slug),
							"data-testid": `roster-invite-team-${e.slug}`,
							onChange: (t) => T(e.slug, t.target.checked)
						}, null, 40, le), R(" " + q(e.name), 1)]))), 128))])) : F("", !0),
						L("div", null, [z(s, {
							type: "submit",
							icon: "i-lucide-send",
							loading: J(S).busy.value,
							"data-testid": "roster-invite-send"
						}, {
							default: Y(() => [R(q(J(i)("roster.organizations.invitations.invite")), 1)]),
							_: 1
						}, 8, ["loading"])])
					], 32)]),
					_: 1
				}),
				z(e, { action: J(w) }, null, 8, ["action"]),
				z(p, {
					ready: !J(v).pending.value && J(v).data.value !== null,
					failed: J(v).failed.value,
					status: J(v).status.value,
					compact: ""
				}, {
					default: Y(() => [L("div", ue, [J(y).length === 0 ? (H(), I("p", de, q(J(i)("roster.organizations.invitations.empty")), 1)) : (H(), I("table", fe, [L("thead", pe, [L("tr", null, [
						L("th", me, q(J(i)("roster.organizations.fields.email")), 1),
						L("th", he, q(J(i)("roster.organizations.fields.status")), 1),
						L("th", ge, q(J(i)("roster.organizations.invitations.expires")), 1),
						L("th", _e, q(J(i)("roster.organizations.fields.actions")), 1)
					])]), L("tbody", ve, [(H(!0), I(M, null, G(J(y), (e) => (H(), I("tr", {
						key: e.id,
						"data-testid": `roster-invitation-row-${e.id}`
					}, [
						L("td", be, q(e.email), 1),
						L("td", xe, [z(l, {
							color: J(h)(e.status),
							variant: "subtle",
							"data-status": e.status
						}, {
							default: Y(() => [R(q(J(u)(`roster.organizations.invitations.statuses.${e.status}`) ? J(i)(`roster.organizations.invitations.statuses.${e.status}`) : e.status), 1)]),
							_: 2
						}, 1032, ["color", "data-status"])]),
						L("td", Se, q(J(d)(e.expires_at)), 1),
						L("td", Ce, [L("div", we, [e.status === "pending" ? (H(), P(s, {
							key: 0,
							size: "sm",
							variant: "ghost",
							icon: "i-lucide-ban",
							loading: J(w).busy.value,
							"data-testid": `roster-invitation-revoke-${e.id}`,
							onClick: (t) => B(e)
						}, {
							default: Y(() => [R(q(J(i)("roster.organizations.invitations.revoke")), 1)]),
							_: 1
						}, 8, [
							"loading",
							"data-testid",
							"onClick"
						])) : F("", !0)])])
					], 8, ye))), 128))])])), z(t, {
						current: J(b).current,
						last: J(b).last,
						testid: "roster-invitations"
					}, null, 8, ["current", "last"])])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])
			]);
		};
	}
}), Ee = {
	class: "flex flex-col gap-4",
	"data-testid": "roster-organization-members"
}, De = { class: "font-semibold" }, Oe = { class: "space-y-4" }, ke = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "roster-members-empty"
}, Ae = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "roster-members-table"
}, je = { class: "text-muted" }, Me = { class: "py-2 pr-3 font-medium" }, Ne = { class: "py-2 pr-3 font-medium" }, Pe = { class: "py-2 pr-3 font-medium" }, Fe = { class: "py-2 pr-3 font-medium" }, Ie = { class: "py-2 text-right font-medium" }, Le = { class: "divide-y divide-default" }, Re = ["data-testid"], ze = { class: "py-2 pr-3" }, Be = { class: "py-2 pr-3" }, Ve = { class: "py-2 pr-3" }, He = { class: "py-2 pr-3" }, Ue = { class: "py-2" }, We = {
	key: 0,
	class: "flex justify-end gap-2"
}, Ge = /* @__PURE__ */ B({
	__name: "OrganizationMembers",
	props: {
		client: {},
		organization: {}
	},
	emits: ["changed"],
	setup(r, { emit: i }) {
		let a = r, u = i, { t: d } = Q(), m = $(), h = N(() => a.organization.slug), _ = N(() => f(m.query.page)), v = o(async (e) => c(await e.GET("/organizations/{organization}/members", { params: {
			path: { organization: h.value },
			query: _.value > 1 ? { page: _.value } : {}
		} })), _), y = N(() => v.data.value?.data ?? []), b = N(() => s(v.data.value)), x = l(), S = W(""), w = l();
		async function j() {
			await x.run(async () => c(await a.client.POST("/organizations/{organization}/members", {
				params: { path: { organization: h.value } },
				body: { user: S.value.trim() }
			}))) && (S.value = "", await v.reload(), u("changed"));
		}
		async function B(e) {
			e.user && await w.run(async () => c(await a.client.POST("/users/{user}/approve", { params: { path: { user: String(e.user.id) } } }))) && await v.reload();
		}
		async function V(e) {
			e.user && await w.run(async () => c(await a.client.POST("/organizations/{organization}/transfer", {
				params: { path: { organization: h.value } },
				body: { user: String(e.user.id) }
			}))) && (await v.reload(), u("changed"));
		}
		async function U(e) {
			e.user && await w.run(async () => c(await a.client.DELETE("/organizations/{organization}/members/{user}", { params: { path: {
				organization: h.value,
				user: String(e.user.id)
			} } }))) && (await v.reload(), u("changed"));
		}
		return (i, a) => {
			let o = T, s = A, c = k, l = D, u = O, f = K("RouterLink"), m = E;
			return H(), I("div", Ee, [
				r.organization.owner === null ? (H(), P(o, {
					key: 0,
					color: "warning",
					variant: "subtle",
					icon: "i-lucide-triangle-alert",
					title: J(d)("roster.organizations.members.no_owner"),
					"data-testid": "roster-organization-no-owner"
				}, null, 8, ["title"])) : F("", !0),
				z(u, null, {
					header: Y(() => [L("h2", De, q(J(d)("roster.organizations.members.add")), 1)]),
					default: Y(() => [L("form", {
						class: "flex flex-wrap items-end gap-2",
						"data-testid": "roster-member-add-form",
						onSubmit: Z(j, ["prevent"])
					}, [
						z(e, { action: J(x) }, null, 8, ["action"]),
						z(c, {
							label: J(d)("roster.organizations.members.user"),
							hint: J(d)("roster.organizations.members.user_hint"),
							error: J(x).error("user"),
							required: ""
						}, {
							default: Y(() => [z(s, {
								modelValue: J(S),
								"onUpdate:modelValue": a[0] ||= (e) => ee(S) ? S.value = e : null,
								class: "w-64",
								"data-testid": "roster-member-add-user"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						z(l, {
							type: "submit",
							icon: "i-lucide-user-plus",
							loading: J(x).busy.value,
							"data-testid": "roster-member-add"
						}, {
							default: Y(() => [R(q(J(d)("roster.organizations.members.add")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)]),
					_: 1
				}),
				z(e, { action: J(w) }, null, 8, ["action"]),
				z(p, {
					ready: !J(v).pending.value && J(v).data.value !== null,
					failed: J(v).failed.value,
					status: J(v).status.value,
					compact: ""
				}, {
					default: Y(() => [L("div", Oe, [J(y).length === 0 ? (H(), I("p", ke, q(J(d)("roster.organizations.members.empty")), 1)) : (H(), I("table", Ae, [L("thead", je, [L("tr", null, [
						L("th", Me, q(J(d)("roster.organizations.members.member")), 1),
						L("th", Ne, q(J(d)("roster.organizations.fields.status")), 1),
						L("th", Pe, q(J(d)("roster.organizations.fields.teams")), 1),
						L("th", Fe, q(J(d)("roster.organizations.members.source")), 1),
						L("th", Ie, q(J(d)("roster.organizations.fields.actions")), 1)
					])]), L("tbody", Le, [(H(!0), I(M, null, G(J(y), (e) => (H(), I("tr", {
						key: String(e.user?.id ?? e.joined_at),
						"data-testid": `roster-member-row-${e.user?.id}`
					}, [
						L("td", ze, [e.user ? (H(), I(M, { key: 0 }, [z(f, {
							to: J(n)(e.user.id),
							class: "font-medium hover:underline"
						}, {
							default: Y(() => [R(q(J(g)(e.user)), 1)]),
							_: 2
						}, 1032, ["to"]), e.owner ? (H(), P(m, {
							key: 0,
							variant: "subtle",
							class: "ml-2",
							"data-testid": "roster-member-owner"
						}, {
							default: Y(() => [R(q(J(d)("roster.organizations.fields.owner")), 1)]),
							_: 1
						})) : F("", !0)], 64)) : F("", !0)]),
						L("td", Be, [e.status ? (H(), P(C, {
							key: 0,
							status: e.status
						}, null, 8, ["status"])) : F("", !0)]),
						L("td", Ve, q(e.teams?.length ? e.teams.join(", ") : J(d)("roster.organizations.none")), 1),
						L("td", He, q(e.source), 1),
						L("td", Ue, [e.user ? (H(), I("div", We, [e.status === "pending" ? (H(), P(l, {
							key: 0,
							size: "sm",
							icon: "i-lucide-check",
							loading: J(w).busy.value,
							"data-testid": `roster-member-approve-${e.user.id}`,
							onClick: (t) => B(e)
						}, {
							default: Y(() => [R(q(J(d)("roster.organizations.members.approve")), 1)]),
							_: 1
						}, 8, [
							"loading",
							"data-testid",
							"onClick"
						])) : F("", !0), e.owner ? F("", !0) : (H(), I(M, { key: 1 }, [r.organization.personal ? F("", !0) : (H(), P(l, {
							key: 0,
							size: "sm",
							variant: "ghost",
							icon: "i-lucide-key-round",
							loading: J(w).busy.value,
							"data-testid": `roster-member-make-owner-${e.user.id}`,
							onClick: (t) => V(e)
						}, {
							default: Y(() => [R(q(J(d)("roster.organizations.members.make_owner")), 1)]),
							_: 1
						}, 8, [
							"loading",
							"data-testid",
							"onClick"
						])), z(l, {
							size: "sm",
							color: "error",
							variant: "outline",
							icon: "i-lucide-user-minus",
							loading: J(w).busy.value,
							"data-testid": `roster-member-remove-${e.user.id}`,
							onClick: (t) => U(e)
						}, {
							default: Y(() => [R(q(J(d)("roster.organizations.members.remove")), 1)]),
							_: 1
						}, 8, [
							"loading",
							"data-testid",
							"onClick"
						])], 64))])) : F("", !0)])
					], 8, Re))), 128))])])), z(t, {
						current: J(b).current,
						last: J(b).last,
						testid: "roster-members"
					}, null, 8, ["current", "last"])])]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])
			]);
		};
	}
}), Ke = {
	class: "flex flex-col gap-4",
	"data-testid": "roster-organization-settings"
}, qe = { class: "font-semibold" }, Je = { class: "flex items-center gap-2 text-sm" }, Ye = { class: "-mt-3 text-xs text-muted" }, Xe = ["value"], Ze = ["value"], Qe = { class: "font-semibold" }, $e = { class: "flex flex-col gap-4" }, et = {
	key: 0,
	class: "text-sm",
	"data-testid": "roster-links-empty"
}, tt = {
	key: 1,
	class: "w-full text-left text-sm"
}, nt = { class: "text-muted" }, rt = { class: "py-2 pr-3 font-medium" }, it = { class: "py-2 pr-3 font-medium" }, at = { class: "py-2 pr-3 font-medium" }, ot = { class: "py-2 pr-3 font-medium" }, st = { class: "py-2 text-right font-medium" }, ct = { class: "divide-y divide-default" }, lt = ["data-testid"], ut = { class: "py-2 pr-3" }, dt = { class: "py-2 pr-3" }, ft = { class: "py-2 pr-3" }, pt = { class: "py-2 pr-3" }, mt = { class: "py-2" }, ht = { class: "flex justify-end" }, gt = { class: "font-semibold" }, _t = { class: "flex flex-col gap-3" }, vt = /* @__PURE__ */ B({
	__name: "OrganizationSettings",
	props: {
		client: {},
		organization: {}
	},
	emits: ["updated", "deleted"],
	setup(t, { emit: n }) {
		let r = t, a = n, { t: o } = Q(), s = N(() => r.organization.slug), f = () => ({ params: { path: { organization: s.value } } }), p = l(), h = U({
			name: "",
			slug: "",
			domains: "",
			auto_join: !1,
			provisioned_status: "active",
			status: "active",
			status_reason: ""
		});
		re(() => r.organization, (e) => {
			h.name = e.name, h.slug = e.slug, h.domains = (e.domains ?? []).join("\n"), h.auto_join = e.auto_join, h.provisioned_status = e.provisioned_status, h.status = e.status, h.status_reason = e.status_reason ?? "";
		}, { immediate: !0 });
		let g = N(() => p.error("domains") ?? Object.entries(p.errors.value).find(([e]) => e.startsWith("domains."))?.[1]?.[0]), _ = l(), v = U({
			source: "",
			external_id: "",
			account_number: ""
		}), b = l();
		async function S() {
			let e = { slug: s.value };
			await p.run(async () => {
				e.slug = c(await r.client.PATCH("/organizations/{organization}", {
					...f(),
					body: i({
						name: h.name,
						slug: h.slug,
						domains: m(h.domains),
						auto_join: h.auto_join,
						provisioned_status: h.provisioned_status,
						status: h.status,
						status_reason: u(h.status_reason)
					})
				})).data.slug;
			}) && a("updated", e.slug);
		}
		async function C(e) {
			await _.run(async () => c(await r.client.DELETE("/organizations/{organization}/links/{source}", { params: { path: {
				organization: s.value,
				source: e.source
			} } }))) && a("updated", s.value);
		}
		async function w() {
			let e = v.source.trim();
			e === "" ? _.errors.value = { source: [o("roster.organizations.links.source_required")] } : await _.run(async () => c(await r.client.PUT("/organizations/{organization}/links/{source}", {
				params: { path: {
					organization: s.value,
					source: e
				} },
				body: {
					source: e,
					external_id: v.external_id.trim(),
					account_number: u(v.account_number)
				}
			}))) && (v.source = "", v.external_id = "", v.account_number = "", a("updated", s.value));
		}
		async function E() {
			window.confirm(o("roster.organizations.danger.confirm_delete")) && await b.run(async () => c(await r.client.DELETE("/organizations/{organization}", f()))) && a("deleted");
		}
		return (n, r) => {
			let i = A, a = k, s = j, c = D, l = O, u = T;
			return H(), I("div", Ke, [
				z(l, null, {
					header: Y(() => [L("h2", qe, q(J(o)("roster.organizations.settings.title")), 1)]),
					default: Y(() => [L("form", {
						class: "flex max-w-2xl flex-col gap-4",
						"data-testid": "roster-organization-settings-form",
						onSubmit: Z(S, ["prevent"])
					}, [
						z(e, { action: J(p) }, null, 8, ["action"]),
						z(a, {
							label: J(o)("roster.organizations.fields.name"),
							error: J(p).error("name"),
							required: ""
						}, {
							default: Y(() => [z(i, {
								modelValue: J(h).name,
								"onUpdate:modelValue": r[0] ||= (e) => J(h).name = e,
								class: "w-full",
								"data-testid": "roster-settings-name"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						z(a, {
							label: J(o)("roster.organizations.fields.slug"),
							error: J(p).error("slug"),
							required: ""
						}, {
							default: Y(() => [z(i, {
								modelValue: J(h).slug,
								"onUpdate:modelValue": r[1] ||= (e) => J(h).slug = e,
								class: "w-full",
								"data-testid": "roster-settings-slug"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						z(a, {
							label: J(o)("roster.organizations.fields.domains"),
							hint: J(o)("roster.organizations.domains_hint"),
							error: J(g)
						}, {
							default: Y(() => [z(s, {
								modelValue: J(h).domains,
								"onUpdate:modelValue": r[2] ||= (e) => J(h).domains = e,
								rows: 3,
								class: "w-full",
								"data-testid": "roster-settings-domains"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						L("label", Je, [X(L("input", {
							"onUpdate:modelValue": r[3] ||= (e) => J(h).auto_join = e,
							type: "checkbox",
							"data-testid": "roster-settings-auto-join"
						}, null, 512), [[te, J(h).auto_join]]), R(" " + q(J(o)("roster.organizations.fields.auto_join")), 1)]),
						L("p", Ye, q(J(o)("roster.organizations.settings.auto_join_hint")), 1),
						z(a, {
							label: J(o)("roster.organizations.settings.provisioned_status"),
							hint: J(o)("roster.organizations.settings.provisioned_status_hint"),
							error: J(p).error("provisioned_status")
						}, {
							default: Y(() => [X(L("select", {
								"onUpdate:modelValue": r[4] ||= (e) => J(h).provisioned_status = e,
								class: "w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "roster-settings-provisioned-status"
							}, [(H(!0), I(M, null, G(J(y), (e) => (H(), I("option", {
								key: e,
								value: e
							}, q(J(o)(`roster.users.statuses.${e}`)), 9, Xe))), 128))], 512), [[ne, J(h).provisioned_status]])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						z(a, {
							label: J(o)("roster.organizations.fields.status"),
							error: J(p).error("status")
						}, {
							default: Y(() => [X(L("select", {
								"onUpdate:modelValue": r[5] ||= (e) => J(h).status = e,
								class: "w-72 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "roster-settings-status"
							}, [(H(!0), I(M, null, G(J(x), (e) => (H(), I("option", {
								key: e,
								value: e
							}, q(J(o)(`roster.organizations.statuses.${e}`)), 9, Ze))), 128))], 512), [[ne, J(h).status]])]),
							_: 1
						}, 8, ["label", "error"]),
						z(a, {
							label: J(o)("roster.organizations.fields.reason"),
							error: J(p).error("status_reason")
						}, {
							default: Y(() => [z(i, {
								modelValue: J(h).status_reason,
								"onUpdate:modelValue": r[6] ||= (e) => J(h).status_reason = e,
								class: "w-80",
								"data-testid": "roster-settings-status-reason"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						L("div", null, [z(c, {
							type: "submit",
							icon: "i-lucide-check",
							loading: J(p).busy.value,
							"data-testid": "roster-settings-save"
						}, {
							default: Y(() => [R(q(J(o)("roster.organizations.save")), 1)]),
							_: 1
						}, 8, ["loading"])])
					], 32)]),
					_: 1
				}),
				z(l, { "data-testid": "roster-organization-links" }, {
					header: Y(() => [L("h2", Qe, q(J(o)("roster.organizations.links.title")), 1)]),
					default: Y(() => [L("div", $e, [
						z(e, { action: J(_) }, null, 8, ["action"]),
						t.organization.links.length === 0 ? (H(), I("p", et, q(J(o)("roster.organizations.links.empty")), 1)) : (H(), I("table", tt, [L("thead", nt, [L("tr", null, [
							L("th", rt, q(J(o)("roster.organizations.fields.source")), 1),
							L("th", it, q(J(o)("roster.organizations.fields.external_id")), 1),
							L("th", at, q(J(o)("roster.organizations.fields.account_number")), 1),
							L("th", ot, q(J(o)("roster.organizations.links.synced")), 1),
							L("th", st, q(J(o)("roster.organizations.fields.actions")), 1)
						])]), L("tbody", ct, [(H(!0), I(M, null, G(t.organization.links, (e) => (H(), I("tr", {
							key: e.source,
							"data-testid": `roster-link-${e.source}`
						}, [
							L("td", ut, [L("code", null, q(e.source), 1)]),
							L("td", dt, q(e.external_id), 1),
							L("td", ft, q(e.account_number ?? J(o)("roster.organizations.none")), 1),
							L("td", pt, q(J(d)(e.synced_at) ?? J(o)("roster.organizations.links.never")), 1),
							L("td", mt, [L("div", ht, [z(c, {
								size: "sm",
								variant: "ghost",
								icon: "i-lucide-unlink",
								loading: J(_).busy.value,
								"data-testid": `roster-unlink-${e.source}`,
								onClick: (t) => C(e)
							}, {
								default: Y(() => [R(q(J(o)("roster.organizations.links.unlink")), 1)]),
								_: 1
							}, 8, [
								"loading",
								"data-testid",
								"onClick"
							])])])
						], 8, lt))), 128))])])),
						L("form", {
							class: "flex flex-wrap items-end gap-3",
							"data-testid": "roster-link-form",
							onSubmit: Z(w, ["prevent"])
						}, [
							z(a, {
								label: J(o)("roster.organizations.fields.source"),
								hint: J(o)("roster.organizations.links.source_hint"),
								error: J(_).error("source"),
								required: ""
							}, {
								default: Y(() => [z(i, {
									modelValue: J(v).source,
									"onUpdate:modelValue": r[7] ||= (e) => J(v).source = e,
									class: "w-40",
									"data-testid": "roster-link-source"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							z(a, {
								label: J(o)("roster.organizations.fields.external_id"),
								error: J(_).error("external_id"),
								required: ""
							}, {
								default: Y(() => [z(i, {
									modelValue: J(v).external_id,
									"onUpdate:modelValue": r[8] ||= (e) => J(v).external_id = e,
									class: "w-56",
									"data-testid": "roster-link-external-id"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							z(a, {
								label: J(o)("roster.organizations.fields.account_number"),
								error: J(_).error("account_number")
							}, {
								default: Y(() => [z(i, {
									modelValue: J(v).account_number,
									"onUpdate:modelValue": r[9] ||= (e) => J(v).account_number = e,
									class: "w-48",
									"data-testid": "roster-link-account-number"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							z(c, {
								type: "submit",
								icon: "i-lucide-link",
								loading: J(_).busy.value,
								"data-testid": "roster-link-submit"
							}, {
								default: Y(() => [R(q(J(o)("roster.organizations.links.link")), 1)]),
								_: 1
							}, 8, ["loading"])
						], 32)
					])]),
					_: 1
				}),
				t.organization.personal ? F("", !0) : (H(), P(l, {
					key: 0,
					"data-testid": "roster-organization-danger"
				}, {
					header: Y(() => [L("h2", gt, q(J(o)("roster.organizations.danger.title")), 1)]),
					default: Y(() => [L("div", _t, [
						z(e, { action: J(b) }, null, 8, ["action"]),
						z(u, {
							color: "warning",
							variant: "subtle",
							title: J(o)("roster.organizations.danger.delete_title"),
							description: J(o)("roster.organizations.danger.delete_warning")
						}, null, 8, ["title", "description"]),
						L("div", null, [z(c, {
							color: "error",
							icon: "i-lucide-trash",
							loading: J(b).busy.value,
							"data-testid": "roster-organization-delete",
							onClick: E
						}, {
							default: Y(() => [R(q(J(o)("roster.organizations.danger.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])
					])]),
					_: 1
				}))
			]);
		};
	}
}), yt = {
	class: "flex flex-col gap-4",
	"data-testid": "roster-organization-teams"
}, bt = { class: "font-semibold" }, xt = { class: "space-y-4" }, St = {
	key: 0,
	class: "atrium-empty",
	"data-testid": "roster-teams-empty"
}, Ct = {
	key: 1,
	class: "w-full text-left text-sm",
	"data-testid": "roster-teams-table"
}, wt = { class: "text-muted" }, Tt = { class: "py-2 pr-3 font-medium" }, Et = { class: "py-2 pr-3 font-medium" }, Dt = { class: "divide-y divide-default" }, Ot = ["data-testid"], kt = { class: "py-2 pr-3" }, At = { class: "py-2 pr-3" }, jt = /* @__PURE__ */ B({
	__name: "OrganizationTeams",
	props: {
		client: {},
		organization: {}
	},
	emits: ["changed"],
	setup(n, { emit: r }) {
		let a = n, d = r, { t: m } = Q(), h = $(), g = N(() => a.organization.slug), _ = N(() => f(h.query.page)), v = o(async (e) => c(await e.GET("/organizations/{organization}/teams", { params: {
			path: { organization: g.value },
			query: _.value > 1 ? { page: _.value } : {}
		} })), _), y = N(() => v.data.value?.data ?? []), b = N(() => s(v.data.value)), x = l(), C = U({
			name: "",
			slug: ""
		});
		async function w() {
			await x.run(async () => c(await a.client.POST("/organizations/{organization}/teams", {
				params: { path: { organization: g.value } },
				body: i({
					name: C.name,
					slug: u(C.slug)
				})
			}))) && (C.name = "", C.slug = "", await v.reload(), d("changed"));
		}
		return (r, i) => {
			let a = A, o = k, s = D, c = O, l = K("RouterLink");
			return H(), I("div", yt, [z(c, null, {
				header: Y(() => [L("h2", bt, q(J(m)("roster.organizations.teams.new")), 1)]),
				default: Y(() => [L("form", {
					class: "flex flex-wrap items-end gap-2",
					"data-testid": "roster-team-create-form",
					onSubmit: Z(w, ["prevent"])
				}, [
					z(e, { action: J(x) }, null, 8, ["action"]),
					z(o, {
						label: J(m)("roster.organizations.fields.name"),
						error: J(x).error("name"),
						required: ""
					}, {
						default: Y(() => [z(a, {
							modelValue: J(C).name,
							"onUpdate:modelValue": i[0] ||= (e) => J(C).name = e,
							class: "w-64",
							"data-testid": "roster-team-create-name"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, ["label", "error"]),
					z(o, {
						label: J(m)("roster.organizations.fields.slug"),
						hint: J(m)("roster.organizations.slug_hint"),
						error: J(x).error("slug")
					}, {
						default: Y(() => [z(a, {
							modelValue: J(C).slug,
							"onUpdate:modelValue": i[1] ||= (e) => J(C).slug = e,
							class: "w-64",
							"data-testid": "roster-team-create-slug"
						}, null, 8, ["modelValue"])]),
						_: 1
					}, 8, [
						"label",
						"hint",
						"error"
					]),
					z(s, {
						type: "submit",
						icon: "i-lucide-plus",
						loading: J(x).busy.value,
						"data-testid": "roster-team-create"
					}, {
						default: Y(() => [R(q(J(m)("roster.organizations.create")), 1)]),
						_: 1
					}, 8, ["loading"])
				], 32)]),
				_: 1
			}), z(p, {
				ready: !J(v).pending.value && J(v).data.value !== null,
				failed: J(v).failed.value,
				status: J(v).status.value,
				compact: ""
			}, {
				default: Y(() => [L("div", xt, [J(y).length === 0 ? (H(), I("p", St, q(J(m)("roster.organizations.teams.empty")), 1)) : (H(), I("table", Ct, [L("thead", wt, [L("tr", null, [L("th", Tt, q(J(m)("roster.organizations.teams.team")), 1), L("th", Et, q(J(m)("roster.organizations.fields.members")), 1)])]), L("tbody", Dt, [(H(!0), I(M, null, G(J(y), (e) => (H(), I("tr", {
					key: e.slug,
					"data-testid": `roster-team-row-${e.slug}`
				}, [L("td", kt, [z(l, {
					to: J(S)(n.organization.slug, e.slug),
					class: "font-medium hover:underline"
				}, {
					default: Y(() => [R(q(e.name), 1)]),
					_: 2
				}, 1032, ["to"])]), L("td", At, q(e.members_count ?? J(m)("roster.organizations.none")), 1)], 8, Ot))), 128))])])), z(t, {
					current: J(b).current,
					last: J(b).last,
					testid: "roster-teams"
				}, null, 8, ["current", "last"])])]),
				_: 1
			}, 8, [
				"ready",
				"failed",
				"status"
			])]);
		};
	}
}), Mt = {
	class: "space-y-4",
	"data-testid": "roster-organization"
}, Nt = { class: "flex flex-wrap items-center justify-between gap-3" }, Pt = { class: "flex items-center gap-3" }, Ft = { class: "min-w-0" }, It = {
	class: "text-xl font-semibold",
	"data-testid": "roster-organization-title"
}, Lt = {
	key: 0,
	class: "text-sm text-muted"
}, Rt = {
	key: 0,
	class: "flex max-w-3xl flex-col gap-4",
	"data-testid": "roster-organization-deleted"
}, zt = { class: "font-semibold" }, Bt = { class: "mb-4 text-sm" }, Vt = {
	key: 1,
	class: "space-y-4"
}, Ht = ["aria-label"], Ut = [
	"aria-current",
	"data-testid",
	"onClick"
], Wt = /* @__PURE__ */ B({
	__name: "index",
	setup(t) {
		let { t: n } = Q(), i = $(), a = ie(), s = N(() => String(i.params.organization)), u = N(() => b(r(i.query.tab))), { client: d, data: f, pending: m, failed: h, status: g, reload: y } = o(async (e) => c(await e.GET("/organizations/{organization}", { params: { path: { organization: s.value } } })).data, s), x = W(!1), S = l(), C = N(() => u.value === "members" || u.value === "teams");
		function k(e) {
			a.replace({ query: {
				...i.query,
				tab: e === "members" ? void 0 : e,
				page: void 0
			} });
		}
		async function A(e) {
			e === s.value ? await y() : await a.replace({
				path: v(e),
				query: { tab: "settings" }
			});
		}
		async function j() {
			d && await S.run(async () => c(await d.POST("/organizations/{organization}/restore", { params: { path: { organization: s.value } } }))) && (x.value = !1, await y());
		}
		async function B() {
			d && window.confirm(n("roster.organizations.danger.confirm_purge")) && await S.run(async () => c(await d.DELETE("/organizations/{organization}/purge", { params: { path: { organization: s.value } } }))) && await a.push({
				path: "/roster/organizations",
				query: { trashed: "only" }
			});
		}
		return (t, r) => {
			let i = D, a = E, o = T, c = O, l = w;
			return H(), I("div", Mt, [L("div", Nt, [L("div", Pt, [
				z(i, {
					to: "/roster/organizations",
					icon: "i-lucide-arrow-left",
					color: "neutral",
					variant: "ghost",
					"aria-label": J(n)("roster.organizations.title")
				}, null, 8, ["aria-label"]),
				L("div", Ft, [L("h1", It, q(J(f)?.name ?? J(n)("roster.organizations.organization")), 1), J(f) ? (H(), I("p", Lt, q(J(f).slug), 1)) : F("", !0)]),
				J(x) ? (H(), P(a, {
					key: 0,
					color: "neutral",
					variant: "subtle"
				}, {
					default: Y(() => [R(q(J(n)("roster.organizations.deleted")), 1)]),
					_: 1
				})) : F("", !0)
			]), J(C) && J(f) && !J(x) ? (H(), P(i, {
				key: 0,
				to: {
					path: "/roster/transfers",
					query: { organization: J(f).slug }
				},
				variant: "outline",
				icon: "i-lucide-arrow-up-down",
				"data-testid": "roster-organization-transfers"
			}, {
				default: Y(() => [R(q(J(n)("roster.organizations.import_export")), 1)]),
				_: 1
			}, 8, ["to"])) : F("", !0)]), z(l, {
				"screen-key": "roster.organization",
				record: J(s)
			}, {
				default: Y(() => [z(p, {
					ready: !J(m) && J(f) !== null,
					failed: J(h),
					status: J(g)
				}, {
					default: Y(() => [J(x) && J(f) ? (H(), I("div", Rt, [
						z(e, { action: J(S) }, null, 8, ["action"]),
						z(o, {
							color: "warning",
							variant: "subtle",
							icon: "i-lucide-trash",
							title: J(n)("roster.organizations.danger.deleted"),
							description: J(n)("roster.organizations.danger.deleted_hint")
						}, null, 8, ["title", "description"]),
						L("div", null, [z(i, {
							icon: "i-lucide-undo-2",
							loading: J(S).busy.value,
							"data-testid": "roster-organization-restore",
							onClick: j
						}, {
							default: Y(() => [R(q(J(n)("roster.organizations.restore")), 1)]),
							_: 1
						}, 8, ["loading"])]),
						z(c, null, {
							header: Y(() => [L("h2", zt, q(J(n)("roster.organizations.danger.title")), 1)]),
							default: Y(() => [L("p", Bt, q(J(n)("roster.organizations.danger.purge_warning")), 1), z(i, {
								color: "error",
								icon: "i-lucide-trash-2",
								loading: J(S).busy.value,
								"data-testid": "roster-organization-purge",
								onClick: B
							}, {
								default: Y(() => [R(q(J(n)("roster.organizations.purge")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						})
					])) : J(f) && J(d) ? (H(), I("div", Vt, [L("nav", {
						class: "flex flex-wrap gap-1 border-b border-default",
						"aria-label": J(f).name,
						"data-testid": "roster-organization-tabs"
					}, [(H(!0), I(M, null, G(J(_), (e) => (H(), I("button", {
						key: e,
						type: "button",
						class: V(["-mb-px border-b-2 px-3 py-2 text-sm font-medium", J(u) === e ? "border-primary" : "border-transparent text-muted"]),
						"aria-current": J(u) === e ? "page" : void 0,
						"data-testid": `roster-organization-tab-${e}`,
						onClick: (t) => k(e)
					}, q(J(n)(`roster.organizations.tabs.${e}`)), 11, Ut))), 128))], 8, Ht), J(u) === "members" ? (H(), P(Ge, {
						key: 0,
						client: J(d),
						organization: J(f),
						onChanged: J(y)
					}, null, 8, [
						"client",
						"organization",
						"onChanged"
					])) : J(u) === "teams" ? (H(), P(jt, {
						key: 1,
						client: J(d),
						organization: J(f),
						onChanged: J(y)
					}, null, 8, [
						"client",
						"organization",
						"onChanged"
					])) : J(u) === "invitations" ? (H(), P(Te, {
						key: 2,
						client: J(d),
						organization: J(f)
					}, null, 8, ["client", "organization"])) : (H(), P(vt, {
						key: 3,
						client: J(d),
						organization: J(f),
						onUpdated: A,
						onDeleted: r[0] ||= (e) => x.value = !0
					}, null, 8, ["client", "organization"]))])) : F("", !0)]),
					_: 1
				}, 8, [
					"ready",
					"failed",
					"status"
				])]),
				_: 1
			}, 8, ["record"])]);
		};
	}
});
export { Wt as default };
