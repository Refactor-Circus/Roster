import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterPager-CFRR10Qn.js";
import { _ as n, c as r, d as i, f as a, h as o, i as s, l as c, m as l, n as u, r as d, s as f } from "./users-JqW-WBif.js";
import { t as p } from "./OrganizationState-GrCgPjha.js";
import { a as m, c as h, l as g, n as _, o as v, r as y, s as b, t as x, u as S } from "./organizations-vLWlhIK9.js";
import { t as C } from "./RosterStatusBadge-CjGfIXnt.js";
import { t as w } from "./RosterTabs-C9EsVdM2.js";
import { AtriumIntegrationScreen as T, AtriumPageHeader as E } from "@refactor-circus/atrium";
import { UAlert as D, UBadge as O, UButton as k, UCard as A, UCheckbox as j, UEmpty as M, UForm as N, UFormField as P, UInput as F, ULink as I, USelect as ee, UTable as L, UTextarea as te } from "@refactor-circus/atrium/ui";
import { Fragment as R, computed as z, createBlock as B, createCommentVNode as V, createElementBlock as H, createElementVNode as U, createTextVNode as W, createVNode as G, defineComponent as K, isRef as ne, openBlock as q, reactive as J, ref as re, renderList as ie, toDisplayString as Y, unref as X, watch as ae, withCtx as Z } from "vue";
import { useI18n as Q } from "vue-i18n";
import { useRoute as $, useRouter as oe } from "vue-router";
// src/components/organizations/OrganizationInvitations.vue?vue&type=script&setup=true&lang.ts
var se = {
	class: "flex flex-col gap-4",
	"data-testid": "roster-organization-invitations"
}, ce = { class: "font-semibold" }, le = { class: "flex flex-wrap gap-4" }, ue = { class: "space-y-4" }, de = ["data-testid"], fe = { class: "flex justify-end" }, pe = /* @__PURE__ */ K({
	__name: "OrganizationInvitations",
	props: {
		client: {},
		organization: {}
	},
	setup(n) {
		let r = n, { t: i, te: u } = Q(), m = $(), g = z(() => r.organization.slug), _ = z(() => f(m.query.page)), v = o(async (e) => c(await e.GET("/organizations/{organization}/invitations", { params: {
			path: { organization: g.value },
			query: _.value > 1 ? { page: _.value } : {}
		} })), _), y = z(() => v.data.value?.data ?? []), b = z(() => s(v.data.value)), x = a(() => g.value), S = z(() => [
			{
				accessorKey: "email",
				header: i("roster.organizations.fields.email")
			},
			{
				id: "status",
				header: i("roster.organizations.fields.status")
			},
			{
				id: "expires",
				header: i("roster.organizations.invitations.expires")
			},
			{
				id: "actions",
				header: i("roster.organizations.fields.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]), C = l(), w = J({
			email: "",
			teams: []
		}), T = l();
		function E(e, t) {
			w.teams = t ? [...w.teams.filter((t) => t !== e), e] : w.teams.filter((t) => t !== e);
		}
		async function D() {
			await C.run(async () => c(await r.client.POST("/organizations/{organization}/invitations", {
				params: { path: { organization: g.value } },
				body: {
					email: w.email.trim(),
					teams: w.teams
				}
			}))) && (w.email = "", w.teams = [], await v.reload());
		}
		async function I(e) {
			await T.run(async () => c(await r.client.DELETE("/organizations/{organization}/invitations/{invitation}", { params: { path: {
				organization: g.value,
				invitation: e.id
			} } }))) && await v.reload();
		}
		return (n, r) => {
			let a = F, o = P, s = j, c = k, l = N, f = A, m = M, g = O, _ = L;
			return q(), H("div", se, [
				G(f, null, {
					header: Z(() => [U("h2", ce, Y(X(i)("roster.organizations.invitations.invite")), 1)]),
					default: Z(() => [G(l, {
						state: X(w),
						class: "flex max-w-2xl flex-col gap-3",
						"data-testid": "roster-invite-form",
						onSubmit: D
					}, {
						default: Z(() => [
							G(e, { action: X(C) }, null, 8, ["action"]),
							G(o, {
								label: X(i)("roster.organizations.fields.email"),
								error: X(C).error("email"),
								required: ""
							}, {
								default: Z(() => [G(a, {
									modelValue: X(w).email,
									"onUpdate:modelValue": r[0] ||= (e) => X(w).email = e,
									type: "email",
									class: "w-80",
									"data-testid": "roster-invite-email"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							X(x).length > 0 ? (q(), B(o, {
								key: 0,
								label: X(i)("roster.organizations.fields.teams"),
								error: X(C).error("teams")
							}, {
								default: Z(() => [U("div", le, [(q(!0), H(R, null, ie(X(x), (e) => (q(), B(s, {
									key: e.slug,
									"model-value": X(w).teams.includes(e.slug),
									label: e.name,
									"data-testid": `roster-invite-team-${e.slug}`,
									"onUpdate:modelValue": (t) => E(e.slug, t === !0)
								}, null, 8, [
									"model-value",
									"label",
									"data-testid",
									"onUpdate:modelValue"
								]))), 128))])]),
								_: 1
							}, 8, ["label", "error"])) : V("", !0),
							U("div", null, [G(c, {
								type: "submit",
								icon: "i-lucide-send",
								loading: X(C).busy.value,
								"data-testid": "roster-invite-send"
							}, {
								default: Z(() => [W(Y(X(i)("roster.organizations.invitations.invite")), 1)]),
								_: 1
							}, 8, ["loading"])])
						]),
						_: 1
					}, 8, ["state"])]),
					_: 1
				}),
				G(e, { action: X(T) }, null, 8, ["action"]),
				G(p, {
					ready: !X(v).pending.value && X(v).data.value !== null,
					failed: X(v).failed.value,
					status: X(v).status.value,
					compact: ""
				}, {
					default: Z(() => [U("div", ue, [X(y).length === 0 ? (q(), B(m, {
						key: 0,
						icon: "i-lucide-mail",
						title: X(i)("roster.organizations.invitations.empty"),
						"data-testid": "roster-invitations-empty"
					}, null, 8, ["title"])) : (q(), B(_, {
						key: 1,
						data: X(y),
						columns: X(S),
						"data-testid": "roster-invitations-table"
					}, {
						"email-cell": Z(({ row: { original: e } }) => [U("span", { "data-testid": `roster-invitation-row-${e.id}` }, Y(e.email), 9, de)]),
						"status-cell": Z(({ row: { original: e } }) => [G(g, {
							color: X(h)(e.status),
							variant: "subtle",
							"data-status": e.status
						}, {
							default: Z(() => [W(Y(X(u)(`roster.organizations.invitations.statuses.${e.status}`) ? X(i)(`roster.organizations.invitations.statuses.${e.status}`) : e.status), 1)]),
							_: 2
						}, 1032, ["color", "data-status"])]),
						"expires-cell": Z(({ row: { original: e } }) => [W(Y(X(d)(e.expires_at)), 1)]),
						"actions-cell": Z(({ row: { original: e } }) => [U("div", fe, [e.status === "pending" ? (q(), B(c, {
							key: 0,
							size: "sm",
							variant: "ghost",
							icon: "i-lucide-ban",
							loading: X(T).busy.value,
							"data-testid": `roster-invitation-revoke-${e.id}`,
							onClick: (t) => I(e)
						}, {
							default: Z(() => [W(Y(X(i)("roster.organizations.invitations.revoke")), 1)]),
							_: 1
						}, 8, [
							"loading",
							"data-testid",
							"onClick"
						])) : V("", !0)])]),
						_: 1
					}, 8, ["data", "columns"])), G(t, {
						current: X(b).current,
						last: X(b).last,
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
}), me = {
	class: "flex flex-col gap-4",
	"data-testid": "roster-organization-members"
}, he = { class: "font-semibold" }, ge = { class: "space-y-4" }, _e = ["data-testid"], ve = {
	key: 0,
	class: "flex justify-end gap-2"
}, ye = /* @__PURE__ */ K({
	__name: "OrganizationMembers",
	props: {
		client: {},
		organization: {}
	},
	emits: ["changed"],
	setup(r, { emit: i }) {
		let a = r, u = i, { t: d } = Q(), m = $(), h = z(() => a.organization.slug), _ = z(() => f(m.query.page)), v = o(async (e) => c(await e.GET("/organizations/{organization}/members", { params: {
			path: { organization: h.value },
			query: _.value > 1 ? { page: _.value } : {}
		} })), _), y = z(() => v.data.value?.data ?? []), b = z(() => s(v.data.value)), x = z(() => [
			{
				id: "member",
				header: d("roster.organizations.members.member")
			},
			{
				id: "status",
				header: d("roster.organizations.fields.status")
			},
			{
				id: "teams",
				header: d("roster.organizations.fields.teams")
			},
			{
				accessorKey: "source",
				header: d("roster.organizations.members.source")
			},
			{
				id: "actions",
				header: d("roster.organizations.fields.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]), S = l(), w = re(""), T = l();
		async function E() {
			await S.run(async () => c(await a.client.POST("/organizations/{organization}/members", {
				params: { path: { organization: h.value } },
				body: { user: w.value.trim() }
			}))) && (w.value = "", await v.reload(), u("changed"));
		}
		async function j(e) {
			e.user && await T.run(async () => c(await a.client.POST("/users/{user}/approve", { params: { path: { user: String(e.user.id) } } }))) && await v.reload();
		}
		async function ee(e) {
			e.user && await T.run(async () => c(await a.client.POST("/organizations/{organization}/transfer", {
				params: { path: { organization: h.value } },
				body: { user: String(e.user.id) }
			}))) && (await v.reload(), u("changed"));
		}
		async function te(e) {
			e.user && await T.run(async () => c(await a.client.DELETE("/organizations/{organization}/members/{user}", { params: { path: {
				organization: h.value,
				user: String(e.user.id)
			} } }))) && (await v.reload(), u("changed"));
		}
		return (i, a) => {
			let o = D, s = F, c = P, l = k, u = N, f = A, m = M, h = I, _ = O, z = L;
			return q(), H("div", me, [
				r.organization.owner === null ? (q(), B(o, {
					key: 0,
					color: "warning",
					variant: "subtle",
					icon: "i-lucide-triangle-alert",
					title: X(d)("roster.organizations.members.no_owner"),
					"data-testid": "roster-organization-no-owner"
				}, null, 8, ["title"])) : V("", !0),
				G(f, null, {
					header: Z(() => [U("h2", he, Y(X(d)("roster.organizations.members.add")), 1)]),
					default: Z(() => [G(u, {
						class: "flex flex-wrap items-start gap-2",
						"data-testid": "roster-member-add-form",
						onSubmit: E
					}, {
						default: Z(() => [
							G(e, { action: X(S) }, null, 8, ["action"]),
							G(c, {
								label: X(d)("roster.organizations.members.user"),
								hint: X(d)("roster.organizations.members.user_hint"),
								error: X(S).error("user"),
								required: ""
							}, {
								default: Z(() => [G(s, {
									modelValue: X(w),
									"onUpdate:modelValue": a[0] ||= (e) => ne(w) ? w.value = e : null,
									class: "w-64",
									"data-testid": "roster-member-add-user"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							G(l, {
								type: "submit",
								icon: "i-lucide-user-plus",
								loading: X(S).busy.value,
								class: "mt-field-label",
								"data-testid": "roster-member-add"
							}, {
								default: Z(() => [W(Y(X(d)("roster.organizations.members.add")), 1)]),
								_: 1
							}, 8, ["loading"])
						]),
						_: 1
					})]),
					_: 1
				}),
				G(e, { action: X(T) }, null, 8, ["action"]),
				G(p, {
					ready: !X(v).pending.value && X(v).data.value !== null,
					failed: X(v).failed.value,
					status: X(v).status.value,
					compact: ""
				}, {
					default: Z(() => [U("div", ge, [X(y).length === 0 ? (q(), B(m, {
						key: 0,
						icon: "i-lucide-users",
						title: X(d)("roster.organizations.members.empty"),
						"data-testid": "roster-members-empty"
					}, null, 8, ["title"])) : (q(), B(z, {
						key: 1,
						data: X(y),
						columns: X(x),
						"data-testid": "roster-members-table"
					}, {
						"member-cell": Z(({ row: { original: e } }) => [U("span", { "data-testid": `roster-member-row-${e.user?.id}` }, [e.user ? (q(), H(R, { key: 0 }, [G(h, {
							to: X(n)(e.user.id),
							class: "font-medium text-default hover:underline"
						}, {
							default: Z(() => [W(Y(X(g)(e.user)), 1)]),
							_: 2
						}, 1032, ["to"]), e.owner ? (q(), B(_, {
							key: 0,
							variant: "subtle",
							class: "ml-2",
							"data-testid": "roster-member-owner"
						}, {
							default: Z(() => [W(Y(X(d)("roster.organizations.fields.owner")), 1)]),
							_: 1
						})) : V("", !0)], 64)) : V("", !0)], 8, _e)]),
						"status-cell": Z(({ row: { original: e } }) => [e.status ? (q(), B(C, {
							key: 0,
							status: e.status
						}, null, 8, ["status"])) : V("", !0)]),
						"teams-cell": Z(({ row: { original: e } }) => [W(Y(e.teams?.length ? e.teams.join(", ") : X(d)("roster.organizations.none")), 1)]),
						"actions-cell": Z(({ row: { original: e } }) => [e.user ? (q(), H("div", ve, [e.status === "pending" ? (q(), B(l, {
							key: 0,
							size: "sm",
							icon: "i-lucide-check",
							loading: X(T).busy.value,
							"data-testid": `roster-member-approve-${e.user.id}`,
							onClick: (t) => j(e)
						}, {
							default: Z(() => [W(Y(X(d)("roster.organizations.members.approve")), 1)]),
							_: 1
						}, 8, [
							"loading",
							"data-testid",
							"onClick"
						])) : V("", !0), e.owner ? V("", !0) : (q(), H(R, { key: 1 }, [r.organization.personal ? V("", !0) : (q(), B(l, {
							key: 0,
							size: "sm",
							variant: "ghost",
							icon: "i-lucide-key-round",
							loading: X(T).busy.value,
							"data-testid": `roster-member-make-owner-${e.user.id}`,
							onClick: (t) => ee(e)
						}, {
							default: Z(() => [W(Y(X(d)("roster.organizations.members.make_owner")), 1)]),
							_: 1
						}, 8, [
							"loading",
							"data-testid",
							"onClick"
						])), G(l, {
							size: "sm",
							color: "error",
							variant: "outline",
							icon: "i-lucide-user-minus",
							loading: X(T).busy.value,
							"data-testid": `roster-member-remove-${e.user.id}`,
							onClick: (t) => te(e)
						}, {
							default: Z(() => [W(Y(X(d)("roster.organizations.members.remove")), 1)]),
							_: 1
						}, 8, [
							"loading",
							"data-testid",
							"onClick"
						])], 64))])) : V("", !0)]),
						_: 1
					}, 8, ["data", "columns"])), G(t, {
						current: X(b).current,
						last: X(b).last,
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
}), be = {
	class: "flex flex-col gap-4",
	"data-testid": "roster-organization-settings"
}, xe = { class: "font-semibold" }, Se = { class: "font-semibold" }, Ce = { class: "flex flex-col gap-4" }, we = {
	key: 0,
	class: "text-sm",
	"data-testid": "roster-links-empty"
}, Te = ["data-testid"], Ee = { class: "flex justify-end" }, De = { class: "font-semibold" }, Oe = { class: "flex flex-col gap-3" }, ke = /* @__PURE__ */ K({
	__name: "OrganizationSettings",
	props: {
		client: {},
		organization: {}
	},
	emits: ["updated", "deleted"],
	setup(t, { emit: n }) {
		let r = t, a = n, { t: o } = Q(), s = z(() => r.organization.slug), f = () => ({ params: { path: { organization: s.value } } }), p = l(), h = J({
			name: "",
			slug: "",
			domains: "",
			auto_join: !1,
			provisioned_status: "active",
			status: "active",
			status_reason: ""
		});
		ae(() => r.organization, (e) => {
			h.name = e.name, h.slug = e.slug, h.domains = (e.domains ?? []).join("\n"), h.auto_join = e.auto_join, h.provisioned_status = e.provisioned_status, h.status = e.status, h.status_reason = e.status_reason ?? "";
		}, { immediate: !0 });
		let g = z(() => p.error("domains") ?? Object.entries(p.errors.value).find(([e]) => e.startsWith("domains."))?.[1]?.[0]), _ = z(() => y.map((e) => ({
			label: o(`roster.users.statuses.${e}`),
			value: e
		}))), v = z(() => x.map((e) => ({
			label: o(`roster.organizations.statuses.${e}`),
			value: e
		}))), b = z(() => [
			{
				id: "source",
				header: o("roster.organizations.fields.source")
			},
			{
				accessorKey: "external_id",
				header: o("roster.organizations.fields.external_id")
			},
			{
				id: "account_number",
				header: o("roster.organizations.fields.account_number")
			},
			{
				id: "synced",
				header: o("roster.organizations.links.synced")
			},
			{
				id: "actions",
				header: o("roster.organizations.fields.actions"),
				meta: { class: {
					th: "text-right",
					td: "text-right"
				} }
			}
		]), S = l(), C = J({
			source: "",
			external_id: "",
			account_number: ""
		}), w = l();
		async function T() {
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
		async function E(e) {
			await S.run(async () => c(await r.client.DELETE("/organizations/{organization}/links/{source}", { params: { path: {
				organization: s.value,
				source: e.source
			} } }))) && a("updated", s.value);
		}
		async function O() {
			let e = C.source.trim();
			e === "" ? S.errors.value = { source: [o("roster.organizations.links.source_required")] } : await S.run(async () => c(await r.client.PUT("/organizations/{organization}/links/{source}", {
				params: { path: {
					organization: s.value,
					source: e
				} },
				body: {
					source: e,
					external_id: C.external_id.trim(),
					account_number: u(C.account_number)
				}
			}))) && (C.source = "", C.external_id = "", C.account_number = "", a("updated", s.value));
		}
		async function M() {
			window.confirm(o("roster.organizations.danger.confirm_delete")) && await w.run(async () => c(await r.client.DELETE("/organizations/{organization}", f()))) && a("deleted");
		}
		return (n, r) => {
			let i = F, a = P, s = te, c = j, l = ee, u = k, f = N, m = A, y = L, x = D;
			return q(), H("div", be, [
				G(m, null, {
					header: Z(() => [U("h2", xe, Y(X(o)("roster.organizations.settings.title")), 1)]),
					default: Z(() => [G(f, {
						state: X(h),
						class: "flex max-w-2xl flex-col gap-4",
						"data-testid": "roster-organization-settings-form",
						onSubmit: T
					}, {
						default: Z(() => [
							G(e, { action: X(p) }, null, 8, ["action"]),
							G(a, {
								label: X(o)("roster.organizations.fields.name"),
								error: X(p).error("name"),
								required: ""
							}, {
								default: Z(() => [G(i, {
									modelValue: X(h).name,
									"onUpdate:modelValue": r[0] ||= (e) => X(h).name = e,
									class: "w-full",
									"data-testid": "roster-settings-name"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							G(a, {
								label: X(o)("roster.organizations.fields.slug"),
								error: X(p).error("slug"),
								required: ""
							}, {
								default: Z(() => [G(i, {
									modelValue: X(h).slug,
									"onUpdate:modelValue": r[1] ||= (e) => X(h).slug = e,
									class: "w-full",
									"data-testid": "roster-settings-slug"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							G(a, {
								label: X(o)("roster.organizations.fields.domains"),
								hint: X(o)("roster.organizations.domains_hint"),
								error: X(g)
							}, {
								default: Z(() => [G(s, {
									modelValue: X(h).domains,
									"onUpdate:modelValue": r[2] ||= (e) => X(h).domains = e,
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
							G(c, {
								modelValue: X(h).auto_join,
								"onUpdate:modelValue": r[3] ||= (e) => X(h).auto_join = e,
								label: X(o)("roster.organizations.fields.auto_join"),
								description: X(o)("roster.organizations.settings.auto_join_hint"),
								"data-testid": "roster-settings-auto-join"
							}, null, 8, [
								"modelValue",
								"label",
								"description"
							]),
							G(a, {
								label: X(o)("roster.organizations.settings.provisioned_status"),
								hint: X(o)("roster.organizations.settings.provisioned_status_hint"),
								error: X(p).error("provisioned_status")
							}, {
								default: Z(() => [G(l, {
									modelValue: X(h).provisioned_status,
									"onUpdate:modelValue": r[4] ||= (e) => X(h).provisioned_status = e,
									items: X(_),
									class: "w-72",
									"data-testid": "roster-settings-provisioned-status"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							G(a, {
								label: X(o)("roster.organizations.fields.status"),
								error: X(p).error("status")
							}, {
								default: Z(() => [G(l, {
									modelValue: X(h).status,
									"onUpdate:modelValue": r[5] ||= (e) => X(h).status = e,
									items: X(v),
									class: "w-72",
									"data-testid": "roster-settings-status"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							G(a, {
								label: X(o)("roster.organizations.fields.reason"),
								error: X(p).error("status_reason")
							}, {
								default: Z(() => [G(i, {
									modelValue: X(h).status_reason,
									"onUpdate:modelValue": r[6] ||= (e) => X(h).status_reason = e,
									class: "w-80",
									"data-testid": "roster-settings-status-reason"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, ["label", "error"]),
							U("div", null, [G(u, {
								type: "submit",
								icon: "i-lucide-check",
								loading: X(p).busy.value,
								"data-testid": "roster-settings-save"
							}, {
								default: Z(() => [W(Y(X(o)("roster.organizations.save")), 1)]),
								_: 1
							}, 8, ["loading"])])
						]),
						_: 1
					}, 8, ["state"])]),
					_: 1
				}),
				G(m, { "data-testid": "roster-organization-links" }, {
					header: Z(() => [U("h2", Se, Y(X(o)("roster.organizations.links.title")), 1)]),
					default: Z(() => [U("div", Ce, [
						G(e, { action: X(S) }, null, 8, ["action"]),
						t.organization.links.length === 0 ? (q(), H("p", we, Y(X(o)("roster.organizations.links.empty")), 1)) : (q(), B(y, {
							key: 1,
							data: t.organization.links,
							columns: X(b),
							"data-testid": "roster-links-table"
						}, {
							"source-cell": Z(({ row: { original: e } }) => [U("code", { "data-testid": `roster-link-${e.source}` }, Y(e.source), 9, Te)]),
							"account_number-cell": Z(({ row: { original: e } }) => [W(Y(e.account_number ?? X(o)("roster.organizations.none")), 1)]),
							"synced-cell": Z(({ row: { original: e } }) => [W(Y(X(d)(e.synced_at) ?? X(o)("roster.organizations.links.never")), 1)]),
							"actions-cell": Z(({ row: { original: e } }) => [U("div", Ee, [G(u, {
								size: "sm",
								variant: "ghost",
								icon: "i-lucide-unlink",
								loading: X(S).busy.value,
								"data-testid": `roster-unlink-${e.source}`,
								onClick: (t) => E(e)
							}, {
								default: Z(() => [W(Y(X(o)("roster.organizations.links.unlink")), 1)]),
								_: 1
							}, 8, [
								"loading",
								"data-testid",
								"onClick"
							])])]),
							_: 1
						}, 8, ["data", "columns"])),
						G(f, {
							state: X(C),
							class: "flex flex-wrap items-start gap-3",
							"data-testid": "roster-link-form",
							onSubmit: O
						}, {
							default: Z(() => [
								G(a, {
									label: X(o)("roster.organizations.fields.source"),
									hint: X(o)("roster.organizations.links.source_hint"),
									error: X(S).error("source"),
									required: ""
								}, {
									default: Z(() => [G(i, {
										modelValue: X(C).source,
										"onUpdate:modelValue": r[7] ||= (e) => X(C).source = e,
										class: "w-40",
										"data-testid": "roster-link-source"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]),
								G(a, {
									label: X(o)("roster.organizations.fields.external_id"),
									error: X(S).error("external_id"),
									required: ""
								}, {
									default: Z(() => [G(i, {
										modelValue: X(C).external_id,
										"onUpdate:modelValue": r[8] ||= (e) => X(C).external_id = e,
										class: "w-56",
										"data-testid": "roster-link-external-id"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								G(a, {
									label: X(o)("roster.organizations.fields.account_number"),
									error: X(S).error("account_number")
								}, {
									default: Z(() => [G(i, {
										modelValue: X(C).account_number,
										"onUpdate:modelValue": r[9] ||= (e) => X(C).account_number = e,
										class: "w-48",
										"data-testid": "roster-link-account-number"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								G(u, {
									type: "submit",
									icon: "i-lucide-link",
									loading: X(S).busy.value,
									class: "mt-field-label",
									"data-testid": "roster-link-submit"
								}, {
									default: Z(() => [W(Y(X(o)("roster.organizations.links.link")), 1)]),
									_: 1
								}, 8, ["loading"])
							]),
							_: 1
						}, 8, ["state"])
					])]),
					_: 1
				}),
				t.organization.personal ? V("", !0) : (q(), B(m, {
					key: 0,
					"data-testid": "roster-organization-danger"
				}, {
					header: Z(() => [U("h2", De, Y(X(o)("roster.organizations.danger.title")), 1)]),
					default: Z(() => [U("div", Oe, [
						G(e, { action: X(w) }, null, 8, ["action"]),
						G(x, {
							color: "warning",
							variant: "subtle",
							title: X(o)("roster.organizations.danger.delete_title"),
							description: X(o)("roster.organizations.danger.delete_warning")
						}, null, 8, ["title", "description"]),
						U("div", null, [G(u, {
							color: "error",
							icon: "i-lucide-trash",
							loading: X(w).busy.value,
							"data-testid": "roster-organization-delete",
							onClick: M
						}, {
							default: Z(() => [W(Y(X(o)("roster.organizations.danger.delete")), 1)]),
							_: 1
						}, 8, ["loading"])])
					])]),
					_: 1
				}))
			]);
		};
	}
}), Ae = {
	class: "flex flex-col gap-4",
	"data-testid": "roster-organization-teams"
}, je = { class: "font-semibold" }, Me = { class: "space-y-4" }, Ne = /* @__PURE__ */ K({
	__name: "OrganizationTeams",
	props: {
		client: {},
		organization: {}
	},
	emits: ["changed"],
	setup(n, { emit: r }) {
		let a = n, d = r, { t: m } = Q(), h = $(), g = z(() => a.organization.slug), _ = z(() => f(h.query.page)), v = o(async (e) => c(await e.GET("/organizations/{organization}/teams", { params: {
			path: { organization: g.value },
			query: _.value > 1 ? { page: _.value } : {}
		} })), _), y = z(() => v.data.value?.data ?? []), b = z(() => s(v.data.value)), x = z(() => [{
			id: "team",
			header: m("roster.organizations.teams.team")
		}, {
			id: "members",
			header: m("roster.organizations.fields.members"),
			meta: { class: {
				th: "text-right",
				td: "text-right tabular-nums"
			} }
		}]), C = l(), w = J({
			name: "",
			slug: ""
		});
		async function T() {
			await C.run(async () => c(await a.client.POST("/organizations/{organization}/teams", {
				params: { path: { organization: g.value } },
				body: i({
					name: w.name,
					slug: u(w.slug)
				})
			}))) && (w.name = "", w.slug = "", await v.reload(), d("changed"));
		}
		return (r, i) => {
			let a = F, o = P, s = k, c = N, l = A, u = M, d = I, f = L;
			return q(), H("div", Ae, [G(l, null, {
				header: Z(() => [U("h2", je, Y(X(m)("roster.organizations.teams.new")), 1)]),
				default: Z(() => [G(c, {
					state: X(w),
					class: "flex flex-wrap items-start gap-2",
					"data-testid": "roster-team-create-form",
					onSubmit: T
				}, {
					default: Z(() => [
						G(e, { action: X(C) }, null, 8, ["action"]),
						G(o, {
							label: X(m)("roster.organizations.fields.name"),
							error: X(C).error("name"),
							required: ""
						}, {
							default: Z(() => [G(a, {
								modelValue: X(w).name,
								"onUpdate:modelValue": i[0] ||= (e) => X(w).name = e,
								class: "w-64",
								"data-testid": "roster-team-create-name"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"]),
						G(o, {
							label: X(m)("roster.organizations.fields.slug"),
							hint: X(m)("roster.organizations.slug_hint"),
							error: X(C).error("slug")
						}, {
							default: Z(() => [G(a, {
								modelValue: X(w).slug,
								"onUpdate:modelValue": i[1] ||= (e) => X(w).slug = e,
								class: "w-64",
								"data-testid": "roster-team-create-slug"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						G(s, {
							type: "submit",
							icon: "i-lucide-plus",
							loading: X(C).busy.value,
							class: "mt-field-label",
							"data-testid": "roster-team-create"
						}, {
							default: Z(() => [W(Y(X(m)("roster.organizations.create")), 1)]),
							_: 1
						}, 8, ["loading"])
					]),
					_: 1
				}, 8, ["state"])]),
				_: 1
			}), G(p, {
				ready: !X(v).pending.value && X(v).data.value !== null,
				failed: X(v).failed.value,
				status: X(v).status.value,
				compact: ""
			}, {
				default: Z(() => [U("div", Me, [X(y).length === 0 ? (q(), B(u, {
					key: 0,
					icon: "i-lucide-users-round",
					title: X(m)("roster.organizations.teams.empty"),
					"data-testid": "roster-teams-empty"
				}, null, 8, ["title"])) : (q(), B(f, {
					key: 1,
					data: X(y),
					columns: X(x),
					"data-testid": "roster-teams-table"
				}, {
					"team-cell": Z(({ row: { original: e } }) => [G(d, {
						to: X(S)(n.organization.slug, e.slug),
						class: "font-medium text-default hover:underline",
						"data-testid": `roster-team-row-${e.slug}`
					}, {
						default: Z(() => [W(Y(e.name), 1)]),
						_: 2
					}, 1032, ["to", "data-testid"])]),
					"members-cell": Z(({ row: { original: e } }) => [W(Y(e.members_count ?? X(m)("roster.organizations.none")), 1)]),
					_: 1
				}, 8, ["data", "columns"])), G(t, {
					current: X(b).current,
					last: X(b).last,
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
}), Pe = {
	class: "space-y-4",
	"data-testid": "roster-organization"
}, Fe = {
	class: "truncate",
	"data-testid": "roster-organization-title"
}, Ie = {
	key: 0,
	class: "flex max-w-3xl flex-col gap-4",
	"data-testid": "roster-organization-deleted"
}, Le = { class: "font-semibold" }, Re = { class: "mb-4 text-sm" }, ze = {
	key: 1,
	class: "space-y-4"
}, Be = /* @__PURE__ */ K({
	__name: "index",
	setup(t) {
		let { t: n } = Q(), i = $(), a = oe(), s = z(() => String(i.params.organization)), u = z(() => b(r(i.query.tab))), d = z(() => _.map((e) => ({
			key: e,
			label: n(`roster.organizations.tabs.${e}`)
		}))), { client: f, data: m, pending: h, failed: g, status: y, reload: x } = o(async (e) => c(await e.GET("/organizations/{organization}", { params: { path: { organization: s.value } } })).data, s), S = re(!1), C = l(), j = z(() => u.value === "members" || u.value === "teams");
		function M(e) {
			a.replace({ query: {
				...i.query,
				tab: e === "members" ? void 0 : e,
				page: void 0
			} });
		}
		async function N(e) {
			e === s.value ? await x() : await a.replace({
				path: v(e),
				query: { tab: "settings" }
			});
		}
		async function P() {
			f && await C.run(async () => c(await f.POST("/organizations/{organization}/restore", { params: { path: { organization: s.value } } }))) && (S.value = !1, await x());
		}
		async function F() {
			f && window.confirm(n("roster.organizations.danger.confirm_purge")) && await C.run(async () => c(await f.DELETE("/organizations/{organization}/purge", { params: { path: { organization: s.value } } }))) && await a.push({
				path: "/roster/organizations",
				query: { trashed: "only" }
			});
		}
		return (t, r) => {
			let i = O, a = k, o = E, c = D, l = A, _ = T;
			return q(), H("div", Pe, [G(o, {
				back: "/roster/organizations",
				"back-label": X(n)("roster.organizations.title"),
				description: X(m)?.slug
			}, {
				title: Z(() => [U("span", Fe, Y(X(m)?.name ?? X(n)("roster.organizations.organization")), 1), X(S) ? (q(), B(i, {
					key: 0,
					color: "neutral",
					variant: "subtle"
				}, {
					default: Z(() => [W(Y(X(n)("roster.organizations.deleted")), 1)]),
					_: 1
				})) : V("", !0)]),
				default: Z(() => [X(j) && X(m) && !X(S) ? (q(), B(a, {
					key: 0,
					to: {
						path: "/roster/transfers",
						query: { organization: X(m).slug }
					},
					variant: "outline",
					icon: "i-lucide-arrow-up-down",
					"data-testid": "roster-organization-transfers"
				}, {
					default: Z(() => [W(Y(X(n)("roster.organizations.import_export")), 1)]),
					_: 1
				}, 8, ["to"])) : V("", !0)]),
				_: 1
			}, 8, ["back-label", "description"]), G(_, {
				"screen-key": "roster.organization",
				record: X(s)
			}, {
				default: Z(() => [G(p, {
					ready: !X(h) && X(m) !== null,
					failed: X(g),
					status: X(y)
				}, {
					default: Z(() => [X(S) && X(m) ? (q(), H("div", Ie, [
						G(e, { action: X(C) }, null, 8, ["action"]),
						G(c, {
							color: "warning",
							variant: "subtle",
							icon: "i-lucide-trash",
							title: X(n)("roster.organizations.danger.deleted"),
							description: X(n)("roster.organizations.danger.deleted_hint")
						}, null, 8, ["title", "description"]),
						U("div", null, [G(a, {
							icon: "i-lucide-undo-2",
							loading: X(C).busy.value,
							"data-testid": "roster-organization-restore",
							onClick: P
						}, {
							default: Z(() => [W(Y(X(n)("roster.organizations.restore")), 1)]),
							_: 1
						}, 8, ["loading"])]),
						G(l, null, {
							header: Z(() => [U("h2", Le, Y(X(n)("roster.organizations.danger.title")), 1)]),
							default: Z(() => [U("p", Re, Y(X(n)("roster.organizations.danger.purge_warning")), 1), G(a, {
								color: "error",
								icon: "i-lucide-trash-2",
								loading: X(C).busy.value,
								"data-testid": "roster-organization-purge",
								onClick: F
							}, {
								default: Z(() => [W(Y(X(n)("roster.organizations.purge")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						})
					])) : X(m) && X(f) ? (q(), H("div", ze, [G(w, {
						tabs: X(d),
						current: X(u),
						label: X(m).name,
						testid: "roster-organization-tab",
						"data-testid": "roster-organization-tabs",
						onSelect: M
					}, null, 8, [
						"tabs",
						"current",
						"label"
					]), X(u) === "members" ? (q(), B(ye, {
						key: 0,
						client: X(f),
						organization: X(m),
						onChanged: X(x)
					}, null, 8, [
						"client",
						"organization",
						"onChanged"
					])) : X(u) === "teams" ? (q(), B(Ne, {
						key: 1,
						client: X(f),
						organization: X(m),
						onChanged: X(x)
					}, null, 8, [
						"client",
						"organization",
						"onChanged"
					])) : X(u) === "invitations" ? (q(), B(pe, {
						key: 2,
						client: X(f),
						organization: X(m)
					}, null, 8, ["client", "organization"])) : (q(), B(ke, {
						key: 3,
						client: X(f),
						organization: X(m),
						onUpdated: N,
						onDeleted: r[0] ||= (e) => S.value = !0
					}, null, 8, ["client", "organization"]))])) : V("", !0)]),
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
export { Be as default };
