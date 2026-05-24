import { useSSRContext, ref, provide, watchEffect, computed, unref, inject, onUnmounted, mergeProps } from 'vue';
import { u as useRoute, a as useHead } from './server.mjs';
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttrs, ssrRenderList, ssrRenderAttr, ssrRenderClass } from 'vue/server-renderer';
import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import '../runtime.mjs';
import 'node:http';
import 'node:https';
import 'node:fs';
import 'node:path';
import 'node:async_hooks';
import 'node:url';
import 'unhead';
import '@unhead/shared';
import 'vue-router';
import 'dayjs';
import 'dayjs/plugin/updateLocale.js';
import 'dayjs/plugin/relativeTime.js';
import 'dayjs/plugin/utc.js';
import 'chalk';
import 'pinia-plugin-persistedstate';
import 'maska';
import 'vuedraggable';
import '@mahdikhashan/vue3-click-outside';
import 'axios';
import 'vue3-toastify';

const _sfc_main$2 = {
  __name: "Nav",
  __ssrInlineRender: true,
  setup(__props) {
    inject("personalDocRef");
    inject("personalDocWrapperRef");
    let docsNav = ref([]);
    const docsNavRef = ref(null);
    const headers = ref([]);
    const contentPos = ref(0);
    onUnmounted(() => {
      window.removeEventListener("scroll", throt_funScroll);
    });
    const throt_funScroll = () => {
      let data = [];
      for (let i = 0; i < headers.value.length; i++) {
        if (headers.value[i].getBoundingClientRect().top < 300) {
          data.push(headers.value[i]);
          docsNav.value.find((element) => element.id == headers.value[i].id).isScrolled = true;
        } else {
          docsNav.value.find((element) => element.id == headers.value[i].id).isScrolled = false;
        }
      }
      if (data.length > 0) {
        docsNav.value.forEach((element) => {
          if (element.id == data[data.length - 1].id) {
            element.isActive = true;
          } else {
            element.isActive = false;
          }
        });
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<nav${ssrRenderAttrs(mergeProps({
        class: "personal-docs__nav docs-nav",
        ref_key: "docsNavRef",
        ref: docsNavRef,
        style: `--contentPos: ${unref(contentPos)}px`
      }, _attrs))}><div class="docs-nav__header">\u0421\u043E\u0434\u0435\u0440\u0436\u0430\u043D\u0438\u0435</div><div class="docs-nav__list"><!--[-->`);
      ssrRenderList(unref(docsNav), (item) => {
        _push(`<a${ssrRenderAttr("href", item.link)} class="${ssrRenderClass([[`docs-nav__item_${item.nodeName}`, { "docs-nav__item_scrolled": item.isScrolled, "docs-nav__item_active": item.isActive }], "docs-nav__item"])}">${ssrInterpolate(item.text.replace(/[.,\/#1234567890!]/g, ""))}</a>`);
      });
      _push(`<!--]--></div></nav>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Docs/Content/Nav/Nav.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const DocsNav = _sfc_main$2;
const _sfc_main$1 = {
  __name: "Content",
  __ssrInlineRender: true,
  setup(__props) {
    const activeDoc = ref(null);
    useRoute();
    const personalDocRef = ref(null);
    const personalDocWrapperRef = ref(null);
    provide("personalDocRef", personalDocRef);
    provide("personalDocWrapperRef", personalDocWrapperRef);
    watchEffect(() => {
      if (activeDoc.value) {
        useHead({
          title: activeDoc.value.meta.title + " | Compas.pro",
          meta: [
            {
              name: "description",
              content: activeDoc.value.meta.description
            }
          ]
        });
        return;
      }
      useHead({
        title: "\u0414\u043E\u043A\u0443\u043C\u0435\u043D\u0442\u044B \u0438 \u042E\u0440\u0438\u0434\u0438\u0447\u0435\u0441\u043A\u0430\u044F \u0418\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u0430 Compas.pro | Compas.pro",
        meta: [
          {
            name: "description",
            content: "\u0412 \u0440\u0430\u0437\u0434\u0435\u043B\u0435 \u043F\u0440\u0435\u0434\u0441\u0442\u0430\u0432\u043B\u0435\u043D\u044B \u043F\u0443\u0431\u043B\u0438\u0447\u043D\u0430\u044F \u043E\u0444\u0435\u0440\u0442\u0430, \u043F\u043E\u043B\u0438\u0442\u0438\u043A\u0430 \u043A\u043E\u043D\u0444\u0438\u0434\u0435\u043D\u0446\u0438\u0430\u043B\u044C\u043D\u043E\u0441\u0442\u0438 \u0438 \u0434\u0440\u0443\u0433\u0438\u0435 \u0432\u0430\u0436\u043D\u044B\u0435 \u0434\u043E\u043A\u0443\u043C\u0435\u043D\u0442\u044B \u0434\u043B\u044F \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0435\u0439 \u043F\u0440\u043E\u0435\u043A\u0442\u0430 Compas.pro."
          }
        ]
      });
    });
    let breadcrumbs = ref([
      {
        title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0430",
        link: "/"
      },
      {
        title: "\u0414\u043E\u043A\u0443\u043C\u0435\u043D\u0442\u044B",
        link: `/docs`
      }
    ]);
    const setDate = computed(() => {
      return new Date(activeDoc.value.date).toLocaleDateString("ru-RU", { year: "numeric", month: "2-digit", day: "2-digit" });
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a2, _b2, _c2;
      var _a, _b, _c;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      _push(`<div class="personal-docs"><div class="personal-docs__left">`);
      if (unref(activeDoc) != null) {
        _push(`<div class="personal-docs__header"><div class="personal-docs__text">\u0433. ${ssrInterpolate(unref(activeDoc).city)}</div><div class="personal-docs__text">\u0414\u0430\u0442\u0430 \u0440\u0430\u0437\u043C\u0435\u0449\u0435\u043D\u0438\u044F: ${ssrInterpolate(unref(setDate))}</div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="approved">${(_a2 = (_a = unref(activeDoc)) == null ? void 0 : _a.approved) != null ? _a2 : ""}</div>`);
      if ((_b = unref(activeDoc)) == null ? void 0 : _b.h1) {
        _push(`<div class="personal-docs__title" id="#h1">${(_b2 = (_c = unref(activeDoc)) == null ? void 0 : _c.h1) != null ? _b2 : ""}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<article class="personal-docs__content">${(_c2 = unref(activeDoc) ? unref(activeDoc).content : null) != null ? _c2 : ""}</article></div>`);
      if (unref(activeDoc) != null) {
        _push(ssrRenderComponent(DocsNav, null, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div><!--]-->`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Docs/Content/Content.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const DocsContent = _sfc_main$1;
const _sfc_main = {
  __name: "[doc]",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    useHead({
      link: [
        {
          rel: "canonical",
          href: `https://compas.pro/docs/${route.params.doc}`
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(DocsContent, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/docs/[doc].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_doc_-961996d7.mjs.map
