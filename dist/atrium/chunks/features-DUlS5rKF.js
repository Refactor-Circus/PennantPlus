import { AtriumIntegrationError as e, AtriumIntegrationScreen as t, AtriumNoAccess as n, AtriumSelect as r, atriumStatus as i, useAtriumIntegration as a, useHead as o } from "@refactor-circus/atrium";
import { UAlert as s, UBadge as c, UButton as ee, UDashboardNavbar as l, UDashboardPanel as u, UEmpty as d, UForm as te, UFormField as f, UInput as p, UPageCard as m, USelect as h, USkeleton as g, UTable as _ } from "@refactor-circus/atrium/ui";
import { Fragment as v, computed as y, createBlock as b, createCommentVNode as x, createElementBlock as S, createElementVNode as C, createTextVNode as w, createVNode as T, defineComponent as E, h as D, isRef as O, openBlock as k, reactive as A, ref as j, renderList as ne, shallowRef as M, toDisplayString as N, unref as P, watch as F, withCtx as I } from "vue";
import { useDebounceFn as L } from "@vueuse/core";
import { useI18n as R } from "vue-i18n";
// src/components/pennantplus.ts
var z = "global";
function B() {
	return a("pennantplus").client;
}
function V(t) {
	if (!t.response.ok) throw new e(t.response.status, t.error);
	return t.data;
}
function H(e) {
	return e;
}
function re() {
	let t = j({}), n = j(null), r = j(!1), a = j(!1);
	async function o(o) {
		a.value = !0, t.value = {}, n.value = null;
		try {
			return await o(), !0;
		} catch (a) {
			let o = i(a), s = a instanceof e ? a.body : null;
			return o === 403 ? r.value = !0 : o === 422 && s?.errors ? t.value = s.errors : n.value = typeof s?.message == "string" ? s.message : a instanceof Error ? a.message : String(a), !1;
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
		forbidden: r,
		busy: a,
		run: o,
		error: s
	};
}
function ie(e) {
	let t = JSON.stringify(e) ?? "null";
	return t.length > 60 ? `${t.slice(0, 57)}...` : t;
}
// src/components/PennantPlusSetValue.vue?vue&type=script&setup=true&lang.ts
var U = { key: 0 }, W = {
	key: 0,
	class: "flex items-center justify-between gap-2 rounded-md border border-default px-3 py-1.5 text-sm"
}, G = { "data-testid": "pennantplus-scope-picked" }, K = {
	key: 1,
	class: "relative"
}, q = {
	key: 0,
	class: "absolute top-full z-20 mt-1.5 flex max-h-60 w-full flex-col overflow-y-auto rounded-md border border-default bg-default p-1 text-sm shadow-lg"
}, J = { class: "text-muted" }, ae = {
	key: 0,
	class: "px-3 py-1.5 text-muted"
}, oe = { class: "sm:mt-field-label" }, se = /* @__PURE__ */ E({
	__name: "PennantPlusSetValue",
	props: {
		scopeTypes: {},
		featureList: {}
	},
	emits: ["saved", "forbidden"],
	setup(e, { emit: t }) {
		let n = e, r = t, { t: a } = R(), o = B(), c = re(), l = j(""), u = j(z), d = j(""), g = j(""), _ = M([]), w = j(!1), E = M(null), D = j("true"), A = j(null), ie = y(() => n.scopeTypes.filter((e) => e.type === "global" || e.type === "other" || e.searchable).map((e) => ({
			label: e.label,
			value: e.type
		}))), se = y(() => ({
			feature: l.value,
			scope_type: u.value,
			value: D.value
		})), Y = y(() => u.value !== "global" && u.value !== "other"), ce = y(() => c.error("scope_type") ?? c.error("scope") ?? (u.value === "global" ? c.error("scope_id") : void 0));
		F(() => c.forbidden.value, (e) => {
			e && r("forbidden");
		});
		function X() {
			d.value = "", g.value = "", _.value = [], w.value = !1, E.value = null;
		}
		async function Z() {
			let e = g.value.trim();
			if (!o || e === "" || !Y.value) {
				_.value = [], w.value = !1;
				return;
			}
			let t = u.value;
			try {
				let n = V(await o.GET("/scopes/{type}/models", { params: {
					path: { type: t },
					query: {
						type: t,
						q: e
					}
				} }));
				_.value = n.data;
			} catch (e) {
				_.value = [], i(e) === 403 && r("forbidden");
			}
			w.value = !0;
		}
		let le = L(Z, 250);
		function ue(e) {
			E.value = e, _.value = [], w.value = !1;
		}
		async function de() {
			A.value = null;
			let e;
			try {
				e = JSON.parse(D.value);
			} catch {
				A.value = a("pennantplus.features.invalid_json");
				return;
			}
			let t = u.value === "other" ? d.value : Y.value ? E.value?.id : void 0;
			await c.run(async () => V(await o.PUT("/values", { body: {
				feature: l.value,
				scope_type: H(u.value),
				...t === void 0 ? {} : { scope_id: t },
				value: H(e)
			} }))) && (l.value = "", u.value = z, D.value = "true", X(), r("saved"));
		}
		return (t, n) => {
			let r = p, i = f, o = h, y = ee, j = te, M = s, F = m;
			return k(), b(F, {
				title: P(a)("pennantplus.features.set_value"),
				variant: "outline"
			}, {
				default: I(() => [T(j, {
					state: P(se),
					class: "grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-5",
					"data-testid": "pennantplus-set-value",
					onSubmit: de
				}, {
					default: I(() => [
						T(i, {
							label: P(a)("pennantplus.features.feature"),
							error: P(c).error("feature"),
							required: ""
						}, {
							default: I(() => [T(r, {
								modelValue: P(l),
								"onUpdate:modelValue": n[0] ||= (e) => O(l) ? l.value = e : null,
								list: e.featureList,
								autocomplete: "off",
								class: "w-full",
								"data-testid": "pennantplus-feature"
							}, null, 8, ["modelValue", "list"])]),
							_: 1
						}, 8, ["label", "error"]),
						T(i, {
							label: P(a)("pennantplus.features.scope"),
							error: P(ce)
						}, {
							default: I(() => [T(o, {
								modelValue: P(u),
								"onUpdate:modelValue": [n[1] ||= (e) => O(u) ? u.value = e : null, X],
								items: P(ie),
								class: "w-full",
								"data-testid": "pennantplus-scope-type"
							}, null, 8, ["modelValue", "items"])]),
							_: 1
						}, 8, ["label", "error"]),
						P(u) === P("global") ? (k(), S("div", U)) : P(u) === P("other") ? (k(), b(i, {
							key: 1,
							label: P(a)("pennantplus.features.scope_string"),
							error: P(c).error("scope_id")
						}, {
							default: I(() => [T(r, {
								modelValue: P(d),
								"onUpdate:modelValue": n[2] ||= (e) => O(d) ? d.value = e : null,
								class: "w-full",
								"data-testid": "pennantplus-scope-string"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label", "error"])) : (k(), b(i, {
							key: 2,
							label: P(a)("pennantplus.features.find_model"),
							error: P(c).error("scope_id")
						}, {
							default: I(() => [P(E) ? (k(), S("div", W, [C("span", G, N(P(E).title) + " (#" + N(P(E).id) + ")", 1), T(y, {
								size: "xs",
								color: "neutral",
								variant: "ghost",
								label: P(a)("pennantplus.features.clear"),
								onClick: X
							}, null, 8, ["label"])])) : (k(), S("div", K, [T(r, {
								modelValue: P(g),
								"onUpdate:modelValue": [n[3] ||= (e) => O(g) ? g.value = e : null, P(le)],
								autocomplete: "off",
								icon: "i-lucide-search",
								placeholder: P(a)("pennantplus.features.find_model_placeholder"),
								class: "w-full",
								"data-testid": "pennantplus-scope-search"
							}, null, 8, [
								"modelValue",
								"placeholder",
								"onUpdate:modelValue"
							]), P(w) ? (k(), S("div", q, [(k(!0), S(v, null, ne(P(_), (e) => (k(), b(y, {
								key: e.id,
								color: "neutral",
								variant: "ghost",
								block: "",
								class: "justify-between",
								"data-testid": "pennantplus-scope-result",
								onClick: (t) => ue(e)
							}, {
								default: I(() => [C("span", null, N(e.title), 1), C("span", J, "#" + N(e.id), 1)]),
								_: 2
							}, 1032, ["onClick"]))), 128)), P(_).length === 0 ? (k(), S("p", ae, N(P(a)("pennantplus.features.no_models")), 1)) : x("", !0)])) : x("", !0)]))]),
							_: 1
						}, 8, ["label", "error"])),
						T(i, {
							label: P(a)("pennantplus.features.value"),
							hint: P(a)("pennantplus.features.value_hint"),
							error: P(A) ?? P(c).error("value"),
							required: ""
						}, {
							default: I(() => [T(r, {
								modelValue: P(D),
								"onUpdate:modelValue": n[4] ||= (e) => O(D) ? D.value = e : null,
								class: "w-full font-mono",
								"data-testid": "pennantplus-value-input"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, [
							"label",
							"hint",
							"error"
						]),
						C("div", oe, [T(y, {
							type: "submit",
							icon: "i-lucide-check",
							label: P(a)("pennantplus.features.save"),
							loading: P(c).busy.value,
							"data-testid": "pennantplus-save"
						}, null, 8, ["label", "loading"])])
					]),
					_: 1
				}, 8, ["state"]), P(c).message.value ? (k(), b(M, {
					key: 0,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					title: P(c).message.value
				}, null, 8, ["title"])) : x("", !0)]),
				_: 1
			}, 8, ["title"]);
		};
	}
}), Y = {
	class: "flex flex-col gap-5",
	"data-testid": "pennantplus-features"
}, ce = ["value"], X = { class: "mt-field-label flex gap-2" }, Z = ["title"], le = {
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
}, he = { key: 5 }, Q = "pennantplus-feature-options", ge = /* @__PURE__ */ E({
	__name: "PennantPlusFeatureFlags",
	setup(e) {
		let { t, locale: a } = R(), o = B(), l = re(), u = M([]), m = M([]), h = M([]), E = j(1), O = j(1), F = j(!1), L = j(!1), z = j(!1), U = j(null), W = A({
			feature: "",
			scope: "",
			scope_id: ""
		}), G = j({
			feature: "",
			scope: "",
			scope_id: ""
		}), K = y(() => z.value || l.forbidden.value), q = y(() => m.value.map((e) => ({
			label: e.label,
			value: e.type
		}))), J = y(() => [
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
				header: () => D("span", { class: "sr-only" }, t("pennantplus.features.actions")),
				meta: { class: { td: "text-right" } }
			}
		]), ae = y(() => new Intl.DateTimeFormat(a.value, {
			dateStyle: "medium",
			timeStyle: "short"
		}));
		function oe() {
			let { feature: e, scope: t, scope_id: n } = G.value;
			return {
				...e === "" ? {} : { feature: e },
				...t === "" ? {} : { scope: t },
				...n === "" ? {} : { scope_id: n },
				...E.value > 1 ? { page: E.value } : {}
			};
		}
		async function ge(e) {
			let [t, n] = await Promise.all([e.GET("/features").then((e) => V(e)), e.GET("/scopes").then((e) => V(e))]);
			u.value = t.data, m.value = n.data;
		}
		async function _e(e) {
			let t = V(await e.GET("/values", { params: { query: oe() } }));
			h.value = t.data, E.value = t.meta?.current_page ?? E.value, O.value = t.meta?.last_page ?? 1;
		}
		async function $(e = !1) {
			if (!o) F.value = !0;
			else try {
				await Promise.all([e ? null : ge(o), _e(o)]), L.value = !1;
			} catch (e) {
				z.value = i(e) === 403, L.value = !z.value;
			} finally {
				F.value = !0;
			}
		}
		async function ve() {
			G.value = { ...W }, E.value = 1, await $(!0);
		}
		async function ye() {
			Object.assign(W, {
				feature: "",
				scope: "",
				scope_id: ""
			}), await ve();
		}
		async function be(e) {
			E.value = e, await $(!0);
		}
		async function xe(e, t, n = !0) {
			return U.value = null, !o || !await l.run(() => e(o)) ? !1 : (U.value = t, await $(n), !0);
		}
		async function Se(e) {
			await xe(async (t) => V(await t.PUT("/values", { body: {
				feature: e.feature,
				scope: e.scope,
				value: H(!e.active)
			} })), t("pennantplus.features.value_saved"));
		}
		async function Ce(e) {
			window.confirm(t("pennantplus.features.forget_confirm")) && await xe(async (t) => V(await t.DELETE("/values", { params: { query: {
				feature: e.feature,
				scope: e.scope
			} } })), t("pennantplus.features.value_forgotten"));
		}
		async function we() {
			let e = G.value.feature;
			e !== "" && window.confirm(t("pennantplus.features.purge_confirm")) && await l.run(async () => V(await o.DELETE("/features/{feature}", { params: {
				path: { feature: e },
				query: { feature: e }
			} }))) && (W.feature = "", G.value = {
				...G.value,
				feature: ""
			}, E.value = 1, U.value = t("pennantplus.features.feature_purged"), await $());
		}
		async function Te() {
			U.value = t("pennantplus.features.value_saved"), await $();
		}
		function Ee(e) {
			return e === null ? "" : ae.value.format(new Date(e));
		}
		return $(), (e, i) => {
			let a = n, o = g, y = s, D = p, A = f, j = r, M = ee, R = te, z = d, B = c, V = _;
			return k(), S("div", Y, [P(K) ? (k(), b(a, { key: 0 })) : P(F) ? P(L) ? (k(), b(y, {
				key: 2,
				color: "error",
				variant: "subtle",
				icon: "i-lucide-circle-alert",
				title: P(t)("pennantplus.features.load_failed"),
				actions: [{
					label: P(t)("pennantplus.features.retry"),
					icon: "i-lucide-refresh-cw",
					size: "sm",
					variant: "outline",
					onClick: () => $()
				}],
				"data-testid": "pennantplus-load-error"
			}, null, 8, ["title", "actions"])) : (k(), S(v, { key: 3 }, [
				C("datalist", {
					id: Q,
					"data-testid": Q
				}, [(k(!0), S(v, null, ne(P(u), (e) => (k(), S("option", {
					key: e.name,
					value: e.name
				}, null, 8, ce))), 128))]),
				P(U) ? (k(), b(y, {
					key: 0,
					color: "success",
					variant: "subtle",
					icon: "i-lucide-check",
					title: P(U),
					"data-testid": "pennantplus-status"
				}, null, 8, ["title"])) : x("", !0),
				P(l).message.value ? (k(), b(y, {
					key: 1,
					color: "error",
					variant: "subtle",
					icon: "i-lucide-circle-alert",
					title: P(l).message.value
				}, null, 8, ["title"])) : x("", !0),
				T(se, {
					"scope-types": P(m),
					"feature-list": Q,
					onSaved: Te,
					onForbidden: i[0] ||= (e) => P(l).forbidden.value = !0
				}, null, 8, ["scope-types"]),
				T(R, {
					state: P(W),
					class: "grid items-start gap-3 sm:grid-cols-2 lg:grid-cols-4",
					"data-testid": "pennantplus-filters",
					onSubmit: ve
				}, {
					default: I(() => [
						T(A, { label: P(t)("pennantplus.features.feature") }, {
							default: I(() => [T(D, {
								modelValue: P(W).feature,
								"onUpdate:modelValue": i[1] ||= (e) => P(W).feature = e,
								list: Q,
								placeholder: P(t)("pennantplus.features.all_features"),
								autocomplete: "off",
								class: "w-full",
								"data-testid": "pennantplus-filter-feature"
							}, null, 8, ["modelValue", "placeholder"])]),
							_: 1
						}, 8, ["label"]),
						T(A, { label: P(t)("pennantplus.features.scope") }, {
							default: I(() => [T(j, {
								modelValue: P(W).scope,
								"onUpdate:modelValue": i[2] ||= (e) => P(W).scope = e,
								items: P(q),
								blank: P(t)("pennantplus.features.all_scopes"),
								class: "w-full",
								"data-testid": "pennantplus-filter-scope"
							}, null, 8, [
								"modelValue",
								"items",
								"blank"
							])]),
							_: 1
						}, 8, ["label"]),
						T(A, { label: P(t)("pennantplus.features.scope_id") }, {
							default: I(() => [T(D, {
								modelValue: P(W).scope_id,
								"onUpdate:modelValue": i[3] ||= (e) => P(W).scope_id = e,
								class: "w-full",
								"data-testid": "pennantplus-filter-scope-id"
							}, null, 8, ["modelValue"])]),
							_: 1
						}, 8, ["label"]),
						C("div", X, [T(M, {
							type: "submit",
							variant: "outline",
							icon: "i-lucide-funnel",
							label: P(t)("pennantplus.features.filter"),
							"data-testid": "pennantplus-filter"
						}, null, 8, ["label"]), T(M, {
							color: "neutral",
							variant: "ghost",
							icon: "i-lucide-x",
							label: P(t)("pennantplus.features.reset"),
							"data-testid": "pennantplus-reset",
							onClick: ye
						}, null, 8, ["label"])])
					]),
					_: 1
				}, 8, ["state"]),
				P(h).length === 0 ? (k(), b(z, {
					key: 2,
					icon: "i-lucide-flag",
					variant: "naked",
					description: P(t)("pennantplus.features.no_values"),
					"data-testid": "pennantplus-no-values"
				}, null, 8, ["description"])) : (k(), b(V, {
					key: 3,
					data: P(h),
					columns: P(J),
					"get-row-id": (e) => `${e.feature}|${e.scope}`,
					class: "rounded-md border border-default",
					"data-testid": "pennantplus-values"
				}, {
					"scope-cell": I(({ row: e }) => [C("span", { title: e.original.scope }, N(e.original.scope_label), 9, Z), e.original.title ? (k(), S("span", le, N(e.original.title), 1)) : x("", !0)]),
					"value-cell": I(({ row: e }) => [C("div", ue, [T(B, {
						color: e.original.active ? "success" : "neutral",
						variant: "subtle",
						label: e.original.active ? P(t)("pennantplus.features.active") : P(t)("pennantplus.features.inactive"),
						"data-status": e.original.active ? "active" : "inactive",
						"data-testid": "pennantplus-value-status"
					}, null, 8, [
						"color",
						"label",
						"data-status"
					]), typeof e.original.value == "boolean" ? x("", !0) : (k(), S("code", de, N(P(ie)(e.original.value)), 1))])]),
					"updated_at-cell": I(({ row: e }) => [w(N(Ee(e.original.updated_at)), 1)]),
					"actions-cell": I(({ row: e }) => [C("div", fe, [T(M, {
						size: "xs",
						variant: "outline",
						icon: e.original.active ? "i-lucide-ban" : "i-lucide-circle-check",
						label: e.original.active ? P(t)("pennantplus.features.deactivate") : P(t)("pennantplus.features.activate"),
						loading: P(l).busy.value,
						"data-testid": "pennantplus-toggle",
						onClick: (t) => Se(e.original)
					}, null, 8, [
						"icon",
						"label",
						"loading",
						"onClick"
					]), T(M, {
						size: "xs",
						color: "neutral",
						variant: "ghost",
						icon: "i-lucide-x",
						label: P(t)("pennantplus.features.forget"),
						"data-testid": "pennantplus-forget",
						onClick: (t) => Ce(e.original)
					}, null, 8, ["label", "onClick"])])]),
					_: 1
				}, 8, [
					"data",
					"columns",
					"get-row-id"
				])),
				P(O) > 1 ? (k(), S("div", pe, [
					T(M, {
						size: "sm",
						color: "neutral",
						variant: "outline",
						icon: "i-lucide-chevron-left",
						label: P(t)("pennantplus.features.previous"),
						disabled: P(E) <= 1,
						"data-testid": "pennantplus-prev",
						onClick: i[4] ||= (e) => be(P(E) - 1)
					}, null, 8, ["label", "disabled"]),
					C("span", me, N(P(t)("pennantplus.features.page", {
						page: P(E),
						pages: P(O)
					})), 1),
					T(M, {
						size: "sm",
						color: "neutral",
						variant: "outline",
						"trailing-icon": "i-lucide-chevron-right",
						label: P(t)("pennantplus.features.next"),
						disabled: P(E) >= P(O),
						"data-testid": "pennantplus-next",
						onClick: i[5] ||= (e) => be(P(E) + 1)
					}, null, 8, ["label", "disabled"])
				])) : x("", !0),
				P(G).feature === "" ? x("", !0) : (k(), S("div", he, [T(M, {
					color: "error",
					variant: "outline",
					size: "sm",
					icon: "i-lucide-trash",
					label: P(t)("pennantplus.features.purge", { feature: P(G).feature }),
					loading: P(l).busy.value,
					"data-testid": "pennantplus-purge",
					onClick: we
				}, null, 8, ["label", "loading"])]))
			], 64)) : (k(), b(o, {
				key: 1,
				class: "h-32"
			}))]);
		};
	}
}), _e = { class: "mb-4 text-sm text-muted" }, $ = /* @__PURE__ */ E({
	__name: "features",
	setup(e) {
		let { t: n } = R();
		return o({ title: () => n("pennantplus.features.title") }), (e, r) => {
			let i = l, a = t, o = u;
			return k(), b(o, null, {
				header: I(() => [T(i, { title: P(n)("pennantplus.features.title") }, null, 8, ["title"])]),
				body: I(() => [C("p", _e, N(P(n)("pennantplus.features.description")), 1), T(a, { "screen-key": "pennantplus.features" }, {
					default: I(() => [T(ge)]),
					_: 1
				})]),
				_: 1
			});
		};
	}
});
export { $ as default };
