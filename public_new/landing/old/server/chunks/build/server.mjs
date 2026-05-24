import { hasInjectionContext, getCurrentInstance, version, ref, watchEffect, watch, inject, toRaw, isRef, isReactive, toRef, useSSRContext, unref, defineComponent, computed, h, resolveComponent, mergeProps, withCtx, createVNode, createTextVNode, toDisplayString, withDirectives, vShow, openBlock, createBlock, Fragment, renderList, createCommentVNode, renderSlot, onUnmounted, provide, createApp, reactive, effectScope, getCurrentScope, onScopeDispose, nextTick, resolveDirective, withKeys, onErrorCaptured, onServerPrefetch, resolveDynamicComponent, shallowRef, shallowReactive, isReadonly, toRefs, markRaw, defineAsyncComponent, isShallow, withAsyncContext, Suspense, Transition } from 'vue';
import { d as useRuntimeConfig$1, w as withQuery, l as hasProtocol, p as parseURL, m as isScriptProtocol, j as joinURL, h as createError$1, $ as $fetch, n as getContext, o as sanitizeStatusCode, q as parseQuery, r as createHooks, t as parse, v as getRequestHeader, x as destr, y as isEqual, z as setCookie, A as getCookie, B as deleteCookie, C as withTrailingSlash, D as withoutTrailingSlash, E as defu } from '../runtime.mjs';
import { getActiveHead } from 'unhead';
import { defineHeadPlugin, composableNames } from '@unhead/shared';
import { createMemoryHistory, createRouter, START_LOCATION, RouterView } from 'vue-router';
import dayjs from 'dayjs';
import updateLocale from 'dayjs/plugin/updateLocale.js';
import relativeTime from 'dayjs/plugin/relativeTime.js';
import utc from 'dayjs/plugin/utc.js';
import chalk from 'chalk';
import { createPersistedState } from 'pinia-plugin-persistedstate';
import { vMaska } from 'maska';
import { ssrRenderAttrs, ssrRenderSlot, ssrRenderAttr, ssrGetDirectiveProps, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderStyle, ssrRenderSuspense, ssrRenderVNode } from 'vue/server-renderer';
import draggable from 'vuedraggable';
import { clickOutSide } from '@mahdikhashan/vue3-click-outside';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import 'node:http';
import 'node:https';
import 'node:fs';
import 'node:path';
import 'node:async_hooks';
import 'node:url';

var __accessCheck = (obj, member, msg) => {
  if (!member.has(obj))
    throw TypeError("Cannot " + msg);
};
var __privateGet = (obj, member, getter) => {
  __accessCheck(obj, member, "read from private field");
  return getter ? getter.call(obj) : member.get(obj);
};
var __privateAdd = (obj, member, value) => {
  if (member.has(obj))
    throw TypeError("Cannot add the same private member more than once");
  member instanceof WeakSet ? member.add(obj) : member.set(obj, value);
};
var __privateSet = (obj, member, value, setter) => {
  __accessCheck(obj, member, "write to private field");
  member.set(obj, value);
  return value;
};
var __privateMethod = (obj, member, method) => {
  __accessCheck(obj, member, "access private method");
  return method;
};
var _id, _debug, _verification, _call, call_fn;
const appConfig = useRuntimeConfig$1().app;
const baseURL = () => appConfig.baseURL;
const nuxtAppCtx = /* @__PURE__ */ getContext("nuxt-app", {
  asyncContext: true
});
const NuxtPluginIndicator = "__nuxt_plugin";
function createNuxtApp(options) {
  let hydratingCount = 0;
  const nuxtApp = {
    provide: void 0,
    globalName: "nuxt",
    versions: {
      get nuxt() {
        return "3.7.3";
      },
      get vue() {
        return nuxtApp.vueApp.version;
      }
    },
    payload: reactive({
      data: {},
      state: {},
      _errors: {},
      ...{ serverRendered: true }
    }),
    static: {
      data: {}
    },
    runWithContext: (fn) => callWithNuxt(nuxtApp, fn),
    isHydrating: false,
    deferHydration() {
      if (!nuxtApp.isHydrating) {
        return () => {
        };
      }
      hydratingCount++;
      let called = false;
      return () => {
        if (called) {
          return;
        }
        called = true;
        hydratingCount--;
        if (hydratingCount === 0) {
          nuxtApp.isHydrating = false;
          return nuxtApp.callHook("app:suspense:resolve");
        }
      };
    },
    _asyncDataPromises: {},
    _asyncData: {},
    _payloadRevivers: {},
    ...options
  };
  nuxtApp.hooks = createHooks();
  nuxtApp.hook = nuxtApp.hooks.hook;
  {
    async function contextCaller(hooks, args) {
      for (const hook of hooks) {
        await nuxtApp.runWithContext(() => hook(...args));
      }
    }
    nuxtApp.hooks.callHook = (name, ...args) => nuxtApp.hooks.callHookWith(contextCaller, name, ...args);
  }
  nuxtApp.callHook = nuxtApp.hooks.callHook;
  nuxtApp.provide = (name, value) => {
    const $name = "$" + name;
    defineGetter(nuxtApp, $name, value);
    defineGetter(nuxtApp.vueApp.config.globalProperties, $name, value);
  };
  defineGetter(nuxtApp.vueApp, "$nuxt", nuxtApp);
  defineGetter(nuxtApp.vueApp.config.globalProperties, "$nuxt", nuxtApp);
  {
    if (nuxtApp.ssrContext) {
      nuxtApp.ssrContext.nuxt = nuxtApp;
      nuxtApp.ssrContext._payloadReducers = {};
      nuxtApp.payload.path = nuxtApp.ssrContext.url;
    }
    nuxtApp.ssrContext = nuxtApp.ssrContext || {};
    if (nuxtApp.ssrContext.payload) {
      Object.assign(nuxtApp.payload, nuxtApp.ssrContext.payload);
    }
    nuxtApp.ssrContext.payload = nuxtApp.payload;
    nuxtApp.ssrContext.config = {
      public: options.ssrContext.runtimeConfig.public,
      app: options.ssrContext.runtimeConfig.app
    };
  }
  const runtimeConfig = options.ssrContext.runtimeConfig;
  nuxtApp.provide("config", runtimeConfig);
  return nuxtApp;
}
async function applyPlugin(nuxtApp, plugin2) {
  if (plugin2.hooks) {
    nuxtApp.hooks.addHooks(plugin2.hooks);
  }
  if (typeof plugin2 === "function") {
    const { provide: provide2 } = await nuxtApp.runWithContext(() => plugin2(nuxtApp)) || {};
    if (provide2 && typeof provide2 === "object") {
      for (const key in provide2) {
        nuxtApp.provide(key, provide2[key]);
      }
    }
  }
}
async function applyPlugins(nuxtApp, plugins2) {
  var _a, _b;
  const parallels = [];
  const errors = [];
  for (const plugin2 of plugins2) {
    if (((_a = nuxtApp.ssrContext) == null ? void 0 : _a.islandContext) && ((_b = plugin2.env) == null ? void 0 : _b.islands) === false) {
      continue;
    }
    const promise = applyPlugin(nuxtApp, plugin2);
    if (plugin2.parallel) {
      parallels.push(promise.catch((e) => errors.push(e)));
    } else {
      await promise;
    }
  }
  await Promise.all(parallels);
  if (errors.length) {
    throw errors[0];
  }
}
/*! @__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function defineNuxtPlugin(plugin2) {
  if (typeof plugin2 === "function") {
    return plugin2;
  }
  delete plugin2.name;
  return Object.assign(plugin2.setup || (() => {
  }), plugin2, { [NuxtPluginIndicator]: true });
}
function callWithNuxt(nuxt, setup, args) {
  const fn = () => setup();
  {
    return nuxt.vueApp.runWithContext(() => nuxtAppCtx.callAsync(nuxt, fn));
  }
}
/*! @__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function useNuxtApp() {
  var _a;
  let nuxtAppInstance;
  if (hasInjectionContext()) {
    nuxtAppInstance = (_a = getCurrentInstance()) == null ? void 0 : _a.appContext.app.$nuxt;
  }
  nuxtAppInstance = nuxtAppInstance || nuxtAppCtx.tryUse();
  if (!nuxtAppInstance) {
    {
      throw new Error("[nuxt] instance unavailable");
    }
  }
  return nuxtAppInstance;
}
/*! @__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function useRuntimeConfig() {
  return (/* @__PURE__ */ useNuxtApp()).$config;
}
function defineGetter(obj, key, val) {
  Object.defineProperty(obj, key, { get: () => val });
}
version[0] === "3";
function resolveUnref(r) {
  return typeof r === "function" ? r() : unref(r);
}
function resolveUnrefHeadInput(ref2) {
  if (ref2 instanceof Promise || ref2 instanceof Date || ref2 instanceof RegExp)
    return ref2;
  const root = resolveUnref(ref2);
  if (!ref2 || !root)
    return root;
  if (Array.isArray(root))
    return root.map((r) => resolveUnrefHeadInput(r));
  if (typeof root === "object") {
    const resolved = {};
    for (const k in root) {
      if (!Object.prototype.hasOwnProperty.call(root, k)) {
        continue;
      }
      if (k === "titleTemplate" || k[0] === "o" && k[1] === "n") {
        resolved[k] = unref(root[k]);
        continue;
      }
      resolved[k] = resolveUnrefHeadInput(root[k]);
    }
    return resolved;
  }
  return root;
}
defineHeadPlugin({
  hooks: {
    "entries:resolve": (ctx) => {
      for (const entry2 of ctx.entries)
        entry2.resolvedInput = resolveUnrefHeadInput(entry2.input);
    }
  }
});
const headSymbol = "usehead";
const _global = typeof globalThis !== "undefined" ? globalThis : typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : {};
const globalKey$1 = "__unhead_injection_handler__";
function setHeadInjectionHandler(handler) {
  _global[globalKey$1] = handler;
}
function injectHead() {
  if (globalKey$1 in _global) {
    return _global[globalKey$1]();
  }
  const head = inject(headSymbol);
  if (!head && "production" !== "production")
    console.warn("Unhead is missing Vue context, falling back to shared context. This may have unexpected results.");
  return head || getActiveHead();
}
function useHead(input, options = {}) {
  const head = options.head || injectHead();
  if (head) {
    if (!head.ssr)
      return clientUseHead(head, input, options);
    return head.push(input, options);
  }
}
function clientUseHead(head, input, options = {}) {
  const deactivated = ref(false);
  const resolvedInput = ref({});
  watchEffect(() => {
    resolvedInput.value = deactivated.value ? {} : resolveUnrefHeadInput(input);
  });
  const entry2 = head.push(resolvedInput.value, options);
  watch(resolvedInput, (e) => {
    entry2.patch(e);
  });
  getCurrentInstance();
  return entry2;
}
const coreComposableNames = [
  "injectHead"
];
({
  "@unhead/vue": [...coreComposableNames, ...composableNames]
});
const LayoutMetaSymbol = Symbol("layout-meta");
const PageRouteSymbol = Symbol("route");
const useRouter = () => {
  var _a;
  return (_a = /* @__PURE__ */ useNuxtApp()) == null ? void 0 : _a.$router;
};
const useRoute = () => {
  if (hasInjectionContext()) {
    return inject(PageRouteSymbol, (/* @__PURE__ */ useNuxtApp())._route);
  }
  return (/* @__PURE__ */ useNuxtApp())._route;
};
/*! @__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function defineNuxtRouteMiddleware(middleware) {
  return middleware;
}
const isProcessingMiddleware = () => {
  try {
    if ((/* @__PURE__ */ useNuxtApp())._processingMiddleware) {
      return true;
    }
  } catch {
    return true;
  }
  return false;
};
const navigateTo = (to, options) => {
  if (!to) {
    to = "/";
  }
  const toPath = typeof to === "string" ? to : withQuery(to.path || "/", to.query || {}) + (to.hash || "");
  if (options == null ? void 0 : options.open) {
    return Promise.resolve();
  }
  const isExternal = (options == null ? void 0 : options.external) || hasProtocol(toPath, { acceptRelative: true });
  if (isExternal) {
    if (!(options == null ? void 0 : options.external)) {
      throw new Error("Navigating to an external URL is not allowed by default. Use `navigateTo(url, { external: true })`.");
    }
    const protocol = parseURL(toPath).protocol;
    if (protocol && isScriptProtocol(protocol)) {
      throw new Error(`Cannot navigate to a URL with '${protocol}' protocol.`);
    }
  }
  const inMiddleware = isProcessingMiddleware();
  const router = useRouter();
  const nuxtApp = /* @__PURE__ */ useNuxtApp();
  {
    if (nuxtApp.ssrContext) {
      const fullPath = typeof to === "string" || isExternal ? toPath : router.resolve(to).fullPath || "/";
      const location2 = isExternal ? toPath : joinURL((/* @__PURE__ */ useRuntimeConfig()).app.baseURL, fullPath);
      async function redirect(response) {
        await nuxtApp.callHook("app:redirected");
        const encodedLoc = location2.replace(/"/g, "%22");
        nuxtApp.ssrContext._renderResponse = {
          statusCode: sanitizeStatusCode((options == null ? void 0 : options.redirectCode) || 302, 302),
          body: `<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0; url=${encodedLoc}"></head></html>`,
          headers: { location: location2 }
        };
        return response;
      }
      if (!isExternal && inMiddleware) {
        router.afterEach((final) => final.fullPath === fullPath ? redirect(false) : void 0);
        return to;
      }
      return redirect(!inMiddleware ? void 0 : (
        /* abort route navigation */
        false
      ));
    }
  }
  if (isExternal) {
    if (options == null ? void 0 : options.replace) {
      location.replace(toPath);
    } else {
      location.href = toPath;
    }
    if (inMiddleware) {
      if (!nuxtApp.isHydrating) {
        return false;
      }
      return new Promise(() => {
      });
    }
    return Promise.resolve();
  }
  return (options == null ? void 0 : options.replace) ? router.replace(to) : router.push(to);
};
const useError = () => toRef((/* @__PURE__ */ useNuxtApp()).payload, "error");
const showError = (_err) => {
  const err = createError(_err);
  try {
    const nuxtApp = /* @__PURE__ */ useNuxtApp();
    const error2 = useError();
    if (false)
      ;
    error2.value = error2.value || err;
  } catch {
    throw err;
  }
  return err;
};
const isNuxtError = (err) => !!(err && typeof err === "object" && "__nuxt_error" in err);
const createError = (err) => {
  const _err = createError$1(err);
  _err.__nuxt_error = true;
  return _err;
};
function useRequestEvent(nuxtApp = /* @__PURE__ */ useNuxtApp()) {
  var _a;
  return (_a = nuxtApp.ssrContext) == null ? void 0 : _a.event;
}
const CookieDefaults = {
  path: "/",
  watch: true,
  decode: (val) => destr(decodeURIComponent(val)),
  encode: (val) => encodeURIComponent(typeof val === "string" ? val : JSON.stringify(val))
};
function useCookie(name, _opts) {
  var _a;
  const opts = { ...CookieDefaults, ..._opts };
  const cookies = readRawCookies(opts) || {};
  const cookie = ref(cookies[name] ?? ((_a = opts.default) == null ? void 0 : _a.call(opts)));
  {
    const nuxtApp = /* @__PURE__ */ useNuxtApp();
    const writeFinalCookieValue = () => {
      if (!isEqual(cookie.value, cookies[name])) {
        writeServerCookie(useRequestEvent(nuxtApp), name, cookie.value, opts);
      }
    };
    const unhook = nuxtApp.hooks.hookOnce("app:rendered", writeFinalCookieValue);
    nuxtApp.hooks.hookOnce("app:error", () => {
      unhook();
      return writeFinalCookieValue();
    });
  }
  return cookie;
}
function readRawCookies(opts = {}) {
  {
    return parse(getRequestHeader(useRequestEvent(), "cookie") || "", opts);
  }
}
function writeServerCookie(event, name, value, opts = {}) {
  if (event) {
    if (value !== null && value !== void 0) {
      return setCookie(event, name, value, opts);
    }
    if (getCookie(event, name) !== void 0) {
      return deleteCookie(event, name, opts);
    }
  }
}
const appPageTransition = false;
const appKeepalive = false;
function definePayloadReducer(name, reduce) {
  {
    (/* @__PURE__ */ useNuxtApp()).ssrContext._payloadReducers[name] = reduce;
  }
}
const firstNonUndefined = (...args) => args.find((arg) => arg !== void 0);
const DEFAULT_EXTERNAL_REL_ATTRIBUTE = "noopener noreferrer";
/*! @__NO_SIDE_EFFECTS__ */
// @__NO_SIDE_EFFECTS__
function defineNuxtLink(options) {
  const componentName = options.componentName || "NuxtLink";
  const resolveTrailingSlashBehavior = (to, resolve) => {
    if (!to || options.trailingSlash !== "append" && options.trailingSlash !== "remove") {
      return to;
    }
    const normalizeTrailingSlash = options.trailingSlash === "append" ? withTrailingSlash : withoutTrailingSlash;
    if (typeof to === "string") {
      return normalizeTrailingSlash(to, true);
    }
    const path = "path" in to ? to.path : resolve(to).path;
    return {
      ...to,
      name: void 0,
      // named routes would otherwise always override trailing slash behavior
      path: normalizeTrailingSlash(path, true)
    };
  };
  return /* @__PURE__ */ defineComponent({
    name: componentName,
    props: {
      // Routing
      to: {
        type: [String, Object],
        default: void 0,
        required: false
      },
      href: {
        type: [String, Object],
        default: void 0,
        required: false
      },
      // Attributes
      target: {
        type: String,
        default: void 0,
        required: false
      },
      rel: {
        type: String,
        default: void 0,
        required: false
      },
      noRel: {
        type: Boolean,
        default: void 0,
        required: false
      },
      // Prefetching
      prefetch: {
        type: Boolean,
        default: void 0,
        required: false
      },
      noPrefetch: {
        type: Boolean,
        default: void 0,
        required: false
      },
      // Styling
      activeClass: {
        type: String,
        default: void 0,
        required: false
      },
      exactActiveClass: {
        type: String,
        default: void 0,
        required: false
      },
      prefetchedClass: {
        type: String,
        default: void 0,
        required: false
      },
      // Vue Router's `<RouterLink>` additional props
      replace: {
        type: Boolean,
        default: void 0,
        required: false
      },
      ariaCurrentValue: {
        type: String,
        default: void 0,
        required: false
      },
      // Edge cases handling
      external: {
        type: Boolean,
        default: void 0,
        required: false
      },
      // Slot API
      custom: {
        type: Boolean,
        default: void 0,
        required: false
      }
    },
    setup(props, { slots }) {
      const router = useRouter();
      const to = computed(() => {
        const path = props.to || props.href || "";
        return resolveTrailingSlashBehavior(path, router.resolve);
      });
      const isExternal = computed(() => {
        if (props.external) {
          return true;
        }
        if (props.target && props.target !== "_self") {
          return true;
        }
        if (typeof to.value === "object") {
          return false;
        }
        return to.value === "" || hasProtocol(to.value, { acceptRelative: true });
      });
      const prefetched = ref(false);
      const el = void 0;
      const elRef = void 0;
      return () => {
        var _a, _b;
        if (!isExternal.value) {
          const routerLinkProps = {
            ref: elRef,
            to: to.value,
            activeClass: props.activeClass || options.activeClass,
            exactActiveClass: props.exactActiveClass || options.exactActiveClass,
            replace: props.replace,
            ariaCurrentValue: props.ariaCurrentValue,
            custom: props.custom
          };
          if (!props.custom) {
            if (prefetched.value) {
              routerLinkProps.class = props.prefetchedClass || options.prefetchedClass;
            }
            routerLinkProps.rel = props.rel;
          }
          return h(
            resolveComponent("RouterLink"),
            routerLinkProps,
            slots.default
          );
        }
        const href = typeof to.value === "object" ? ((_a = router.resolve(to.value)) == null ? void 0 : _a.href) ?? null : to.value || null;
        const target = props.target || null;
        const rel = props.noRel ? null : firstNonUndefined(props.rel, options.externalRelAttribute, href ? DEFAULT_EXTERNAL_REL_ATTRIBUTE : "") || null;
        const navigate = () => navigateTo(href, { replace: props.replace });
        if (props.custom) {
          if (!slots.default) {
            return null;
          }
          return slots.default({
            href,
            navigate,
            get route() {
              if (!href) {
                return void 0;
              }
              const url = parseURL(href);
              return {
                path: url.pathname,
                fullPath: url.pathname,
                get query() {
                  return parseQuery(url.search);
                },
                hash: url.hash,
                // stub properties for compat with vue-router
                params: {},
                name: void 0,
                matched: [],
                redirectedFrom: void 0,
                meta: {},
                href
              };
            },
            rel,
            target,
            isExternal: isExternal.value,
            isActive: false,
            isExactActive: false
          });
        }
        return h("a", { ref: el, href, rel, target }, (_b = slots.default) == null ? void 0 : _b.call(slots));
      };
    }
  });
}
const __nuxt_component_0 = /* @__PURE__ */ defineNuxtLink({ componentName: "NuxtLink" });
const unhead_KgADcZ0jPj = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:head",
  enforce: "pre",
  setup(nuxtApp) {
    const head = nuxtApp.ssrContext.head;
    setHeadInjectionHandler(
      // need a fresh instance of the nuxt app to avoid parallel requests interfering with each other
      () => (/* @__PURE__ */ useNuxtApp()).vueApp._context.provides.usehead
    );
    nuxtApp.vueApp.use(head);
  }
});
function createContext(opts = {}) {
  let currentInstance;
  let isSingleton = false;
  const checkConflict = (instance) => {
    if (currentInstance && currentInstance !== instance) {
      throw new Error("Context conflict");
    }
  };
  let als;
  if (opts.asyncContext) {
    const _AsyncLocalStorage = opts.AsyncLocalStorage || globalThis.AsyncLocalStorage;
    if (_AsyncLocalStorage) {
      als = new _AsyncLocalStorage();
    } else {
      console.warn("[unctx] `AsyncLocalStorage` is not provided.");
    }
  }
  const _getCurrentInstance = () => {
    if (als && currentInstance === void 0) {
      const instance = als.getStore();
      if (instance !== void 0) {
        return instance;
      }
    }
    return currentInstance;
  };
  return {
    use: () => {
      const _instance = _getCurrentInstance();
      if (_instance === void 0) {
        throw new Error("Context is not available");
      }
      return _instance;
    },
    tryUse: () => {
      return _getCurrentInstance();
    },
    set: (instance, replace) => {
      if (!replace) {
        checkConflict(instance);
      }
      currentInstance = instance;
      isSingleton = true;
    },
    unset: () => {
      currentInstance = void 0;
      isSingleton = false;
    },
    call: (instance, callback) => {
      checkConflict(instance);
      currentInstance = instance;
      try {
        return als ? als.run(instance, callback) : callback();
      } finally {
        if (!isSingleton) {
          currentInstance = void 0;
        }
      }
    },
    async callAsync(instance, callback) {
      currentInstance = instance;
      const onRestore = () => {
        currentInstance = instance;
      };
      const onLeave = () => currentInstance === instance ? onRestore : void 0;
      asyncHandlers.add(onLeave);
      try {
        const r = als ? als.run(instance, callback) : callback();
        if (!isSingleton) {
          currentInstance = void 0;
        }
        return await r;
      } finally {
        asyncHandlers.delete(onLeave);
      }
    }
  };
}
function createNamespace(defaultOpts = {}) {
  const contexts = {};
  return {
    get(key, opts = {}) {
      if (!contexts[key]) {
        contexts[key] = createContext({ ...defaultOpts, ...opts });
      }
      contexts[key];
      return contexts[key];
    }
  };
}
const _globalThis = typeof globalThis !== "undefined" ? globalThis : typeof self !== "undefined" ? self : typeof global !== "undefined" ? global : {};
const globalKey = "__unctx__";
_globalThis[globalKey] || (_globalThis[globalKey] = createNamespace());
const asyncHandlersKey = "__unctx_async_handlers__";
const asyncHandlers = _globalThis[asyncHandlersKey] || (_globalThis[asyncHandlersKey] = /* @__PURE__ */ new Set());
function executeAsync(function_) {
  const restores = [];
  for (const leaveHandler of asyncHandlers) {
    const restore2 = leaveHandler();
    if (restore2) {
      restores.push(restore2);
    }
  }
  const restore = () => {
    for (const restore2 of restores) {
      restore2();
    }
  };
  let awaitable = function_();
  if (awaitable && typeof awaitable === "object" && "catch" in awaitable) {
    awaitable = awaitable.catch((error2) => {
      restore();
      throw error2;
    });
  }
  return [awaitable, restore];
}
const _routes = [
  {
    name: "403",
    path: "/403",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-255b80e4.mjs').then((m) => m.default || m)
  },
  {
    name: "articles-category-category",
    path: "/articles-category/:category()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_category_-ec0cbdef.mjs').then((m) => m.default || m)
  },
  {
    name: "articles-category",
    path: "/articles-category",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-278f7222.mjs').then((m) => m.default || m)
  },
  {
    name: "articles-id",
    path: "/articles/:id()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_id_-225ebbaa.mjs').then((m) => m.default || m)
  },
  {
    name: "articles",
    path: "/articles",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-05808294.mjs').then((m) => m.default || m)
  },
  {
    name: "auth-tab",
    path: "/auth/:tab()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_tab_-f6cea4da.mjs').then((m) => m.default || m)
  },
  {
    name: "auth",
    path: "/auth",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-039e482c.mjs').then((m) => m.default || m)
  },
  {
    name: "contacts",
    path: "/contacts",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-6d08b515.mjs').then((m) => m.default || m)
  },
  {
    name: "docs-doc",
    path: "/docs/:doc()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_doc_-961996d7.mjs').then((m) => m.default || m)
  },
  {
    name: "docs",
    path: "/docs",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-dae37408.mjs').then((m) => m.default || m)
  },
  {
    name: "index",
    path: "/",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-921546a4.mjs').then((m) => m.default || m)
  },
  {
    name: "knowledge-category-category",
    path: "/knowledge-category/:category()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_category_-319dd3ff.mjs').then((m) => m.default || m)
  },
  {
    name: "knowledge-category",
    path: "/knowledge-category",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-9fcd2b2c.mjs').then((m) => m.default || m)
  },
  {
    name: "knowledge-id",
    path: "/knowledge/:id()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_id_-906f9aac.mjs').then((m) => m.default || m)
  },
  {
    name: "knowledge",
    path: "/knowledge",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-94f31da3.mjs').then((m) => m.default || m)
  },
  {
    name: "payment",
    path: "/payment",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-d6fea6a7.mjs').then((m) => m.default || m)
  },
  {
    name: "products-distance-tab",
    path: "/products/distance/:tab()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_tab_-784041ab.mjs').then((m) => m.default || m)
  },
  {
    name: "products-distance",
    path: "/products/distance",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-c3499185.mjs').then((m) => m.default || m)
  },
  {
    name: "products-fines-type",
    path: "/products/fines/:type()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_type_-39d87629.mjs').then((m) => m.default || m)
  },
  {
    name: "products-fines",
    path: "/products/fines",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-26ec33e9.mjs').then((m) => m.default || m)
  },
  {
    name: "products-fines-list",
    path: "/products/fines/list",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./list-ef25a268.mjs').then((m) => m.default || m)
  },
  {
    name: "products",
    path: "/products",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-b29f29fd.mjs').then((m) => m.default || m)
  },
  {
    name: "questions-category-category",
    path: "/questions-category/:category()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_category_-95ee9bbe.mjs').then((m) => m.default || m)
  },
  {
    name: "questions-category",
    path: "/questions-category",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-59c0e016.mjs').then((m) => m.default || m)
  },
  {
    name: "questions-id",
    path: "/questions/:id()",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./_id_-2f92489c.mjs').then((m) => m.default || m)
  },
  {
    name: "questions",
    path: "/questions",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-b16f22e2.mjs').then((m) => m.default || m)
  },
  {
    name: "tariffs",
    path: "/tariffs",
    meta: {},
    alias: [],
    redirect: void 0,
    component: () => import('./index-143e0a9f.mjs').then((m) => m.default || m)
  }
];
const routerOptions0 = {
  scrollBehavior(to, from, savedPosition) {
    var _a;
    const nuxtApp = /* @__PURE__ */ useNuxtApp();
    const behavior = ((_a = useRouter().options) == null ? void 0 : _a.scrollBehaviorType) ?? "auto";
    let position = savedPosition || void 0;
    const routeAllowsScrollToTop = typeof to.meta.scrollToTop === "function" ? to.meta.scrollToTop(to, from) : to.meta.scrollToTop;
    if (!position && from && to && routeAllowsScrollToTop !== false && _isDifferentRoute(from, to)) {
      position = { left: 0, top: 0 };
    }
    if (to.path === from.path) {
      if (from.hash && !to.hash) {
        return { left: 0, top: 0 };
      }
      if (to.hash) {
        return { el: to.hash, top: _getHashElementScrollMarginTop(to.hash), behavior };
      }
    }
    const hasTransition = (route) => !!(route.meta.pageTransition ?? appPageTransition);
    const hookToWait = hasTransition(from) && hasTransition(to) ? "page:transition:finish" : "page:finish";
    return new Promise((resolve) => {
      nuxtApp.hooks.hookOnce(hookToWait, async () => {
        await nextTick();
        if (to.hash) {
          position = { el: to.hash, top: _getHashElementScrollMarginTop(to.hash), behavior };
        }
        resolve(position);
      });
    });
  }
};
function _getHashElementScrollMarginTop(selector) {
  try {
    const elem = document.querySelector(selector);
    if (elem) {
      return parseFloat(getComputedStyle(elem).scrollMarginTop);
    }
  } catch {
  }
  return 0;
}
function _isDifferentRoute(from, to) {
  return to.path !== from.path || JSON.stringify(from.params) !== JSON.stringify(to.params);
}
const configRouterOptions = {};
const routerOptions = {
  ...configRouterOptions,
  ...routerOptions0
};
const validate = /* @__PURE__ */ defineNuxtRouteMiddleware(async (to) => {
  var _a;
  let __temp, __restore;
  if (!((_a = to.meta) == null ? void 0 : _a.validate)) {
    return;
  }
  useRouter();
  const result = ([__temp, __restore] = executeAsync(() => Promise.resolve(to.meta.validate(to))), __temp = await __temp, __restore(), __temp);
  if (result === true) {
    return;
  }
  {
    return result;
  }
});
const globalMiddleware = [
  validate
];
const namedMiddleware = {};
const plugin$1 = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:router",
  enforce: "pre",
  async setup(nuxtApp) {
    var _a, _b;
    let __temp, __restore;
    let routerBase = (/* @__PURE__ */ useRuntimeConfig()).app.baseURL;
    if (routerOptions.hashMode && !routerBase.includes("#")) {
      routerBase += "#";
    }
    const history = ((_a = routerOptions.history) == null ? void 0 : _a.call(routerOptions, routerBase)) ?? createMemoryHistory(routerBase);
    const routes = ((_b = routerOptions.routes) == null ? void 0 : _b.call(routerOptions, _routes)) ?? _routes;
    let startPosition;
    const initialURL = nuxtApp.ssrContext.url;
    const router = createRouter({
      ...routerOptions,
      scrollBehavior: (to, from, savedPosition) => {
        var _a2;
        if (from === START_LOCATION) {
          startPosition = savedPosition;
          return;
        }
        router.options.scrollBehavior = routerOptions.scrollBehavior;
        return (_a2 = routerOptions.scrollBehavior) == null ? void 0 : _a2.call(routerOptions, to, START_LOCATION, startPosition || savedPosition);
      },
      history,
      routes
    });
    nuxtApp.vueApp.use(router);
    const previousRoute = shallowRef(router.currentRoute.value);
    router.afterEach((_to, from) => {
      previousRoute.value = from;
    });
    Object.defineProperty(nuxtApp.vueApp.config.globalProperties, "previousRoute", {
      get: () => previousRoute.value
    });
    const _route = shallowRef(router.resolve(initialURL));
    const syncCurrentRoute = () => {
      _route.value = router.currentRoute.value;
    };
    nuxtApp.hook("page:finish", syncCurrentRoute);
    router.afterEach((to, from) => {
      var _a2, _b2, _c, _d;
      if (((_b2 = (_a2 = to.matched[0]) == null ? void 0 : _a2.components) == null ? void 0 : _b2.default) === ((_d = (_c = from.matched[0]) == null ? void 0 : _c.components) == null ? void 0 : _d.default)) {
        syncCurrentRoute();
      }
    });
    const route = {};
    for (const key in _route.value) {
      Object.defineProperty(route, key, {
        get: () => _route.value[key]
      });
    }
    nuxtApp._route = shallowReactive(route);
    nuxtApp._middleware = nuxtApp._middleware || {
      global: [],
      named: {}
    };
    useError();
    try {
      if (true) {
        ;
        [__temp, __restore] = executeAsync(() => router.push(initialURL)), await __temp, __restore();
        ;
      }
      ;
      [__temp, __restore] = executeAsync(() => router.isReady()), await __temp, __restore();
      ;
    } catch (error2) {
      [__temp, __restore] = executeAsync(() => nuxtApp.runWithContext(() => showError(error2))), await __temp, __restore();
    }
    const initialLayout = nuxtApp.payload.state._layout;
    router.beforeEach(async (to, from) => {
      var _a2, _b2;
      to.meta = reactive(to.meta);
      if (nuxtApp.isHydrating && initialLayout && !isReadonly(to.meta.layout)) {
        to.meta.layout = initialLayout;
      }
      nuxtApp._processingMiddleware = true;
      if (!((_a2 = nuxtApp.ssrContext) == null ? void 0 : _a2.islandContext)) {
        const middlewareEntries = /* @__PURE__ */ new Set([...globalMiddleware, ...nuxtApp._middleware.global]);
        for (const component of to.matched) {
          const componentMiddleware = component.meta.middleware;
          if (!componentMiddleware) {
            continue;
          }
          if (Array.isArray(componentMiddleware)) {
            for (const entry2 of componentMiddleware) {
              middlewareEntries.add(entry2);
            }
          } else {
            middlewareEntries.add(componentMiddleware);
          }
        }
        for (const entry2 of middlewareEntries) {
          const middleware = typeof entry2 === "string" ? nuxtApp._middleware.named[entry2] || await ((_b2 = namedMiddleware[entry2]) == null ? void 0 : _b2.call(namedMiddleware).then((r) => r.default || r)) : entry2;
          if (!middleware) {
            throw new Error(`Unknown route middleware: '${entry2}'.`);
          }
          const result = await nuxtApp.runWithContext(() => middleware(to, from));
          {
            if (result === false || result instanceof Error) {
              const error2 = result || createError$1({
                statusCode: 404,
                statusMessage: `Page Not Found: ${initialURL}`
              });
              await nuxtApp.runWithContext(() => showError(error2));
              return false;
            }
          }
          if (result === true) {
            continue;
          }
          if (result || result === false) {
            return result;
          }
        }
      }
    });
    router.onError(() => {
      delete nuxtApp._processingMiddleware;
    });
    router.afterEach(async (to, _from, failure) => {
      var _a2;
      delete nuxtApp._processingMiddleware;
      if ((failure == null ? void 0 : failure.type) === 4) {
        return;
      }
      if (to.matched.length === 0 && !((_a2 = nuxtApp.ssrContext) == null ? void 0 : _a2.islandContext)) {
        await nuxtApp.runWithContext(() => showError(createError$1({
          statusCode: 404,
          fatal: false,
          statusMessage: `Page not found: ${to.fullPath}`
        })));
      } else if (to.redirectedFrom && to.fullPath !== initialURL) {
        await nuxtApp.runWithContext(() => navigateTo(to.fullPath || "/"));
      }
    });
    nuxtApp.hooks.hookOnce("app:created", async () => {
      try {
        await router.replace({
          ...router.resolve(initialURL),
          name: void 0,
          // #4920, #4982
          force: true
        });
        router.options.scrollBehavior = routerOptions.scrollBehavior;
      } catch (error2) {
        await nuxtApp.runWithContext(() => showError(error2));
      }
    });
    return { provide: { router } };
  }
});
const isVue2 = false;
/*!
 * pinia v2.1.7
 * (c) 2023 Eduardo San Martin Morote
 * @license MIT
 */
