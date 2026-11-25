import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterProfileFields-BdSCb99p.js";
import { t as n } from "./RosterState-4BrqaETn.js";
import { t as r } from "./RosterStatusBadge-CjGfIXnt.js";
import { a as i, d as a, f as o, g as s, h as c, l, m as u, n as d, o as f } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as p, AtriumPageHeader as m, AtriumSelect as h, isExternalUrl as g, navigateTo as _ } from "@refactor-circus/atrium";
import { UAlert as v, UBadge as ee, UButton as te, UCard as y, UForm as b, UFormField as x, UInput as ne, ULink as re, USelect as ie } from "@refactor-circus/atrium/ui";
import { Fragment as S, computed as C, createBlock as w, createCommentVNode as T, createElementBlock as E, createElementVNode as D, createTextVNode as O, createVNode as k, defineComponent as A, isRef as j, openBlock as M, reactive as N, ref as P, renderList as F, toDisplayString as I, unref as L, watch as ae, withCtx as R } from "vue";
import { useI18n as oe } from "vue-i18n";
import { useRoute as se, useRouter as ce } from "vue-router";
// src/components/users/RosterUserMemberships.vue?vue&type=script&setup=true&lang.ts
var z = { class: "font-semibold" }, le = { class: "flex flex-col gap-4" }, ue = {
	key: 1,
	class: "text-sm text-muted"
}, de = {
	key: 2,
	class: "flex flex-col gap-1 text-sm"
}, B = ["data-testid"], fe = /* @__PURE__ */ A({
	__name: "RosterUserMemberships",
	props: {
		client: {},
		user: {},
		memberships: {}
	},
	emits: ["switched", "joined"],
	setup(t, { emit: n }) {
		let r = t, i = n, { t: a } = oe(), s = u(), c = P(null), d = N({
			organization: r.user.current_organization ?? r.memberships[0]?.slug ?? "",
			team: r.user.current_team ?? ""
		});
		ae(() => [r.user.current_organization, r.user.current_team], () => {
			d.organization = r.user.current_organization ?? r.memberships[0]?.slug ?? "", d.team = r.user.current_team ?? "";
		});
		let f = o(() => d.organization || null), p = C(() => String(r.user.id)), m = C(() => r.memberships.map((e) => ({
			label: e.name,
			value: e.slug
		}))), g = C(() => f.value.map((e) => ({
			label: e.name,
			value: e.slug
		})));
		async function _() {
			c.value = null, await s.run(async () => l(await r.client.PUT("/users/{user}/context", {
				params: { path: { user: p.value } },
				body: {
					organization: d.organization,
					team: d.team || null
				}
			}))) && i("switched");
		}
		async function ne() {
			c.value = null;
			let e = 0;
			await s.run(async () => {
				e = l(await r.client.POST("/users/{user}/domain-join", { params: { path: { user: p.value } } })).data.length;
			}) && (c.value = a("roster.users.memberships.joined", { count: e }), i("joined"));
		}
		return (n, r) => {
			let i = v, o = re, l = ee, u = ie, f = x, p = h, C = te, A = b, j = y;
			return M(), w(j, { "data-testid": "roster-user-memberships" }, {
				header: R(() => [D("h2", z, I(L(a)("roster.users.memberships.title")), 1)]),
				default: R(() => [D("div", le, [
					k(e, { action: L(s) }, null, 8, ["action"]),
					L(c) ? (M(), w(i, {
						key: 0,
						color: "success",
						variant: "subtle",
						title: L(c),
						"data-testid": "roster-user-notice"
					}, null, 8, ["title"])) : T("", !0),
					t.memberships.length === 0 ? (M(), E("p", ue, I(L(a)("roster.users.memberships.none")), 1)) : (M(), E("ul", de, [(M(!0), E(S, null, F(t.memberships, (e) => (M(), E("li", {
						key: e.slug,
						"data-testid": `roster-membership-${e.slug}`
					}, [k(o, {
						to: `/roster/organizations/${encodeURIComponent(e.slug)}`,
						class: "font-medium text-default hover:underline"
					}, {
						default: R(() => [O(I(e.name), 1)]),
						_: 2
					}, 1032, ["to"]), e.slug === t.user.current_organization ? (M(), w(l, {
						key: 0,
						variant: "subtle",
						class: "ml-2"
					}, {
						default: R(() => [O(I(L(a)("roster.users.memberships.current")), 1)]),
						_: 1
					})) : T("", !0)], 8, B))), 128))])),
					t.memberships.length > 0 ? (M(), w(A, {
						key: 3,
						state: L(d),
						class: "flex flex-wrap items-start gap-2",
						"data-testid": "roster-user-context-form",
						onSubmit: _
					}, {
						default: R(() => [
							k(f, {
								label: L(a)("roster.users.memberships.organization"),
								error: L(s).error("organization")
							}, {
								default: R(() => [k(u, {
									modelValue: L(d).organization,
									"onUpdate:modelValue": r[0] ||= (e) => L(d).organization = e,
									items: L(m),
									class: "w-56",
									"data-testid": "roster-user-context-organization"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							k(f, {
								label: L(a)("roster.users.memberships.team"),
								error: L(s).error("team")
							}, {
								default: R(() => [k(p, {
									modelValue: L(d).team,
									"onUpdate:modelValue": r[1] ||= (e) => L(d).team = e,
									items: L(g),
									blank: L(a)("roster.users.memberships.no_team"),
									class: "w-56",
									"data-testid": "roster-user-context-team"
								}, null, 8, [
									"modelValue",
									"items",
									"blank"
								])]),
								_: 1
							}, 8, ["label", "error"]),
							k(C, {
								type: "submit",
								icon: "i-lucide-arrow-left-right",
								loading: L(s).busy.value,
								class: "mt-field-label",
								"data-testid": "roster-user-context-switch"
							}, {
								default: R(() => [O(I(L(a)("roster.users.memberships.switch")), 1)]),
								_: 1
							}, 8, ["loading"])
						]),
						_: 1
					}, 8, ["state"])) : T("", !0),
					D("div", null, [k(C, {
						variant: "ghost",
						icon: "i-lucide-globe",
						loading: L(s).busy.value,
						"data-testid": "roster-user-domain-join",
						onClick: ne
					}, {
						default: R(() => [O(I(L(a)("roster.users.memberships.domain_join")), 1)]),
						_: 1
					}, 8, ["loading"])])
				])]),
				_: 1
			});
		};
	}
}), pe = {
	class: "space-y-4",
	"data-testid": "roster-user"
}, me = {
	class: "truncate",
	"data-testid": "roster-user-title"
}, he = {
	key: 0,
	class: "flex max-w-3xl flex-col gap-4",
	"data-testid": "roster-user-deleted"
}, ge = { class: "font-semibold" }, _e = { class: "mb-4 text-sm" }, ve = {
	key: 1,
	class: "grid gap-4 lg:grid-cols-2"
}, ye = { class: "font-semibold" }, be = { class: "font-semibold" }, xe = { class: "font-semibold" }, Se = { class: "flex flex-col gap-3" }, Ce = { class: "text-sm" }, we = { class: "flex flex-wrap items-start gap-3" }, Te = { class: "font-semibold" }, Ee = { class: "flex flex-col gap-3" }, De = {
	key: 0,
	class: "text-sm"
}, Oe = { class: "lg:col-span-2" }, ke = { class: "font-semibold" }, Ae = { class: "flex flex-col gap-3" }, je = { class: "font-semibold" }, Me = { class: "flex flex-col gap-3" }, Ne = { class: "text-sm" }, V = /* @__PURE__ */ A({
	__name: "[user]",
	setup(o) {
		let { t: h } = oe(), ee = se(), re = ce(), S = C(() => String(ee.params.user)), A = () => ({ params: { path: { user: S.value } } }), { client: F, data: z, pending: le, failed: ue, status: de, reload: B } = c(async (e) => l(await e.GET("/users/{user}", A())).data), V = c(async (e) => l(await e.GET("/organizations", { params: { query: {
			user: S.value,
			per_page: 100
		} } })).data), Pe = C(() => s(z.value) ?? h("roster.users.user")), H = P(!1), U = u(), W = N({
			name: "",
			email: "",
			password: ""
		}), G = u(), K = P(f()), q = u(), J = P(""), Y = u(), X = N({
			status: void 0,
			reason: ""
		}), Z = u(), Q = P(""), $ = u(), Fe = C(() => {
			let e = z.value?.status;
			return [
				"active",
				"suspended",
				"deactivated"
			].filter((t) => t !== e && (e !== "pending" || t !== "active"));
		}), Ie = C(() => Fe.value.map((e) => ({
			label: h(`roster.users.statuses.${e}`),
			value: e
		})));
		ae(z, (e) => {
			e && (W.name = e.name ?? "", W.email = e.email ?? "", W.password = "", K.value = f(e.profile), X.status = Fe.value[0], X.reason = "");
		}, { immediate: !0 });
		async function Le() {
			let e = {};
			W.name.trim() !== "" && (e.name = W.name), e.email = W.email, W.password !== "" && (e.password = W.password), F && await U.run(async () => l(await F.PATCH("/users/{user}", {
				...A(),
				body: a(e)
			}))) && await B();
		}
		async function Re() {
			F && await G.run(async () => l(await F.PATCH("/users/{user}/profile", {
				...A(),
				body: i(K.value)
			}))) && await B();
		}
		async function ze() {
			F && await q.run(async () => l(await F.POST("/users/{user}/approve", A()))) && await B();
		}
		async function Be() {
			F && await q.run(async () => l(await F.POST("/users/{user}/reject", {
				...A(),
				body: { reason: d(J.value) }
			}))) && (J.value = "", await B());
		}
		async function Ve() {
			if (!F || X.status === void 0) return;
			let e = d(X.reason), t = X.status;
			await Y.run(async () => {
				switch (t) {
					case "suspended": return l(await F.POST("/users/{user}/suspend", {
						...A(),
						body: { reason: e }
					}));
					case "deactivated": return l(await F.POST("/users/{user}/deactivate", {
						...A(),
						body: { reason: e }
					}));
					default: return l(await F.POST("/users/{user}/reactivate", A()));
				}
			}) && await B();
		}
		async function He() {
			if (!F) return;
			let e = { url: "" };
			await Z.run(async () => {
				let t = l(await F.POST("/users/{user}/impersonate", {
					...A(),
					body: { reason: Q.value }
				})).url;
				if (typeof t != "string" || !g(t)) throw Error(h("roster.users.impersonate.unsafe_link"));
				e.url = t;
			}) && await _(e.url, { external: !0 });
		}
		async function Ue() {
			F && window.confirm(h("roster.users.danger.confirm_delete")) && await $.run(async () => l(await F.DELETE("/users/{user}", A()))) && (H.value = !0);
		}
		async function We() {
			F && await $.run(async () => l(await F.POST("/users/{user}/restore", A()))) && (H.value = !1, await B());
		}
		async function Ge() {
			F && window.confirm(h("roster.users.danger.confirm_purge")) && await $.run(async () => l(await F.DELETE("/users/{user}/purge", A()))) && await re.push({
				path: "/roster/users",
				query: { trashed: "only" }
			});
		}
		return (i, a) => {
			let o = m, s = v, c = te, l = y, u = ne, d = x, f = b, g = ie, _ = p;
			return M(), E("div", pe, [k(o, {
				back: "/roster/users",
				"back-label": L(h)("roster.users.title"),
				description: L(z)?.email
			}, {
				title: R(() => [D("span", me, I(L(Pe)), 1), L(z) && !L(H) ? (M(), w(r, {
					key: 0,
					status: L(z).status,
					"data-testid": "roster-user-status-badge"
				}, null, 8, ["status"])) : T("", !0)]),
				_: 1
			}, 8, ["back-label", "description"]), k(_, {
				"screen-key": "roster.user",
				record: L(S)
			}, {
				default: R(() => [k(n, {
					ready: !L(le) && L(z) !== null,
					failed: L(ue),
					status: L(de)
				}, {
					default: R(() => [L(H) && L(z) ? (M(), E("div", he, [
						k(e, { action: L($) }, null, 8, ["action"]),
						k(s, {
							color: "warning",
							variant: "subtle",
							icon: "i-lucide-trash",
							title: L(h)("roster.users.danger.deleted"),
							description: L(h)("roster.users.danger.deleted_hint")
						}, null, 8, ["title", "description"]),
						D("div", null, [k(c, {
							icon: "i-lucide-undo-2",
							loading: L($).busy.value,
							"data-testid": "roster-user-restore",
							onClick: We
						}, {
							default: R(() => [O(I(L(h)("roster.users.restore")), 1)]),
							_: 1
						}, 8, ["loading"])]),
						k(l, null, {
							header: R(() => [D("h2", ge, I(L(h)("roster.users.danger.title")), 1)]),
							default: R(() => [D("p", _e, I(L(h)("roster.users.danger.purge_warning")), 1), k(c, {
								color: "error",
								icon: "i-lucide-trash-2",
								loading: L($).busy.value,
								"data-testid": "roster-user-purge",
								onClick: Ge
							}, {
								default: R(() => [O(I(L(h)("roster.users.purge")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						})
					])) : L(z) ? (M(), E("div", ve, [
						k(l, null, {
							header: R(() => [D("h2", ye, I(L(h)("roster.users.account.title")), 1)]),
							default: R(() => [k(f, {
								state: L(W),
								class: "flex flex-col gap-4",
								"data-testid": "roster-user-account-form",
								onSubmit: Le
							}, {
								default: R(() => [
									k(e, { action: L(U) }, null, 8, ["action"]),
									k(d, {
										label: L(h)("roster.users.fields.name"),
										error: L(U).error("name")
									}, {
										default: R(() => [k(u, {
											modelValue: L(W).name,
											"onUpdate:modelValue": a[0] ||= (e) => L(W).name = e,
											class: "w-full",
											"data-testid": "roster-account-name"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									k(d, {
										label: L(h)("roster.users.fields.email"),
										error: L(U).error("email"),
										required: ""
									}, {
										default: R(() => [k(u, {
											modelValue: L(W).email,
											"onUpdate:modelValue": a[1] ||= (e) => L(W).email = e,
											type: "email",
											class: "w-full",
											"data-testid": "roster-account-email"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									k(d, {
										label: L(h)("roster.users.fields.password"),
										hint: L(h)("roster.users.account.password_hint"),
										error: L(U).error("password")
									}, {
										default: R(() => [k(u, {
											modelValue: L(W).password,
											"onUpdate:modelValue": a[2] ||= (e) => L(W).password = e,
											type: "password",
											class: "w-full",
											"data-testid": "roster-account-password"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									D("div", null, [k(c, {
										type: "submit",
										icon: "i-lucide-check",
										loading: L(U).busy.value,
										"data-testid": "roster-account-save"
									}, {
										default: R(() => [O(I(L(h)("roster.users.save")), 1)]),
										_: 1
									}, 8, ["loading"])])
								]),
								_: 1
							}, 8, ["state"])]),
							_: 1
						}),
						k(l, null, {
							header: R(() => [D("h2", be, I(L(h)("roster.users.profile.title")), 1)]),
							default: R(() => [k(f, {
								class: "flex flex-col gap-4",
								"data-testid": "roster-user-profile-form",
								onSubmit: Re
							}, {
								default: R(() => [
									k(e, { action: L(G) }, null, 8, ["action"]),
									k(t, {
										modelValue: L(K),
										"onUpdate:modelValue": a[3] ||= (e) => j(K) ? K.value = e : null,
										errors: L(G).errors.value
									}, null, 8, ["modelValue", "errors"]),
									D("div", null, [k(c, {
										type: "submit",
										icon: "i-lucide-check",
										loading: L(G).busy.value,
										"data-testid": "roster-profile-save"
									}, {
										default: R(() => [O(I(L(h)("roster.users.save")), 1)]),
										_: 1
									}, 8, ["loading"])])
								]),
								_: 1
							})]),
							_: 1
						}),
						L(z).status === "pending" ? (M(), w(l, {
							key: 0,
							class: "lg:col-span-2",
							"data-testid": "roster-user-approval"
						}, {
							header: R(() => [D("h2", xe, I(L(h)("roster.users.approval.title")), 1)]),
							default: R(() => [D("div", Se, [
								D("p", Ce, I(L(h)("roster.users.approval.hint")), 1),
								k(e, { action: L(q) }, null, 8, ["action"]),
								D("div", we, [k(c, {
									icon: "i-lucide-check",
									loading: L(q).busy.value,
									class: "mt-field-label",
									"data-testid": "roster-user-approve",
									onClick: ze
								}, {
									default: R(() => [O(I(L(h)("roster.users.approve")), 1)]),
									_: 1
								}, 8, ["loading"]), k(f, {
									class: "flex items-start gap-2",
									"data-testid": "roster-user-reject-form",
									onSubmit: Be
								}, {
									default: R(() => [k(d, {
										label: L(h)("roster.users.fields.reason"),
										error: L(q).error("reason")
									}, {
										default: R(() => [k(u, {
											modelValue: L(J),
											"onUpdate:modelValue": a[4] ||= (e) => j(J) ? J.value = e : null,
											class: "w-64",
											"data-testid": "roster-user-reject-reason"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]), k(c, {
										type: "submit",
										color: "error",
										icon: "i-lucide-x",
										loading: L(q).busy.value,
										class: "mt-field-label",
										"data-testid": "roster-user-reject"
									}, {
										default: R(() => [O(I(L(h)("roster.users.approval.reject")), 1)]),
										_: 1
									}, 8, ["loading"])]),
									_: 1
								})])
							])]),
							_: 1
						})) : T("", !0),
						k(l, { class: "lg:col-span-2" }, {
							header: R(() => [D("h2", Te, I(L(h)("roster.users.status.title")), 1)]),
							default: R(() => [D("div", Ee, [
								L(z).status_reason ? (M(), E("p", De, I(L(h)("roster.users.fields.reason")) + ": " + I(L(z).status_reason), 1)) : T("", !0),
								k(e, { action: L(Y) }, null, 8, ["action"]),
								k(f, {
									state: L(X),
									class: "flex flex-wrap items-start gap-3",
									"data-testid": "roster-user-status-form",
									onSubmit: Ve
								}, {
									default: R(() => [
										k(d, { label: L(h)("roster.users.status.change_to") }, {
											default: R(() => [k(g, {
												modelValue: L(X).status,
												"onUpdate:modelValue": a[5] ||= (e) => L(X).status = e,
												items: L(Ie),
												class: "w-56",
												"data-testid": "roster-user-new-status"
											}, null, 8, ["modelValue", "items"])]),
											_: 1
										}, 8, ["label"]),
										k(d, {
											label: L(h)("roster.users.fields.reason"),
											hint: L(h)("roster.users.status.reason_hint"),
											error: L(Y).error("reason")
										}, {
											default: R(() => [k(u, {
												modelValue: L(X).reason,
												"onUpdate:modelValue": a[6] ||= (e) => L(X).reason = e,
												class: "w-80",
												"data-testid": "roster-user-status-reason"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, [
											"label",
											"hint",
											"error"
										]),
										k(c, {
											type: "submit",
											variant: "outline",
											icon: "i-lucide-refresh-cw",
											loading: L(Y).busy.value,
											class: "mt-field-label",
											"data-testid": "roster-user-change-status"
										}, {
											default: R(() => [O(I(L(h)("roster.users.status.update")), 1)]),
											_: 1
										}, 8, ["loading"])
									]),
									_: 1
								}, 8, ["state"])
							])]),
							_: 1
						}),
						D("div", Oe, [L(F) ? (M(), w(fe, {
							key: 0,
							client: L(F),
							user: L(z),
							memberships: L(V).data.value ?? [],
							onSwitched: L(B),
							onJoined: L(V).reload
						}, null, 8, [
							"client",
							"user",
							"memberships",
							"onSwitched",
							"onJoined"
						])) : T("", !0)]),
						k(l, {
							class: "lg:col-span-2",
							"data-testid": "roster-user-impersonate"
						}, {
							header: R(() => [D("h2", ke, I(L(h)("roster.users.impersonate.title")), 1)]),
							default: R(() => [D("div", Ae, [k(e, { action: L(Z) }, null, 8, ["action"]), k(f, {
								class: "flex flex-wrap items-start gap-2",
								"data-testid": "roster-user-impersonate-form",
								onSubmit: He
							}, {
								default: R(() => [k(d, {
									label: L(h)("roster.users.fields.reason"),
									hint: L(h)("roster.users.impersonate.reason_hint"),
									error: L(Z).error("reason"),
									required: ""
								}, {
									default: R(() => [k(u, {
										modelValue: L(Q),
										"onUpdate:modelValue": a[7] ||= (e) => j(Q) ? Q.value = e : null,
										class: "w-80",
										"data-testid": "roster-user-impersonation-reason"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]), k(c, {
									type: "submit",
									color: "warning",
									icon: "i-lucide-eye",
									loading: L(Z).busy.value,
									class: "mt-field-label",
									"data-testid": "roster-user-impersonate-submit"
								}, {
									default: R(() => [O(I(L(h)("roster.users.impersonate.start")), 1)]),
									_: 1
								}, 8, ["loading"])]),
								_: 1
							})])]),
							_: 1
						}),
						k(l, {
							class: "lg:col-span-2",
							"data-testid": "roster-user-danger"
						}, {
							header: R(() => [D("h2", je, I(L(h)("roster.users.danger.title")), 1)]),
							default: R(() => [D("div", Me, [
								k(e, { action: L($) }, null, 8, ["action"]),
								D("p", Ne, I(L(h)("roster.users.danger.delete_warning")), 1),
								D("div", null, [k(c, {
									color: "error",
									icon: "i-lucide-trash",
									loading: L($).busy.value,
									"data-testid": "roster-user-delete",
									onClick: Ue
								}, {
									default: R(() => [O(I(L(h)("roster.users.danger.delete")), 1)]),
									_: 1
								}, 8, ["loading"])])
							])]),
							_: 1
						})
					])) : T("", !0)]),
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
export { V as default };
