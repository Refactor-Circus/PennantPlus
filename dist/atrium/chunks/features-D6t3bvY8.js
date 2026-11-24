import { AtriumIntegrationError as e, AtriumIntegrationScreen as t, AtriumNoAccess as n, atriumStatus as r, useAtriumIntegration as i, useHead as a } from "@refactor-circus/atrium";
import { UAlert as o, UBadge as s, UButton as ee, UDashboardNavbar as c, UDashboardPanel as l, UEmpty as u, UForm as d, UFormField as te, UInput as ne, UPageCard as f, USelect as p, USkeleton as m, UTable as h } from "@refactor-circus/atrium/ui";
import { Fragment as g, computed as _, createBlock as v, createCommentVNode as y, createElementBlock as b, createElementVNode as x, createTextVNode as S, createVNode as C, defineComponent as w, h as T, isRef as E, openBlock as D, reactive as O, ref as k, renderList as re, shallowRef as A, toDisplayString as j, unref as M, watch as N, withCtx as P } from "vue";
import { useDebounceFn as F } from "@vueuse/core";
import { useI18n as I } from "vue-i18n";
// src/components/pennantplus.ts
var L = "global";
function R() {
	return i("pennantplus").client;
}
function z(t) {
	if (!t.response.ok) throw new e(t.response.status, t.error);
	return t.data;
}
function B(e) {
	return e;
}
function ie() {
	let t = k({}), n = k(null), i = k(!1), a = k(!1);
	async function o(o) {
		a.value = !0, t.value = {}, n.value = null;
		try {
			return await o(), !0;
		} catch (a) {
			let o = r(a), s = a instanceof e ? a.body : null;
			return o === 403 ? i.value = !0 : o === 422 && s?.errors ? t.value = s.errors : n.value = typeof s?.message == "string" ? s.message : a instanceof Error ? a.message : String(a), !1;
		} finally {
			a.value = !1;
		}
	}
	function s(e) {
		return t.value[e]?.[0];
	}
	return {
		errors: t,
		message: n,
		forbidden: i,
		busy: a,
		run: o,
		error: s
	};
}
function V(e) {
	let t = JSON.stringify(e) ?? "null";
	return t.length > 60 ? `${t.slice(0, 57)}...` : t;
}
// src/components/PennantPlusSetValue.vue?vue&type=script&setup=true&lang.ts
var H = { key: 0 }, U = {
	key: 0,
	class: "flex items-center justify-between gap-2 rounded-md border border-default px-3 py-1.5 text-sm"
}, W = { "data-testid": "pennantplus-scope-picked" }, G = {
	key: 1,
	class: "relative"
}, K = {
	key: 0,
	class: "absolute top-full z-20 mt-1.5 flex max-h-60 w-full flex-col overflow-y-auto rounded-md border border-default bg-default p-1 text-sm shadow-lg"
}, q = { class: "text-muted" }, J = {
	key: 0,
	class: "px-3 py-1.5 text-muted"
}, ae = { class: "flex items-end sm:pt-6" }, oe = /* @__PURE__ */ w({
	__name: "PennantPlusSetValue",
	props: {
		scopeTypes: {},
		featureList: {}
	},
	emits: ["saved", "forbidden"],
	setup(e, { emit: t }) {
		let n = e, i = t, { t: a } = I(), s = R(), c = ie(), l = k(""), u = k(L), m = k(""), h = k(""), S = A([]), w = k(!1), T = A(null), O = k("true"), V = k(null), oe = _(() => n.scopeTypes.filter((e) => e.type === "global" || e.type === "other" || e.searchable).map((e) => ({
			label: e.label,
			value: e.type
		}))), se = _(() => ({
			feature: l.value,
			scope_type: u.value,
			value: O.value
		})), Y = _(() => u.value !== "global" && u.value !== "other"), ce = _(() => c.error("scope_type") ?? c.error("scope") ?? (u.value === "global" ? c.error("scope_id") : void 0));
		N(() => c.forbidden.value, (e) => {
			e && i("forbidden");
		});
		function X() {
			m.value = "", h.value = "", S.value = [], w.value = !1, T.value = null;
		}
		async function le() {
			let e = h.value.trim();
			if (!s || e === "" || !Y.value) {
				S.value = [], w.value = !1;
				return;
			}
			let t = u.value;
			try {
				let n = z(await s.GET("/scopes/{type}/models", { params: {
					path: { type: t },
					query: {
						type: t,
						q: e
					}
				} }));
				S.value = n.data;
			} catch (e) {
				S.value = [], r(e) === 403 && i("forbidden");
			}
			w.value = !0;
		}
		let ue = F(le, 250);
		function de(e) {
			T.value = e, S.value = [], w.value = !1;
		}
		async function fe() {
			V.value = null;
			let e;
			try {
				e = JSON.parse(O.value);
			} catch {
				V.value = a("pennantplus.features.invalid_json");
				return;
			}
			let t = u.value === "other" ? m.value : Y.value ? T.value?.id : void 0;
			await c.run(async () => z(await s.PUT("/values", { body: {
				feature: l.value,
				scope_type: B(u.value),
				...t === void 0 ? {} : { scope_id: t },
				value: B(e)
			} }))) && (l.value = "", u.value = L, O.value = "true", X(), i("saved"));
		}
		return (t, n) => {
			let r = ne, i = te, s = p, _ = ee, k = d, A = o, N = f;
			return D(), v(N, {
				title: M(a)("pennantplus.features.set_value"),
				variant: "outline"
			}, {
				default: P(() => [C(k, {
					state: M(se),
					class: "grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-5",
					"data-testid": "pennantplus-set-value",
					onSubmit: fe
				}, {
					default: P(() => [
						C(i, {
							label: M(a)("pennantplus.features.feature"),
							error: M(c).error("feature"),
							required: ""
						}, {
							default: P(() => [C(r, {
								modelValue: M(l),
								"onUpdate:modelValue": n[0] ||= (e) => E(l) ? l.value = e : null,
								list: e.featureList,
								autocomplete: "off",
								class: "w-full",
								"data-testid": "pennantplus-feature"
							}, null, 8, ["modelValue", "list"])]),
							_: 1
						}, 8, ["label", "error"]),
						C(i, {
							label: M(a)("pennantplus.features.scope"),
							error: M(ce)
						}, {
							default: P(() => [C(s, {
								modelValue: M(u),
								"onUpdate:modelValue": [n[1] ||= (e) => E(u) ? u.value = e : null, X],
								items: M(oe),
								class: "w-full",
								"data-testid": "pennantplus-scope-type"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label", "error"]),
						M(u) === M("global") ? (D(), b("div", H)) : M(u) === M("other") ? (D(), v(i, {
							key: 1,
							label: M(a)("pennantplus.features.scope_string"),
							error: M(c).error("scope_id")
						}, {
							default: P(() => [C(r, {
								modelValue: M(m),
								"onUpdate:modelValue": n[2] ||= (e) => E(m) ? m.value = e : null,
								class: "w-full",
								"data-testid": "pennantplus-scope-string"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"])) : (D(), v(i, {
							key: 2,
							label: M(a)("pennantplus.features.find_model"),
							error: M(c).error("scope_id")
						}, {
							default: P(() => [M(T) ? (D(), b("div", U, [x("span", W, j(M(T).title) + " (#" + j(M(T).id) + ")", 1), C(_, {
								size: "xs",
								color: "neutral",
								variant: "ghost",
								label: M(a)("pennantplus.features.clear"),
								onClick: X
							}, null, 8, ["label"])])) : (D(), b("div", G, [C(r, {
								modelValue: M(h),
								"onUpdate:modelValue": [n[3] ||= (e) => E(h) ? h.value = e : null, M(ue)],
								autocomplete: "off",
								icon: "i-lucide-search",
								placeholder: M(a)("pennantplus.features.find_model_placeholder"),
								class: "w-full",
								"data-testid": "pennantplus-scope-search"
							}, null, 8, [
								"modelValue",
								"placeholder",
								"onUpdate:modelValue"
							]), M(w) ? (D(), b("div", K, [(D(!0), b(g, null, re(M(S), (e) => (D(), v(_, {
								key: e.id,
								color: "neutral",
								variant: "ghost",
								block: "",
								class: "justify-between",
								"data-testid": "pennantplus-scope-result",
								onClick: (t) => de(e)
							}, {
								default: P(() => [x("span", null, j(e.title), 1), x("span", q, "#" + j(e.id), 1)]),
								_: 2
							}, 1032, ["onClick"]))), 128)), M(S).length === 0 ? (D(), b("p", J, j(M(a)("pennantplus.features.no_models")), 1)) : y("", !0)])) : y("", !0)]))]),
							_: 1
						}, 8, ["label", "error"])),
						C(i, {
							label: M(a)("pennantplus.features.value"),
							hint: M(a)("pennantplus.features.value_hint"),
							error: M(V) ?? M(c).error("value"),
							required: ""
						}, {
							default: P(() => [C(r, {
								modelValue: M(O),
								"onUpdate:modelValue": n[4] ||= (e) => E(O) ? O.value = e : null,
								class: "w-full font-mono",
								"data-testid": "pennantplus-value-input"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						x("div", ae, [C(_, {
							type: "submit",
							icon: "i-lucide-check",
							label: M(a)("pennantplus.features.save"),
							loading: M(c).busy.value,
							"data-testid": "pennantplus-save"
						}, null, 8, ["label", "loading"])])
					]),
					_: 1
				}, 8, ["state"]), M(c).message.value ? (D(), v(A, {
					key: 0,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					title: M(c).message.value
				}, null, 8, ["title"])) : y("", !0)]),
				_: 1
			}, 8, ["title"]);
		};
	}
}), se = {
	class: "flex flex-col gap-5",
	"data-testid": "pennantplus-features"
}, Y = ["value"], ce = { class: "flex gap-2" }, X = ["title"], le = {
	key: 0,
	class: "block text-xs text-muted"
}, ue = { class: "flex items-center gap-2" }, de = {
	key: 0,
	class: "text-xs"
}, fe = { class: "flex justify-end gap-2" }, pe = {
	key: 4,
	class: "flex items-center justify-between gap-3 text-sm"
}, me = {
	class: "text-muted",
	"data-testid": "pennantplus-page"
}, he = { key: 5 }, Z = "pennantplus-feature-options", Q = "*", ge = /* @__PURE__ */ w({
	__name: "PennantPlusFeatureFlags",
	setup(e) {
		let { t, locale: i } = I(), a = R(), c = ie(), l = A([]), f = A([]), w = A([]), N = k(1), F = k(1), L = k(!1), H = k(!1), U = k(!1), W = k(null), G = O({
			feature: "",
			scope: "",
			scope_id: ""
		}), K = k({
			feature: "",
			scope: "",
			scope_id: ""
		}), q = _(() => U.value || c.forbidden.value), J = _({
			get: () => G.scope === "" ? Q : G.scope,
			set: (e) => {
				G.scope = e === Q ? "" : e;
			}
		}), ae = _(() => [{
			label: t("pennantplus.features.all_scopes"),
			value: Q
		}, ...f.value.map((e) => ({
			label: e.label,
			value: e.type
		}))]), ge = _(() => [
			{
				accessorKey: "feature",
				header: t("pennantplus.features.feature"),
				meta: { class: { td: "font-medium" } }
			},
			{
				accessorKey: "scope",
				header: t("pennantplus.features.scope")
			},
			{
				accessorKey: "value",
				header: t("pennantplus.features.value")
			},
			{
				accessorKey: "updated_at",
				header: t("pennantplus.features.updated"),
				meta: { class: { td: "text-muted" } }
			},
			{
				id: "actions",
				header: () => T("span", { class: "sr-only" }, t("pennantplus.features.actions")),
				meta: { class: { td: "text-right" } }
			}
		]), _e = _(() => new Intl.DateTimeFormat(i.value, {
			dateStyle: "medium",
			timeStyle: "short"
		}));
		function ve() {
			let { feature: e, scope: t, scope_id: n } = K.value;
			return {
				...e === "" ? {} : { feature: e },
				...t === "" ? {} : { scope: t },
				...n === "" ? {} : { scope_id: n },
				...N.value > 1 ? { page: N.value } : {}
			};
		}
		async function ye(e) {
			let [t, n] = await Promise.all([e.GET("/features").then((e) => z(e)), e.GET("/scopes").then((e) => z(e))]);
			l.value = t.data, f.value = n.data;
		}
		async function be(e) {
			let t = z(await e.GET("/values", { params: { query: ve() } }));
			w.value = t.data, N.value = t.meta?.current_page ?? N.value, F.value = t.meta?.last_page ?? 1;
		}
		async function $(e = !1) {
			if (!a) L.value = !0;
			else try {
				await Promise.all([e ? null : ye(a), be(a)]), H.value = !1;
			} catch (e) {
				U.value = r(e) === 403, H.value = !U.value;
			} finally {
				L.value = !0;
			}
		}
		async function xe() {
			K.value = { ...G }, N.value = 1, await $(!0);
		}
		async function Se() {
			Object.assign(G, {
				feature: "",
				scope: "",
				scope_id: ""
			}), await xe();
		}
		async function Ce(e) {
			N.value = e, await $(!0);
		}
		async function we(e, t, n = !0) {
			return W.value = null, !a || !await c.run(() => e(a)) ? !1 : (W.value = t, await $(n), !0);
		}
		async function Te(e) {
			await we(async (t) => z(await t.PUT("/values", { body: {
				feature: e.feature,
				scope: e.scope,
				value: B(!e.active)
			} })), t("pennantplus.features.value_saved"));
		}
		async function Ee(e) {
			window.confirm(t("pennantplus.features.forget_confirm")) && await we(async (t) => z(await t.DELETE("/values", { params: { query: {
				feature: e.feature,
				scope: e.scope
			} } })), t("pennantplus.features.value_forgotten"));
		}
		async function De() {
			let e = K.value.feature;
			e !== "" && window.confirm(t("pennantplus.features.purge_confirm")) && await c.run(async () => z(await a.DELETE("/features/{feature}", { params: {
				path: { feature: e },
				query: { feature: e }
			} }))) && (G.feature = "", K.value = {
				...K.value,
				feature: ""
			}, N.value = 1, W.value = t("pennantplus.features.feature_purged"), await $());
		}
		async function Oe() {
			W.value = t("pennantplus.features.value_saved"), await $();
		}
		function ke(e) {
			return e === null ? "" : _e.value.format(new Date(e));
		}
		return $(), (e, r) => {
			let i = n, a = m, _ = o, T = ne, O = te, k = p, A = ee, I = d, R = u, z = s, B = h;
			return D(), b("div", se, [M(q) ? (D(), v(i, { key: 0 })) : M(L) ? M(H) ? (D(), v(_, {
				key: 2,
				color: "error",
				variant: "subtle",
				icon: "i-lucide-circle-alert",
				title: M(t)("pennantplus.features.load_failed"),
				actions: [{
					label: M(t)("pennantplus.features.retry"),
					icon: "i-lucide-refresh-cw",
					size: "sm",
					variant: "outline",
					onClick: () => $()
				}],
				"data-testid": "pennantplus-load-error"
			}, null, 8, ["title", "actions"])) : (D(), b(g, { key: 3 }, [
				x("datalist", {
					id: Z,
					"data-testid": Z
				}, [(D(!0), b(g, null, re(M(l), (e) => (D(), b("option", {
					key: e.name,
					value: e.name
				}, null, 8, Y))), 128))]),
				M(W) ? (D(), v(_, {
					key: 0,
					color: "success",
					variant: "subtle",
					icon: "i-lucide-check",
					title: M(W),
					"data-testid": "pennantplus-status"
				}, null, 8, ["title"])) : y("", !0),
				M(c).message.value ? (D(), v(_, {
					key: 1,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					title: M(c).message.value
				}, null, 8, ["title"])) : y("", !0),
				C(oe, {
					"scope-types": M(f),
					"feature-list": Z,
					onSaved: Oe,
					onForbidden: r[0] ||= (e) => M(c).forbidden.value = !0
				}, null, 8, ["scope-types"]),
				C(I, {
					state: M(G),
					class: "grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-4",
					"data-testid": "pennantplus-filters",
					onSubmit: xe
				}, {
					default: P(() => [
						C(O, { label: M(t)("pennantplus.features.feature") }, {
							default: P(() => [C(T, {
								modelValue: M(G).feature,
								"onUpdate:modelValue": r[1] ||= (e) => M(G).feature = e,
								list: Z,
								placeholder: M(t)("pennantplus.features.all_features"),
								autocomplete: "off",
								class: "w-full",
								"data-testid": "pennantplus-filter-feature"
							}, null, 8, ["modelValue", "placeholder"])]),
							_: 1
						}, 8, ["label"]),
						C(O, { label: M(t)("pennantplus.features.scope") }, {
							default: P(() => [C(k, {
								modelValue: M(J),
								"onUpdate:modelValue": r[2] ||= (e) => E(J) ? J.value = e : null,
								items: M(ae),
								class: "w-full",
								"data-testid": "pennantplus-filter-scope"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label"]),
						C(O, { label: M(t)("pennantplus.features.scope_id") }, {
							default: P(() => [C(T, {
								modelValue: M(G).scope_id,
								"onUpdate:modelValue": r[3] ||= (e) => M(G).scope_id = e,
								class: "w-full",
								"data-testid": "pennantplus-filter-scope-id"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						x("div", ce, [C(A, {
							type: "submit",
							variant: "outline",
							icon: "i-lucide-funnel",
							label: M(t)("pennantplus.features.filter"),
							"data-testid": "pennantplus-filter"
						}, null, 8, ["label"]), C(A, {
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-x",
							label: M(t)("pennantplus.features.reset"),
							"data-testid": "pennantplus-reset",
							onClick: Se
						}, null, 8, ["label"])])
					]),
					_: 1
				}, 8, ["state"]),
				M(w).length === 0 ? (D(), v(R, {
					key: 2,
					icon: "i-lucide-flag",
					variant: "naked",
					description: M(t)("pennantplus.features.no_values"),
					"data-testid": "pennantplus-no-values"
				}, null, 8, ["description"])) : (D(), v(B, {
					key: 3,
					data: M(w),
					columns: M(ge),
					"get-row-id": (e) => `${e.feature}|${e.scope}`,
					class: "rounded-md border border-default",
					"data-testid": "pennantplus-values"
				}, {
					"scope-cell": P(({ row: e }) => [x("span", { title: e.original.scope }, j(e.original.scope_label), 9, X), e.original.title ? (D(), b("span", le, j(e.original.title), 1)) : y("", !0)]),
					"value-cell": P(({ row: e }) => [x("div", ue, [C(z, {
						color: e.original.active ? "success" : "neutral",
						variant: "subtle",
						label: e.original.active ? M(t)("pennantplus.features.active") : M(t)("pennantplus.features.inactive"),
						"data-status": e.original.active ? "active" : "inactive",
						"data-testid": "pennantplus-value-status"
					}, null, 8, [
						"color",
						"label",
						"data-status"
					]), typeof e.original.value == "boolean" ? y("", !0) : (D(), b("code", de, j(M(V)(e.original.value)), 1))])]),
					"updated_at-cell": P(({ row: e }) => [S(j(ke(e.original.updated_at)), 1)]),
					"actions-cell": P(({ row: e }) => [x("div", fe, [C(A, {
						size: "xs",
						variant: "outline",
						icon: e.original.active ? "i-lucide-ban" : "i-lucide-circle-check",
						label: e.original.active ? M(t)("pennantplus.features.deactivate") : M(t)("pennantplus.features.activate"),
						loading: M(c).busy.value,
						"data-testid": "pennantplus-toggle",
						onClick: (t) => Te(e.original)
					}, null, 8, [
						"icon",
						"label",
						"loading",
						"onClick"
					]), C(A, {
						size: "xs",
						color: "neutral",
						variant: "ghost",
						icon: "i-lucide-x",
						label: M(t)("pennantplus.features.forget"),
						"data-testid": "pennantplus-forget",
						onClick: (t) => Ee(e.original)
					}, null, 8, ["label", "onClick"])])]),
					_: 1
				}, 8, [
					"data",
					"columns",
					"get-row-id"
				])),
				M(F) > 1 ? (D(), b("div", pe, [
					C(A, {
						size: "sm",
						color: "neutral",
						variant: "outline",
						icon: "i-lucide-chevron-left",
						label: M(t)("pennantplus.features.previous"),
						disabled: M(N) <= 1,
						"data-testid": "pennantplus-prev",
						onClick: r[4] ||= (e) => Ce(M(N) - 1)
					}, null, 8, ["label", "disabled"]),
					x("span", me, j(M(t)("pennantplus.features.page", {
						page: M(N),
						pages: M(F)
					})), 1),
					C(A, {
						size: "sm",
						color: "neutral",
						variant: "outline",
						"trailing-icon": "i-lucide-chevron-right",
						label: M(t)("pennantplus.features.next"),
						disabled: M(N) >= M(F),
						"data-testid": "pennantplus-next",
						onClick: r[5] ||= (e) => Ce(M(N) + 1)
					}, null, 8, ["label", "disabled"])
				])) : y("", !0),
				M(K).feature === "" ? y("", !0) : (D(), b("div", he, [C(A, {
					color: "error",
					variant: "outline",
					size: "sm",
					icon: "i-lucide-trash",
					label: M(t)("pennantplus.features.purge", { feature: M(K).feature }),
					loading: M(c).busy.value,
					"data-testid": "pennantplus-purge",
					onClick: De
				}, null, 8, ["label", "loading"])]))
			], 64)) : (D(), v(a, {
				key: 1,
				class: "h-32"
			}))]);
		};
	}
}), _e = { class: "mb-4 text-sm text-muted" }, ve = /* @__PURE__ */ w({
	__name: "features",
	setup(e) {
		let { t: n } = I();
		return a({ title: () => n("pennantplus.features.title") }), (e, r) => {
			let i = c, a = t, o = l;
			return D(), v(o, null, {
				header: P(() => [C(i, { title: M(n)("pennantplus.features.title") }, null, 8, ["title"])]),
				body: P(() => [x("p", _e, j(M(n)("pennantplus.features.description")), 1), C(a, { "screen-key": "pennantplus.features" }, {
					default: P(() => [C(ge)]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { ve as default };