let activePinia;
const setActivePinia = (pinia) => activePinia = pinia;
const piniaSymbol = (
  /* istanbul ignore next */
  Symbol()
);
function isPlainObject(o) {
  return o && typeof o === "object" && Object.prototype.toString.call(o) === "[object Object]" && typeof o.toJSON !== "function";
}
var MutationType;
(function(MutationType2) {
  MutationType2["direct"] = "direct";
  MutationType2["patchObject"] = "patch object";
  MutationType2["patchFunction"] = "patch function";
})(MutationType || (MutationType = {}));
function createPinia() {
  const scope = effectScope(true);
  const state = scope.run(() => ref({}));
  let _p = [];
  let toBeInstalled = [];
  const pinia = markRaw({
    install(app) {
      setActivePinia(pinia);
      {
        pinia._a = app;
        app.provide(piniaSymbol, pinia);
        app.config.globalProperties.$pinia = pinia;
        toBeInstalled.forEach((plugin2) => _p.push(plugin2));
        toBeInstalled = [];
      }
    },
    use(plugin2) {
      if (!this._a && !isVue2) {
        toBeInstalled.push(plugin2);
      } else {
        _p.push(plugin2);
      }
      return this;
    },
    _p,
    // it's actually undefined here
    // @ts-expect-error
    _a: null,
    _e: scope,
    _s: /* @__PURE__ */ new Map(),
    state
  });
  return pinia;
}
const noop = () => {
};
function addSubscription(subscriptions, callback, detached, onCleanup = noop) {
  subscriptions.push(callback);
  const removeSubscription = () => {
    const idx = subscriptions.indexOf(callback);
    if (idx > -1) {
      subscriptions.splice(idx, 1);
      onCleanup();
    }
  };
  if (!detached && getCurrentScope()) {
    onScopeDispose(removeSubscription);
  }
  return removeSubscription;
}
function triggerSubscriptions(subscriptions, ...args) {
  subscriptions.slice().forEach((callback) => {
    callback(...args);
  });
}
const fallbackRunWithContext = (fn) => fn();
function mergeReactiveObjects(target, patchToApply) {
  if (target instanceof Map && patchToApply instanceof Map) {
    patchToApply.forEach((value, key) => target.set(key, value));
  }
  if (target instanceof Set && patchToApply instanceof Set) {
    patchToApply.forEach(target.add, target);
  }
  for (const key in patchToApply) {
    if (!patchToApply.hasOwnProperty(key))
      continue;
    const subPatch = patchToApply[key];
    const targetValue = target[key];
    if (isPlainObject(targetValue) && isPlainObject(subPatch) && target.hasOwnProperty(key) && !isRef(subPatch) && !isReactive(subPatch)) {
      target[key] = mergeReactiveObjects(targetValue, subPatch);
    } else {
      target[key] = subPatch;
    }
  }
  return target;
}
const skipHydrateSymbol = (
  /* istanbul ignore next */
  Symbol()
);
function shouldHydrate(obj) {
  return !isPlainObject(obj) || !obj.hasOwnProperty(skipHydrateSymbol);
}
const { assign } = Object;
function isComputed(o) {
  return !!(isRef(o) && o.effect);
}
function createOptionsStore(id, options, pinia, hot) {
  const { state, actions, getters } = options;
  const initialState = pinia.state.value[id];
  let store;
  function setup() {
    if (!initialState && (!("production" !== "production") )) {
      {
        pinia.state.value[id] = state ? state() : {};
      }
    }
    const localState = toRefs(pinia.state.value[id]);
    return assign(localState, actions, Object.keys(getters || {}).reduce((computedGetters, name) => {
      computedGetters[name] = markRaw(computed(() => {
        setActivePinia(pinia);
        const store2 = pinia._s.get(id);
        return getters[name].call(store2, store2);
      }));
      return computedGetters;
    }, {}));
  }
  store = createSetupStore(id, setup, options, pinia, hot, true);
  return store;
}
function createSetupStore($id, setup, options = {}, pinia, hot, isOptionsStore) {
  let scope;
  const optionsForPlugin = assign({ actions: {} }, options);
  const $subscribeOptions = {
    deep: true
    // flush: 'post',
  };
  let isListening;
  let isSyncListening;
  let subscriptions = [];
  let actionSubscriptions = [];
  let debuggerEvents;
  const initialState = pinia.state.value[$id];
  if (!isOptionsStore && !initialState && (!("production" !== "production") )) {
    {
      pinia.state.value[$id] = {};
    }
  }
  ref({});
  let activeListener;
  function $patch(partialStateOrMutator) {
    let subscriptionMutation;
    isListening = isSyncListening = false;
    if (typeof partialStateOrMutator === "function") {
      partialStateOrMutator(pinia.state.value[$id]);
      subscriptionMutation = {
        type: MutationType.patchFunction,
        storeId: $id,
        events: debuggerEvents
      };
    } else {
      mergeReactiveObjects(pinia.state.value[$id], partialStateOrMutator);
      subscriptionMutation = {
        type: MutationType.patchObject,
        payload: partialStateOrMutator,
        storeId: $id,
        events: debuggerEvents
      };
    }
    const myListenerId = activeListener = Symbol();
    nextTick().then(() => {
      if (activeListener === myListenerId) {
        isListening = true;
      }
    });
    isSyncListening = true;
    triggerSubscriptions(subscriptions, subscriptionMutation, pinia.state.value[$id]);
  }
  const $reset = isOptionsStore ? function $reset2() {
    const { state } = options;
    const newState = state ? state() : {};
    this.$patch(($state) => {
      assign($state, newState);
    });
  } : (
    /* istanbul ignore next */
    noop
  );
  function $dispose() {
    scope.stop();
    subscriptions = [];
    actionSubscriptions = [];
    pinia._s.delete($id);
  }
  function wrapAction(name, action) {
    return function() {
      setActivePinia(pinia);
      const args = Array.from(arguments);
      const afterCallbackList = [];
      const onErrorCallbackList = [];
      function after(callback) {
        afterCallbackList.push(callback);
      }
      function onError(callback) {
        onErrorCallbackList.push(callback);
      }
      triggerSubscriptions(actionSubscriptions, {
        args,
        name,
        store,
        after,
        onError
      });
      let ret;
      try {
        ret = action.apply(this && this.$id === $id ? this : store, args);
      } catch (error2) {
        triggerSubscriptions(onErrorCallbackList, error2);
        throw error2;
      }
      if (ret instanceof Promise) {
        return ret.then((value) => {
          triggerSubscriptions(afterCallbackList, value);
          return value;
        }).catch((error2) => {
          triggerSubscriptions(onErrorCallbackList, error2);
          return Promise.reject(error2);
        });
      }
      triggerSubscriptions(afterCallbackList, ret);
      return ret;
    };
  }
  const partialStore = {
    _p: pinia,
    // _s: scope,
    $id,
    $onAction: addSubscription.bind(null, actionSubscriptions),
    $patch,
    $reset,
    $subscribe(callback, options2 = {}) {
      const removeSubscription = addSubscription(subscriptions, callback, options2.detached, () => stopWatcher());
      const stopWatcher = scope.run(() => watch(() => pinia.state.value[$id], (state) => {
        if (options2.flush === "sync" ? isSyncListening : isListening) {
          callback({
            storeId: $id,
            type: MutationType.direct,
            events: debuggerEvents
          }, state);
        }
      }, assign({}, $subscribeOptions, options2)));
      return removeSubscription;
    },
    $dispose
  };
  const store = reactive(partialStore);
  pinia._s.set($id, store);
  const runWithContext = pinia._a && pinia._a.runWithContext || fallbackRunWithContext;
  const setupStore = runWithContext(() => pinia._e.run(() => (scope = effectScope()).run(setup)));
  for (const key in setupStore) {
    const prop = setupStore[key];
    if (isRef(prop) && !isComputed(prop) || isReactive(prop)) {
      if (!isOptionsStore) {
        if (initialState && shouldHydrate(prop)) {
          if (isRef(prop)) {
            prop.value = initialState[key];
          } else {
            mergeReactiveObjects(prop, initialState[key]);
          }
        }
        {
          pinia.state.value[$id][key] = prop;
        }
      }
    } else if (typeof prop === "function") {
      const actionValue = wrapAction(key, prop);
      {
        setupStore[key] = actionValue;
      }
      optionsForPlugin.actions[key] = prop;
    } else ;
  }
  {
    assign(store, setupStore);
    assign(toRaw(store), setupStore);
  }
  Object.defineProperty(store, "$state", {
    get: () => pinia.state.value[$id],
    set: (state) => {
      $patch(($state) => {
        assign($state, state);
      });
    }
  });
  pinia._p.forEach((extender) => {
    {
      assign(store, scope.run(() => extender({
        store,
        app: pinia._a,
        pinia,
        options: optionsForPlugin
      })));
    }
  });
  if (initialState && isOptionsStore && options.hydrate) {
    options.hydrate(store.$state, initialState);
  }
  isListening = true;
  isSyncListening = true;
  return store;
}
function defineStore(idOrOptions, setup, setupOptions) {
  let id;
  let options;
  const isSetupStore = typeof setup === "function";
  if (typeof idOrOptions === "string") {
    id = idOrOptions;
    options = isSetupStore ? setupOptions : setup;
  } else {
    options = idOrOptions;
    id = idOrOptions.id;
  }
  function useStore(pinia, hot) {
    const hasContext = hasInjectionContext();
    pinia = // in test mode, ignore the argument provided as we can always retrieve a
    // pinia instance with getActivePinia()
    (pinia) || (hasContext ? inject(piniaSymbol, null) : null);
    if (pinia)
      setActivePinia(pinia);
    pinia = activePinia;
    if (!pinia._s.has(id)) {
      if (isSetupStore) {
        createSetupStore(id, setup, options, pinia);
      } else {
        createOptionsStore(id, options, pinia);
      }
    }
    const store = pinia._s.get(id);
    return store;
  }
  useStore.$id = id;
  return useStore;
}
function storeToRefs(store) {
  {
    store = toRaw(store);
    const refs = {};
    for (const key in store) {
      const value = store[key];
      if (isRef(value) || isReactive(value)) {
        refs[key] = // ---
        toRef(store, key);
      }
    }
    return refs;
  }
}
const plugin = /* @__PURE__ */ defineNuxtPlugin((nuxtApp) => {
  const pinia = createPinia();
  nuxtApp.vueApp.use(pinia);
  setActivePinia(pinia);
  {
    nuxtApp.payload.pinia = pinia.state.value;
  }
  return {
    provide: {
      pinia
    }
  };
});
const reducers = {
  NuxtError: (data) => isNuxtError(data) && data.toJSON(),
  EmptyShallowRef: (data) => isRef(data) && isShallow(data) && !data.value && (typeof data.value === "bigint" ? "0n" : JSON.stringify(data.value) || "_"),
  EmptyRef: (data) => isRef(data) && !data.value && (typeof data.value === "bigint" ? "0n" : JSON.stringify(data.value) || "_"),
  ShallowRef: (data) => isRef(data) && isShallow(data) && data.value,
  ShallowReactive: (data) => isReactive(data) && isShallow(data) && toRaw(data),
  Ref: (data) => isRef(data) && data.value,
  Reactive: (data) => isReactive(data) && toRaw(data)
};
const revive_payload_server_eJ33V7gbc6 = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:revive-payload:server",
  setup() {
    for (const reducer in reducers) {
      definePayloadReducer(reducer, reducers[reducer]);
    }
  }
});
const components_plugin_KR1HBZs4kY = /* @__PURE__ */ defineNuxtPlugin({
  name: "nuxt:global-components"
});
dayjs.extend(updateLocale);
dayjs.extend(relativeTime);
dayjs.extend(utc);
const plugin_8SbxDRbG6Y = /* @__PURE__ */ defineNuxtPlugin(async (nuxtApp) => nuxtApp.provide("dayjs", dayjs));
var Methods = /* @__PURE__ */ ((Methods2) => {
  Methods2["Init"] = "init";
  Methods2["AddFileExtension"] = "addFileExtension";
  Methods2["ExtLink"] = "extLink";
  Methods2["File"] = "file";
  Methods2["FirstPartyParams"] = "firstPartyParams";
  Methods2["FirstPartyParamsHashed"] = "firstPartyParamsHashed";
  Methods2["GetClientID"] = "getClientID";
  Methods2["Hit"] = "hit";
  Methods2["NotBounce"] = "notBounce";
  Methods2["Params"] = "params";
  Methods2["ReachGoal"] = "reachGoal";
  Methods2["SetUserID"] = "setUserID";
  Methods2["UserParams"] = "userParams";
  return Methods2;
})(Methods || {});
class YandexMetrika {
  constructor(id) {
    __privateAdd(this, _call);
    __privateAdd(this, _id, void 0);
    __privateAdd(this, _debug, false);
    __privateAdd(this, _verification, null);
    __privateSet(this, _id, id);
  }
  static src(cdn = false) {
    return cdn ? "https://cdn.jsdelivr.net/npm/yandex-metrica-watch/tag.js" : "https://mc.yandex.ru/metrika/tag.js";
  }
  get debug() {
    return __privateGet(this, _debug);
  }
  set debug(value) {
    __privateSet(this, _debug, value);
  }
  get verification() {
    return __privateGet(this, _verification);
  }
  set verification(value) {
    __privateSet(this, _verification, value);
  }
  get id() {
    return __privateGet(this, _id);
  }
  init(options = {}) {
    __privateMethod(this, _call, call_fn).call(this, Methods.Init, ...arguments);
  }
  addFileExtension(extensions) {
    __privateMethod(this, _call, call_fn).call(this, Methods.AddFileExtension, ...arguments);
  }
  extLink(url, options = {}) {
    __privateMethod(this, _call, call_fn).call(this, Methods.ExtLink, ...arguments);
  }
  file(url, options) {
    __privateMethod(this, _call, call_fn).call(this, Methods.File, ...arguments);
  }
  firstPartyParams(people) {
    __privateMethod(this, _call, call_fn).call(this, Methods.FirstPartyParams, ...arguments);
  }
  firstPartyParamsHashed(people) {
    __privateMethod(this, _call, call_fn).call(this, Methods.FirstPartyParamsHashed, ...arguments);
  }
  getClientID(cb) {
    __privateMethod(this, _call, call_fn).call(this, Methods.GetClientID, ...arguments);
  }
  hit(url = "", options) {
    __privateMethod(this, _call, call_fn).call(this, Methods.Hit, ...arguments);
  }
  notBounce(options = {}) {
    __privateMethod(this, _call, call_fn).call(this, Methods.NotBounce, ...arguments);
  }
  params(params = {}) {
    __privateMethod(this, _call, call_fn).call(this, Methods.Params, ...arguments);
  }
  reachGoal(target, params, callback, ctx) {
    __privateMethod(this, _call, call_fn).call(this, Methods.ReachGoal, ...arguments);
  }
  setUserID(userId) {
    __privateMethod(this, _call, call_fn).call(this, Methods.SetUserID, ...arguments);
  }
  userParams(params = {}) {
    __privateMethod(this, _call, call_fn).call(this, Methods.UserParams, ...arguments);
  }
}
_id = new WeakMap();
_debug = new WeakMap();
_verification = new WeakMap();
_call = new WeakSet();
call_fn = function(type, ...args) {
  if (__privateGet(this, _debug)) {
    console.log(`${chalk.bgGreen(chalk.black("[yandex-metrika]"))} ${chalk.blue(type)}`, ...args);
  }
};
const plugin_uP3bIRSu5e = /* @__PURE__ */ defineNuxtPlugin({
  parallel: true,
  setup() {
    const config = (/* @__PURE__ */ useRuntimeConfig()).public.yandexMetrika;
    const { id, cdn = false, delay = 0, debug, verification = null, options = {} } = config;
    {
      useHead({
        noscript: [
          {
            key: "yandex-metrika-noscript",
            innerHTML: `<div><img src="https://mc.yandex.ru/watch/${id}" style="position:absolute; left:-9999px;" alt="" />`
          }
        ]
      });
      if (delay && delay > 0)
        ;
      else {
        useHead({
          script: [
            {
              key: "yandex-metrika",
              innerHTML: `(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)}; m[i].l=1*new Date(); for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }} k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)}) (window, document, "script", "${YandexMetrika.src(
                cdn
              )}", "ym");`
            }
          ]
        });
      }
    }
    useHead({
      script: [
        {
          key: "yandex-metrika-init",
          innerHTML: `ym("${id}","${Methods.Init}", ${JSON.stringify(options)});`
        }
      ]
    });
    const yandexMetrika = new YandexMetrika(id);
    yandexMetrika.debug = debug;
    yandexMetrika.verification = verification;
    return {
      provide: {
        yandexMetrika: new Proxy(yandexMetrika, {
          get(target, name) {
            if (typeof target[name] === "function") {
              return target[name].bind(target);
            }
            return target[name];
          }
        })
      }
    };
  }
});
function usePersistedstateCookies(cookieOptions) {
  return {
    getItem: (key) => {
      return useCookie(key, {
        ...cookieOptions,
        encode: encodeURIComponent,
        decode: decodeURIComponent
      }).value;
    },
    setItem: (key, value) => {
      useCookie(key, {
        ...cookieOptions,
        encode: encodeURIComponent,
        decode: decodeURIComponent
      }).value = value;
    }
  };
}
function usePersistedstateLocalStorage() {
  return {
    getItem: (key) => {
      return !(/* @__PURE__ */ useNuxtApp()).ssrContext ? localStorage.getItem(key) : null;
    },
    setItem: (key, value) => {
      if (!(/* @__PURE__ */ useNuxtApp()).ssrContext)
        localStorage.setItem(key, value);
    }
  };
}
function usePersistedstateSessionStorage() {
  return {
    getItem: (key) => {
      return !(/* @__PURE__ */ useNuxtApp()).ssrContext ? sessionStorage.getItem(key) : null;
    },
    setItem: (key, value) => {
      if (!(/* @__PURE__ */ useNuxtApp()).ssrContext)
        sessionStorage.setItem(key, value);
    }
  };
}
const persistedState = {
  localStorage: usePersistedstateLocalStorage(),
  sessionStorage: usePersistedstateSessionStorage(),
  cookies: usePersistedstateCookies(),
  cookiesWithOptions: usePersistedstateCookies
};
const plugin_1UohGbtF8v = /* @__PURE__ */ defineNuxtPlugin((nuxtApp) => {
  const {
    cookieOptions,
    debug,
    storage
  } = (/* @__PURE__ */ useRuntimeConfig()).public.persistedState;
  const pinia = nuxtApp.$pinia;
  pinia.use(createPersistedState({
    storage: storage === "cookies" ? persistedState.cookiesWithOptions(cookieOptions) : persistedState[storage],
    debug
  }));
});
const vMaska_TdwQJsEXN7 = /* @__PURE__ */ defineNuxtPlugin((nuxtApp) => {
  nuxtApp.vueApp.directive("maska", vMaska);
  return;
});
const plugins = [
  unhead_KgADcZ0jPj,
  plugin$1,
  plugin,
  revive_payload_server_eJ33V7gbc6,
  components_plugin_KR1HBZs4kY,
  plugin_8SbxDRbG6Y,
  plugin_uP3bIRSu5e,
  plugin_1UohGbtF8v,
  vMaska_TdwQJsEXN7
];
const _sfc_main$F = {
  __name: "AppMain",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<main${ssrRenderAttrs(mergeProps({ class: "main" }, _attrs))}><div class="container">`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div></main>`);
    };
  }
};
const _sfc_setup$F = _sfc_main$F.setup;
_sfc_main$F.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppMain/AppMain.vue");
  return _sfc_setup$F ? _sfc_setup$F(props, ctx) : void 0;
};
const AppMain = _sfc_main$F;
const interpolatePath = (route, match) => {
  return match.path.replace(/(:\w+)\([^)]+\)/g, "$1").replace(/(:\w+)[?+*]/g, "$1").replace(/:\w+/g, (r) => {
    var _a;
    return ((_a = route.params[r.slice(1)]) == null ? void 0 : _a.toString()) || "";
  });
};
const generateRouteKey = (routeProps, override) => {
  const matchedRoute = routeProps.route.matched.find((m) => {
    var _a;
    return ((_a = m.components) == null ? void 0 : _a.default) === routeProps.Component.type;
  });
  const source = override ?? (matchedRoute == null ? void 0 : matchedRoute.meta.key) ?? (matchedRoute && interpolatePath(routeProps.route, matchedRoute));
  return typeof source === "function" ? source(routeProps.route) : source;
};
const wrapInKeepAlive = (props, children) => {
  return { default: () => children };
};
const RouteProvider = /* @__PURE__ */ defineComponent({
  name: "RouteProvider",
  props: {
    vnode: {
      type: Object,
      required: true
    },
    route: {
      type: Object,
      required: true
    },
    vnodeRef: Object,
    renderKey: String,
    trackRootNodes: Boolean
  },
  setup(props) {
    const previousKey = props.renderKey;
    const previousRoute = props.route;
    const route = {};
    for (const key in props.route) {
      Object.defineProperty(route, key, {
        get: () => previousKey === props.renderKey ? props.route[key] : previousRoute[key]
      });
    }
    provide(PageRouteSymbol, shallowReactive(route));
    return () => {
      return h(props.vnode, { ref: props.vnodeRef });
    };
  }
});
const _wrapIf = (component, props, slots) => {
  props = props === true ? {} : props;
  return { default: () => {
    var _a;
    return props ? h(component, props, slots) : (_a = slots.default) == null ? void 0 : _a.call(slots);
  } };
};
const __nuxt_component_1 = /* @__PURE__ */ defineComponent({
  name: "NuxtPage",
  inheritAttrs: false,
  props: {
    name: {
      type: String
    },
    transition: {
      type: [Boolean, Object],
      default: void 0
    },
    keepalive: {
      type: [Boolean, Object],
      default: void 0
    },
    route: {
      type: Object
    },
    pageKey: {
      type: [Function, String],
      default: null
    }
  },
  setup(props, { attrs, expose }) {
    const nuxtApp = /* @__PURE__ */ useNuxtApp();
    const pageRef = ref();
    inject(PageRouteSymbol, null);
    expose({ pageRef });
    inject(LayoutMetaSymbol, null);
    let vnode;
    const done = nuxtApp.deferHydration();
    return () => {
      return h(RouterView, { name: props.name, route: props.route, ...attrs }, {
        default: (routeProps) => {
          if (!routeProps.Component) {
            return;
          }
          const key = generateRouteKey(routeProps, props.pageKey);
          const hasTransition = !!(props.transition ?? routeProps.route.meta.pageTransition ?? appPageTransition);
          const transitionProps = hasTransition && _mergeTransitionProps([
            props.transition,
            routeProps.route.meta.pageTransition,
            appPageTransition,
            { onAfterLeave: () => {
              nuxtApp.callHook("page:transition:finish", routeProps.Component);
            } }
          ].filter(Boolean));
          vnode = _wrapIf(
            Transition,
            hasTransition && transitionProps,
            wrapInKeepAlive(
              props.keepalive ?? routeProps.route.meta.keepalive ?? appKeepalive,
              h(Suspense, {
                suspensible: true,
                onPending: () => nuxtApp.callHook("page:start", routeProps.Component),
                onResolve: () => {
                  nextTick(() => nuxtApp.callHook("page:finish", routeProps.Component).finally(done));
                }
              }, {
                // @ts-expect-error seems to be an issue in vue types
                default: () => h(RouteProvider, {
                  key,
                  vnode: routeProps.Component,
                  route: routeProps.route,
                  renderKey: key,
                  trackRootNodes: hasTransition,
                  vnodeRef: pageRef
                })
              })
            )
          ).default();
          return vnode;
        }
      });
    };
  }
});
function _toArray(val) {
  return Array.isArray(val) ? val : val ? [val] : [];
}
function _mergeTransitionProps(routeProps) {
  const _props = routeProps.map((prop) => ({
    ...prop,
    onAfterLeave: _toArray(prop.onAfterLeave)
  }));
  return defu(..._props);
}
const _sfc_main$E = {
  __name: "Logo",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__logo" }, _attrs))}><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="110px" height="19px" viewBox="0 0 110 19" version="1.1"><title>Group 4</title><g id="Логистика-2" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="Собираем-штрафы-по-базам-Desktop-HD-Copy-9" transform="translate(-142, -21)"><g id="Group-4" transform="translate(142, 21)"><path d="M108.901739,0.220862719 L107.519448,3.56971232 L99.8099684,3.56971232 C99.3539548,3.56971232 98.9976942,3.70271627 98.7411866,3.96872418 C98.484679,4.23473209 98.3564252,4.60049297 98.3564252,5.06600682 C98.3564252,5.71202603 98.8646903,6.215541 99.8812205,6.57655174 C100.54624,6.82355909 101.600772,7.04206559 103.044815,7.23207124 C104.488858,7.42207689 105.738145,7.70708536 106.792676,8.08709667 C108.968241,9.06562577 110.037023,10.6759237 109.999021,12.9179903 C109.970521,14.5140378 109.372003,15.7205737 108.203468,16.537598 C107.186938,17.2501192 105.966151,17.6063798 104.541109,17.6063798 L95.4157577,17.6038627 L93.8717577,14.2538627 L104.541109,14.2575302 C104.915692,14.2575302 105.223966,14.1877322 105.465933,14.0481362 L105.58139,13.9725217 C105.856898,13.7160141 105.989902,13.3502532 105.980402,12.8752391 C105.989902,12.0772153 105.619391,11.5071984 104.868869,11.1651882 C104.165848,10.8516789 102.956937,10.5547951 101.242136,10.2745367 C99.527335,9.99427839 98.1426688,9.65464329 97.0881375,9.25563142 C95.2545829,8.38160542 94.3378057,6.98506389 94.3378057,5.06600682 C94.3378057,3.59346302 94.9030725,2.40117756 96.0336061,1.48915044 C97.0691369,0.643625293 98.3279243,0.220862719 99.8099684,0.220862719 L108.901739,0.220862719 Z" id="Combined-Shape" fill="#FD8301" fill-rule="nonzero"></path><polygon id="Path" fill="#FD8301" fill-rule="nonzero" points="86.7423298 17.6063798 85.2887865 14.1720276 79.7738725 14.1720276 83.1227221 6.14903903 88.0676192 17.6063798 92.5137514 17.6063798 84.5762653 0.235113143 81.6834293 0.235113143 73.7459432 17.6063798"></polygon><path d="M63.9986534,3.66946528 L70.3258415,3.66946528 C70.6298506,3.66946528 70.914859,3.80246924 71.180867,4.06847715 C71.5703785,4.44848845 71.7651343,5.17526007 71.7651343,6.24879199 C71.7651343,7.31282364 71.5703785,8.03484511 71.180867,8.41485641 C70.914859,8.68086432 70.6298506,8.81386828 70.3258415,8.81386828 L70.3258415,8.81386828 L65.4236957,8.81386828 L63.9986534,12.2624708 L63.9986534,3.66946528 Z M63.9986534,12.2624708 L70.3258415,12.2624708 C71.988391,12.2624708 73.3896826,11.6924539 74.5297166,10.55242 C75.5462468,9.53588975 76.0545119,8.10134709 76.0545119,6.24879199 C76.0545119,4.3962369 75.5462468,2.95694409 74.5297166,1.93091358 C73.3896826,0.800379955 71.988391,0.235113143 70.3258415,0.235113143 L59.7092758,0.235113143 L59.7092758,17.6063798 L63.9986534,17.6063798 L63.9986534,12.2624708 Z" id="pas" fill="#FD8301" fill-rule="nonzero"></path><g id="Group-3"><path d="M8.40775005,0.235113143 L18.3649243,0.250727311 C18.1214858,0.462325543 17.8841623,0.684107546 17.6529326,0.915337248 C16.8051537,1.76311623 16.1720797,2.66313028 15.7189106,3.66498163 L8.40775005,3.66946528 C7.36271897,3.66946528 6.44594171,4.06372701 5.65741825,4.85225046 C4.74539113,5.76427758 4.28937757,7.07531657 4.28937757,8.78536743 C4.28937757,10.6759237 4.74539113,12.0772153 5.65741825,12.9892425 C6.44594171,13.7777659 7.36271897,14.1720276 8.40775005,14.1720276 L14.7428899,14.1505635 C14.8308882,15.3986613 15.0704467,16.5725565 15.6400487,17.6291668 L8.40775005,17.6063798 C6.00417857,17.6063798 3.97586824,16.7751051 2.32281908,15.1125556 C0.774273027,13.5735098 0,11.4644471 0,8.78536743 C0,6.28679312 0.774273027,4.26798308 2.32281908,2.72893731 C3.97586824,1.06638787 6.00417857,0.235113143 8.40775005,0.235113143 Z" id="compss" fill="#DEDEDE" fill-rule="nonzero"></path><polygon id="Path" fill="#FD8301" fill-rule="nonzero" points="57.2724533 17.6063798 57.2724533 0.235113143 54.2798643 0.235113143 45.7866117 14.0580242 47.6534172 17.0648637 53.2680842 8.15834878 53.2680842 17.6063798"></polygon><path d="M27.774076,18.0338925 C30.1776475,18.0338925 32.2059578,17.2026178 33.859007,15.5400683 C35.407553,14.0010225 36.1818261,11.796957 36.1818261,8.92787167 C36.1818261,6.23929171 36.8610963,2.72893731 35.3125502,1.18989154 C33.6595011,-0.472657906 30.1776475,0.092608905 27.774076,0.092608905 L25.7505158,0.092608905 C23.3469444,0.092608905 21.318634,0.923883628 19.6655849,2.58643307 C18.1170388,4.12547884 17.3427658,6.23929171 17.3427658,8.92787167 C17.3427658,11.796957 16.9010026,15.6113204 18.4400484,17.1503662 C20.1025979,18.8129157 23.3469444,18.0338925 25.7505158,18.0338925 L27.774076,18.0338925 Z" id="Path" fill="#DEDEDE" fill-rule="nonzero"></path><polygon id="Path" fill="#DEDEDE" fill-rule="nonzero" points="42.0387503 17.6063798 42.0387503 8.15834878 45.1168418 12.9892425 46.9836473 9.99665346 41.0269702 0.235113143 38.0343812 0.235113143 38.0343812 17.6063798"></polygon><g id="Group-2" transform="translate(26.9371, 9.1534) rotate(-315) translate(-26.9371, -9.1534)translate(23.5372, -0)"><polygon id="Triangle" fill="#C74923" points="3.53058653 1.76874373e-13 6.79964813 9.15337248 2.34385583e-13 9.15337248"></polygon><polygon id="Triangle" fill="#1353A2" transform="translate(3.3998, 13.7301) scale(1, -1) translate(-3.3998, -13.7301)" points="3.53058653 9.15337248 6.79964813 18.306745 8.54585248e-14 18.306745"></polygon><circle id="Oval" fill="#FFFFFF" cx="3.39982407" cy="9.15337248" r="1"></circle></g></g></g></g></g></svg></figure>`);
    };
  }
};
const _sfc_setup$E = _sfc_main$E.setup;
_sfc_main$E.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Logo/Logo.vue");
  return _sfc_setup$E ? _sfc_setup$E(props, ctx) : void 0;
};
const IconLogo = _sfc_main$E;
const _sfc_main$D = {
  __name: "AppButton",
  __ssrInlineRender: true,
  props: {
    disabledOption: {
      default: false,
      type: Boolean
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<button${ssrRenderAttrs(mergeProps({
        disabled: props.disabledOption
      }, _attrs))}><span>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</span></button>`);
    };
  }
};
const _sfc_setup$D = _sfc_main$D.setup;
_sfc_main$D.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppButton/AppButton.vue");
  return _sfc_setup$D ? _sfc_setup$D(props, ctx) : void 0;
};
const AppButton = _sfc_main$D;
const _sfc_main$C = {
  __name: "Drag",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__draggable" }, _attrs))}><svg width="11px" height="12px" viewBox="0 0 11 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><g id="Журнал" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="Логистика-Copy-39" transform="translate(-270.000000, -316.000000)" fill="#A6B7D4"><g id="Group-2-Copy-7" transform="translate(254.000000, 222.000000)"><g id="Group-Copy-3" transform="translate(1.000000, 78.000000)"><g id="Group-11" transform="translate(15.000000, 15.000000)"><path d="M1,1 L10,1 C10.5522847,1 11,1.44771525 11,2 C11,2.55228475 10.5522847,3 10,3 L1,3 C0.44771525,3 5.11724585e-16,2.55228475 0,2 C-6.76353751e-17,1.44771525 0.44771525,1 1,1 Z M1,6 L10,6 C10.5522847,6 11,6.44771525 11,7 C11,7.55228475 10.5522847,8 10,8 L1,8 C0.44771525,8 5.11724585e-16,7.55228475 0,7 C-6.76353751e-17,6.44771525 0.44771525,6 1,6 Z M1,11 L10,11 C10.5522847,11 11,11.4477153 11,12 C11,12.5522848 10.5522847,13 10,13 L1,13 C0.44771525,13 5.11724585e-16,12.5522848 0,12 C-6.76353751e-17,11.4477153 0.44771525,11 1,11 Z" id="Combined-Shape"></path></g></g></g></g></g></svg></figure>`);
    };
  }
};
const _sfc_setup$C = _sfc_main$C.setup;
_sfc_main$C.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Drag/Drag.vue");
  return _sfc_setup$C ? _sfc_setup$C(props, ctx) : void 0;
};
const IconDrag = _sfc_main$C;
const _sfc_main$B = {
  __name: "Arrow",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__arrow" }, _attrs))}><svg width="6" height="9" viewBox="0 0 6 9" xmlns="http://www.w3.org/2000/svg"><path d="M.915 8.943 5.25 4.675a.344.344 0 0 0 .068-.222.344.344 0 0 0-.068-.222L.915.066a.587.587 0 0 0-.683.102C.027.35-.03.578.06.851l3.688 3.585L.06 8.056c-.114.25-.068.489.137.716.204.228.443.285.717.171z" fill="#000" fill-rule="evenodd"></path></svg></figure>`);
    };
  }
};
const _sfc_setup$B = _sfc_main$B.setup;
_sfc_main$B.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Arrow/Arrow.vue");
  return _sfc_setup$B ? _sfc_setup$B(props, ctx) : void 0;
};
const IconArrow = _sfc_main$B;
const _sfc_main$A = {
  __name: "Settings",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__settings" }, _attrs))}><svg width="15px" height="15px" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>Настройки</title><g id="Журнал" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="Логистика-Copy-37" transform="translate(-1673.000000, -231.000000)" fill="#A6B7D4" fill-rule="nonzero"><g id="Group-Copy" transform="translate(254.000000, 222.000000)"><path d="M1427.27083,9 C1427.68511,9 1428.02084,9.33572998 1428.02084,9.75001121 L1428.02084,10.7021181 C1428.555,10.841881 1429.05942,11.0518244 1429.52452,11.32425 L1430.19725,10.6515195 C1430.49023,10.3585405 1430.96505,10.3585405 1431.25796,10.6514447 L1432.34841,11.7418945 C1432.64131,12.0348735 1432.64131,12.5096937 1432.34841,12.8025979 L1431.67568,13.4753285 C1431.94818,13.9404326 1432.15804,14.4448497 1432.29788,14.9790131 L1433.24991,14.9789384 C1433.66412,14.9789384 1434,15.3147431 1434,15.7289496 L1434,17.2710504 C1434,17.6852569 1433.6642,18.0210616 1433.24999,18.0210616 L1432.29781,18.0211363 C1432.15797,18.5552998 1431.94803,19.0596421 1431.6756,19.5247463 L1432.34841,20.1975515 C1432.64131,20.4904558 1432.64131,20.965276 1432.34841,21.2581802 L1431.25803,22.34863 C1430.96513,22.6415343 1430.49031,22.6415343 1430.1974,22.34863 L1429.52452,21.6758248 C1429.05949,21.9482503 1428.55508,22.158119 1428.02099,22.2979566 L1428.02099,23.2499888 C1428.02099,23.6641953 1427.68526,24 1427.27098,24 L1425.72895,24 C1425.31474,24 1424.97894,23.6641953 1424.97894,23.2499888 L1424.97894,22.2978819 C1424.44485,22.1580442 1423.94051,21.9482503 1423.47548,21.6758248 L1422.80267,22.34863 C1422.50977,22.6415343 1422.03495,22.6415343 1421.74204,22.34863 L1420.65159,21.258255 C1420.35869,20.9653508 1420.35869,20.4904558 1420.65159,20.1975515 L1421.3244,19.524821 C1421.0519,19.0597169 1420.84203,18.5553745 1420.70219,18.0213606 L1419.75001,18.0213606 C1419.3358,18.0213606 1419,17.6856306 1419,17.2713493 L1419,15.7292486 C1419,15.3150421 1419.3358,14.9792374 1419.75001,14.9792374 L1420.70189,14.9792374 C1420.84173,14.4451487 1421.05153,13.9407316 1421.32395,13.4756274 L1420.65115,12.8029716 C1420.35824,12.5100674 1420.35824,12.0351724 1420.65115,11.7422682 L1421.74152,10.6518184 C1422.03443,10.3588394 1422.50932,10.3588394 1422.80222,10.6517437 L1423.47488,11.3243995 C1423.94006,11.0518991 1424.44455,10.841881 1424.97879,10.7020434 L1424.97879,9.75001121 C1424.97879,9.33572998 1425.31459,9 1425.7288,9 Z M1426.50138,13.7478139 C1424.9815,13.7478139 1423.74938,14.979926 1423.74938,16.4998134 C1423.74938,18.0197007 1424.9815,19.2518129 1426.50138,19.2518129 C1428.02127,19.2518129 1429.25338,18.0197007 1429.25338,16.4998134 C1429.25338,14.979926 1428.02127,13.7478139 1426.50138,13.7478139 Z" id="Combined-Shape"></path></g></g></g></svg></figure>`);
    };
  }
};
const _sfc_setup$A = _sfc_main$A.setup;
_sfc_main$A.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Settings/Settings.vue");
  return _sfc_setup$A ? _sfc_setup$A(props, ctx) : void 0;
};
const IconSettings = _sfc_main$A;
const _sfc_main$z = {
  __name: "Triangle",
  __ssrInlineRender: true,
  props: {
    fill: {
      type: String,
      default: "#1253a2"
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__triangle" }, _attrs))}><svg${ssrRenderAttr("fill", props.fill)} width="8" height="5"><polygon points="0,0 8,0 4,5"${ssrRenderAttr("fill", props.fill)}></polygon></svg></figure>`);
    };
  }
};
const _sfc_setup$z = _sfc_main$z.setup;
_sfc_main$z.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Triangle/Triangle.vue");
  return _sfc_setup$z ? _sfc_setup$z(props, ctx) : void 0;
};
const IconTriangle = _sfc_main$z;
const PopupScripts = {
  // Закрытие выпадающего списка
  hideDetails(element) {
    switch (element) {
      case null:
        console.error("Element not found");
        break;
      case void 0:
        console.error("Element not found");
        break;
      case "All":
        let data = document.querySelectorAll(".popup");
        data.forEach((element2) => {
          element2.removeAttribute("open");
          element2.classList.remove("popup_up");
          element2.classList.remove("popup_visible");
          element2.classList.remove("popup_right");
        });
        break;
      default:
        element.removeAttribute("open");
        element.classList.remove("popup_up");
        element.classList.remove("popup_visible");
        element.classList.remove("popup_right");
        break;
    }
  },
  // Установка позиции выплывающего списка
  setDropdownPosition(element) {
    let scrollBlock = null;
    scrollBlock = element.closest(".section__scroll-area");
    if (element != null && !element.classList.contains("popup_visible")) {
      setTimeout(() => {
        let dropdown = element.querySelector(".popup__content");
        let dropdownRect = dropdown.getBoundingClientRect();
        if (scrollBlock != null) {
          let scrollBlockRect = scrollBlock.getBoundingClientRect();
          if (dropdownRect.top + dropdownRect.height >= scrollBlockRect.height + scrollBlockRect.top || dropdownRect.top + dropdownRect.height > window.innerHeight) {
            element.classList.add("popup_up");
          } else {
            element.classList.remove("popup_up");
          }
          if (scrollBlockRect.right < dropdownRect.right) {
            element.classList.add("popup_right");
          } else {
            element.classList.remove("popup_right");
          }
        } else {
          if (dropdownRect.top + dropdownRect.height > window.innerHeight) {
            element.classList.add("popup_up");
          } else {
            element.classList.remove("popup_up");
          }
        }
        element.classList.add("popup_visible");
      }, 10);
    }
  }
};
const _sfc_main$y = {
  __name: "Popup",
  __ssrInlineRender: true,
  props: {
    closeByClick: {
      default: false,
      type: Boolean
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isCanSelect: {
      default: true,
      type: Boolean
    }
  },
  emits: ["clickOutside", "openPopup"],
  setup(__props, { expose: __expose, emit: __emit }) {
    const popupRef = ref(null);
    let mouseDownEvent = ref(null);
    let popupHeight = ref(null);
    const props = __props;
    const emit = __emit;
    const clickOutside = (event) => {
      if (props.isCanSelect) {
        if (mouseDownEvent.value == null || mouseDownEvent.value.target.closest(".popup") == null) {
          emit("clickOutside", event);
          PopupScripts.hideDetails(popupRef.value);
        }
      } else {
        emit("clickOutside", event);
        PopupScripts.hideDetails(popupRef.value);
      }
      mouseDownEvent.value = null;
    };
    __expose({
      popupRef
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<details${ssrRenderAttrs(mergeProps({
        class: ["popup", props.isReadOnly ? "popup_readonly" : ""],
        ref_key: "popupRef",
        ref: popupRef,
        style: `--popupHeight: ${unref(popupHeight)}px`
      }, _attrs, ssrGetDirectiveProps(_ctx, unref(clickOutSide), (event) => clickOutside(event))))}><summary class="popup__summary">`);
      ssrRenderSlot(_ctx.$slots, "summary", {}, null, _push, _parent);
      _push(`</summary><div class="popup__content">`);
      ssrRenderSlot(_ctx.$slots, "content", {}, null, _push, _parent);
      _push(`</div></details>`);
    };
  }
};
const _sfc_setup$y = _sfc_main$y.setup;
_sfc_main$y.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPopup/Popup.vue");
  return _sfc_setup$y ? _sfc_setup$y(props, ctx) : void 0;
};
const AppPopup = _sfc_main$y;
const _sfc_main$x = {
  __name: "Save",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__save" }, _attrs))}><svg width="15px" height="15px" viewBox="0 0 15 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>Применить изменения</title><g id="Страница-таблицы" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="Логистика-Copy-33" transform="translate(-1625.000000, -176.000000)" fill="#A6B7D4" fill-rule="nonzero"><g id="Group-14" transform="translate(254.000000, 167.000000)"><g id="save" transform="translate(1371.000000, 9.000000)"><path d="M13.125,0 C14.1610169,0 15,0.838983051 15,1.875 L15,13.125 C15,14.1610169 14.1610169,15 13.125,15 L1.875,15 C0.838983051,15 0,14.1610169 0,13.125 L0,1.875 C0,0.838983051 0.838983051,0 1.875,0 Z M3.00609756,1.20121951 L2.10365854,1.20121951 C1.60365854,1.20121951 1.20121951,1.6097561 1.20121951,2.10365854 L1.20121951,12.8963415 C1.20121951,13.3963415 1.6097561,13.7987805 2.10365854,13.7987805 L12.8963415,13.7987805 C13.3963415,13.7987805 13.7987805,13.3902439 13.7987805,12.8963415 L13.8109756,2.1097561 C13.8109756,1.6097561 13.402439,1.20731707 12.9085366,1.20731707 L12.0060976,1.20731707 L12.0060976,5.70121951 C12.0060976,6.20121951 11.597561,6.60365854 11.1036585,6.60365854 L3.90853659,6.60365854 C3.40853659,6.60365854 3.00609756,6.19512195 3.00609756,5.70121951 L3.00609756,1.20121951 Z M3.54071213,8.93945312 C3.83348463,8.93945312 4.08221451,9.01011142 4.28690177,9.15142802 C4.49158904,9.29274461 4.59393267,9.51866105 4.59393267,9.82917733 L4.23842321,9.82917733 C4.21941201,9.67962255 4.17885478,9.56492164 4.11675152,9.48507459 C4.00141691,9.33932205 3.80560154,9.26644578 3.52930541,9.26644578 C3.30624066,9.26644578 3.14591286,9.31334008 3.04832203,9.40712867 C2.9507312,9.50091726 2.90193578,9.60991481 2.90193578,9.73412133 C2.90193578,9.87100197 2.95896939,9.97112763 3.07303659,10.0344983 C3.14781398,10.0750555 3.31701367,10.1257521 3.58063566,10.1865879 L3.9722664,10.2759406 C4.16111099,10.3190326 4.30686353,10.3779673 4.40952402,10.4527447 C4.58696189,10.5832883 4.67568083,10.7727666 4.67568083,11.0211796 C4.67568083,11.3304285 4.56319789,11.5515921 4.33823202,11.6846705 C4.11326614,11.817749 3.85186212,11.8842882 3.55401998,11.8842882 C3.2067487,11.8842882 2.93488853,11.7955692 2.73843945,11.6181313 C2.54199038,11.4419609 2.44566696,11.2030535 2.4494692,10.9014091 L2.80497866,10.9014091 C2.81385055,11.0598357 2.85123925,11.1884782 2.91714474,11.2873364 C3.04261867,11.4723788 3.26378231,11.5649 3.58063566,11.5649 C3.72258596,11.5649 3.85186212,11.5446214 3.96846416,11.5040641 C4.19406374,11.4254845 4.30686353,11.2848016 4.30686353,11.0820155 C4.30686353,10.9299259 4.25933553,10.821562 4.16427953,10.7569239 C4.06795611,10.6935533 3.91713391,10.6384208 3.71181294,10.5915265 L3.33349004,10.5059761 C3.08634443,10.4502099 2.91144138,10.3887403 2.8087809,10.3215674 C2.63134302,10.2049654 2.54262408,10.0306961 2.54262408,9.79875941 C2.54262408,9.54781156 2.6294419,9.34185688 2.80307754,9.18089538 C2.97671317,9.01993388 3.22259137,8.93945312 3.54071213,8.93945312 Z M6.3942934,9.00979457 L7.40759041,11.80254 L6.99314623,11.80254 L6.70987934,10.9660471 L5.60532856,10.9660471 L5.30305046,11.80254 L4.91522196,11.80254 L5.96654138,9.00979457 L6.3942934,9.00979457 Z M7.97222308,9.00979457 L8.77449576,11.3880958 L9.56726284,9.00979457 L9.99121262,9.00979457 L8.97221225,11.80254 L8.57107591,11.80254 L7.55397666,9.00979457 L7.97222308,9.00979457 Z M12.4208441,9.00979457 L12.4208441,9.35199619 L10.7535618,9.35199619 L10.7535618,10.1998957 L12.2953702,10.1998957 L12.2953702,10.5230862 L10.7535618,10.5230862 L10.7535618,11.469844 L12.4493609,11.469844 L12.4493609,11.80254 L10.3847445,11.80254 L10.3847445,9.00979457 L12.4208441,9.00979457 Z M6.16425787,9.42423875 L5.71369241,10.6580657 L6.58820765,10.6580657 L6.16425787,9.42423875 Z M11.097561,1.20731707 L3.90243902,1.20731707 L3.90243902,5.25609756 C3.90243902,5.50609756 4.10365854,5.70731707 4.35365854,5.70731707 L10.6463415,5.70731707 C10.8963415,5.70731707 11.097561,5.50609756 11.097561,5.25609756 L11.097561,1.20731707 Z M8.84756098,2.10365854 C9.09756098,2.10365854 9.29878049,2.30487805 9.29878049,2.55487805 L9.29878049,4.35365854 C9.29878049,4.60365854 9.09756098,4.80487805 8.84756098,4.80487805 C8.60365854,4.80487805 8.40243902,4.60365854 8.39634146,4.35365854 L8.39634146,2.55487805 C8.39634146,2.30487805 8.59756098,2.10365854 8.84756098,2.10365854 Z" id="Shape"></path></g></g></g></g></svg></figure>`);
    };
  }
};
const _sfc_setup$x = _sfc_main$x.setup;
_sfc_main$x.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Save/Save.vue");
  return _sfc_setup$x ? _sfc_setup$x(props, ctx) : void 0;
};
const IconSave = _sfc_main$x;
const _sfc_main$w = {
  __name: "PopupOption",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "popup__option popup-option" }, _attrs))}>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div>`);
    };
  }
};
const _sfc_setup$w = _sfc_main$w.setup;
_sfc_main$w.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPopup/PopupOption/PopupOption.vue");
  return _sfc_setup$w ? _sfc_setup$w(props, ctx) : void 0;
};
const PopupOption = _sfc_main$w;
const _sfc_main$v = {
  __name: "Save",
  __ssrInlineRender: true,
  props: {
    is_admin: {
      default: true,
      type: Boolean
    },
    roles: {
      default: [],
      type: Array
    }
  },
  emits: [
    "saveSettings"
  ],
  setup(__props, { emit: __emit }) {
    const popupSavesRef = ref(null);
    let menu = ref({
      saves: {
        isShow: false,
        activeTab: null,
        tabs: [
          {
            tab: "myself",
            key: "myself",
            title: "Применить для себя"
          },
          {
            tab: "roles",
            key: "roles",
            title: "Применить для роли"
          },
          {
            tab: "all",
            key: "all",
            title: "Применить для всех"
          }
        ],
        options: []
      },
      activeTab: null
    });
    const props = __props;
    const emit = __emit;
    const changeSaveTab = (tab) => {
      setTimeout(() => {
        menu.value.saves.activeTab = tab;
        if (tab != null && tab.key != "roles") {
          emit("saveSettings", tab.key ?? tab.id);
          popupSavesRef.value.popupRef.removeAttribute("open");
        }
      }, 10);
    };
    watch(() => props.roles, () => {
      if (!props.is_admin) {
        menu.value.saves.tabs = [
          {
            tab: "myself",
            key: "myself",
            title: "Применить для себя"
          }
        ];
      }
      menu.value.saves.options = props.roles;
    }, {
      deep: true
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppPopup, mergeProps({
        class: "popup_save",
        ref_key: "popupSavesRef",
        ref: popupSavesRef,
        isCanSelect: false,
        closeByClick: false,
        onClickOutside: () => changeSaveTab(null)
      }, _attrs), {
        summary: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(IconSave, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(IconSave)
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (unref(menu).saves.activeTab != null && unref(menu).saves.activeTab.tab == "roles") {
              _push2(`<!--[-->`);
              _push2(ssrRenderComponent(PopupOption, {
                class: "popup-option__sublink popup-option__sublink_back",
                onClick: () => changeSaveTab(null)
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(unref(menu).saves.activeTab.title)} `);
                    _push3(ssrRenderComponent(IconArrow, null, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createTextVNode(toDisplayString(unref(menu).saves.activeTab.title) + " ", 1),
                      createVNode(IconArrow)
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`<!--[-->`);
              ssrRenderList(unref(menu).saves.options, (option) => {
                _push2(ssrRenderComponent(PopupOption, {
                  onClick: () => changeSaveTab(option)
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`${ssrInterpolate(option.label)}`);
                    } else {
                      return [
                        createTextVNode(toDisplayString(option.label), 1)
                      ];
                    }
                  }),
                  _: 2
                }, _parent2, _scopeId));
              });
              _push2(`<!--]--><!--]-->`);
            } else {
              _push2(`<!--[-->`);
              ssrRenderList(unref(menu).saves.tabs, (option) => {
                _push2(ssrRenderComponent(PopupOption, {
                  class: option.tab == "roles" ? "popup-option__sublink" : "",
                  onClick: () => changeSaveTab(option)
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`${ssrInterpolate(option.title)} `);
                      _push3(ssrRenderComponent(IconArrow, {
                        style: option.tab == "roles" ? null : { display: "none" }
                      }, null, _parent3, _scopeId2));
                    } else {
                      return [
                        createTextVNode(toDisplayString(option.title) + " ", 1),
                        withDirectives(createVNode(IconArrow, null, null, 512), [
                          [vShow, option.tab == "roles"]
                        ])
                      ];
                    }
                  }),
                  _: 2
                }, _parent2, _scopeId));
              });
              _push2(`<!--]-->`);
            }
          } else {
            return [
              unref(menu).saves.activeTab != null && unref(menu).saves.activeTab.tab == "roles" ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                createVNode(PopupOption, {
                  class: "popup-option__sublink popup-option__sublink_back",
                  onClick: () => changeSaveTab(null)
                }, {
                  default: withCtx(() => [
                    createTextVNode(toDisplayString(unref(menu).saves.activeTab.title) + " ", 1),
                    createVNode(IconArrow)
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                (openBlock(true), createBlock(Fragment, null, renderList(unref(menu).saves.options, (option) => {
                  return openBlock(), createBlock(PopupOption, {
                    onClick: () => changeSaveTab(option)
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(option.label), 1)
                    ]),
                    _: 2
                  }, 1032, ["onClick"]);
                }), 256))
              ], 64)) : (openBlock(true), createBlock(Fragment, { key: 1 }, renderList(unref(menu).saves.tabs, (option) => {
                return openBlock(), createBlock(PopupOption, {
                  class: option.tab == "roles" ? "popup-option__sublink" : "",
                  onClick: () => changeSaveTab(option)
                }, {
                  default: withCtx(() => [
                    createTextVNode(toDisplayString(option.title) + " ", 1),
                    withDirectives(createVNode(IconArrow, null, null, 512), [
                      [vShow, option.tab == "roles"]
                    ])
                  ]),
                  _: 2
                }, 1032, ["class", "onClick"]);
              }), 256))
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$v = _sfc_main$v.setup;
_sfc_main$v.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPopup/Save/Save.vue");
  return _sfc_setup$v ? _sfc_setup$v(props, ctx) : void 0;
};
const PopupSave = _sfc_main$v;
const _sfc_main$u = {
  __name: "FormItem",
  __ssrInlineRender: true,
  props: {
    required: {
      default: false,
      type: Boolean
    },
    isReadOnly: {
      default: false,
      type: Boolean
    }
  },
  setup(__props, { expose: __expose }) {
    const formItemRef = ref(null);
    const props = __props;
    const setClasses = computed(() => {
      return [
        props.required ? "form-item_required" : "",
        props.isReadOnly ? "form-item_readonly" : ""
      ];
    });
    __expose({
      formItemRef
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: ["form__item", setClasses.value],
        ref_key: "formItemRef",
        ref: formItemRef
      }, _attrs))}>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div>`);
    };
  }
};
const _sfc_setup$u = _sfc_main$u.setup;
_sfc_main$u.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppForm/FormItem/FormItem.vue");
  return _sfc_setup$u ? _sfc_setup$u(props, ctx) : void 0;
};
const FormItem = _sfc_main$u;
const _sfc_main$t = {
  __name: "CheckboxLabel",
  __ssrInlineRender: true,
  props: {
    title: {
      default: null
    },
    isHTML: {
      default: false,
      type: Boolean
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      if (props.isHTML) {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "form-item__title" }, _attrs))}>${props.title ?? ""}</div>`);
      } else {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "form-item__title" }, _attrs))}>${ssrInterpolate(props.title)}</div>`);
      }
    };
  }
};
const _sfc_setup$t = _sfc_main$t.setup;
_sfc_main$t.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Checkbox/CheckboxLabel/CheckboxLabel.vue");
  return _sfc_setup$t ? _sfc_setup$t(props, ctx) : void 0;
};
const CheckboxLabel = _sfc_main$t;
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _sfc_main$s = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<svg${ssrRenderAttrs(mergeProps({
    width: "11px",
    height: "7px",
    viewBox: "0 0 11 7",
    version: "1.1",
    xmlns: "http://www.w3.org/2000/svg",
    "xmlns:xlink": "http://www.w3.org/1999/xlink"
  }, _attrs))}><g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g id="Логистика-Copy-32" transform="translate(-272.000000, -548.000000)" fill="#FFFFFF"><g id="Group-14" transform="translate(254.000000, 95.000000)"><g id="Group-13" transform="translate(1.000000, 78.000000)"><g id="Group-12" transform="translate(0.000000, 355.000000)"><path d="M17.4960082,23.029716 C17.2220704,22.7557783 17.2144741,22.3073185 17.4781961,22.0289453 L17.6341634,21.8643131 C17.8982638,21.5855405 18.3327059,21.579897 18.6021442,21.8493352 L21.4923837,24.7395747 C21.5710554,24.8182464 21.694517,24.8271951 21.7881557,24.7411781 L26.4398514,20.4681088 C26.7212884,20.2095795 27.1519971,20.2383046 27.4010679,20.531329 L27.5483703,20.704626 C27.7977985,20.9980709 27.7734603,21.4470989 27.4898712,21.7114149 L21.995288,26.8325798 C21.7962134,27.0181251 21.4806223,27.0143302 21.2864658,26.8201737 L17.4960082,23.029716 Z" id="Path"></path></g></g></g></g></g></svg>`);
}
const _sfc_setup$s = _sfc_main$s.setup;
_sfc_main$s.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Checkbox/CheckboxField/IconCheckbox.vue");
  return _sfc_setup$s ? _sfc_setup$s(props, ctx) : void 0;
};
const IconCheckbox = /* @__PURE__ */ _export_sfc(_sfc_main$s, [["ssrRender", _sfc_ssrRender]]);
const _sfc_main$r = {
  __name: "CheckboxField",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        value: false
      },
      type: Object
    },
    disabled: {
      default: false,
      type: Boolean
    },
    changeValueLabel: {
      default: true,
      type: Boolean
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[--><input autocomplete="off" type="checkbox"${ssrRenderAttr("value", props.item.value)}${ssrIncludeBooleanAttr(props.item.value) ? " checked" : ""}${ssrIncludeBooleanAttr(props.disabled) ? " disabled" : ""}${ssrRenderAttr("id", `checkbox_${props.item.id}`)}><label${ssrRenderAttr("for", `checkbox_${props.item.id}`)}>`);
      _push(ssrRenderComponent(IconCheckbox, null, null, _parent));
      _push(`</label><!--]-->`);
    };
  }
};
const _sfc_setup$r = _sfc_main$r.setup;
_sfc_main$r.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Checkbox/CheckboxField/CheckboxField.vue");
  return _sfc_setup$r ? _sfc_setup$r(props, ctx) : void 0;
};
const CheckboxField = _sfc_main$r;
const _sfc_main$q = {
  __name: "Checkbox",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        title: "",
        value: false
      },
      type: Object
    },
    disabled: {
      default: false,
      type: Boolean
    },
    changeValueLabel: {
      default: true,
      type: Boolean
    },
    isTextClickable: {
      default: true,
      type: Boolean
    }
  },
  emits: [
    "changeValue"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const changeValue = (event) => {
      if (!props.disabled) {
        emit("changeValue", {
          id: props.item.id,
          key: props.item.key,
          value: !props.item.value
        }, event);
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: ["form-item__checkbox", props.disabled ? "form-item__checkbox_disabled" : ""],
        onClick: (event) => props.changeValueLabel ? changeValue(event) : null
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(CheckboxLabel, {
              onClick: (e) => !props.isTextClickable ? e.stopPropagation() : "",
              title: props.item.title,
              isHTML: props.item.isHTML
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(CheckboxField, {
              item: {
                id: props.item.id,
                value: props.item.value
              },
              changeValueLabel: props.changeValueLabel,
              disabled: props.disabled,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(CheckboxLabel, {
                onClick: (e) => !props.isTextClickable ? e.stopPropagation() : "",
                title: props.item.title,
                isHTML: props.item.isHTML
              }, null, 8, ["onClick", "title", "isHTML"]),
              createVNode(CheckboxField, {
                item: {
                  id: props.item.id,
                  value: props.item.value
                },
                changeValueLabel: props.changeValueLabel,
                disabled: props.disabled,
                onChangeValue: (data) => changeValue(data)
              }, null, 8, ["item", "changeValueLabel", "disabled", "onChangeValue"])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$q = _sfc_main$q.setup;
_sfc_main$q.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Checkbox/Checkbox.vue");
  return _sfc_setup$q ? _sfc_setup$q(props, ctx) : void 0;
};
const AppCheckbox = _sfc_main$q;
const _sfc_main$p = {
  __name: "PasswordEye",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__password-eye" }, _attrs))}><svg xmlns="http://www.w3.org/2000/svg" width="20" height="12" viewBox="0 0 20 12" fill="none"><path d="M18.5861 6.13417C16.8107 3.06495 13.4922 1 9.69149 1C5.89075 1 2.57232 3.06495 0.796875 6.13417" stroke="#BDC0C6" stroke-width="1.6"></path><path d="M18.5861 5.27892C16.8107 8.34813 13.4922 10.4131 9.69149 10.4131C5.89075 10.4131 2.57232 8.34813 0.796875 5.27892" stroke="#BDC0C6" stroke-width="1.6"></path><circle cx="9.78204" cy="5.70587" r="2.19493" stroke="#BDC0C6" stroke-width="1.6"></circle></svg></figure>`);
    };
  }
};
const _sfc_setup$p = _sfc_main$p.setup;
_sfc_main$p.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/PasswordEye/PasswordEye.vue");
  return _sfc_setup$p ? _sfc_setup$p(props, ctx) : void 0;
};
const IconPasswordEye = _sfc_main$p;
const _sfc_main$o = {
  __name: "InputField",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        value: "",
        type: "text",
        placeholder: "",
        title: "Undefined title"
      },
      type: Object
    },
    enabledAutocomplete: {
      default: true,
      type: Boolean
    },
    disabled: {
      default: false,
      type: Boolean
    },
    mask: {
      default: null,
      type: String
    }
  },
  setup(__props, { expose: __expose }) {
    const inputRef = ref();
    let passwordType = ref("password");
    const props = __props;
    const changePasswordType = () => {
      if (passwordType.value == "password") {
        passwordType.value = "text";
      } else {
        passwordType.value = "password";
      }
    };
    watch(
      () => props.item.focus,
      () => {
        if (props.item.focus) {
          setTimeout(() => {
            inputRef.value.focus();
          }, 10);
        }
      }
    );
    __expose({
      inputRef
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _directive_maska = resolveDirective("maska");
      if (props.mask != "" && ![void 0, null].includes(props.mask) || props.item.type == "number") {
        _push(`<input${ssrRenderAttrs(mergeProps({
          ref_key: "inputRef",
          ref: inputRef,
          "data-maska-tokens": "A:[a-zA-Zа-яА-Я]|#:[0-9]|X:[0-9a-zA-Zа-яА-Я]",
          "data-maska": props.item.type == "number" ? "#######################" : props.mask,
          type: "text",
          autocorrect: "off",
          value: props.item.value,
          disabled: props.disabled,
          placeholder: props.item.placeholder,
          autocomplete: props.enabledAutocomplete ? "on" : "off",
          readonly: ""
        }, _attrs, ssrGetDirectiveProps(_ctx, _directive_maska)))}>`);
      } else if (props.item.type == "password") {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "input__group" }, _attrs))}><input autocorrect="off"${ssrRenderAttr("type", unref(passwordType))}${ssrRenderAttr("value", props.item.value)}${ssrIncludeBooleanAttr(props.disabled) ? " disabled" : ""}${ssrRenderAttr("placeholder", props.item.placeholder)}${ssrRenderAttr("autocomplete", props.enabledAutocomplete ? "on" : "off")} readonly>`);
        _push(ssrRenderComponent(IconPasswordEye, {
          class: unref(passwordType) == "text" ? "icon__password-eye_active" : "",
          onClick: changePasswordType
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<input${ssrRenderAttrs(mergeProps({
          autocorrect: "off",
          ref_key: "inputRef",
          ref: inputRef,
          type: props.item.type,
          value: props.item.value,
          disabled: props.disabled,
          placeholder: props.item.placeholder,
          autocomplete: props.enabledAutocomplete ? "on" : "off",
          readonly: ""
        }, _attrs))}>`);
      }
    };
  }
};
const _sfc_setup$o = _sfc_main$o.setup;
_sfc_main$o.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Input/InputField/InputField.vue");
  return _sfc_setup$o ? _sfc_setup$o(props, ctx) : void 0;
};
const InputField = _sfc_main$o;
const _sfc_main$n = {
  __name: "FormLabel",
  __ssrInlineRender: true,
  props: {
    title: {
      default: "",
      type: String
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "form-item__title" }, _attrs))}>${ssrInterpolate(props.title)}</div>`);
    };
  }
};
const _sfc_setup$n = _sfc_main$n.setup;
_sfc_main$n.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppForm/FormLabel/FormLabel.vue");
  return _sfc_setup$n ? _sfc_setup$n(props, ctx) : void 0;
};
const FormLabel = _sfc_main$n;
const _imports_0$1 = "" + __publicAssetsURL("icons/success.svg");
const _imports_1 = "" + __publicAssetsURL("icons/unsuccess.svg");
const _sfc_main$m = {
  __name: "FormValue",
  __ssrInlineRender: true,
  props: {
    value: {
      default: null
    },
    isHTML: {
      default: false,
      type: Boolean
    },
    isLink: {
      default: false,
      type: Boolean
    },
    link: {
      default: null,
      type: String
    },
    substring: {
      default: null,
      type: String
    }
  },
  setup(__props) {
    const props = __props;
    const setClasses = computed(() => {
      return [
        [null, void 0].includes(props.value) || String(props.value) == "" ? "form-item__value_empty" : "",
        props.isHTML ? "form-item__value_html" : "",
        ![null, void 0].includes(props.substring) && props.substring != "" ? "form-item__value_substring" : "",
        props.isLink && ![null, void 0].includes(props.link) && props.link != "" ? "form-item__value_link" : ""
      ];
    });
    return (_ctx, _push, _parent, _attrs) => {
      if (props.value == "true") {
        _push(`<img${ssrRenderAttrs(mergeProps({
          src: _imports_0$1,
          class: "form-item__value form-item__value_checkmark"
        }, _attrs))}>`);
      } else if (props.value == "false") {
        _push(`<img${ssrRenderAttrs(mergeProps({
          src: _imports_1,
          class: "form-item__value form-item__value_unsuccess"
        }, _attrs))}>`);
      } else if (props.isHTML) {
        _push(`<span${ssrRenderAttrs(mergeProps({
          class: ["form-item__value", setClasses.value]
        }, _attrs))}>${props.value ?? ""}</span>`);
      } else {
        _push(`<span${ssrRenderAttrs(mergeProps({
          class: ["form-item__value", setClasses.value]
        }, _attrs))}>${ssrInterpolate(props.value)} `);
        if (![null, void 0].includes(props.substring) && props.substring != "") {
          _push(`<div class="form-item__substring">${ssrInterpolate(props.substring)}</div>`);
        } else {
          _push(`<!---->`);
        }
        _push(`</span>`);
      }
    };
  }
};
const _sfc_setup$m = _sfc_main$m.setup;
_sfc_main$m.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppForm/FormValue/FormValue.vue");
  return _sfc_setup$m ? _sfc_setup$m(props, ctx) : void 0;
};
const FormValue = _sfc_main$m;
const _sfc_main$l = {
  __name: "Input",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        value: "",
        type: "text",
        focus: false,
        placeholder: "",
        substring: null,
        title: "Undefined title"
      },
      type: Object
    },
    disabled: {
      default: false,
      type: Boolean
    },
    enabledAutocomplete: {
      default: true,
      type: Boolean
    },
    mask: {
      default: null,
      type: String
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isLink: {
      default: false,
      type: Boolean
    },
    isShowSubstring: {
      default: true,
      type: Boolean
    }
  },
  setup(__props, { expose: __expose }) {
    const inputRef = ref(null);
    const props = __props;
    __expose({
      inputRef
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: "form-item__input",
        required: props.item.required,
        style: `--substring: ${props.item.substring != void 0 ? props.item.substring : ""}`
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(FormLabel, {
              style: props.item.title != null && props.item.title != "" ? null : { display: "none" },
              title: props.item.title
            }, null, _parent2, _scopeId));
            if (props.isReadOnly) {
              _push2(ssrRenderComponent(FormValue, {
                isHTML: false,
                isLink: props.isLink,
                value: props.item.type == "password" ? "**********" : props.item.value,
                link: props.item.external_link,
                substring: props.item.substring,
                onClick: () => props.isLink ? _ctx.$emit("openLink", props.item) : ""
              }, null, _parent2, _scopeId));
            } else {
              _push2(ssrRenderComponent(InputField, {
                ref_key: "inputRef",
                ref: inputRef,
                item: props.item,
                mask: props.mask,
                disabled: props.disabled,
                enabledAutocomplete: props.enabledAutocomplete,
                onFocus: (data) => _ctx.$emit("focus", data),
                onBlur: (data) => _ctx.$emit("blur", data),
                onChangeValue: (data) => _ctx.$emit("changeValue", data)
              }, null, _parent2, _scopeId));
            }
            if (props.isShowSubstring && !props.isReadOnly && ![null, void 0].includes(props.item.substring) && props.item.substring != "") {
              _push2(`<span class="form-item__substring"${_scopeId}>${ssrInterpolate(props.item.substring)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
          } else {
            return [
              withDirectives(createVNode(FormLabel, {
                title: props.item.title
              }, null, 8, ["title"]), [
                [vShow, props.item.title != null && props.item.title != ""]
              ]),
              props.isReadOnly ? (openBlock(), createBlock(FormValue, {
                key: 0,
                isHTML: false,
                isLink: props.isLink,
                value: props.item.type == "password" ? "**********" : props.item.value,
                link: props.item.external_link,
                substring: props.item.substring,
                onClick: () => props.isLink ? _ctx.$emit("openLink", props.item) : ""
              }, null, 8, ["isLink", "value", "link", "substring", "onClick"])) : (openBlock(), createBlock(InputField, {
                key: 1,
                ref_key: "inputRef",
                ref: inputRef,
                item: props.item,
                mask: props.mask,
                disabled: props.disabled,
                enabledAutocomplete: props.enabledAutocomplete,
                onFocus: (data) => _ctx.$emit("focus", data),
                onBlur: (data) => _ctx.$emit("blur", data),
                onChangeValue: (data) => _ctx.$emit("changeValue", data)
              }, null, 8, ["item", "mask", "disabled", "enabledAutocomplete", "onFocus", "onBlur", "onChangeValue"])),
              props.isShowSubstring && !props.isReadOnly && ![null, void 0].includes(props.item.substring) && props.item.substring != "" ? (openBlock(), createBlock("span", {
                key: 2,
                class: "form-item__substring"
              }, toDisplayString(props.item.substring), 1)) : createCommentVNode("", true),
              renderSlot(_ctx.$slots, "default")
            ];
          }
        }),
        _: 3
      }, _parent));
    };
  }
};
const _sfc_setup$l = _sfc_main$l.setup;
_sfc_main$l.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Input/Input.vue");
  return _sfc_setup$l ? _sfc_setup$l(props, ctx) : void 0;
};
const AppInput = _sfc_main$l;
const _sfc_main$k = {
  __name: "H3",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<h3${ssrRenderAttrs(_attrs)}>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</h3>`);
    };
  }
};
const _sfc_setup$k = _sfc_main$k.setup;
_sfc_main$k.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppHeaders/H3/H3.vue");
  return _sfc_setup$k ? _sfc_setup$k(props, ctx) : void 0;
};
const AppH3 = _sfc_main$k;
const _sfc_main$j = {
  __name: "Delete",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__delete" }, _attrs))}><svg viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g fill="currentColor" fill-rule="nonzero"><path d="M2.08859116,2.2156945 L2.14644661,2.14644661 C2.32001296,1.97288026 2.58943736,1.95359511 2.7843055,2.08859116 L2.85355339,2.14644661 L6,5.293 L9.14644661,2.14644661 C9.34170876,1.95118446 9.65829124,1.95118446 9.85355339,2.14644661 C10.0488155,2.34170876 10.0488155,2.65829124 9.85355339,2.85355339 L6.707,6 L9.85355339,9.14644661 C10.0271197,9.32001296 10.0464049,9.58943736 9.91140884,9.7843055 L9.85355339,9.85355339 C9.67998704,10.0271197 9.41056264,10.0464049 9.2156945,9.91140884 L9.14644661,9.85355339 L6,6.707 L2.85355339,9.85355339 C2.65829124,10.0488155 2.34170876,10.0488155 2.14644661,9.85355339 C1.95118446,9.65829124 1.95118446,9.34170876 2.14644661,9.14644661 L5.293,6 L2.14644661,2.85355339 C1.97288026,2.67998704 1.95359511,2.41056264 2.08859116,2.2156945 L2.14644661,2.14644661 L2.08859116,2.2156945 Z"></path></g></g></svg><figcaption>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</figcaption></figure>`);
    };
  }
};
const _sfc_setup$j = _sfc_main$j.setup;
_sfc_main$j.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Delete/Delete.vue");
  return _sfc_setup$j ? _sfc_setup$j(props, ctx) : void 0;
};
const IconDelete = _sfc_main$j;
const _sfc_main$i = {
  __name: "AppWarning",
  __ssrInlineRender: true,
  props: {
    isShow: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "closeModal"
  ],
  setup(__props, { expose: __expose, emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const modalRef = ref(null);
    const warningRef = ref(null);
    ref(null);
    watch(() => props.isShow, () => {
      if (props.isShow) {
        document.body.parentNode.classList.add("body_uncscroll");
      } else {
        if (document.querySelector(".modal-container") == null) {
          document.body.parentNode.classList.remove("body_uncscroll");
        }
      }
    }, {
      deep: true
    });
    onUnmounted(() => {
      if (document.querySelector(".modal-container") == null) {
        document.body.parentNode.classList.remove("body_uncscroll");
      }
      if (modalRef.value) {
        modalRef.value.classList.remove("modal_warning");
      }
      document.querySelectorAll(".modal__content").forEach((elem) => {
        elem.classList.remove("modal__content_unset");
      });
    });
    __expose({
      warningRef
    });
    return (_ctx, _push, _parent, _attrs) => {
      if (props.isShow) {
        _push(`<div${ssrRenderAttrs(mergeProps({
          class: "warning",
          ref_key: "warningRef",
          ref: warningRef
        }, _attrs))}><div class="warning__background"><div class="warning__content">`);
        _push(ssrRenderComponent(IconDelete, {
          class: "warning__close",
          onClick: () => emit("closeModal", true)
        }, null, _parent));
        _push(ssrRenderComponent(AppH3, { class: "warning__title" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              ssrRenderSlot(_ctx.$slots, "title", {}, null, _push2, _parent2, _scopeId);
            } else {
              return [
                renderSlot(_ctx.$slots, "title")
              ];
            }
          }),
          _: 3
        }, _parent));
        _push(`<div class="warning__body">`);
        ssrRenderSlot(_ctx.$slots, "body", {}, null, _push, _parent);
        _push(`</div></div></div></div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$i = _sfc_main$i.setup;
_sfc_main$i.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppWarning/AppWarning.vue");
  return _sfc_setup$i ? _sfc_setup$i(props, ctx) : void 0;
};
const AppWarning$1 = _sfc_main$i;
const _sfc_main$h = {
  __name: "SelectField",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        required: false,
        title: "Select title",
        value: null,
        focus: false,
        key: null,
        options: [],
        lockedOptions: []
      },
      type: Object
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isMultiple: {
      default: false,
      type: Boolean
    },
    isHaveNullOption: {
      default: true,
      type: Boolean
    },
    isFiltered: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "changeValue",
    "clickOutside"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const popupRef = ref(null);
    const inputRef = ref(null);
    const mirrorRef = ref(null);
    const nullOption = {
      label: "Не выбрано",
      value: null
    };
    let options = ref([]);
    let search = ref(null);
    let backupOptions = ref([]);
    let multiplyValues = ref([]);
    let activeOptions = ref(props.isMultiple ? [] : nullOption);
    const callAction = async (data) => {
      const showContent = (state) => {
        setTimeout(() => {
          if (state) {
            if (props.isMultiple) {
              mirrorRef.value.focus();
            } else {
              if (popupRef.value.popupRef.hasAttribute("open")) {
                inputRef.value.inputRef.inputRef.focus();
              } else {
                inputRef.value.inputRef.inputRef.blur();
              }
            }
          } else {
            popupRef.value.popupRef.removeAttribute("open");
            inputRef.value.inputRef.inputRef.blur();
          }
        }, 10);
      };
      const initShowContent = (event) => {
        if (props.isReadOnly) {
          event.preventDefault();
        } else {
          showContent(true);
        }
      };
      const getOptions = async () => {
        const isEmpty = (obj) => {
          for (const prop in obj) {
            if (Object.hasOwn(obj, prop)) {
              return false;
            }
          }
          return true;
        };
        let localOptions = props.item.options == null ? [] : props.item.options.filter((p) => p != null && typeof p == "object" && !Array.isArray(p) && !isEmpty(p));
        options.value = JSON.parse(JSON.stringify(localOptions));
      };
      const setActiveOptions = async (value) => {
        search.value = "";
        const findOption = (value2) => {
          let findedOption = options.value == null ? null : options.value.find((option) => option.value == (value2 == null ? null : String(value2)));
          if ([null, void 0].includes(findedOption)) {
            return nullOption;
          } else {
            return findedOption;
          }
        };
        if (props.isMultiple) {
          let data2 = [];
          for (let item of multiplyValues.value) {
            data2.push(findOption(item));
          }
          activeOptions.value = data2.filter((option) => option.value != null);
        } else {
          activeOptions.value = findOption(value);
        }
      };
      const searchOptions = (value) => {
        search.value = value;
        options.value = backupOptions.value.filter((option) => String(option.label).toLowerCase().includes(String(search.value).toLowerCase()));
        if (!popupRef.value.popupRef.hasAttribute("open")) {
          popupRef.value.popupRef.setAttribute("open", true);
        }
      };
      const changeValue = (value, event = null) => {
        if (value == null || (![null, void 0].includes(props.item.lockedOptions) ? !props.item.lockedOptions.includes(value) : true)) {
          search.value = null;
          if (props.isFiltered) {
            options.value = backupOptions.value;
          }
          if (props.isMultiple) {
            if (multiplyValues.value.includes(value)) {
              multiplyValues.value = multiplyValues.value.filter((option) => option != value);
              showContent(true);
            } else {
              multiplyValues.value.push(value);
            }
            setTimeout(() => {
              mirrorRef.value.focus();
            }, 10);
            emit("changeValue", {
              key: props.item.key,
              value: multiplyValues.value
            });
          } else {
            emit("changeValue", {
              key: props.item.key,
              value
            });
            setTimeout(() => {
              showContent(false);
            }, 10);
          }
          setActiveOptions(value);
        }
      };
      switch (data.action) {
        case "showContent":
          showContent(data.value);
          break;
        case "setActiveOptions":
          await setActiveOptions(data.value);
          break;
        case "searchOptions":
          searchOptions(data.value);
          break;
        case "changeValue":
          changeValue(data.value, data.event);
          break;
        case "getOptions":
          await getOptions();
          break;
        case "initShowContent":
          initShowContent(data.event);
          break;
      }
    };
    watch(() => props.item.options, () => {
      callAction({
        action: "getOptions",
        value: null
      });
    }, {
      deep: true
    });
    watch(() => props.item.value, () => {
      if (props.isMultiple) {
        if ([null, void 0].includes(props.item.value) || typeof props.item.value == "string") {
          multiplyValues.value = [];
        } else {
          multiplyValues.value = [...new Set(props.item.value)];
        }
      }
      callAction({
        action: "getOptions",
        value: null
      });
      callAction({
        action: "setActiveOptions",
        value: props.item.value
      });
    });
    watch(() => props.item.focus, () => {
      setTimeout(() => {
        if (props.item.focus) {
          inputRef.value.inputRef.inputRef.focus();
          popupRef.value.popupRef.setAttribute("open", true);
          if (props.isFiltered) {
            PopupScripts.setDropdownPosition(popupRef.value.popupRef);
          }
        }
      }, 10);
    });
    return (_ctx, _push, _parent, _attrs) => {
      if (props.isMultiple && props.isReadOnly) {
        _push(`<div${ssrRenderAttrs(mergeProps({
          class: ["form-item__value form-item__value_multiply", unref(activeOptions) == null || unref(activeOptions).length == 0 ? "form-item__value_empty" : ""]
        }, _attrs))}><!--[-->`);
        ssrRenderList(unref(activeOptions), (tab) => {
          _push(`<div class="select__active-option-tab">${ssrInterpolate(tab.label)}</div>`);
        });
        _push(`<!--]--></div>`);
      } else if (props.isReadOnly) {
        _push(ssrRenderComponent(FormValue, mergeProps({
          value: unref(activeOptions) == null || unref(activeOptions).value == null ? null : unref(activeOptions).label,
          isHTML: false,
          isLink: false
        }, _attrs), null, _parent));
      } else {
        _push(ssrRenderComponent(AppPopup, mergeProps({
          class: ["select__popup", props.isMultiple ? "select__popup_multiply" : ""],
          closeByClick: false,
          ref_key: "popupRef",
          ref: popupRef,
          isCanSelect: true,
          isReadOnly: props.isReadOnly,
          onClick: (event) => callAction({ action: "initShowContent", event }),
          onClickOutside: () => emit("clickOutside", true)
        }, _attrs), {
          summary: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(AppInput, {
                ref_key: "inputRef",
                ref: inputRef,
                item: {
                  id: props.item.id,
                  title: null,
                  type: "text",
                  focus: false,
                  key: props.item.key,
                  placeholder: null,
                  value: props.isReadOnly ? unref(activeOptions) == null ? null : unref(activeOptions).label : unref(search),
                  substring: props.isReadOnly ? null : " "
                },
                disabled: !props.isFiltered,
                isReadOnly: props.isReadOnly,
                enabledAutocomplete: false,
                onChangeValue: (data) => callAction({ action: "searchOptions", value: data.value }),
                onKeydown: (event) => {
                  event.preventDefault();
                  callAction({ action: "searchOptions", value: event.target.value + " " });
                }
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="select__active-option active-option" style="${ssrRenderStyle(!props.isReadOnly && (props.isMultiple || ([null, void 0].includes(unref(search)) || unref(search) == "")) ? null : { display: "none" })}"${_scopeId2}>`);
                    if (props.isMultiple && unref(activeOptions) != null) {
                      _push3(`<!--[--><!--[-->`);
                      ssrRenderList(unref(activeOptions), (tab) => {
                        _push3(`<div class="select__active-option-tab"${_scopeId2}>${ssrInterpolate(tab.label)} `);
                        _push3(ssrRenderComponent(IconDelete, {
                          onClick: (event) => callAction({ action: "changeValue", value: tab.value, event })
                        }, null, _parent3, _scopeId2));
                        _push3(`</div>`);
                      });
                      _push3(`<!--]--><div class="active-option__mirror"${_scopeId2}><div class="form-item__mirror"${_scopeId2}>${ssrInterpolate(unref(search))}</div><input type="text"${ssrRenderAttr("value", unref(search))}${ssrIncludeBooleanAttr(!props.isFiltered) ? " disabled" : ""}${_scopeId2}></div><!--]-->`);
                    } else {
                      _push3(`<!--[-->${ssrInterpolate(unref(activeOptions) == null ? nullOption.label : unref(activeOptions).label)}<!--]-->`);
                    }
                    _push3(`</div>`);
                  } else {
                    return [
                      withDirectives(createVNode("div", { class: "select__active-option active-option" }, [
                        props.isMultiple && unref(activeOptions) != null ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(unref(activeOptions), (tab) => {
                            return openBlock(), createBlock("div", { class: "select__active-option-tab" }, [
                              createTextVNode(toDisplayString(tab.label) + " ", 1),
                              createVNode(IconDelete, {
                                onClick: (event) => callAction({ action: "changeValue", value: tab.value, event })
                              }, null, 8, ["onClick"])
                            ]);
                          }), 256)),
                          createVNode("div", { class: "active-option__mirror" }, [
                            createVNode("div", { class: "form-item__mirror" }, toDisplayString(unref(search)), 1),
                            createVNode("input", {
                              ref_key: "mirrorRef",
                              ref: mirrorRef,
                              type: "text",
                              value: unref(search),
                              disabled: !props.isFiltered,
                              onInput: (e) => callAction({ action: "searchOptions", value: e.target.value })
                            }, null, 40, ["value", "disabled", "onInput"])
                          ])
                        ], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                          createTextVNode(toDisplayString(unref(activeOptions) == null ? nullOption.label : unref(activeOptions).label), 1)
                        ], 64))
                      ], 512), [
                        [vShow, !props.isReadOnly && (props.isMultiple || ([null, void 0].includes(unref(search)) || unref(search) == ""))]
                      ])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(AppInput, {
                  ref_key: "inputRef",
                  ref: inputRef,
                  item: {
                    id: props.item.id,
                    title: null,
                    type: "text",
                    focus: false,
                    key: props.item.key,
                    placeholder: null,
                    value: props.isReadOnly ? unref(activeOptions) == null ? null : unref(activeOptions).label : unref(search),
                    substring: props.isReadOnly ? null : " "
                  },
                  disabled: !props.isFiltered,
                  isReadOnly: props.isReadOnly,
                  enabledAutocomplete: false,
                  onChangeValue: (data) => callAction({ action: "searchOptions", value: data.value }),
                  onKeydown: withKeys((event) => {
                    event.preventDefault();
                    callAction({ action: "searchOptions", value: event.target.value + " " });
                  }, ["space"])
                }, {
                  default: withCtx(() => [
                    withDirectives(createVNode("div", { class: "select__active-option active-option" }, [
                      props.isMultiple && unref(activeOptions) != null ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(unref(activeOptions), (tab) => {
                          return openBlock(), createBlock("div", { class: "select__active-option-tab" }, [
                            createTextVNode(toDisplayString(tab.label) + " ", 1),
                            createVNode(IconDelete, {
                              onClick: (event) => callAction({ action: "changeValue", value: tab.value, event })
                            }, null, 8, ["onClick"])
                          ]);
                        }), 256)),
                        createVNode("div", { class: "active-option__mirror" }, [
                          createVNode("div", { class: "form-item__mirror" }, toDisplayString(unref(search)), 1),
                          createVNode("input", {
                            ref_key: "mirrorRef",
                            ref: mirrorRef,
                            type: "text",
                            value: unref(search),
                            disabled: !props.isFiltered,
                            onInput: (e) => callAction({ action: "searchOptions", value: e.target.value })
                          }, null, 40, ["value", "disabled", "onInput"])
                        ])
                      ], 64)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                        createTextVNode(toDisplayString(unref(activeOptions) == null ? nullOption.label : unref(activeOptions).label), 1)
                      ], 64))
                    ], 512), [
                      [vShow, !props.isReadOnly && (props.isMultiple || ([null, void 0].includes(unref(search)) || unref(search) == ""))]
                    ])
                  ]),
                  _: 1
                }, 8, ["item", "disabled", "isReadOnly", "onChangeValue", "onKeydown"])
              ];
            }
          }),
          content: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(PopupOption, {
                class: "popup__option_null",
                style: props.isHaveNullOption & !props.isMultiple || unref(options).length == 0 ? null : { display: "none" },
                onClick: () => callAction({ action: "changeValue", value: null })
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Не выбрано `);
                  } else {
                    return [
                      createTextVNode(" Не выбрано ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`<!--[-->`);
              ssrRenderList(unref(options), (option) => {
                _push2(ssrRenderComponent(PopupOption, {
                  class: ["popup-option__root", option.value == unref(activeOptions).value || unref(multiplyValues).includes(option.value) ? "popup__option_active" : "", ![null, void 0].includes(props.item.lockedOptions) && props.item.lockedOptions.includes(option.value) ? "popup__option_disabled" : ""],
                  onClick: () => callAction({ action: "changeValue", value: option.value })
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`<div class="popup-option__text"${_scopeId2}>${ssrInterpolate(option.label)}</div>`);
                    } else {
                      return [
                        createVNode("div", { class: "popup-option__text" }, toDisplayString(option.label), 1)
                      ];
                    }
                  }),
                  _: 2
                }, _parent2, _scopeId));
              });
              _push2(`<!--]-->`);
            } else {
              return [
                withDirectives(createVNode(PopupOption, {
                  class: "popup__option_null",
                  onClick: () => callAction({ action: "changeValue", value: null })
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Не выбрано ")
                  ]),
                  _: 1
                }, 8, ["onClick"]), [
                  [vShow, props.isHaveNullOption & !props.isMultiple || unref(options).length == 0]
                ]),
                (openBlock(true), createBlock(Fragment, null, renderList(unref(options), (option) => {
                  return openBlock(), createBlock(PopupOption, {
                    class: ["popup-option__root", option.value == unref(activeOptions).value || unref(multiplyValues).includes(option.value) ? "popup__option_active" : "", ![null, void 0].includes(props.item.lockedOptions) && props.item.lockedOptions.includes(option.value) ? "popup__option_disabled" : ""],
                    onClick: () => callAction({ action: "changeValue", value: option.value })
                  }, {
                    default: withCtx(() => [
                      createVNode("div", { class: "popup-option__text" }, toDisplayString(option.label), 1)
                    ]),
                    _: 2
                  }, 1032, ["class", "onClick"]);
                }), 256))
              ];
            }
          }),
          _: 1
        }, _parent));
      }
    };
  }
};
const _sfc_setup$h = _sfc_main$h.setup;
_sfc_main$h.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSelects/Select/SelectField/SelectField.vue");
  return _sfc_setup$h ? _sfc_setup$h(props, ctx) : void 0;
};
const SelectField = _sfc_main$h;
const _sfc_main$g = {
  __name: "Select",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        required: false,
        title: "Select title",
        value: null,
        focus: false,
        key: null,
        options: [],
        lockedOptions: []
      },
      type: Object
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isMultiple: {
      default: false,
      type: Boolean
    },
    isHaveNullOption: {
      default: true,
      type: Boolean
    },
    isFiltered: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "changeValue",
    "clickOutside"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: "form-item__select select",
        required: props.item.required
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(FormLabel, {
              style: props.item.title != null && props.item.title != "" ? null : { display: "none" },
              title: props.item.title
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(SelectField, {
              item: props.item,
              isReadOnly: props.isReadOnly,
              isMultiple: props.isMultiple,
              isFiltered: props.isFiltered,
              isHaveNullOption: props.isHaveNullOption,
              onClickOutside: () => emit("clickOutside", true),
              onChangeValue: (data) => emit("changeValue", data)
            }, null, _parent2, _scopeId));
          } else {
            return [
              withDirectives(createVNode(FormLabel, {
                title: props.item.title
              }, null, 8, ["title"]), [
                [vShow, props.item.title != null && props.item.title != ""]
              ]),
              createVNode(SelectField, {
                item: props.item,
                isReadOnly: props.isReadOnly,
                isMultiple: props.isMultiple,
                isFiltered: props.isFiltered,
                isHaveNullOption: props.isHaveNullOption,
                onClickOutside: () => emit("clickOutside", true),
                onChangeValue: (data) => emit("changeValue", data)
              }, null, 8, ["item", "isReadOnly", "isMultiple", "isFiltered", "isHaveNullOption", "onClickOutside", "onChangeValue"])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$g = _sfc_main$g.setup;
_sfc_main$g.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSelects/Select/Select.vue");
  return _sfc_setup$g ? _sfc_setup$g(props, ctx) : void 0;
};
const AppSelect = _sfc_main$g;
const useCommonStore = defineStore("commonStore", {
  // states
  state: () => {
    return {
      isShowMobileMenu: true,
      menu: [],
      activeTab: null,
      tabs: [],
      updatedMethods: {},
      modalInfo: [],
      accounts: []
    };
  },
  persist: true,
  // actions
  actions: {}
});
const http = {
  call(type, url, params, headers) {
    return new Promise(async function(resolve, reject) {
      try {
        let response = await axios({
          method: type.toUpperCase(),
          url: url + (type.toLowerCase() == "get" && params.length > 0 ? "?" + params : ""),
          data: JSON_stringify(params, false),
          headers: Object.assign({ "Content-Type": "application/json" }, headers)
          //withCredentials: true,
        }).catch((error2) => {
          const router = useRouter();
          const userStore = useUserStore();
          const commonStore = useCommonStore();
          let lastModal = userStore.modals[userStore.modals.length - 1];
          if (error2.response.status == 401) {
            window.location.href = "/auth";
          } else if (error2.response.status == 404) {
            if (lastModal) {
              lastModal.errorCode = 404;
              window.history.pushState("", "Title", "/404");
            } else {
              resolve(error2.response);
              router.push({ path: "/404" });
            }
          } else if (type.toUpperCase() == "GET" && error2.response.status == 403) {
            commonStore.errorPage = {
              code: 403,
              reason: error2.response.data.message
            };
            if (lastModal) {
              lastModal.errorCode = 403;
              window.history.pushState("", "Title", "/403");
            } else {
              router.push({ path: "/403" });
            }
          } else if (error2.response.status == 409) {
            commonScripts.showNotification(
              {
                title: "Ошибка при сохранении",
                description: error2.response.data.message
              },
              "error"
            );
          } else {
            commonScripts.showNotification(
              {
                title: "Ошибка при сохранении",
                description: error2.response.data.message
              },
              "error"
            );
          }
          resolve(error2.response);
        });
        resolve(response.data);
      } catch (e) {
        reject(e);
      }
    });
  }
};
function JSON_stringify(s, emit_unicode) {
  var json = JSON.stringify(s);
  return emit_unicode ? json : json.replace(/[\u007f-\uffff]/g, function(c) {
    return "\\u" + ("0000" + c.charCodeAt(0).toString(16)).slice(-4);
  });
}
const api = {
  callMethod(type, method, params = {}, headers = { Authorization: "Bearer null" }, is_prefix = true) {
    return new Promise(async (resolve, reject) => {
      const url = ("https://compas.pro") + (is_prefix ? "/api/" : "/") + method;
      try {
        let result = await http.call(type, url, params, headers);
        resolve(result);
      } catch (e) {
        reject(e);
      }
    });
  }
};
const commonScripts$1 = {
  // Очистка выделений
  clearSelection() {
    if (document.selection && document.selection.empty) {
      document.selection.empty();
    } else if (window.getSelection) {
      var sel = window.getSelection();
      sel.removeAllRanges();
    }
  },
  // Поиск опций
  async getInfoAutocomplete(search, id) {
    const userStore = useUserStore();
    let request = await fetch(`https://opt6.compas.pro/api/objects/search?${id == null ? "entity=products" : "field_id=" + id}&q=${search}`, {
      method: "GET",
      headers: {
        "Authorization": `Bearer ${userStore.userToken}`
      }
    });
    return await request.json();
  },
  // Показать уведомление
  showNotification(message, type = "default", options = {}) {
    const formatedMessage = `
            <h4 class="Toastify__toast-title">${message.title}</h4>
            <p class="Toastify__toast-description">${message.description}</p>
        `;
    toast(formatedMessage, {
      ...options,
      type,
      position: toast.POSITION.TOP_RIGHT,
      limit: 8,
      pauseOnHover: true,
      dangerouslyHTMLString: true,
      hideProgressBar: true,
      autoClose: 3e3,
      newestOnTop: true
    });
  },
  // Трансформирование параметров
  transformParams(type, params = null) {
    const addressToParams = () => {
      let response = {};
      let changedKey = null;
      const address = window.location.search.split("?")[1];
      const addressFields = address ? address.split("&") : [];
      for (let key of addressFields) {
        changedKey = key.split("=")[0];
        if (key.includes("filter")) {
          if (type == "addressToParams") {
            changedKey = changedKey.replace("filter[", "").replace("]", "").replace("filter%5B", "").replace("%5D", "");
          } else if (type == "addressToAddress") {
            changedKey = changedKey.replace("%5B", "[").replace("%5D", "]");
          }
          response[changedKey] = decodeURIComponent(key.split("=")[1].replaceAll("+", " "));
        } else {
          let value = decodeURIComponent(key.split("=")[1].replaceAll("+", " "));
          switch (key.split("=")[0]) {
            case "sort_order":
              response[changedKey] = value == "undefined" ? "desc" : value;
              break;
            case "sort_field":
              response[changedKey] = value == "undefined" ? "id" : value;
              break;
            default:
              response[changedKey] = value;
              break;
          }
        }
      }
      return response;
    };
    const paramsToAddress = () => {
      let requestParams = {};
      for (let key in params) {
        if (!["page", "per_page", "trashed", "sort_field", "sort_order", "q", "object", "tab"].includes(key)) {
          requestParams[`filter[${key}]`] = params[key];
        } else {
          switch (key) {
            case "sort_order":
              requestParams[key] = params[key] ?? "id";
              break;
            case "sort_field":
              requestParams[key] = params[key] ?? "desc";
              break;
            default:
              requestParams[key] = params[key];
              break;
          }
        }
      }
      return requestParams;
    };
    switch (type) {
      case "paramsToAddress":
        return paramsToAddress();
      default:
        return addressToParams();
    }
  },
  // Установка адреса в зависимости от гет параметров
  async setURLParams(data) {
    window.history.replaceState("", "title", `?${new URLSearchParams(data).toString()}`);
  },
  // Получить название страницы (паеределать)
  getPageName(arr) {
    for (let link of arr) {
      if (getCompliance(document.location.pathname) == link.link) {
        return link.name;
      } else {
        for (let child of link.children) {
          if (getCompliance(document.location.pathname) == child.link) {
            return child.name;
          }
        }
      }
    }
    return "Без названия";
  }
};
function getCompliance(url) {
  if (document.location.pathname.substring(url.length - 1) == "/") {
    return url.substring(0, url.length - 1);
  } else {
    return url;
  }
}
const useUserStore = defineStore("userStore", {
  // states
  state: () => {
    return {
      user: {
        name: "Денис Потемкин",
        avatar: "/user/avatar.svg",
        color: "linear-gradient(82deg, #7ba06d, #6204c4)"
      },
      roles: [],
      authButtonLoad: false,
      regButtonLoad: false,
      authData: {
        domain: ""
      },
      regData: {
        email: "",
        emailError: [],
        password: "",
        passwordError: [],
        domain: "",
        tariff: "",
        tariffError: "",
        domainError: [],
        passwordConfirmation: "",
        passwordConfirmationError: [],
        confidence: false
      },
      authError: {
        text: "",
        status: false
      },
      modals: []
      // userToken: "1Eg6R5LWw2VsRXwn7gYcSYJ81awict9B5xllQES9yTcwavoaDQFslm9BtkQ7",
    };
  },
  // persist: {
  // 	storage: persistedState.localStorage,
  // },
  // actions
  actions: {
    async logIn(data, authRef) {
      let response = null;
      try {
        this.authButtonLoad = true;
        response = await api.callMethod("POST", `tenant/check`, data, { Authorization: "Bearer null" }, true, true, this.authData.domain);
        if ((response == null ? void 0 : response.code) == 404 || !(response == null ? void 0 : response.success)) {
          this.authData = {
            domain: ""
          };
          this.authError = {
            status: true,
            text: response == null ? void 0 : response.error
          };
          return;
        } else {
          if (response.success) {
            this.userToken = response.token;
            this.authError = {
              status: false,
              text: ""
            };
            const commonStore = useCommonStore();
            console.log(commonStore.accounts);
            const isInside = commonStore.accounts.find((i) => i.toLowerCase() == this.authData.domain.toLowerCase());
            !isInside && commonStore.accounts.push(this.authData.domain.toLowerCase());
            console.log(commonStore.accounts);
            navigateTo(`https://${this.authData.domain}.compas.pro/`, { external: true });
          }
        }
      } catch (e) {
        this.authData = {
          domain: ""
        };
        this.authError = {
          status: true,
          text: response == null ? void 0 : response.error
        };
      } finally {
        this.authButtonLoad = false;
      }
    },
    // Выход из приложения
    async logOut() {
      this.userToken = null;
      this.authError = {
        status: false,
        text: ""
      };
      this.authData = {
        email: "",
        password: ""
      };
      await this.clearStore();
    },
    async forgetPassword(data) {
      commonScripts$1.showNotification({
        title: "Восстановление пароля",
        description: `Сообщение было отправлено на почту ${data.email}`
      });
    },
    async registration(payload, numbers) {
      try {
        this.regButtonLoad = true;
        this.regData.emailError = [];
        this.regData.passwordError = [];
        this.regData.domainError = [];
        this.regData.passwordConfirmationError = [];
        const res = await api.callMethod("POST", "registration", { domain: payload.domain ? payload.domain : void 0, email: payload.email, password: payload.password, password_confirmation: payload.passwordConfirmation, tariff: payload.tariff ? payload.tariff : 1, ...numbers });
        const { success, data, token, domain, url } = res;
        if (success && token) {
          const commonStore = useCommonStore();
          const isInside = commonStore.accounts.find((i) => i.toLowerCase() == this.regData.domain.toLowerCase());
          !isInside && commonStore.accounts.push(domain.toLowerCase());
          console.log(success, data, token, domain, url);
          navigateTo(`https://${domain}.compas.pro${url ? url : ""}/?token=${token}`, { external: true });
          this.regData.email = "";
          this.regData.password = "";
          this.regData.domain = "";
          this.regData.passwordConfirmation = "";
        }
        for (let key in data) {
          if (key == "password_confirmation") {
            this.regData["passwordConfirmationError"] = data[key];
          }
          if (key in this.regData) {
            this.regData[`${key}Error`] = data[key];
          }
        }
      } catch (e) {
        console.log(e, "123");
        this.regData.email = "";
        this.regData.password = "";
        this.regData.domain = "";
        this.regData.passwordConfirmation = "";
      } finally {
        this.regButtonLoad = false;
      }
    },
    async clearStore() {
      this.authButtonLoad = false;
      this.regButtonLoad = false;
      this.authData = {
        email: "",
        password: "",
        remember_me: false
      };
      this.authError = {
        text: "",
        status: false
      };
    }
  }
});
const _sfc_main$f = {
  __name: "Settings",
  __ssrInlineRender: true,
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const userStore = useUserStore();
    const isShow = inject("isShow");
    const settingsTab = inject("settingsTab");
    const emit = __emit;
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    const changeValue = (data) => {
      settingsTab.value[data.key] = data.value;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Настройки раздела `);
          } else {
            return [
              createTextVNode(" Настройки раздела ")
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="warning__list"${_scopeId}>`);
            _push2(ssrRenderComponent(AppInput, {
              class: "warning-list__field",
              item: {
                id: 2,
                required: false,
                substring: null,
                type: "text",
                title: "Раздел",
                placeholder: null,
                value: unref(settingsTab).title,
                key: "title",
                focus: false
              },
              isReadOnly: true,
              mask: null,
              disabled: false,
              enabledAutocomplete: false
            }, null, _parent2, _scopeId));
            _push2(`<div class="warning-list__group-field"${_scopeId}>`);
            _push2(ssrRenderComponent(AppCheckbox, {
              item: {
                id: 6,
                value: unref(settingsTab).has_roles_read,
                isHTML: false,
                required: false,
                title: "Ограничить видимость раздела",
                key: "has_roles_read"
              },
              disabled: false,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
            if (unref(settingsTab).has_roles_read) {
              _push2(ssrRenderComponent(AppSelect, {
                class: "warning-list__field",
                item: {
                  id: 1,
                  key: "roles_read",
                  value: unref(settingsTab).roles_read,
                  focus: false,
                  required: false,
                  title: "Роли",
                  lockedOptions: [],
                  options: unref(userStore).roles
                },
                isReadOnly: false,
                isHaveNullOption: false,
                isMultiple: true,
                isFiltered: true,
                onChangeValue: (data) => changeValue(data)
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div><div class="warning__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              class: "button_blue",
              onClick: () => emit("callAction", { action: "saveSettings", value: "settings" })
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Сохранить `);
                } else {
                  return [
                    createTextVNode(" Сохранить ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppButton, {
              onClick: () => showWarning(false)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` Отмена `);
                } else {
                  return [
                    createTextVNode(" Отмена ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "warning__list" }, [
                createVNode(AppInput, {
                  class: "warning-list__field",
                  item: {
                    id: 2,
                    required: false,
                    substring: null,
                    type: "text",
                    title: "Раздел",
                    placeholder: null,
                    value: unref(settingsTab).title,
                    key: "title",
                    focus: false
                  },
                  isReadOnly: true,
                  mask: null,
                  disabled: false,
                  enabledAutocomplete: false
                }, null, 8, ["item"]),
                createVNode("div", { class: "warning-list__group-field" }, [
                  createVNode(AppCheckbox, {
                    item: {
                      id: 6,
                      value: unref(settingsTab).has_roles_read,
                      isHTML: false,
                      required: false,
                      title: "Ограничить видимость раздела",
                      key: "has_roles_read"
                    },
                    disabled: false,
                    onChangeValue: (data) => changeValue(data)
                  }, null, 8, ["item", "onChangeValue"]),
                  unref(settingsTab).has_roles_read ? (openBlock(), createBlock(AppSelect, {
                    key: 0,
                    class: "warning-list__field",
                    item: {
                      id: 1,
                      key: "roles_read",
                      value: unref(settingsTab).roles_read,
                      focus: false,
                      required: false,
                      title: "Роли",
                      lockedOptions: [],
                      options: unref(userStore).roles
                    },
                    isReadOnly: false,
                    isHaveNullOption: false,
                    isMultiple: true,
                    isFiltered: true,
                    onChangeValue: (data) => changeValue(data)
                  }, null, 8, ["item", "onChangeValue"])) : createCommentVNode("", true)
                ])
              ]),
              createVNode("div", { class: "warning__actions" }, [
                createVNode(AppButton, {
                  class: "button_blue",
                  onClick: () => emit("callAction", { action: "saveSettings", value: "settings" })
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Сохранить ")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(AppButton, {
                  onClick: () => showWarning(false)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" Отмена ")
                  ]),
                  _: 1
                }, 8, ["onClick"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$f = _sfc_main$f.setup;
_sfc_main$f.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTabs/Warning/Settings/Settings.vue");
  return _sfc_setup$f ? _sfc_setup$f(props, ctx) : void 0;
};
const WarningSettings = _sfc_main$f;
const _sfc_main$e = {
  __name: "Warning",
  __ssrInlineRender: true,
  setup(__props) {
    const isShow = inject("isShow");
    return (_ctx, _push, _parent, _attrs) => {
      if (unref(isShow).state && unref(isShow).type == "updateTabs") {
        _push(ssrRenderComponent(WarningSettings, _attrs, null, _parent));
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$e = _sfc_main$e.setup;
_sfc_main$e.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTabs/Warning/Warning.vue");
  return _sfc_setup$e ? _sfc_setup$e(props, ctx) : void 0;
};
const AppWarning = _sfc_main$e;
const _sfc_main$d = {
  __name: "Tabs",
  __ssrInlineRender: true,
  props: {
    tabs: {
      default: [],
      type: Object
    },
    is_admin: {
      default: false,
      type: Boolean
    },
    haveSettings: {
      default: true,
      type: Boolean
    },
    userRole: {
      default: null,
      type: Number
    },
    isCanChange: {
      default: true,
      type: Boolean
    },
    isShowActions: {
      default: true,
      type: Boolean
    },
    isShowBlueArrow: {
      default: false,
      type: Boolean
    }
  },
  emits: ["callAction"],
  setup(__props, { emit: __emit }) {
    const popupRef = ref(null);
    const props = __props;
    const emit = __emit;
    let activeTab = ref(inject("activeTab"));
    let settingsTab = ref(null);
    let isShow = ref({
      state: false,
      type: null
    });
    let tabs = ref([]);
    provide("isShow", isShow);
    provide("settingsTab", settingsTab);
    let settingsTabs = ref({
      tabs: [
        {
          tab: "enabled",
          title: "Отображение"
        },
        {
          tab: "order",
          title: "Порядок"
        }
      ],
      saves: {
        isShow: false
      },
      activeTab: null
    });
    const copyField = (event) => {
      let parentElem = document.createElement("div");
      let elem = event.target.cloneNode(true);
      parentElem.appendChild(elem);
      parentElem.id = "clone-elem";
      parentElem.classList.add("clone-elem");
      parentElem.classList.add("popup-option__draggable");
      elem.style.width = `${event.target.offsetWidth}px`;
      document.body.appendChild(parentElem);
      event.dataTransfer.setDragImage(parentElem, 5, 8);
    };
    const callAction = (data) => {
      const changeSettingsTab = (data2) => {
        setTimeout(() => {
          settingsTabs.value.activeTab = data2;
        }, 10);
      };
      const toggleOpenChild = (data2) => {
        var _a, _b, _c;
        (_c = (_b = (_a = popupRef.value) == null ? void 0 : _a[0]) == null ? void 0 : _b.popupRef) == null ? void 0 : _c.setAttribute("open", "");
        data2.open = !(data2 == null ? void 0 : data2.open);
      };
      const closeAllChild = () => {
        var _a;
        for (let tab of tabs.value) {
          if (((_a = tab.childs) == null ? void 0 : _a.length) > 0) {
            for (let child of tab.childs) {
              child.open = false;
            }
          }
        }
      };
      const saveSettings = (data2) => {
        const transformTabs = () => {
          let request = JSON.parse(JSON.stringify(tabs.value));
          for (let tab of request) {
            if (!tab.has_roles_read) {
              tab.roles_read = [];
            }
          }
          return request;
        };
        emit("callAction", {
          action: "saveSettings",
          value: {
            fields: transformTabs(),
            role: data2
          }
        });
        isShow.value.state = false;
        settingsTabs.value.saves.isShow = false;
      };
      const changeValue = (data2) => {
        tabs.value.find((option) => option.tab == data2.key).enabled = data2.value;
        settingsTabs.value.saves.isShow = true;
      };
      const hideTab = (tab) => {
        tabs.value.find((p) => p.id == tab.id).enabled = false;
        settingsTabs.value.saves.isShow = true;
      };
      const changeTab = (data2, type = null) => {
        var _a, _b, _c;
        activeTab.value.tab = data2;
        activeTab.value.type = type;
        callAction({ action: "closeAllChild" });
        (_c = (_b = (_a = popupRef.value) == null ? void 0 : _a[0]) == null ? void 0 : _b.popupRef) == null ? void 0 : _c.removeAttribute("open");
        emit("callAction", {
          action: "changeTab",
          value: activeTab.value.tab,
          type
        });
      };
      const settingsDragEnd = () => {
        settingsTabs.value.saves.isShow = true;
        document.querySelectorAll("#clone-elem").forEach((element) => {
          element.remove();
        });
      };
      switch (data.action) {
        case "saveSettings":
          saveSettings(data.value);
          break;
        case "toggleOpenChild":
          toggleOpenChild(data.value);
          break;
        case "closeAllChild":
          closeAllChild();
          break;
        case "changeSettingsTab":
          changeSettingsTab(data.value);
          break;
        case "changeValue":
          changeValue(data.value);
          break;
        case "dragEnd":
          settingsDragEnd(data.value);
          break;
        case "changeTab":
          changeTab(data.value, data.type);
          break;
        case "hideTab":
          hideTab(data.value);
          break;
      }
    };
    const showSettingsTab = (tab) => {
      isShow.value = {
        type: "updateTabs",
        state: true
      };
      settingsTab.value = tab;
    };
    const setTabs = () => {
      tabs.value = props.tabs;
      activeTab.value.tab = Array.isArray(tabs.value) ? tabs.value.length > 0 ? tabs.value[0].tab : null : null;
    };
    watch(
      () => props.tabs,
      () => {
        setTabs();
      },
      {
        deep: true
      }
    );
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "tabs" }, _attrs))}><div class="tabs__list"><!--[-->`);
      ssrRenderList(unref(tabs).filter((p) => p.enabled), (tab) => {
        _push(`<!--[-->`);
        if (tab.childs != void 0) {
          _push(ssrRenderComponent(AppPopup, {
            ref_for: true,
            ref_key: "popupRef",
            ref: popupRef,
            class: ["popup__tabs", { tabs__item_active: tab.childs.find((p) => p.alias == unref(activeTab).tab) != void 0, tabs__item_disabled: props.isCanChange == false }],
            closeByClick: false,
            isCanSelect: false,
            onClick: ($event) => !popupRef.value[0].popupRef.hasAttribute("open") ? callAction({ action: "closeAllChild" }) : "",
            onClickOutside: ($event) => callAction({ action: "closeAllChild" })
          }, {
            summary: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<div class="${ssrRenderClass([{ tabs__item_active: _ctx.$route.fullPath.includes(tab.tab), tabs__item_disabled: !props.isCanChange }, "tabs__item"])}"${_scopeId}>`);
                _push2(ssrRenderComponent(IconTriangle, null, null, _parent2, _scopeId));
                _push2(`<p class="tabs__item-text"${_scopeId}>${ssrInterpolate(tab.title)}</p></div>`);
              } else {
                return [
                  createVNode("div", {
                    class: [{ tabs__item_active: _ctx.$route.fullPath.includes(tab.tab), tabs__item_disabled: !props.isCanChange }, "tabs__item"]
                  }, [
                    createVNode(IconTriangle),
                    createVNode("p", { class: "tabs__item-text" }, toDisplayString(tab.title), 1)
                  ], 2)
                ];
              }
            }),
            content: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`<!--[-->`);
                ssrRenderList(tab.childs, (child) => {
                  _push2(`<!--[-->`);
                  if (!tab.childs.find((p) => p == null ? void 0 : p.open) || (child == null ? void 0 : child.open)) {
                    _push2(ssrRenderComponent(PopupOption, {
                      class: { popup__option_active: child.alias == unref(activeTab).tab, popup__option_open: child == null ? void 0 : child.open },
                      onClick: () => {
                        var _a;
                        return ((_a = child.childs) == null ? void 0 : _a.length) > 0 ? callAction({ action: "toggleOpenChild", value: child }) : callAction({ action: "changeTab", value: child.alias, type: "module" });
                      }
                    }, {
                      default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                        var _a, _b;
                        if (_push3) {
                          _push3(`<span${_scopeId2}>${ssrInterpolate(child.title)}</span>`);
                          if (((_a = child.childs) == null ? void 0 : _a.length) > 0) {
                            _push3(ssrRenderComponent(IconArrow, { class: "popup__arrow" }, null, _parent3, _scopeId2));
                          } else {
                            _push3(`<!---->`);
                          }
                        } else {
                          return [
                            createVNode("span", null, toDisplayString(child.title), 1),
                            ((_b = child.childs) == null ? void 0 : _b.length) > 0 ? (openBlock(), createBlock(IconArrow, {
                              key: 0,
                              class: "popup__arrow"
                            })) : createCommentVNode("", true)
                          ];
                        }
                      }),
                      _: 2
                    }, _parent2, _scopeId));
                  } else {
                    _push2(`<!---->`);
                  }
                  if (child == null ? void 0 : child.open) {
                    _push2(`<!--[-->`);
                    ssrRenderList(child == null ? void 0 : child.childs, (kid) => {
                      _push2(ssrRenderComponent(PopupOption, {
                        class: kid.alias == unref(activeTab).tab ? "popup__option_active" : "",
                        onClick: () => callAction({ action: "changeTab", value: kid.alias, type: "module" })
                      }, {
                        default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                          if (_push3) {
                            _push3(`${ssrInterpolate(kid.title)}`);
                          } else {
                            return [
                              createTextVNode(toDisplayString(kid.title), 1)
                            ];
                          }
                        }),
                        _: 2
                      }, _parent2, _scopeId));
                    });
                    _push2(`<!--]-->`);
                  } else {
                    _push2(`<!---->`);
                  }
                  _push2(`<!--]-->`);
                });
                _push2(`<!--]-->`);
              } else {
                return [
                  (openBlock(true), createBlock(Fragment, null, renderList(tab.childs, (child) => {
                    return openBlock(), createBlock(Fragment, null, [
                      !tab.childs.find((p) => p == null ? void 0 : p.open) || (child == null ? void 0 : child.open) ? (openBlock(), createBlock(PopupOption, {
                        key: 0,
                        class: { popup__option_active: child.alias == unref(activeTab).tab, popup__option_open: child == null ? void 0 : child.open },
                        onClick: () => {
                          var _a;
                          return ((_a = child.childs) == null ? void 0 : _a.length) > 0 ? callAction({ action: "toggleOpenChild", value: child }) : callAction({ action: "changeTab", value: child.alias, type: "module" });
                        }
                      }, {
                        default: withCtx(() => {
                          var _a;
                          return [
                            createVNode("span", null, toDisplayString(child.title), 1),
                            ((_a = child.childs) == null ? void 0 : _a.length) > 0 ? (openBlock(), createBlock(IconArrow, {
                              key: 0,
                              class: "popup__arrow"
                            })) : createCommentVNode("", true)
                          ];
                        }),
                        _: 2
                      }, 1032, ["class", "onClick"])) : createCommentVNode("", true),
                      (child == null ? void 0 : child.open) ? (openBlock(true), createBlock(Fragment, { key: 1 }, renderList(child == null ? void 0 : child.childs, (kid) => {
                        return openBlock(), createBlock(PopupOption, {
                          class: kid.alias == unref(activeTab).tab ? "popup__option_active" : "",
                          onClick: () => callAction({ action: "changeTab", value: kid.alias, type: "module" })
                        }, {
                          default: withCtx(() => [
                            createTextVNode(toDisplayString(kid.title), 1)
                          ]),
                          _: 2
                        }, 1032, ["class", "onClick"]);
                      }), 256)) : createCommentVNode("", true)
                    ], 64);
                  }), 256))
                ];
              }
            }),
            _: 2
          }, _parent));
        } else {
          _push(`<div class="${ssrRenderClass([{ tabs__item_active: unref(activeTab).tab == tab.tab, tabs__item_disabled: !props.isCanChange }, "tabs__item"])}"><span class="tabs__item-text">${ssrInterpolate(tab.title)}</span>`);
          if (props.haveSettings && Boolean(props.is_admin)) {
            _push(ssrRenderComponent(AppPopup, {
              class: "popup__actions",
              isCanSelect: false,
              closeByClick: true
            }, {
              summary: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(IconSettings, null, null, _parent2, _scopeId));
                } else {
                  return [
                    createVNode(IconSettings)
                  ];
                }
              }),
              content: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(ssrRenderComponent(PopupOption, {
                    onClick: () => showSettingsTab(tab)
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(` Настроить `);
                      } else {
                        return [
                          createTextVNode(" Настроить ")
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                  _push2(ssrRenderComponent(PopupOption, {
                    onClick: () => callAction({ action: "hideTab", value: tab })
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(` Скрыть `);
                      } else {
                        return [
                          createTextVNode(" Скрыть ")
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                } else {
                  return [
                    createVNode(PopupOption, {
                      onClick: () => showSettingsTab(tab)
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Настроить ")
                      ]),
                      _: 2
                    }, 1032, ["onClick"]),
                    createVNode(PopupOption, {
                      onClick: () => callAction({ action: "hideTab", value: tab })
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" Скрыть ")
                      ]),
                      _: 2
                    }, 1032, ["onClick"])
                  ];
                }
              }),
              _: 2
            }, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`</div>`);
        }
        _push(`<!--]-->`);
      });
      _push(`<!--]--></div>`);
      if (__props.isShowActions) {
        _push(`<div class="tabs__actions">`);
        _push(ssrRenderComponent(PopupSave, {
          style: unref(settingsTabs).saves.isShow ? null : { display: "none" },
          onSaveSettings: (role) => callAction({ action: "saveSettings", value: role })
        }, null, _parent));
        _push(ssrRenderComponent(AppPopup, {
          class: "popup__settings",
          isCanSelect: false,
          closeByClick: false,
          onClickOutside: () => callAction({ action: "changeSettingsTab", value: null })
        }, {
          summary: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(IconSettings, null, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(IconSettings)
              ];
            }
          }),
          content: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              if (unref(settingsTabs).activeTab == null) {
                _push2(`<!--[-->`);
                ssrRenderList(unref(settingsTabs).tabs, (tab) => {
                  _push2(ssrRenderComponent(PopupOption, {
                    class: "popup-option__sublink",
                    onClick: () => callAction({ action: "changeSettingsTab", value: tab })
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(tab.title)} `);
                        _push3(ssrRenderComponent(IconArrow, null, null, _parent3, _scopeId2));
                      } else {
                        return [
                          createTextVNode(toDisplayString(tab.title) + " ", 1),
                          createVNode(IconArrow)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                });
                _push2(`<!--]-->`);
              } else {
                _push2(`<!--[-->`);
                _push2(ssrRenderComponent(PopupOption, {
                  class: "popup-option__sublink popup-option__sublink_back",
                  onClick: () => callAction({ action: "changeSettingsTab", value: null })
                }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(`${ssrInterpolate(unref(settingsTabs).activeTab.title)} `);
                      _push3(ssrRenderComponent(IconArrow, null, null, _parent3, _scopeId2));
                    } else {
                      return [
                        createTextVNode(toDisplayString(unref(settingsTabs).activeTab.title) + " ", 1),
                        createVNode(IconArrow)
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
                if (unref(settingsTabs).activeTab.tab == "order") {
                  _push2(ssrRenderComponent(unref(draggable), {
                    ref: "draggableRef",
                    class: "popup-option__draggable",
                    group: "popup-menu__settings",
                    itemKey: "settings-visible",
                    modelValue: unref(tabs),
                    "onUpdate:modelValue": ($event) => isRef(tabs) ? tabs.value = $event : tabs = $event,
                    handle: ".icon__draggable",
                    onEnd: (event) => callAction({ action: "dragEnd", value: event })
                  }, {
                    item: withCtx(({ element: option }, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(ssrRenderComponent(PopupOption, {
                          onDragstart: (event) => copyField(event),
                          class: "popup-option__sublink",
                          style: option.enabled ? null : { display: "none" }
                        }, {
                          default: withCtx((_2, _push4, _parent4, _scopeId3) => {
                            if (_push4) {
                              _push4(ssrRenderComponent(IconDrag, null, null, _parent4, _scopeId3));
                              _push4(` ${ssrInterpolate(option.title)}`);
                            } else {
                              return [
                                createVNode(IconDrag),
                                createTextVNode(" " + toDisplayString(option.title), 1)
                              ];
                            }
                          }),
                          _: 2
                        }, _parent3, _scopeId2));
                      } else {
                        return [
                          withDirectives(createVNode(PopupOption, {
                            onDragstart: (event) => copyField(event),
                            class: "popup-option__sublink"
                          }, {
                            default: withCtx(() => [
                              createVNode(IconDrag),
                              createTextVNode(" " + toDisplayString(option.title), 1)
                            ]),
                            _: 2
                          }, 1032, ["onDragstart"]), [
                            [vShow, option.enabled]
                          ])
                        ];
                      }
                    }),
                    _: 1
                  }, _parent2, _scopeId));
                } else {
                  _push2(`<!--[-->`);
                  ssrRenderList(unref(tabs), (option) => {
                    _push2(ssrRenderComponent(PopupOption, { class: "popup__option_checkbox" }, {
                      default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                        if (_push3) {
                          _push3(ssrRenderComponent(AppCheckbox, {
                            item: {
                              id: option.id,
                              title: option.title,
                              type: "checkbox",
                              disabled: false,
                              value: option.enabled,
                              options: null,
                              key: option.tab
                            },
                            onChangeValue: (data) => callAction({ action: "changeValue", value: data })
                          }, null, _parent3, _scopeId2));
                        } else {
                          return [
                            createVNode(AppCheckbox, {
                              item: {
                                id: option.id,
                                title: option.title,
                                type: "checkbox",
                                disabled: false,
                                value: option.enabled,
                                options: null,
                                key: option.tab
                              },
                              onChangeValue: (data) => callAction({ action: "changeValue", value: data })
                            }, null, 8, ["item", "onChangeValue"])
                          ];
                        }
                      }),
                      _: 2
                    }, _parent2, _scopeId));
                  });
                  _push2(`<!--]-->`);
                }
                _push2(`<!--]-->`);
              }
            } else {
              return [
                unref(settingsTabs).activeTab == null ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(unref(settingsTabs).tabs, (tab) => {
                  return openBlock(), createBlock(PopupOption, {
                    class: "popup-option__sublink",
                    onClick: () => callAction({ action: "changeSettingsTab", value: tab })
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(tab.title) + " ", 1),
                      createVNode(IconArrow)
                    ]),
                    _: 2
                  }, 1032, ["onClick"]);
                }), 256)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                  createVNode(PopupOption, {
                    class: "popup-option__sublink popup-option__sublink_back",
                    onClick: () => callAction({ action: "changeSettingsTab", value: null })
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(unref(settingsTabs).activeTab.title) + " ", 1),
                      createVNode(IconArrow)
                    ]),
                    _: 1
                  }, 8, ["onClick"]),
                  unref(settingsTabs).activeTab.tab == "order" ? (openBlock(), createBlock(unref(draggable), {
                    key: 0,
                    ref: "draggableRef",
                    class: "popup-option__draggable",
                    group: "popup-menu__settings",
                    itemKey: "settings-visible",
                    modelValue: unref(tabs),
                    "onUpdate:modelValue": ($event) => isRef(tabs) ? tabs.value = $event : tabs = $event,
                    handle: ".icon__draggable",
                    onEnd: (event) => callAction({ action: "dragEnd", value: event })
                  }, {
                    item: withCtx(({ element: option }) => [
                      withDirectives(createVNode(PopupOption, {
                        onDragstart: (event) => copyField(event),
                        class: "popup-option__sublink"
                      }, {
                        default: withCtx(() => [
                          createVNode(IconDrag),
                          createTextVNode(" " + toDisplayString(option.title), 1)
                        ]),
                        _: 2
                      }, 1032, ["onDragstart"]), [
                        [vShow, option.enabled]
                      ])
                    ]),
                    _: 1
                  }, 8, ["modelValue", "onUpdate:modelValue", "onEnd"])) : (openBlock(true), createBlock(Fragment, { key: 1 }, renderList(unref(tabs), (option) => {
                    return openBlock(), createBlock(PopupOption, { class: "popup__option_checkbox" }, {
                      default: withCtx(() => [
                        createVNode(AppCheckbox, {
                          item: {
                            id: option.id,
                            title: option.title,
                            type: "checkbox",
                            disabled: false,
                            value: option.enabled,
                            options: null,
                            key: option.tab
                          },
                          onChangeValue: (data) => callAction({ action: "changeValue", value: data })
                        }, null, 8, ["item", "onChangeValue"])
                      ]),
                      _: 2
                    }, 1024);
                  }), 256))
                ], 64))
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(AppWarning, {
        onCallAction: (data) => callAction(data)
      }, null, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$d = _sfc_main$d.setup;
_sfc_main$d.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTabs/Tabs.vue");
  return _sfc_setup$d ? _sfc_setup$d(props, ctx) : void 0;
};
const AppTabs = _sfc_main$d;
const _sfc_main$c = {
  __name: "MenuLink",
  __ssrInlineRender: true,
  props: {
    children: {
      default: [],
      type: Array,
      required: false
    },
    item: {
      default: {},
      type: Object,
      required: true
    }
  },
  setup(__props) {
    let activeTab = ref({
      type: null,
      tab: "mkad"
    });
    provide("activeTab", activeTab);
    const changeTab = async (tab) => {
      activeTab.value.tab = tab.value;
      await navigateTo(tab.value);
    };
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_NuxtLink = __nuxt_component_0;
      const _component_AppTabs = AppTabs;
      if (((_a = props.item.childs) == null ? void 0 : _a.length) <= 0) {
        _push(ssrRenderComponent(_component_NuxtLink, mergeProps(_ctx.$attrs, _attrs), {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              ssrRenderSlot(_ctx.$slots, "default", {}, null, _push2, _parent2, _scopeId);
            } else {
              return [
                renderSlot(_ctx.$slots, "default")
              ];
            }
          }),
          _: 3
        }, _parent));
      } else {
        _push(ssrRenderComponent(_component_AppTabs, mergeProps({
          tabs: [props.item],
          isShowActions: false,
          isShowBlueArrow: true,
          onCallAction: (tab) => changeTab(tab)
        }, _attrs), null, _parent));
      }
    };
  }
};
const _sfc_setup$c = _sfc_main$c.setup;
_sfc_main$c.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppMenu/Desktop/components/MenuLink/MenuLink.vue");
  return _sfc_setup$c ? _sfc_setup$c(props, ctx) : void 0;
};
const MenuLink = _sfc_main$c;
const _sfc_main$b = {
  __name: "Gamburger",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__gamburger" }, _attrs))}><svg xmlns="http://www.w3.org/2000/svg" width="15" height="12" viewBox="0 0 15 12"><path d="M1 0h13a1 1 0 1 1 0 2H1a1 1 0 0 1 0-2zm0 5h13a1 1 0 0 1 0 2H1a1 1 0 1 1 0-2zm0 5h13a1 1 0 0 1 0 2H1a1 1 0 1 1 0-2z" fill="#A6B7D4" fill-rule="evenodd"></path></svg></figure>`);
    };
  }
};
const _sfc_setup$b = _sfc_main$b.setup;
_sfc_main$b.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Gamburger/Gamburger.vue");
  return _sfc_setup$b ? _sfc_setup$b(props, ctx) : void 0;
};
const IconGamburger = _sfc_main$b;
const _sfc_main$a = {
  __name: "H2",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<h2${ssrRenderAttrs(_attrs)}>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</h2>`);
    };
  }
};
const _sfc_setup$a = _sfc_main$a.setup;
_sfc_main$a.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppHeaders/H2/H2.vue");
  return _sfc_setup$a ? _sfc_setup$a(props, ctx) : void 0;
};
const AppH2 = _sfc_main$a;
const _sfc_main$9 = {
  __name: "Mobile",
  __ssrInlineRender: true,
  setup(__props) {
    let settingsMenu = ref({
      isShow: false,
      activeTab: null,
      parentTab: null
    });
    provide("settingsMenu", settingsMenu);
    const menu = inject("menu");
    const callAction = (data) => {
      const showMenu = (state) => {
        if (!state) {
          settingsMenu.value.activeTab = null;
        }
        settingsMenu.value.isShow = state;
        if (state) {
          document.body.classList.add("body_uncscroll");
        } else {
          document.body.classList.remove("body_uncscroll");
        }
      };
      const navigateMenu = (value, parent) => {
        if (parent) {
          settingsMenu.value.parentTab = settingsMenu.value.activeTab;
        }
        if (value == false) {
          showMenu(false);
        } else {
          settingsMenu.value.activeTab = value;
        }
      };
      switch (data.action) {
        case "showMenu":
          showMenu(data.value);
          break;
        case "navigateMenu":
          navigateMenu(data.value, data.parent);
          break;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c;
      _push(`<aside${ssrRenderAttrs(mergeProps({ class: "menu menu_mobile" }, _attrs))}>`);
      _push(ssrRenderComponent(IconGamburger, {
        class: "menu__gamburger",
        onClick: () => callAction({ action: "showMenu", value: true })
      }, null, _parent));
      _push(`<div class="menu__content" style="${ssrRenderStyle(unref(settingsMenu).isShow ? null : { display: "none" })}">`);
      _push(ssrRenderComponent(AppH2, {
        class: "menu__nav-back",
        onClick: () => {
          callAction({ action: "navigateMenu", value: unref(settingsMenu).activeTab == null ? false : unref(settingsMenu).parentTab ? unref(settingsMenu).parentTab : null });
          unref(settingsMenu).parentTab = null;
        }
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(IconArrow, null, null, _parent2, _scopeId));
            _push2(` ${ssrInterpolate(unref(settingsMenu).activeTab == null ? "Меню" : unref(settingsMenu).activeTab.title)}`);
          } else {
            return [
              createVNode(IconArrow),
              createTextVNode(" " + toDisplayString(unref(settingsMenu).activeTab == null ? "Меню" : unref(settingsMenu).activeTab.title), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      if (unref(settingsMenu).activeTab == null) {
        _push(`<nav class="menu__list"><!--[-->`);
        ssrRenderList(unref(menu), (item) => {
          var _a2, _b2;
          _push(`<!--[-->`);
          if (item == null ? void 0 : item.line) {
            _push(`<hr class="menu__line">`);
          } else {
            _push(`<a class="${ssrRenderClass([{ menu__item_active: ((_a2 = item == null ? void 0 : item.childs) == null ? void 0 : _a2.length) > 0 && _ctx.$route.fullPath.includes(item.tab) || _ctx.$route.fullPath === item.tab }, "menu__item"])}" style="${ssrRenderStyle(item.enabled ? null : { display: "none" })}">${ssrInterpolate(item.title)} `);
            if (((_b2 = item == null ? void 0 : item.childs) == null ? void 0 : _b2.length) > 0) {
              _push(ssrRenderComponent(IconArrow, null, null, _parent));
            } else {
              _push(`<!---->`);
            }
            _push(`</a>`);
          }
          _push(`<!--]-->`);
        });
        _push(`<!--]--></nav>`);
      } else {
        _push(`<!---->`);
      }
      if (((_c = (_b = (_a = unref(settingsMenu)) == null ? void 0 : _a.activeTab) == null ? void 0 : _b.childs) == null ? void 0 : _c.length) > 0) {
        _push(`<nav class="menu__list"><!--[-->`);
        ssrRenderList(unref(settingsMenu).activeTab.childs, (child) => {
          var _a2, _b2;
          _push(`<a${ssrRenderAttr("to", child.alias)} class="${ssrRenderClass([{ menu__item_active: ((_a2 = child == null ? void 0 : child.childs) == null ? void 0 : _a2.length) > 0 && _ctx.$route.fullPath.includes(child.alias) || _ctx.$route.fullPath === child.alias }, "menu__item"])}" style="${ssrRenderStyle(child.enabled ? null : { display: "none" })}">${ssrInterpolate(child.title)} `);
          if (((_b2 = child == null ? void 0 : child.childs) == null ? void 0 : _b2.length) > 0) {
            _push(ssrRenderComponent(IconArrow, null, null, _parent));
          } else {
            _push(`<!---->`);
          }
          _push(`</a>`);
        });
        _push(`<!--]--></nav>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div></aside>`);
    };
  }
};
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppMenu/Mobile/Mobile.vue");
  return _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const MobileMenu = _sfc_main$9;
const _sfc_main$8 = {
  __name: "Desktop",
  __ssrInlineRender: true,
  emits: ["callAction"],
  setup(__props, { emit: __emit }) {
    const route = useRoute();
    const menuRef = ref(null);
    const menu = inject("menu");
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<header${ssrRenderAttrs(mergeProps({
        class: "menu menu_desktop",
        ref_key: "menuRef",
        ref: menuRef
      }, _attrs))}><div class="container">`);
      if (!unref(route).fullPath.includes("/auth")) {
        _push(`<div class="menu__links">`);
        _push(ssrRenderComponent(MobileMenu, null, null, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: unref(menu).length > 0 ? unref(menu)[0].tab : "/",
          class: "menu__logo"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(IconLogo, null, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(IconLogo)
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<div class="menu__content menu__list_desktop"><nav class="menu__list menu__list_desktop">`);
        if (!((_a = _ctx.item) == null ? void 0 : _a.line)) {
          _push(`<!--[-->`);
          ssrRenderList(unref(menu), (item) => {
            var _a2;
            _push(ssrRenderComponent(MenuLink, {
              style: !item.is_hidden ? null : { display: "none" },
              class: ["menu__item", { menu__item_children: ((_a2 = item == null ? void 0 : item.childs) == null ? void 0 : _a2.length) > 0 }],
              to: item == null ? void 0 : item.tab,
              key: item == null ? void 0 : item.id,
              item
            }, {
              default: withCtx((_, _push2, _parent2, _scopeId) => {
                if (_push2) {
                  _push2(`${ssrInterpolate(item == null ? void 0 : item.title)}`);
                } else {
                  return [
                    createTextVNode(toDisplayString(item == null ? void 0 : item.title), 1)
                  ];
                }
              }),
              _: 2
            }, _parent));
          });
          _push(`<!--]-->`);
        } else {
          _push(`<!---->`);
        }
        _push(`</nav></div></div>`);
      } else {
        _push(`<!---->`);
      }
      if (!unref(route).fullPath.includes("/auth")) {
        _push(`<div class="menu__actions"><div class="menu__buttons">`);
        _push(ssrRenderComponent(_component_NuxtLink, {
          class: "button-text",
          to: "/auth/accounts"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` Войти `);
            } else {
              return [
                createTextVNode(" Войти ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(ssrRenderComponent(_component_NuxtLink, {
          class: "menu__registration",
          to: "/auth/registration"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(AppButton, { class: "button_blue" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` Регистрация `);
                  } else {
                    return [
                      createTextVNode(" Регистрация ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(AppButton, { class: "button_blue" }, {
                  default: withCtx(() => [
                    createTextVNode(" Регистрация ")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</div></div>`);
      } else {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: unref(menu).length > 0 ? unref(menu)[0].tab : "/",
          class: "menu__logo menu__logo_centered"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(IconLogo, null, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(IconLogo)
              ];
            }
          }),
          _: 1
        }, _parent));
      }
      _push(`</div></header>`);
    };
  }
};
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppMenu/Desktop/Desktop.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const MenuDesktop = _sfc_main$8;
const menuItems = [
  {
    id: 1,
    title: "Главная",
    slug: "main",
    sort: 0,
    tab: "/",
    is_hidden: 1,
    enabled: 1,
    childs: []
  },
  {
    id: 2,
    title: "Продукты",
    slug: "main",
    sort: 0,
    tab: "products",
    is_hidden: 0,
    enabled: 1,
    childs: [
      {
        id: 3,
        title: "Проверка штрафов",
        slug: "main",
        sort: 0,
        is_hidden: 0,
        enabled: 1,
        alias: "/products/fines"
      },
      {
        id: 12,
        title: "Расчет расстояния",
        slug: "main",
        sort: 0,
        is_hidden: 0,
        enabled: 1,
        alias: "/products/distance",
        childs: [
          {
            id: 13,
            title: "За МКАД",
            slug: "main",
            sort: 0,
            alias: "/products/distance/mkad",
            is_hidden: 0,
            enabled: 1,
            childs: []
          },
          {
            id: 14,
            title: "За КАД",
            slug: "main",
            sort: 0,
            alias: "/products/distance/kad",
            is_hidden: 0,
            enabled: 1,
            childs: []
          }
        ]
      }
    ]
  },
  {
    id: 15,
    title: "Тарифы",
    slug: "main",
    sort: 0,
    tab: "/tariffs",
    is_hidden: 0,
    enabled: 1,
    childs: []
  },
  {
    id: 16,
    title: "Контакты",
    slug: "auth",
    sort: 0,
    tab: "/contacts",
    is_hidden: 0,
    enabled: 1,
    childs: []
  },
  {
    id: 17,
    title: "Войти",
    slug: "auth",
    sort: 0,
    tab: "/auth/entry",
    is_hidden: 1,
    enabled: 1,
    childs: []
  },
  {
    id: 18,
    title: "Регистрация",
    slug: "auth",
    sort: 0,
    tab: "/auth/registration",
    is_hidden: 1,
    enabled: 1,
    childs: []
  },
  {
    id: 20,
    title: "Вопрос-ответ",
    slug: "auth",
    sort: 0,
    tab: "/questions",
    is_hidden: 1,
    enabled: 1,
    childs: []
  },
  {
    id: 21,
    title: "Статьи",
    slug: "auth",
    sort: 0,
    tab: "/articles",
    is_hidden: 1,
    enabled: 1,
    childs: []
  },
  {
    id: 19,
    title: "Документы",
    slug: "auth",
    sort: 0,
    tab: "/docs",
    is_hidden: 1,
    enabled: 1,
    childs: []
  }
];
const _sfc_main$7 = {
  __name: "AppMenu",
  __ssrInlineRender: true,
  props: {
    isShowDesktop: {
      type: Boolean,
      default: true
    },
    isShowMobile: {
      type: Boolean,
      default: true
    }
  },
  emits: ["callAction"],
  setup(__props, { emit: __emit }) {
    const props = __props;
    let menu = ref(menuItems);
    provide("menu", menu);
    return (_ctx, _push, _parent, _attrs) => {
      if (props.isShowDesktop) {
        _push(ssrRenderComponent(MenuDesktop, mergeProps({
          onCallAction: (data) => _ctx.callAction(data)
        }, _attrs), null, _parent));
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppMenu/AppMenu.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const AppMenu = _sfc_main$7;
const telegram = "" + __publicAssetsURL("footer/telegram.svg");
const vk = "" + __publicAssetsURL("footer/vk.svg");
const youtube = "" + __publicAssetsURL("footer/youtube.svg");
const _sfc_main$6 = {
  __name: "Footer",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    let icons = [
      {
        name: "Телеграм",
        icon: telegram,
        link: "https://t.me/compas_pro"
      },
      {
        name: "ВКонтакте",
        icon: vk,
        link: "https://vk.com/cmps_pr"
      },
      {
        name: "Youtube",
        icon: youtube,
        link: "https://www.youtube.com/@cmps-pro"
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<footer${ssrRenderAttrs(mergeProps({ class: "footer" }, _attrs))}><div class="container"><div class="footer__column">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "footer__logo"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(IconLogo, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(IconLogo)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="footer__desc">Компас Про - удобное управление транспортом</div><div class="footer__link footer-link"><a href="mailto:info@compas.pro" class="footer-link__href footer-link__href_bold"> info@compas.pro </a></div><div class="footer__link footer-link"><div class="footer-link__title">Для закрывающих документов</div><a href="mailto:info@compas.pro" class="footer-link__href footer-link__href_bold"> buh@compas.pro </a></div><div class="footer__icons"><!--[-->`);
      ssrRenderList(unref(icons), (icon) => {
        _push(ssrRenderComponent(_component_NuxtLink, {
          to: icon.link
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<figure class="ibg footer__icon"${_scopeId}><img${ssrRenderAttr("src", icon.icon)}${ssrRenderAttr("alt", `${icon.name}. Иконка`)}${_scopeId}></figure>`);
            } else {
              return [
                createVNode("figure", { class: "ibg footer__icon" }, [
                  createVNode("img", {
                    src: icon.icon,
                    alt: `${icon.name}. Иконка`
                  }, null, 8, ["src", "alt"])
                ])
              ];
            }
          }),
          _: 2
        }, _parent));
      });
      _push(`<!--]--></div>`);
      if (unref(route).fullPath.includes("/products/fines")) {
        _push(`<div class="footer-link__title"> Оплата штрафов ГИБДД осуществляется НКО «МОНЕТА.РУ» (ООО). Лицензия ЦБ РФ №3508-К от 2 июля 2012 года. </div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="footer__column footer__column_possibilities"><div class="footer__title">Возможности сервиса</div><li class="footer__list">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/products/fines",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Отслеживание штрафов `);
          } else {
            return [
              createTextVNode(" Отслеживание штрафов ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/products/distance",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Расчёт расстояния `);
          } else {
            return [
              createTextVNode(" Расчёт расстояния ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</li></div><div class="footer__column"><div class="footer__title">Карта сайта</div><nav class="footer__list">`);
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Главная страница `);
          } else {
            return [
              createTextVNode(" Главная страница ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/tariffs",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Тарифы `);
          } else {
            return [
              createTextVNode(" Тарифы ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/contacts",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Контакты `);
          } else {
            return [
              createTextVNode(" Контакты ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/auth/entry",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Войти `);
          } else {
            return [
              createTextVNode(" Войти ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/auth/registration",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Регистрация `);
          } else {
            return [
              createTextVNode(" Регистрация ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/questions",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Вопрос-ответ `);
          } else {
            return [
              createTextVNode(" Вопрос-ответ ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/articles",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Статьи `);
          } else {
            return [
              createTextVNode(" Статьи ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/knowledge",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` База знаний `);
          } else {
            return [
              createTextVNode(" База знаний ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/docs",
        class: "footer-link__href"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Документы `);
          } else {
            return [
              createTextVNode(" Документы ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</nav></div></div></footer>`);
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppFooter/Footer.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const AppFooter = _sfc_main$6;
const _sfc_main$5 = {
  __name: "app",
  __ssrInlineRender: true,
  setup(__props) {
    let route = useRoute();
    return (_ctx, _push, _parent, _attrs) => {
      const _component_AppMain = AppMain;
      const _component_NuxtPage = __nuxt_component_1;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "wrapper" }, _attrs))}><div class="${ssrRenderClass([unref(route).path == "/auth" ? "page_auth" : "", "page"])}">`);
      _push(ssrRenderComponent(AppMenu, { isShowMobile: false }, null, _parent));
      _push(ssrRenderComponent(_component_AppMain, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(_component_NuxtPage, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(_component_NuxtPage)
            ];
          }
        }),
        _: 1
      }, _parent));
      if (!unref(route).fullPath.includes("/auth")) {
        _push(ssrRenderComponent(AppFooter, null, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("app.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const AppComponent = _sfc_main$5;
const _imports_0 = "" + __publicAssetsURL("pages/error/404.svg");
const _sfc_main$4 = {
  __name: "H1",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<h1${ssrRenderAttrs(_attrs)}>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</h1>`);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppHeaders/H1/H1.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const AppH1 = _sfc_main$4;
const _sfc_main$3 = {
  __name: "404",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<!--[--><figure class="ibg error__img"><img${ssrRenderAttr("src", _imports_0)} alt="Страница не найдена"></figure>`);
      _push(ssrRenderComponent(AppH1, { class: "error__title" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Страница не найдена, ошибка 404 `);
          } else {
            return [
              createTextVNode(" Страница не найдена, ошибка 404 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "error__link"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Главная страница `);
          } else {
            return [
              createTextVNode(" Главная страница ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ErrorPage/404/404.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const undefinedPage = _sfc_main$3;
const page403 = "" + __publicAssetsURL("pages/error/403.svg");
const _sfc_main$2 = {
  __name: "403",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<!--[--><figure class="ibg error__img"><img${ssrRenderAttr("src", unref(page403))} alt="Доступ к странице ограничен"></figure>`);
      _push(ssrRenderComponent(AppH1, { class: "error__title" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Доступ к странице ограничен, ошибка 403 `);
          } else {
            return [
              createTextVNode(" Доступ к странице ограничен, ошибка 403 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(_component_NuxtLink, {
        to: "/",
        class: "error__link"
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` Главная страница `);
          } else {
            return [
              createTextVNode(" Главная страница ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ErrorPage/403/403.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const restrictedPage = _sfc_main$2;
const _sfc_main$1 = {
  __name: "error",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    const error2 = useError();
    const route = useRoute();
    console.log(route);
    if (route.fullPath != "/404" && route.fullPath != "/403") {
      [__temp, __restore] = withAsyncContext(() => navigateTo("/404")), await __temp, __restore();
    }
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "wrapper" }, _attrs))}><div class="page error">`);
      _push(ssrRenderComponent(AppMenu, null, null, _parent));
      _push(ssrRenderComponent(AppMain, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (unref(error2).statusCode === 403 || unref(route).fullPath.split("/").includes("403")) {
              _push2(ssrRenderComponent(restrictedPage, null, null, _parent2, _scopeId));
            } else if (unref(error2).statusCode === 404 || unref(route).fullPath.split("/").includes("404")) {
              _push2(ssrRenderComponent(undefinedPage, null, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              unref(error2).statusCode === 403 || unref(route).fullPath.split("/").includes("403") ? (openBlock(), createBlock(restrictedPage, { key: 0 })) : unref(error2).statusCode === 404 || unref(route).fullPath.split("/").includes("404") ? (openBlock(), createBlock(undefinedPage, { key: 1 })) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("error.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const ErrorComponent = _sfc_main$1;
const _sfc_main = {
  __name: "nuxt-root",
  __ssrInlineRender: true,
  setup(__props) {
    const IslandRenderer = /* @__PURE__ */ defineAsyncComponent(() => import('./island-renderer-8716ca71.mjs').then((r) => r.default || r));
    const nuxtApp = /* @__PURE__ */ useNuxtApp();
    nuxtApp.deferHydration();
    nuxtApp.ssrContext.url;
    const SingleRenderer = false;
    provide(PageRouteSymbol, useRoute());
    nuxtApp.hooks.callHookWith((hooks) => hooks.map((hook) => hook()), "vue:setup");
    const error2 = useError();
    onErrorCaptured((err, target, info) => {
      nuxtApp.hooks.callHook("vue:error", err, target, info).catch((hookError) => console.error("[nuxt] Error in `vue:error` hook", hookError));
      {
        const p = nuxtApp.runWithContext(() => showError(err));
        onServerPrefetch(() => p);
        return false;
      }
    });
    const islandContext = nuxtApp.ssrContext.islandContext;
    return (_ctx, _push, _parent, _attrs) => {
      ssrRenderSuspense(_push, {
        default: () => {
          if (unref(error2)) {
            _push(ssrRenderComponent(unref(ErrorComponent), { error: unref(error2) }, null, _parent));
          } else if (unref(islandContext)) {
            _push(ssrRenderComponent(unref(IslandRenderer), { context: unref(islandContext) }, null, _parent));
          } else if (unref(SingleRenderer)) {
            ssrRenderVNode(_push, createVNode(resolveDynamicComponent(unref(SingleRenderer)), null, null), _parent);
          } else {
            _push(ssrRenderComponent(unref(AppComponent), null, null, _parent));
          }
        },
        _: 1
      });
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("node_modules/nuxt/dist/app/components/nuxt-root.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const RootComponent = _sfc_main;
if (!globalThis.$fetch) {
  globalThis.$fetch = $fetch.create({
    baseURL: baseURL()
  });
}
let entry;
{
  entry = async function createNuxtAppServer(ssrContext) {
    const vueApp = createApp(RootComponent);
    const nuxt = createNuxtApp({ vueApp, ssrContext });
    try {
      await applyPlugins(nuxt, plugins);
      await nuxt.hooks.callHook("app:created", vueApp);
    } catch (err) {
      await nuxt.hooks.callHook("app:error", err);
      nuxt.payload.error = nuxt.payload.error || err;
    }
    if (ssrContext == null ? void 0 : ssrContext._renderResponse) {
      throw new Error("skipping render");
    }
    return vueApp;
  };
}
const entry$1 = (ctx) => entry(ctx);

export { AppH2 as A, AppTabs as B, AppH3 as C, useRequestEvent as D, useRuntimeConfig as E, FormItem as F, PopupSave as G, IconSettings as H, IconTriangle as I, IconDrag as J, IconDelete as K, PopupOption as P, _export_sfc as _, useHead as a, api as b, createError as c, defineStore as d, entry$1 as default, __nuxt_component_0 as e, useNuxtApp as f, AppSelect as g, FormLabel as h, AppPopup as i, AppInput as j, AppButton as k, PopupScripts as l, IconPasswordEye as m, navigateTo as n, AppWarning$1 as o, FormValue as p, commonScripts$1 as q, restrictedPage as r, storeToRefs as s, IconArrow as t, useRoute as u, persistedState as v, useUserStore as w, AppH1 as x, useCommonStore as y, AppCheckbox as z };
//# sourceMappingURL=server.mjs.map
