import { t as e } from "./RosterActionError-DqFXfLSN.js";
import { t } from "./RosterProfileFields-BdSCb99p.js";
import { t as ee } from "./RosterState-4BrqaETn.js";
import { t as n } from "./RosterStatusBadge-CjGfIXnt.js";
import { a as r, d as i, f as a, g as o, h as s, l as c, m as l, n as u, o as d } from "./users-JqW-WBif.js";
import { AtriumIntegrationScreen as f, isExternalUrl as te, navigateTo as ne } from "@refactor-circus/atrium";
import { UAlert as re, UBadge as p, UButton as ie, UCard as ae, UFormField as oe, UInput as m } from "@refactor-circus/atrium/ui";
import { Fragment as h, computed as g, createBlock as _, createCommentVNode as v, createElementBlock as y, createElementVNode as b, createTextVNode as x, createVNode as S, defineComponent as C, isRef as w, openBlock as T, reactive as E, ref as D, renderList as O, resolveComponent as se, toDisplayString as k, unref as A, vModelSelect as j, watch as ce, withCtx as M, withDirectives as N, withModifiers as P } from "vue";
import { useI18n as le } from "vue-i18n";
import { useRoute as ue, useRouter as de } from "vue-router";
// src/components/users/RosterUserMemberships.vue?vue&type=script&setup=true&lang.ts
var F = { class: "font-semibold" }, I = { class: "flex flex-col gap-4" }, L = {
	key: 1,
	class: "text-sm text-muted"
}, R = {
	key: 2,
	class: "flex flex-col gap-1 text-sm"
}, fe = ["data-testid"], pe = ["value"], me = { value: "" }, z = ["value"], he = /* @__PURE__ */ C({
	__name: "RosterUserMemberships",
	props: {
		client: {},
		user: {},
		memberships: {}
	},
	emits: ["switched", "joined"],
	setup(t, { emit: ee }) {
		let n = t, r = ee, { t: i } = le(), o = l(), s = D(null), u = E({
			organization: n.user.current_organization ?? n.memberships[0]?.slug ?? "",
			team: n.user.current_team ?? ""
		});
		ce(() => [n.user.current_organization, n.user.current_team], () => {
			u.organization = n.user.current_organization ?? n.memberships[0]?.slug ?? "", u.team = n.user.current_team ?? "";
		});
		let d = a(() => u.organization || null), f = g(() => String(n.user.id));
		async function te() {
			s.value = null, await o.run(async () => c(await n.client.PUT("/users/{user}/context", {
				params: { path: { user: f.value } },
				body: {
					organization: u.organization,
					team: u.team || null
				}
			}))) && r("switched");
		}
		async function ne() {
			s.value = null;
			let e = 0;
			await o.run(async () => {
				e = c(await n.client.POST("/users/{user}/domain-join", { params: { path: { user: f.value } } })).data.length;
			}) && (s.value = i("roster.users.memberships.joined", { count: e }), r("joined"));
		}
		return (ee, n) => {
			let r = re, a = se("RouterLink"), c = p, l = oe, f = ie, m = ae;
			return T(), _(m, { "data-testid": "roster-user-memberships" }, {
				header: M(() => [b("h2", F, k(A(i)("roster.users.memberships.title")), 1)]),
				default: M(() => [b("div", I, [
					S(e, { action: A(o) }, null, 8, ["action"]),
					A(s) ? (T(), _(r, {
						key: 0,
						color: "success",
						variant: "subtle",
						title: A(s),
						"data-testid": "roster-user-notice"
					}, null, 8, ["title"])) : v("", !0),
					t.memberships.length === 0 ? (T(), y("p", L, k(A(i)("roster.users.memberships.none")), 1)) : (T(), y("ul", R, [(T(!0), y(h, null, O(t.memberships, (e) => (T(), y("li", {
						key: e.slug,
						"data-testid": `roster-membership-${e.slug}`
					}, [S(a, {
						to: `/roster/organizations/${encodeURIComponent(e.slug)}`,
						class: "font-medium hover:underline"
					}, {
						default: M(() => [x(k(e.name), 1)]),
						_: 2
					}, 1032, ["to"]), e.slug === t.user.current_organization ? (T(), _(c, {
						key: 0,
						variant: "subtle",
						class: "ml-2"
					}, {
						default: M(() => [x(k(A(i)("roster.users.memberships.current")), 1)]),
						_: 1
					})) : v("", !0)], 8, fe))), 128))])),
					t.memberships.length > 0 ? (T(), y("form", {
						key: 3,
						class: "flex flex-wrap items-end gap-2",
						"data-testid": "roster-user-context-form",
						onSubmit: P(te, ["prevent"])
					}, [
						S(l, {
							label: A(i)("roster.users.memberships.organization"),
							error: A(o).error("organization")
						}, {
							default: M(() => [N(b("select", {
								"onUpdate:modelValue": n[0] ||= (e) => A(u).organization = e,
								class: "w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "roster-user-context-organization"
							}, [(T(!0), y(h, null, O(t.memberships, (e) => (T(), y("option", {
								key: e.slug,
								value: e.slug
							}, k(e.name), 9, pe))), 128))], 512), [[j, A(u).organization]])]),
							_: 1
						}, 8, ["label", "error"]),
						S(l, {
							label: A(i)("roster.users.memberships.team"),
							error: A(o).error("team")
						}, {
							default: M(() => [N(b("select", {
								"onUpdate:modelValue": n[1] ||= (e) => A(u).team = e,
								class: "w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
								"data-testid": "roster-user-context-team"
							}, [b("option", me, k(A(i)("roster.users.memberships.no_team")), 1), (T(!0), y(h, null, O(A(d), (e) => (T(), y("option", {
								key: e.slug,
								value: e.slug
							}, k(e.name), 9, z))), 128))], 512), [[j, A(u).team]])]),
							_: 1
						}, 8, ["label", "error"]),
						S(f, {
							type: "submit",
							icon: "i-lucide-arrow-left-right",
							loading: A(o).busy.value,
							"data-testid": "roster-user-context-switch"
						}, {
							default: M(() => [x(k(A(i)("roster.users.memberships.switch")), 1)]),
							_: 1
						}, 8, ["loading"])
					], 32)) : v("", !0),
					b("div", null, [S(f, {
						variant: "ghost",
						icon: "i-lucide-globe",
						loading: A(o).busy.value,
						"data-testid": "roster-user-domain-join",
						onClick: ne
					}, {
						default: M(() => [x(k(A(i)("roster.users.memberships.domain_join")), 1)]),
						_: 1
					}, 8, ["loading"])])
				])]),
				_: 1
			});
		};
	}
}), ge = {
	class: "space-y-4",
	"data-testid": "roster-user"
}, _e = { class: "flex flex-wrap items-center gap-3" }, ve = { class: "min-w-0" }, ye = {
	class: "text-xl font-semibold",
	"data-testid": "roster-user-title"
}, be = {
	key: 0,
	class: "text-sm text-muted"
}, xe = {
	key: 0,
	class: "flex max-w-3xl flex-col gap-4",
	"data-testid": "roster-user-deleted"
}, Se = { class: "font-semibold" }, Ce = { class: "mb-4 text-sm" }, we = {
	key: 1,
	class: "grid gap-4 lg:grid-cols-2"
}, Te = { class: "font-semibold" }, Ee = { class: "font-semibold" }, De = { class: "font-semibold" }, Oe = { class: "flex flex-col gap-3" }, ke = { class: "text-sm" }, Ae = { class: "flex flex-wrap items-end gap-3" }, je = { class: "font-semibold" }, Me = { class: "flex flex-col gap-3" }, Ne = {
	key: 0,
	class: "text-sm"
}, Pe = ["value"], Fe = { class: "lg:col-span-2" }, Ie = { class: "font-semibold" }, Le = { class: "flex flex-col gap-3" }, Re = { class: "font-semibold" }, ze = { class: "flex flex-col gap-3" }, Be = { class: "text-sm" }, B = /* @__PURE__ */ C({
	__name: "[user]",
	setup(a) {
		let { t: p } = le(), C = ue(), se = de(), F = g(() => String(C.params.user)), I = () => ({ params: { path: { user: F.value } } }), { client: L, data: R, pending: fe, failed: pe, status: me, reload: z } = s(async (e) => c(await e.GET("/users/{user}", I())).data), B = s(async (e) => c(await e.GET("/organizations", { params: { query: {
			user: F.value,
			per_page: 100
		} } })).data), Ve = g(() => o(R.value) ?? p("roster.users.user")), V = D(!1), H = l(), U = E({
			name: "",
			email: "",
			password: ""
		}), W = l(), G = D(d()), K = l(), q = D(""), J = l(), Y = E({
			status: "",
			reason: ""
		}), X = l(), Z = D(""), Q = l(), $ = g(() => {
			let e = R.value?.status;
			return [
				"active",
				"suspended",
				"deactivated"
			].filter((t) => t !== e && (e !== "pending" || t !== "active"));
		});
		ce(R, (e) => {
			e && (U.name = e.name ?? "", U.email = e.email ?? "", U.password = "", G.value = d(e.profile), Y.status = $.value[0] ?? "", Y.reason = "");
		}, { immediate: !0 });
		async function He() {
			let e = {};
			U.name.trim() !== "" && (e.name = U.name), e.email = U.email, U.password !== "" && (e.password = U.password), L && await H.run(async () => c(await L.PATCH("/users/{user}", {
				...I(),
				body: i(e)
			}))) && await z();
		}
		async function Ue() {
			L && await W.run(async () => c(await L.PATCH("/users/{user}/profile", {
				...I(),
				body: r(G.value)
			}))) && await z();
		}
		async function We() {
			L && await K.run(async () => c(await L.POST("/users/{user}/approve", I()))) && await z();
		}
		async function Ge() {
			L && await K.run(async () => c(await L.POST("/users/{user}/reject", {
				...I(),
				body: { reason: u(q.value) }
			}))) && (q.value = "", await z());
		}
		async function Ke() {
			if (!L || Y.status === "") return;
			let e = u(Y.reason), t = Y.status;
			await J.run(async () => {
				switch (t) {
					case "suspended": return c(await L.POST("/users/{user}/suspend", {
						...I(),
						body: { reason: e }
					}));
					case "deactivated": return c(await L.POST("/users/{user}/deactivate", {
						...I(),
						body: { reason: e }
					}));
					default: return c(await L.POST("/users/{user}/reactivate", I()));
				}
			}) && await z();
		}
		async function qe() {
			if (!L) return;
			let e = { url: "" };
			await X.run(async () => {
				let t = c(await L.POST("/users/{user}/impersonate", {
					...I(),
					body: { reason: Z.value }
				})).url;
				if (typeof t != "string" || !te(t)) throw Error(p("roster.users.impersonate.unsafe_link"));
				e.url = t;
			}) && await ne(e.url, { external: !0 });
		}
		async function Je() {
			L && window.confirm(p("roster.users.danger.confirm_delete")) && await Q.run(async () => c(await L.DELETE("/users/{user}", I()))) && (V.value = !0);
		}
		async function Ye() {
			L && await Q.run(async () => c(await L.POST("/users/{user}/restore", I()))) && (V.value = !1, await z());
		}
		async function Xe() {
			L && window.confirm(p("roster.users.danger.confirm_purge")) && await Q.run(async () => c(await L.DELETE("/users/{user}/purge", I()))) && await se.push({
				path: "/roster/users",
				query: { trashed: "only" }
			});
		}
		return (r, i) => {
			let a = ie, o = re, s = ae, c = m, l = oe, u = f;
			return T(), y("div", ge, [b("div", _e, [
				S(a, {
					to: "/roster/users",
					icon: "i-lucide-arrow-left",
					color: "neutral",
					variant: "ghost",
					"aria-label": A(p)("roster.users.title")
				}, null, 8, ["aria-label"]),
				b("div", ve, [b("h1", ye, k(A(Ve)), 1), A(R)?.email ? (T(), y("p", be, k(A(R).email), 1)) : v("", !0)]),
				A(R) && !A(V) ? (T(), _(n, {
					key: 0,
					status: A(R).status,
					"data-testid": "roster-user-status-badge"
				}, null, 8, ["status"])) : v("", !0)
			]), S(u, {
				"screen-key": "roster.user",
				record: A(F)
			}, {
				default: M(() => [S(ee, {
					ready: !A(fe) && A(R) !== null,
					failed: A(pe),
					status: A(me)
				}, {
					default: M(() => [A(V) && A(R) ? (T(), y("div", xe, [
						S(e, { action: A(Q) }, null, 8, ["action"]),
						S(o, {
							color: "warning",
							variant: "subtle",
							icon: "i-lucide-trash",
							title: A(p)("roster.users.danger.deleted"),
							description: A(p)("roster.users.danger.deleted_hint")
						}, null, 8, ["title", "description"]),
						b("div", null, [S(a, {
							icon: "i-lucide-undo-2",
							loading: A(Q).busy.value,
							"data-testid": "roster-user-restore",
							onClick: Ye
						}, {
							default: M(() => [x(k(A(p)("roster.users.restore")), 1)]),
							_: 1
						}, 8, ["loading"])]),
						S(s, null, {
							header: M(() => [b("h2", Se, k(A(p)("roster.users.danger.title")), 1)]),
							default: M(() => [b("p", Ce, k(A(p)("roster.users.danger.purge_warning")), 1), S(a, {
								color: "error",
								icon: "i-lucide-trash-2",
								loading: A(Q).busy.value,
								"data-testid": "roster-user-purge",
								onClick: Xe
							}, {
								default: M(() => [x(k(A(p)("roster.users.purge")), 1)]),
								_: 1
							}, 8, ["loading"])]),
							_: 1
						})
					])) : A(R) ? (T(), y("div", we, [
						S(s, null, {
							header: M(() => [b("h2", Te, k(A(p)("roster.users.account.title")), 1)]),
							default: M(() => [b("form", {
								class: "flex flex-col gap-4",
								"data-testid": "roster-user-account-form",
								onSubmit: P(He, ["prevent"])
							}, [
								S(e, { action: A(H) }, null, 8, ["action"]),
								S(l, {
									label: A(p)("roster.users.fields.name"),
									error: A(H).error("name")
								}, {
									default: M(() => [S(c, {
										modelValue: A(U).name,
										"onUpdate:modelValue": i[0] ||= (e) => A(U).name = e,
										class: "w-full",
										"data-testid": "roster-account-name"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								S(l, {
									label: A(p)("roster.users.fields.email"),
									error: A(H).error("email"),
									required: ""
								}, {
									default: M(() => [S(c, {
										modelValue: A(U).email,
										"onUpdate:modelValue": i[1] ||= (e) => A(U).email = e,
										type: "email",
										class: "w-full",
										"data-testid": "roster-account-email"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]),
								S(l, {
									label: A(p)("roster.users.fields.password"),
									hint: A(p)("roster.users.account.password_hint"),
									error: A(H).error("password")
								}, {
									default: M(() => [S(c, {
										modelValue: A(U).password,
										"onUpdate:modelValue": i[2] ||= (e) => A(U).password = e,
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
								b("div", null, [S(a, {
									type: "submit",
									icon: "i-lucide-check",
									loading: A(H).busy.value,
									"data-testid": "roster-account-save"
								}, {
									default: M(() => [x(k(A(p)("roster.users.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						}),
						S(s, null, {
							header: M(() => [b("h2", Ee, k(A(p)("roster.users.profile.title")), 1)]),
							default: M(() => [b("form", {
								class: "flex flex-col gap-4",
								"data-testid": "roster-user-profile-form",
								onSubmit: P(Ue, ["prevent"])
							}, [
								S(e, { action: A(W) }, null, 8, ["action"]),
								S(t, {
									modelValue: A(G),
									"onUpdate:modelValue": i[3] ||= (e) => w(G) ? G.value = e : null,
									errors: A(W).errors.value
								}, null, 8, ["modelValue", "errors"]),
								b("div", null, [S(a, {
									type: "submit",
									icon: "i-lucide-check",
									loading: A(W).busy.value,
									"data-testid": "roster-profile-save"
								}, {
									default: M(() => [x(k(A(p)("roster.users.save")), 1)]),
									_: 1
								}, 8, ["loading"])])
							], 32)]),
							_: 1
						}),
						A(R).status === "pending" ? (T(), _(s, {
							key: 0,
							class: "lg:col-span-2",
							"data-testid": "roster-user-approval"
						}, {
							header: M(() => [b("h2", De, k(A(p)("roster.users.approval.title")), 1)]),
							default: M(() => [b("div", Oe, [
								b("p", ke, k(A(p)("roster.users.approval.hint")), 1),
								S(e, { action: A(K) }, null, 8, ["action"]),
								b("div", Ae, [S(a, {
									icon: "i-lucide-check",
									loading: A(K).busy.value,
									"data-testid": "roster-user-approve",
									onClick: We
								}, {
									default: M(() => [x(k(A(p)("roster.users.approve")), 1)]),
									_: 1
								}, 8, ["loading"]), b("form", {
									class: "flex items-end gap-2",
									"data-testid": "roster-user-reject-form",
									onSubmit: P(Ge, ["prevent"])
								}, [S(l, {
									label: A(p)("roster.users.fields.reason"),
									error: A(K).error("reason")
								}, {
									default: M(() => [S(c, {
										modelValue: A(q),
										"onUpdate:modelValue": i[4] ||= (e) => w(q) ? q.value = e : null,
										class: "w-64",
										"data-testid": "roster-user-reject-reason"
									}, null, 8, ["modelValue"])]),
									_: 1
								}, 8, ["label", "error"]), S(a, {
									type: "submit",
									color: "error",
									icon: "i-lucide-x",
									loading: A(K).busy.value,
									"data-testid": "roster-user-reject"
								}, {
									default: M(() => [x(k(A(p)("roster.users.approval.reject")), 1)]),
									_: 1
								}, 8, ["loading"])], 32)])
							])]),
							_: 1
						})) : v("", !0),
						S(s, { class: "lg:col-span-2" }, {
							header: M(() => [b("h2", je, k(A(p)("roster.users.status.title")), 1)]),
							default: M(() => [b("div", Me, [
								A(R).status_reason ? (T(), y("p", Ne, k(A(p)("roster.users.fields.reason")) + ": " + k(A(R).status_reason), 1)) : v("", !0),
								S(e, { action: A(J) }, null, 8, ["action"]),
								b("form", {
									class: "flex flex-wrap items-end gap-3",
									"data-testid": "roster-user-status-form",
									onSubmit: P(Ke, ["prevent"])
								}, [
									S(l, { label: A(p)("roster.users.status.change_to") }, {
										default: M(() => [N(b("select", {
											"onUpdate:modelValue": i[5] ||= (e) => A(Y).status = e,
											class: "w-56 rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
											"data-testid": "roster-user-new-status"
										}, [(T(!0), y(h, null, O(A($), (e) => (T(), y("option", {
											key: e,
											value: e
										}, k(A(p)(`roster.users.statuses.${e}`)), 9, Pe))), 128))], 512), [[j, A(Y).status]])]),
										_: 1
									}, 8, ["label"]),
									S(l, {
										label: A(p)("roster.users.fields.reason"),
										hint: A(p)("roster.users.status.reason_hint"),
										error: A(J).error("reason")
									}, {
										default: M(() => [S(c, {
											modelValue: A(Y).reason,
											"onUpdate:modelValue": i[6] ||= (e) => A(Y).reason = e,
											class: "w-80",
											"data-testid": "roster-user-status-reason"
										}, null, 8, ["modelValue"])]),
										_: 1
									}, 8, [
										"label",
										"hint",
										"error"
									]),
									S(a, {
										type: "submit",
										variant: "outline",
										icon: "i-lucide-refresh-cw",
										loading: A(J).busy.value,
										"data-testid": "roster-user-change-status"
									}, {
										default: M(() => [x(k(A(p)("roster.users.status.update")), 1)]),
										_: 1
									}, 8, ["loading"])
								], 32)
							])]),
							_: 1
						}),
						b("div", Fe, [A(L) ? (T(), _(he, {
							key: 0,
							client: A(L),
							user: A(R),
							memberships: A(B).data.value ?? [],
							onSwitched: A(z),
							onJoined: A(B).reload
						}, null, 8, [
							"client",
							"user",
							"memberships",
							"onSwitched",
							"onJoined"
						])) : v("", !0)]),
						S(s, {
							class: "lg:col-span-2",
							"data-testid": "roster-user-impersonate"
						}, {
							header: M(() => [b("h2", Ie, k(A(p)("roster.users.impersonate.title")), 1)]),
							default: M(() => [b("div", Le, [S(e, { action: A(X) }, null, 8, ["action"]), b("form", {
								class: "flex flex-wrap items-end gap-2",
								"data-testid": "roster-user-impersonate-form",
								onSubmit: P(qe, ["prevent"])
							}, [S(l, {
								label: A(p)("roster.users.fields.reason"),
								hint: A(p)("roster.users.impersonate.reason_hint"),
								error: A(X).error("reason"),
								required: ""
							}, {
								default: M(() => [S(c, {
									modelValue: A(Z),
									"onUpdate:modelValue": i[7] ||= (e) => w(Z) ? Z.value = e : null,
									class: "w-80",
									"data-testid": "roster-user-impersonation-reason"
								}, null, 8, ["modelValue"])]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]), S(a, {
								type: "submit",
								color: "warning",
								icon: "i-lucide-eye",
								loading: A(X).busy.value,
								"data-testid": "roster-user-impersonate-submit"
							}, {
								default: M(() => [x(k(A(p)("roster.users.impersonate.start")), 1)]),
								_: 1
							}, 8, ["loading"])], 32)])]),
							_: 1
						}),
						S(s, {
							class: "lg:col-span-2",
							"data-testid": "roster-user-danger"
						}, {
							header: M(() => [b("h2", Re, k(A(p)("roster.users.danger.title")), 1)]),
							default: M(() => [b("div", ze, [
								S(e, { action: A(Q) }, null, 8, ["action"]),
								b("p", Be, k(A(p)("roster.users.danger.delete_warning")), 1),
								b("div", null, [S(a, {
									color: "error",
									icon: "i-lucide-trash",
									loading: A(Q).busy.value,
									"data-testid": "roster-user-delete",
									onClick: Je
								}, {
									default: M(() => [x(k(A(p)("roster.users.danger.delete")), 1)]),
									_: 1
								}, 8, ["loading"])])
							])]),
							_: 1
						})
					])) : v("", !0)]),
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
export { B as default };
