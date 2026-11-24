import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterProfileFields-BdSCb99p.js";
import { t as ee } from "./RosterState-4BrqaETn.js";
import { t as n } from "./RosterStatusBadge-CjGfIXnt.js";
import { a as r, d as i, f as a, g as o, h as s, l as c, m as l, n as u, o as d } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as f, isExternalUrl as p, navigateTo as m } from "@refactor-circus/atrium";
import { UAlert as te, UBadge as h, UButton as ne, UCard as re, UForm as ie, UFormField as ae, UInput as g, ULink as _, UPageHeader as v, USelect as y } from "@refactor-circus/atrium/ui";
import { Fragment as b, computed as x, createBlock as S, createCommentVNode as C, createElementBlock as w, createElementVNode as T, createSlots as oe, createTextVNode as E, createVNode as D, defineComponent as O, isRef as k, openBlock as A, reactive as j, ref as M, renderList as N, toDisplayString as P, unref as F, watch as I, withCtx as L } from "vue";
import { useI18n as se } from "vue-i18n";
import { useRoute as ce, useRouter as le } from "vue-router";
// src/components/users/RosterUserMemberships.vue?vue&type=script&setup=true&lang.ts
var R = { class: "font-semibold" }, z = { class: "flex flex-col gap-4" }, ue = {
	key: 1,
	class: "text-sm text-muted"
}, de = {
	key: 2,
	class: "flex flex-col gap-1 text-sm"
}, fe = ["data-testid"], B = "*", pe = /* @__PURE__ */ O({
	__name: "RosterUserMemberships",
	props: {
		client: {},
		user: {},
		memberships: {}
	},
	emits: ["switched", "joined"],
	setup(t, { emit: ee }) {
		let n = t, r = ee, { t: i } = se(), o = l(), s = M(null), u = j({
			organization: n.user.current_organization ?? n.memberships[0]?.slug ?? "",
			team: n.user.current_team ?? ""
		});
		I(() => [n.user.current_organization, n.user.current_team], () => {
			u.organization = n.user.current_organization ?? n.memberships[0]?.slug ?? "", u.team = n.user.current_team ?? "";
		});
		let d = a(() => u.organization || null), f = x(() => String(n.user.id)), p = x(() => n.memberships.map((e) => ({
			label: e.name,
			value: e.slug
		}))), m = x(() => [{
			label: i("roster.users.memberships.no_team"),
			value: B
		}, ...d.value.map((e) => ({
			label: e.name,
			value: e.slug
		}))]), g = x({
			get: () => u.team || B,
			set: (e) => {
				u.team = e === B ? "" : e;
			}
		});
		async function v() {
			s.value = null, await o.run(async () => c(await n.client.PUT("/users/{user}/context", {
				params: { path: { user: f.value } },
				body: {
					organization: u.organization,
					team: u.team || null
				}
			}))) && r("switched");
		}
		async function oe() {
			s.value = null;
			let e = 0;
			await o.run(async () => {
				e = c(await n.client.POST("/users/{user}/domain-join", { params: { path: { user: f.value } } })).data.length;
			}) && (s.value = i("roster.users.memberships.joined", { count: e }), r("joined"));
		}
		return (ee, n) => {
			let r = te, a = _, c = h, l = y, d = ae, f = ne, x = ie, O = re;
			return A(), S(O, { "data-testid": "roster-user-memberships" }, {
				header: L(() => [T("h2", R, P(F(i)("roster.users.memberships.title")), 1)]),
				default: L(() => [T("div", z, [
					D(e, { action: F(o) }, null, 8, ["action"]),
					F(s) ? (A(), S(r, {
						key: 0,
						color: "success",
						variant: "subtle",
						title: F(s),
						"data-testid": "roster-user-notice"
					}, null, 8, ["title"])) : C("", !0),
					t.memberships.length === 0 ? (A(), w("p", ue, P(F(i)("roster.users.memberships.none")), 1)) : (A(), w("ul", de, [(A(!0), w(b, null, N(t.memberships, (e) => (A(), w("li", {
						key: e.slug,
						"data-testid": `roster-membership-${e.slug}`
					}, [D(a, {
						to: `/roster/organizations/${encodeURIComponent(e.slug)}`,
						class: "font-medium text-default hover:underline"
					}, {
						default: L(() => [E(P(e.name), 1)]),
						_: 2
					}, 1032, ["to"]), e.slug === t.user.current_organization ? (A(), S(c, {
						key: 0,
						variant: "subtle",
						class: "ml-2"
					}, {
						default: L(() => [E(P(F(i)("roster.users.memberships.current")), 1)]),
						_: 1
					})) : C("", !0)], 8, fe))), 128))])),
					t.memberships.length > 0 ? (A(), S(x, {
						key: 3,
						state: F(u),
						class: "flex flex-wrap items-end gap-2",
						"data-testid": "roster-user-context-form",
						onSubmit: v
					}, {
						default: L(() => [
							D(d, {
								label: F(i)("roster.users.memberships.organization"),
								error: F(o).error("organization")
							}, {
								default: L(() => [D(l, {
									modelValue: F(u).organization,
									"onUpdate:modelValue": n[0] ||= (e) => F(u).organization = e,
									items: F(p),
									class: "w-56",
									"data-testid": "roster-user-context-organization"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							D(d, {
								label: F(i)("roster.users.memberships.team"),
								error: F(o).error("team")
							}, {
								default: L(() => [D(l, {
									modelValue: F(g),
									"onUpdate:modelValue": n[1] ||= (e) => k(g) ? g.value = e : null,
									items: F(m),
									class: "w-56",
									"data-testid": "roster-user-context-team"
								}, null, 8, ["modelValue", "items"])]),
								_: 1
							}, 8, ["label", "error"]),
							D(f, {
								type: "submit",
								icon: "i-lucide-arrow-left-right",
								loading: F(o).busy.value,
								"data-testid": "roster-user-context-switch"
							}, {
								default: L(() => [E(P(F(i)("roster.users.memberships.switch")), 1)]),
								_: 1
							}, 8, ["loading"])
						]),
						_: 1
					}, 8, ["state"])) : C("", !0),
					T("div", null, [D(f, {
						variant: "ghost",
						icon: "i-lucide-globe",
						loading: F(o).busy.value,
						"data-testid": "roster-user-domain-join",
						onClick: oe
					}, {
						default: L(() => [E(P(F(i)("roster.users.memberships.domain_join")), 1)]),
						_: 1
					}, 8, ["loading"])])
				])]),
				_: 1
			});
		};
	}
}), me = {
	class: "space-y-4",
	"data-testid": "roster-user"
}, he = { class: "flex items-center gap-3" }, ge = {
	class: "truncate",
	"data-testid": "roster-user-title"
}, _e = {
	key: 0,
	class: "flex max-w-3xl flex-col gap-4",
	"data-testid": "roster-user-deleted"
}, ve = { class: "font-semibold" }, ye = { class: "mb-4 text-sm" }, be = {
	key: 1,
	class: "grid gap-4 lg:grid-cols-2"
}, xe = { class: "font-semibold" }, Se = { class: "font-semibold" }, Ce = { class: "font-semibold" }, we = { class: "flex flex-col gap-3" }, Te = { class: "text-sm" }, Ee = { class: "flex flex-wrap items-end gap-3" }, De = { class: "font-semibold" }, Oe = { class: "flex flex-col gap-3" }, ke = {
	key: 0,
	class: "text-sm"
}, Ae = { class: "lg:col-span-2" }, je = { class: "font-semibold" }, Me = { class: "flex flex-col gap-3" }, Ne = { class: "font-semibold" }, Pe = { class: "flex flex-col gap-3" }, Fe = { class: "text-sm" }, V = /* @__PURE__ */ O({
	__name: "[user]",
	setup(a) {
		let { t: h } = se(), _ = ce(), b = le(), O = x(() => String(_.params.user)), N = () => ({ params: { path: { user: O.value } } }), { client: R, data: z, pending: ue, failed: de, status: fe, reload: B } = s(async (e) => c(await e.GET("/users/{user}", N())).data), V = s(async (e) => c(await e.GET("/organizations", { params: { query: {
			user: O.value,
			per_page: 100
		} } })).data), Ie = x(() => o(z.value) ?? h("roster.users.user")), H = M(!1), U = l(), W = j({
			name: "",
			email: "",
			password: ""
		}), G = l(), K = M(d()), q = l(), J = M(""), Y = l(), X = j({
			status: void 0,
			reason: ""
		}), Z = l(), Q = M(""), $ = l(), Le = x(() => {
			let e = z.value?.status;
			return [
				"active",
				"suspended",
				"deactivated"
			].filter((t) => t !== e && (e !== "pending" || t !== "active"));
		}), Re = x(() => Le.value.map((e) => ({
			label: h(`roster.users.statuses.${e}`),
			value: e
		})));
		I(z, (e) => {
			e && (W.name = e.name ?? "", W.email = e.email ?? "", W.password = "", K.value = d(e.profile), X.status = Le.value[0], X.reason = "");
		}, { immediate: !0 });
		async function ze() {
			let e = {};
			W.name.trim() !== "" && (e.name = W.name), e.email = W.email, W.password !== "" && (e.password = W.password), R && await U.run(async () => c(await R.PATCH("/users/{user}", {
				...N(),
				body: i(e)
			}))) && await B();
		}
		async function Be() {
			R && await G.run(async () => c(await R.PATCH("/users/{user}/profile", {
				...N(),
				body: r(K.value)
			}))) && await B();
		}
		async function Ve() {
			R && await q.run(async () => c(await R.POST("/users/{user}/approve", N()))) && await B();
		}
		async function He() {
			R && await q.run(async () => c(await R.POST("/users/{user}/reject", {
				...N(),
				body: { reason: u(J.value) }
			}))) && (J.value = "", await B());
		}
		async function Ue() {
			if (!R || X.status === void 0) return;
			let e = u(X.reason), t = X.status;
			await Y.run(async () => {
				switch (t) {
					case "suspended": return c(await R.POST("/users/{user}/suspend", {
						...N(),
						body: { reason: e }
					}));
					case "deactivated": return c(await R.POST("/users/{user}/deactivate", {
						...N(),
						body: { reason: e }
					}));
					default: return c(await R.POST("/users/{user}/reactivate", N()));
				}
			}) && await B();
		}
		async function We() {
			if (!R) return;
			let e = { url: "" };
			await Z.run(async () => {
				let t = c(await R.POST("/users/{user}/impersonate", {
					...N(),
					body: { reason: Q.value }
				})).url;
				if (typeof t != "string" || !p(t)) throw Error(h("roster.users.impersonate.unsafe_link"));
				e.url = t;
			}) && await m(e.url, { external: !0 });
		}
		async function Ge() {
			R && window.confirm(h("roster.users.danger.confirm_delete")) && await $.run(async () => c(await R.DELETE("/users/{user}", N()))) && (H.value = !0);
		}
		async function Ke() {
			R && await $.run(async () => c(await R.POST("/users/{user}/restore", N()))) && (H.value = !1, await B());
		}
		async function qe() {
			R && window.confirm(h("roster.users.danger.confirm_purge")) && await $.run(async () => c(await R.DELETE("/users/{user}/purge", N()))) && await b.push({
				path: "/roster/users",
				query: { trashed: "only" }
			});
		}
		return (r, i) => {
			let a = ne, o = v, s = te, c = re, l = g, u = ae, d = ie, p = y, m = f;
			return A(), w("div", me, [D(o, null, oe({
				title: L(() => [T("span", he, [
					D(a, {
						to: "/roster/users",
						icon: "i-lucide-arrow-left",
						color: "neutral",
						variant: "ghost",
						"aria-label": F(h)("roster.users.title")
					}, null, 8, ["aria-label"]),
					T("span", ge, P(F(Ie)), 1),
					F(z) && !F(H) ? (A(), S(n, {
						key: 0,
						status: F(z).status,
						"data-testid": "roster-user-status-badge"
					}, null, 8, ["status"])) : C("", !0)
				])]),
				_: 2
			}, [F(z)?.email ? {
				name: "description",
				fn: L(() => [E(P(F(z).email), 1)]),
				key: "0"
			} : void 0]), 1024), D(m, {
				"screen-key": "roster.user",
				record: F(O)
			}, {
				default: L(() => [D(ee, {
					ready: !F(ue) && F(z) !== null,
					failed: F(de),
					status: F(fe)
				}, {
					default: L(() => [F(H) && F(z) ? (A(), w("div", _e, [
						D(e, { action: F($) }, null, 8, ["action"]),
						D(s, {
							color: "warning",
							variant: "subtle",
							icon: "i-lucide-trash",
							title: F(h)("roster.users.danger.deleted"),
							description: F(h)("roster.users.danger.deleted_hint")
						}, null, 8, ["title", "description"]),
						T("div", null, [D(a, {
							icon: "i-lucide-undo-2",
							loading: F($).busy.value,
							"data-testid": "roster-user-restore",
							onClick: Ke
						}, {
							default: L(() => [E(P(F(h)("roster.users.restore")), 1)]),
							_: 1
						}, 8, ["loading"])]),
						D(c, null, {
							header: L(() => [T("h2", ve, P(F(h)("roster.users.danger.title")), 1)]),
							default: L(() => [T("p", ye, P(F(h)("roster.users.danger.purge_warning")), 1), D(a, {
								color: "error",
								icon: "i-lucide-trash-2",
								loading: F($).busy.value,
								"data-testid": "roster-user-purge",
								onClick: qe
							}, {
								default: L(() => [E(P(F(h)("roster.users.purge")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						})
					])) : F(z) ? (A(), w("div", be, [
						D(c, null, {
							header: L(() => [T("h2", xe, P(F(h)("roster.users.account.title")), 1)]),
							default: L(() => [D(d, {
								state: F(W),
								class: "flex flex-col gap-4",
								"data-testid": "roster-user-account-form",
								onSubmit: ze
							}, {
								default: L(() => [
									D(e, { action: F(U) }, null, 8, ["action"]),
									D(u, {
										label: F(h)("roster.users.fields.name"),
										error: F(U).error("name")
									}, {
										default: L(() => [D(l, {
											modelValue: F(W).name,
											"onUpdate:modelValue": i[0] ||= (e) => F(W).name = e,
											class: "w-full",
											"data-testid": "roster-account-name"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									D(u, {
										label: F(h)("roster.users.fields.email"),
										error: F(U).error("email"),
										required: ""
									}, {
										default: L(() => [D(l, {
											modelValue: F(W).email,
											"onUpdate:modelValue": i[1] ||= (e) => F(W).email = e,
											type: "email",
											class: "w-full",
											"data-testid": "roster-account-email"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]),
									D(u, {
										label: F(h)("roster.users.fields.password"),
										hint: F(h)("roster.users.account.password_hint"),
										error: F(U).error("password")
									}, {
										default: L(() => [D(l, {
											modelValue: F(W).password,
											"onUpdate:modelValue": i[2] ||= (e) => F(W).password = e,
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
									T("div", null, [D(a, {
										type: "submit",
										icon: "i-lucide-check",
										loading: F(U).busy.value,
										"data-testid": "roster-account-save"
									}, {
										default: L(() => [E(P(F(h)("roster.users.save")), 1)]),
										_: 1
									}, 8, ["loading"])])
								]),
								_: 1
							}, 8, ["state"])]),
							_: 1
						}),
						D(c, null, {
							header: L(() => [T("h2", Se, P(F(h)("roster.users.profile.title")), 1)]),
							default: L(() => [D(d, {
								class: "flex flex-col gap-4",
								"data-testid": "roster-user-profile-form",
								onSubmit: Be
							}, {
								default: L(() => [
									D(e, { action: F(G) }, null, 8, ["action"]),
									D(t, {
										modelValue: F(K),
										"onUpdate:modelValue": i[3] ||= (e) => k(K) ? K.value = e : null,
										errors: F(G).errors.value
									}, null, 8, ["modelValue", "errors"]),
									T("div", null, [D(a, {
										type: "submit",
										icon: "i-lucide-check",
										loading: F(G).busy.value,
										"data-testid": "roster-profile-save"
									}, {
										default: L(() => [E(P(F(h)("roster.users.save")), 1)]),
										_: 1
									}, 8, ["loading"])])
								]),
								_: 1
							})]),
							_: 1
						}),
						F(z).status === "pending" ? (A(), S(c, {
							key: 0,
							class: "lg:col-span-2",
							"data-testid": "roster-user-approval"
						}, {
							header: L(() => [T("h2", Ce, P(F(h)("roster.users.approval.title")), 1)]),
							default: L(() => [T("div", we, [
								T("p", Te, P(F(h)("roster.users.approval.hint")), 1),
								D(e, { action: F(q) }, null, 8, ["action"]),
								T("div", Ee, [D(a, {
									icon: "i-lucide-check",
									loading: F(q).busy.value,
									"data-testid": "roster-user-approve",
									onClick: Ve
								}, {
									default: L(() => [E(P(F(h)("roster.users.approve")), 1)]),
									_: 1
								}, 8, ["loading"]), D(d, {
									class: "flex items-end gap-2",
									"data-testid": "roster-user-reject-form",
									onSubmit: He
								}, {
									default: L(() => [D(u, {
										label: F(h)("roster.users.fields.reason"),
										error: F(q).error("reason")
									}, {
										default: L(() => [D(l, {
											modelValue: F(J),
											"onUpdate:modelValue": i[4] ||= (e) => k(J) ? J.value = e : null,
											class: "w-64",
											"data-testid": "roster-user-reject-reason"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, ["label", "error"]), D(a, {
										type: "submit",
										color: "error",
										icon: "i-lucide-x",
										loading: F(q).busy.value,
										"data-testid": "roster-user-reject"
									}, {
										default: L(() => [E(P(F(h)("roster.users.approval.reject")), 1)]),
										_: 1
									}, 8, ["loading"])]),
									_: 1
								})])
							])]),
							_: 1
						})) : C("", !0),
						D(c, { class: "lg:col-span-2" }, {
							header: L(() => [T("h2", De, P(F(h)("roster.users.status.title")), 1)]),
							default: L(() => [T("div", Oe, [
								F(z).status_reason ? (A(), w("p", ke, P(F(h)("roster.users.fields.reason")) + ": " + P(F(z).status_reason), 1)) : C("", !0),
								D(e, { action: F(Y) }, null, 8, ["action"]),
								D(d, {
									state: F(X),
									class: "flex flex-wrap items-end gap-3",
									"data-testid": "roster-user-status-form",
									onSubmit: Ue
								}, {
									default: L(() => [
										D(u, { label: F(h)("roster.users.status.change_to") }, {
											default: L(() => [D(p, {
												modelValue: F(X).status,
												"onUpdate:modelValue": i[5] ||= (e) => F(X).status = e,
												items: F(Re),
												class: "w-56",
												"data-testid": "roster-user-new-status"
											}, null, 8, ["modelValue", "items"])]),
											_: 1
										}, 8, ["label"]),
										D(u, {
											label: F(h)("roster.users.fields.reason"),
											hint: F(h)("roster.users.status.reason_hint"),
											error: F(Y).error("reason")
										}, {
											default: L(() => [D(l, {
												modelValue: F(X).reason,
												"onUpdate:modelValue": i[6] ||= (e) => F(X).reason = e,
												class: "w-80",
												"data-testid": "roster-user-status-reason"
											}, null, 8, ["modelValue"])]),
											_: 1
										}, 8, [
											"label",
											"hint",
											"error"
										]),
										D(a, {
											type: "submit",
											variant: "outline",
											icon: "i-lucide-refresh-cw",
											loading: F(Y).busy.value,
											"data-testid": "roster-user-change-status"
										}, {
											default: L(() => [E(P(F(h)("roster.users.status.update")), 1)]),
											_: 1
										}, 8, ["loading"])
									]),
									_: 1
								}, 8, ["state"])
							])]),
							_: 1
						}),
						T("div", Ae, [F(R) ? (A(), S(pe, {
							key: 0,
							client: F(R),
							user: F(z),
							memberships: F(V).data.value ?? [],
							onSwitched: F(B),
							onJoined: F(V).reload
						}, null, 8, [
							"client",
							"user",
							"memberships",
							"onSwitched",
							"onJoined"
						])) : C("", !0)]),
						D(c, {
							class: "lg:col-span-2",
							"data-testid": "roster-user-impersonate"
						}, {
							header: L(() => [T("h2", je, P(F(h)("roster.users.impersonate.title")), 1)]),
							default: L(() => [T("div", Me, [D(e, { action: F(Z) }, null, 8, ["action"]), D(d, {
								class: "flex flex-wrap items-end gap-2",
								"data-testid": "roster-user-impersonate-form",
								onSubmit: We
							}, {
								default: L(() => [D(u, {
									label: F(h)("roster.users.fields.reason"),
									hint: F(h)("roster.users.impersonate.reason_hint"),
									error: F(Z).error("reason"),
									required: ""
								}, {
									default: L(() => [D(l, {
										modelValue: F(Q),
										"onUpdate:modelValue": i[7] ||= (e) => k(Q) ? Q.value = e : null,
										class: "w-80",
										"data-testid": "roster-user-impersonation-reason"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, [
									"label",
									"hint",
									"error"
								]), D(a, {
									type: "submit",
									color: "warning",
									icon: "i-lucide-eye",
									loading: F(Z).busy.value,
									"data-testid": "roster-user-impersonate-submit"
								}, {
									default: L(() => [E(P(F(h)("roster.users.impersonate.start")), 1)]),
									_: 1
								}, 8, ["loading"])]),
								_: 1
							})])]),
							_: 1
						}),
						D(c, {
							class: "lg:col-span-2",
							"data-testid": "roster-user-danger"
						}, {
							header: L(() => [T("h2", Ne, P(F(h)("roster.users.danger.title")), 1)]),
							default: L(() => [T("div", Pe, [
								D(e, { action: F($) }, null, 8, ["action"]),
								T("p", Fe, P(F(h)("roster.users.danger.delete_warning")), 1),
								T("div", null, [D(a, {
									color: "error",
									icon: "i-lucide-trash",
									loading: F($).busy.value,
									"data-testid": "roster-user-delete",
									onClick: Ge
								}, {
									default: L(() => [E(P(F(h)("roster.users.danger.delete")), 1)]),
									_: 1
								}, 8, ["loading"])])
							])]),
							_: 1
						})
					])) : C("", !0)]),
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
