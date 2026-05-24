import { a as useHead, u as useRoute, A as AppH2, C as AppH3, k as AppButton, w as useUserStore, s as storeToRefs, y as useCommonStore, b as api, x as AppH1, j as AppInput, z as AppCheckbox, _ as _export_sfc, e as __nuxt_component_0 } from './server.mjs';
import { ssrRenderComponent, ssrRenderAttrs, ssrRenderList, ssrRenderAttr, ssrInterpolate, ssrRenderClass, ssrRenderStyle } from 'vue/server-renderer';
import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import { useSSRContext, mergeModels, toRefs, useModel, mergeProps, unref, ref, provide, watch, computed, withCtx, createTextVNode, createVNode, openBlock, createBlock, Fragment, renderList, toDisplayString, isRef, withAsyncContext } from 'vue';
import { A as AppSection } from './AppSection-1ea634ac.mjs';
import { C as CommonProgramm, Y as YoutubeWhite, g as gibdd, p as parking, f as fspp, m as madi, a as mugand, i as infinity, b as mileage, c as protection, n as notification, r as receipt, d as reduction } from './Programm-1c3794ff.mjs';
import { F as FansyBox } from './Validate-398d291a.mjs';
import { C as CommonSocial } from './Social-983a064f.mjs';
import { A as AppTable } from './AppTable-9ca02910.mjs';
import { u as useFinesStore } from './finesStore-67f46f86.mjs';
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
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'devalue';
import '@unhead/ssr';
import './program-f89190ac.mjs';
import 'lodash';
import '@vuepic/vue-datepicker';
import 'vue-accessible-color-picker';
import './Input-3345b1b6.mjs';
import './ButtonText-edbdf3ac.mjs';
import 'lodash/isEqual.js';
import 'lodash/throttle.js';
import './Field-d36cf7e6.mjs';
import './dayjs-ce9ed7b6.mjs';
import 'lodash-es';
import 'lodash/isEmpty.js';

const _sfc_main$8 = {
  __name: "Base",
  __ssrInlineRender: true,
  setup(__props) {
    let fines = [
      {
        id: 0,
        title: "\u0413\u0418\u0411\u0414\u0414",
        desc: "\u041F\u043E\u0438\u0441\u043A \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u0431\u0430\u0437\u0435 \u0413\u0418\u0411\u0414\u0414",
        link: gibdd
      },
      {
        id: 1,
        title: "\u041C\u043E\u0441\u043A\u043E\u0432\u0441\u043A\u0438\u0439 \u043F\u0430\u0440\u043A\u0438\u043D\u0433",
        desc: "\u041F\u043E\u0438\u0441\u043A \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u0431\u0430\u0437\u0435 \u041C\u043E\u0441\u043A\u043E\u0432\u0441\u043A\u043E\u0433\u043E \u043F\u0430\u0440\u043A\u0438\u043D\u0433\u0430",
        link: parking
      },
      {
        id: 4,
        title: "\u0424\u0421\u0421\u041F",
        desc: "\u041F\u043E\u0438\u0441\u043A \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u0431\u0430\u0437\u0435 \u0424\u0435\u0434\u0435\u0440\u0430\u043B\u044C\u043D\u0430\u044F \u0441\u043B\u0443\u0436\u0431\u0430 \u0441\u0443\u0434\u0435\u0431\u043D\u044B\u0445 \u043F\u0440\u0438\u0441\u0442\u0430\u0432\u043E\u0432",
        link: fspp
      },
      {
        id: 3,
        title: "\u041C\u0410\u0414\u0418",
        desc: "\u041F\u043E\u0438\u0441\u043A \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u0431\u0430\u0437\u0435 \u041C\u0410\u0414\u0418",
        link: madi
      },
      {
        id: 3,
        title: "\u041C\u0423\u0413\u0410\u0414\u041D",
        desc: "\u041F\u043E\u0438\u0441\u043A \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u0431\u0430\u0437\u0435 \u0413\u043E\u0441\u0430\u0432\u0442\u043E\u0434\u043E\u0440\u043D\u0430\u0434\u0437\u043E\u0440",
        link: mugand
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "base section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u043E\u0431\u0438\u0440\u0430\u0435\u043C \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0431\u0430\u0437\u0430\u043C `);
                } else {
                  return [
                    createTextVNode(" \u0421\u043E\u0431\u0438\u0440\u0430\u0435\u043C \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0431\u0430\u0437\u0430\u043C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="base__list"${_scopeId}><!--[-->`);
            ssrRenderList(unref(fines), (item) => {
              _push2(`<div class="base__item base-item"${_scopeId}><figure class="ibg base-item__image"${_scopeId}><img${ssrRenderAttr("src", item.link)}${ssrRenderAttr("alt", item.title)}${_scopeId}></figure><div class="base-item__info"${_scopeId}><div class="base-item__title"${_scopeId}>${ssrInterpolate(item.title)}</div><div class="base-item__desc"${_scopeId}>${ssrInterpolate(item.desc)}</div></div></div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u0421\u043E\u0431\u0438\u0440\u0430\u0435\u043C \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0431\u0430\u0437\u0430\u043C ")
                ]),
                _: 1
              }),
              createVNode("div", { class: "base__list" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(fines), (item) => {
                  return openBlock(), createBlock("div", { class: "base__item base-item" }, [
                    createVNode("figure", { class: "ibg base-item__image" }, [
                      createVNode("img", {
                        src: item.link,
                        alt: item.title
                      }, null, 8, ["src", "alt"])
                    ]),
                    createVNode("div", { class: "base-item__info" }, [
                      createVNode("div", { class: "base-item__title" }, toDisplayString(item.title), 1),
                      createVNode("div", { class: "base-item__desc" }, toDisplayString(item.desc), 1)
                    ])
                  ]);
                }), 256))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/FinesList/Base/Base.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const MainBase = _sfc_main$8;
const _sfc_main$7 = {
  __name: "Pluses",
  __ssrInlineRender: true,
  setup(__props) {
    let pluses = [
      {
        id: 0,
        title: "\u041D\u0435\u043E\u0433\u0440\u0430\u043D\u0438\u0447\u0435\u043D\u044B\u0439 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A",
        desc: "\u041C\u043E\u0436\u043D\u043E \u0434\u043E\u0431\u0430\u0432\u0438\u0442\u044C \u043D\u0435\u043E\u0433\u0440\u0430\u043D\u0438\u0447\u0435\u043D\u043D\u043E\u0435 \u043A\u043E\u043B-\u0432\u043E \u043C\u0430\u0448\u0438\u043D \u0438 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u0435\u0439",
        image: infinity
      },
      {
        id: 1,
        title: "\u0421\u043E\u043A\u0440\u0430\u0449\u0435\u043D\u0438\u0435 \u0437\u0430\u0442\u0440\u0430\u0442 \u0434\u043E 50%",
        desc: "\u041C\u0430\u043A\u0441\u0438\u043C\u0430\u043B\u044C\u043D\u043E \u0431\u044B\u0441\u0442\u0440\u043E \u0443\u0437\u043D\u0430\u0435\u0442\u0435 \u043E \u0448\u0442\u0440\u0430\u0444\u0430\u0445 \u0432 \u0430\u0432\u0442\u043E\u043C\u0430\u0442\u0438\u0447\u0435\u0441\u043A\u043E\u043C \u0440\u0435\u0436\u0438\u043C\u0435, \u0442\u0435\u043C \u0441\u0430\u043C\u044B\u043C \u0443 \u0432\u0430\u0441 \u0435\u0441\u0442\u044C \u0432\u0440\u0435\u043C\u044F \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u043F\u043E \u0441\u043A\u0438\u0434\u043A\u0435",
        image: mileage
      },
      {
        id: 2,
        title: "\u0411\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u044C",
        desc: "\u0414\u0430\u043D\u043D\u044B\u0435 \u043F\u0435\u0440\u0435\u0434\u0430\u044E\u0442\u0441\u044F \u0432 \u0437\u0430\u0448\u0438\u0444\u0440\u043E\u0432\u0430\u043D\u043D\u043E\u043C \u0432\u0438\u0434\u0435, \u043E\u043D\u0438 \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u044B \u0442\u043E\u043B\u044C\u043A\u043E \u043F\u043E\u043B\u0443\u0447\u0430\u0442\u0435\u043B\u044E",
        image: protection
      },
      {
        id: 3,
        title: "\u0423\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u0435 \u043E \u043F\u043E\u0433\u0430\u0448\u0435\u043D\u0438\u0438",
        desc: "\u041C\u044B \u043E\u043F\u043E\u0432\u0435\u0441\u0442\u0438\u043C \u0432\u0430\u0441 \u043E \u0442\u043E\u043C, \u0447\u0442\u043E \u0448\u0442\u0440\u0430\u0444 \u0431\u044B\u043B \u043F\u043E\u0433\u0430\u0448\u0435\u043D \u0438 \u0441\u043E\u043E\u0442\u0432\u0435\u0442\u0441\u0442\u0432\u0443\u044E\u0449\u0430\u044F \u0437\u0430\u043F\u0438\u0441\u044C \u0441\u043E\u0437\u0434\u0430\u043D\u0430 \u0432 \u0413\u0418\u0421 \u0413\u041C\u041F",
        image: notification
      },
      {
        id: 4,
        title: "\u041A\u0432\u0438\u0442\u0430\u043D\u0446\u0438\u044F \u043E\u0431 \u043E\u043F\u043B\u0430\u0442\u0435",
        desc: "\u041F\u043E\u0441\u043B\u0435 \u0441\u043E\u0432\u0435\u0440\u0448\u0435\u043D\u0438\u044F \u043E\u043F\u043B\u0430\u0442\u044B \u0431\u0430\u043D\u043A\u043E\u0432\u0441\u043A\u043E\u0439 \u043A\u0430\u0440\u0442\u043E\u0439 \u043D\u0430 \u0412\u0430\u0448\u0443 \u044D\u043B\u0435\u043A\u0442\u0440\u043E\u043D\u043D\u0443\u044E \u043F\u043E\u0447\u0442\u0443 \u043F\u0440\u0438\u0434\u0435\u0442 \u043A\u0432\u0438\u0442\u0430\u043D\u0446\u0438\u044F \u043E\u0431 \u0443\u0441\u043F\u0435\u0448\u043D\u043E\u0439 \u043E\u043F\u043B\u0430\u0442\u0435.",
        image: receipt
      },
      {
        id: 5,
        title: "\u0418\u0441\u0442\u043E\u0440\u0438\u044F \u0448\u0442\u0440\u0430\u0444\u043E\u0432",
        desc: "\u0412\u0441\u044F \u0438\u0441\u0442\u043E\u0440\u0438\u044F \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0441\u043E\u0445\u0440\u0430\u043D\u044F\u0435\u0442\u0441\u044F \u043F\u043E \u043A\u0430\u0436\u0434\u043E\u043C\u0443 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044E \u0438 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044E, \u0447\u0442\u043E \u043F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u0430\u043D\u0430\u043B\u0438\u0437\u0438\u0440\u043E\u0432\u0430\u0442\u044C \u0441\u0442\u0430\u0442\u0438\u0441\u0442\u0438\u043A\u0443 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u0439.",
        image: reduction
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "pluses section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041F\u043B\u044E\u0441\u044B \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u0430 \xAB\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E\xBB \u0434\u043B\u044F \u043F\u043E\u0438\u0441\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 `);
                } else {
                  return [
                    createTextVNode(" \u041F\u043B\u044E\u0441\u044B \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u0430 \xAB\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E\xBB \u0434\u043B\u044F \u043F\u043E\u0438\u0441\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="pluses__list"${_scopeId}><!--[-->`);
            ssrRenderList(unref(pluses), (item) => {
              _push2(`<div class="pluses__item"${_scopeId}><figure class="ibg pluses__icon"${_scopeId}><img${ssrRenderAttr("src", item.image)}${ssrRenderAttr("alt", item.title)}${_scopeId}></figure><div class="pluses__title"${_scopeId}>${ssrInterpolate(item.title)}</div><div class="pluses__desc"${_scopeId}>${ssrInterpolate(item.desc)}</div></div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u041F\u043B\u044E\u0441\u044B \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u0430 \xAB\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E\xBB \u0434\u043B\u044F \u043F\u043E\u0438\u0441\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 ")
                ]),
                _: 1
              }),
              createVNode("div", { class: "pluses__list" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(pluses), (item) => {
                  return openBlock(), createBlock("div", { class: "pluses__item" }, [
                    createVNode("figure", { class: "ibg pluses__icon" }, [
                      createVNode("img", {
                        src: item.image,
                        alt: item.title
                      }, null, 8, ["src", "alt"])
                    ]),
                    createVNode("div", { class: "pluses__title" }, toDisplayString(item.title), 1),
                    createVNode("div", { class: "pluses__desc" }, toDisplayString(item.desc), 1)
                  ]);
                }), 256))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/FinesList/Pluses/Pluses.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const MainPluses = _sfc_main$7;
const _sfc_main$6 = {
  __name: "Step",
  __ssrInlineRender: true,
  props: {
    text: { type: String, required: true },
    name: { type: String, required: true },
    isActive: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const { isActive, name, text } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "step" }, _attrs))}><div class="${ssrRenderClass([{ step__name_active: unref(isActive) }, "step__name"])}">${ssrInterpolate(unref(name))}</div><div class="${ssrRenderClass([{ step__text_active: unref(isActive) }, "step__text"])}">${ssrInterpolate(unref(text))}</div></div>`);
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/FinesList/Steps/components/Step/Step.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const Step = _sfc_main$6;
const _sfc_main$5 = {
  __name: "StepsList",
  __ssrInlineRender: true,
  props: /* @__PURE__ */ mergeModels({
    steps: { type: Array, required: true }
  }, {
    "modelValue": {},
    "modelModifiers": {}
  }),
  emits: ["update:modelValue"],
  setup(__props) {
    const props = __props;
    const { steps: steps2 } = toRefs(props);
    const activeStep = useModel(__props, "modelValue");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "steps__list" }, _attrs))} data-v-e1fda8fa><!--[-->`);
      ssrRenderList(unref(steps2), ({ text, id }) => {
        _push(ssrRenderComponent(Step, {
          name: `\u0428\u0430\u0433 ${id}`,
          text,
          isActive: activeStep.value == id,
          onClick: ($event) => activeStep.value = id
        }, null, _parent));
      });
      _push(`<!--]--></div>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/FinesList/Steps/components/StepsList/StepsList.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const StepsList = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["__scopeId", "data-v-e1fda8fa"]]);
const steps = [
  {
    id: 1,
    text: "\u0423\u043A\u0430\u0436\u0438\u0442\u0435 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044E \u043E\u0431 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u0435, \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u0435 \u0438\u043B\u0438 \u043A\u043E\u043C\u043F\u0430\u043D\u0438\u0438 \u0432 \u0441\u043E\u043E\u0442\u0432\u0435\u0442\u0441\u0442\u0432\u0443\u044E\u0449\u0438\u0445 \u043F\u043E\u043B\u044F\u0445."
  },
  {
    id: 2,
    text: "\u041A\u043B\u0438\u043A\u043D\u0438\u0442\u0435 \u043F\u043E \u043A\u043D\u043E\u043F\u043A\u0435 \xAB\u041E\u0431\u043D\u043E\u0432\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B\xBB."
  },
  {
    id: 3,
    text: "\u041F\u043E\u0434\u043E\u0436\u0434\u0438\u0442\u0435, \u043A\u043E\u0433\u0434\u0430 \u0441\u0444\u043E\u0440\u043C\u0438\u0440\u0443\u0435\u0442\u0441\u044F \u043E\u0442\u0447\u0435\u0442 \u043F\u043E \u0448\u0442\u0440\u0430\u0444\u0430\u043C \u0413\u0418\u0411\u0414\u0414."
  },
  {
    id: 4,
    text: "\u0412\u044B\u0431\u0435\u0440\u0438\u0442\u0435 \u0441\u043F\u043E\u0441\u043E\u0431 \u043F\u043E\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044F \u0431\u0430\u043B\u0430\u043D\u0441\u0430: \u0432\u044B\u0441\u0442\u0430\u0432\u043B\u0435\u043D\u0438\u0435 \u0441\u0447\u0435\u0442\u0430 \u0434\u043B\u044F \u044E\u0440\u0438\u0434\u0438\u0447\u0435\u0441\u043A\u0438\u0445 \u043B\u0438\u0446 \u0438\u043B\u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0431\u0430\u043D\u043A\u043E\u0432\u0441\u043A\u043E\u0439 \u043A\u0430\u0440\u0442\u043E\u0439."
  },
  {
    id: 5,
    text: "\u0412 \u043F\u043E\u043B\u0443\u0447\u0435\u043D\u043D\u043E\u043C \u043E\u0442\u0447\u0435\u0442\u0435 \u043A\u043B\u0438\u043A\u043D\u0438\u0442\u0435 \u043F\u043E \u043A\u043D\u043E\u043F\u043A\u0435 \xAB\u041E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\xBB."
  }
];
const _sfc_main$4 = {
  __name: "Steps",
  __ssrInlineRender: true,
  setup(__props) {
    const activeStep = ref(1);
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "section_without-background section_gray steps" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="steps__top"${_scopeId}>`);
            _push2(ssrRenderComponent(AppH2, { class: "steps__title" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414`);
                } else {
                  return [
                    createTextVNode("\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<p class="steps__text"${_scopeId}>\u0421 \u043F\u043E\u043C\u043E\u0449\u044C\u044E \u0441\u0435\u0440\u0432\u0438\u0441\u0430 \xABCompas.pro\xBB \u0432\u044B \u043C\u043E\u0436\u0435\u0442\u0435 \u0431\u044B\u0441\u0442\u0440\u043E \u0438 \u0443\u0434\u043E\u0431\u043D\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u0413\u0418\u0411\u0414\u0414 \u043E\u043D\u043B\u0430\u0439\u043D \u0432\u0441\u0435\u0433\u043E \u0437\u0430 \u043F\u0430\u0440\u0443 \u043C\u0438\u043D\u0443\u0442. \u041E\u0442\u0447\u0435\u0442 \u0432\u043A\u043B\u044E\u0447\u0430\u0435\u0442 \u0448\u0442\u0440\u0430\u0444\u044B \u0437\u0430 \u043F\u0440\u0435\u0432\u044B\u0448\u0435\u043D\u0438\u0435 \u0441\u043A\u043E\u0440\u043E\u0441\u0442\u0438, \u043D\u0435\u043F\u0440\u0430\u0432\u0438\u043B\u044C\u043D\u0443\u044E \u043F\u0430\u0440\u043A\u043E\u0432\u043A\u0443 \u0438 \u0434\u0440\u0443\u0433\u0438\u0435 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F.</p></div><div class="steps__steps"${_scopeId}>`);
            _push2(ssrRenderComponent(AppH3, { class: "steps__subtitle" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414`);
                } else {
                  return [
                    createTextVNode("\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<p class="steps__text"${_scopeId}>\u0427\u0442\u043E\u0431\u044B \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0438 \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u0413\u0418\u0411\u0414\u0414, \u043D\u0443\u0436\u043D\u043E \u0432\u044B\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u043F\u044F\u0442\u044C \u043F\u0440\u043E\u0441\u0442\u044B\u0445 \u0448\u0430\u0433\u043E\u0432.</p>`);
            _push2(ssrRenderComponent(StepsList, {
              modelValue: unref(activeStep),
              "onUpdate:modelValue": ($event) => isRef(activeStep) ? activeStep.value = $event : null,
              steps: unref(steps)
            }, null, _parent2, _scopeId));
            _push2(`<p class="steps__text steps__text_margin"${_scopeId}>\u0427\u0442\u043E\u0431\u044B \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444, \u0443\u0431\u0435\u0434\u0438\u0442\u0435\u0441\u044C, \u0447\u0442\u043E \u0443 \u0432\u0430\u0441 \u0434\u043E\u0441\u0442\u0430\u0442\u043E\u0447\u043D\u043E \u0441\u0440\u0435\u0434\u0441\u0442\u0432 \u043D\u0430 \u0431\u0430\u043B\u0430\u043D\u0441\u0435 \u043B\u0438\u0447\u043D\u043E\u0433\u043E \u043A\u0430\u0431\u0438\u043D\u0435\u0442\u0430 \u043D\u0430\u0448\u0435\u0433\u043E \u0441\u0435\u0440\u0432\u0438\u0441\u0430. \u041E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u0440\u043E\u0438\u0437\u0432\u043E\u0434\u0438\u0442\u0441\u044F \u0441 \u0432\u0430\u0448\u0435\u0433\u043E \u0431\u0430\u043B\u0430\u043D\u0441\u0430 \u0432 Compas.pro.</p><p class="steps__text"${_scopeId}>\u041F\u0440\u0438 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0435 \u043F\u043E \u0433\u043E\u0441 \u043D\u043E\u043C\u0435\u0440\u0443 \u0432\u044B \u043D\u0430\u0439\u0434\u0443\u0442\u0441\u044F \u0448\u0442\u0440\u0430\u0444\u044B \u0441 \u043A\u0430\u043C\u0435\u0440 \u0432\u0438\u0434\u0435\u043E\u0444\u0438\u043A\u0441\u0430\u0446\u0438\u0438, \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u043E\u0433\u043E \u0443\u0434\u043E\u0441\u0442\u043E\u0432\u0435\u0440\u0435\u043D\u0438\u044F - \u0432\u044B\u043F\u0438\u0441\u0430\u043D\u043D\u044B\u0435 \u0438\u043D\u0441\u043F\u0435\u043A\u0442\u043E\u0440\u0430\u043C\u0438 \u0413\u0418\u0411\u0414\u0414.</p></div>`);
            _push2(ssrRenderComponent(FansyBox, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`<div href="https://www.youtube.com/watch?v=jhFDyDgMVUI" data-fancybox class="steps__video"${_scopeId2}>`);
                  _push3(ssrRenderComponent(YoutubeWhite, { class: "steps__video-play" }, null, _parent3, _scopeId2));
                  _push3(`</div>`);
                } else {
                  return [
                    createVNode("div", {
                      href: "https://www.youtube.com/watch?v=jhFDyDgMVUI",
                      "data-fancybox": "",
                      class: "steps__video"
                    }, [
                      createVNode(YoutubeWhite, { class: "steps__video-play" })
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode("div", { class: "steps__top" }, [
                createVNode(AppH2, { class: "steps__title" }, {
                  default: withCtx(() => [
                    createTextVNode("\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414")
                  ]),
                  _: 1
                }),
                createVNode("p", { class: "steps__text" }, "\u0421 \u043F\u043E\u043C\u043E\u0449\u044C\u044E \u0441\u0435\u0440\u0432\u0438\u0441\u0430 \xABCompas.pro\xBB \u0432\u044B \u043C\u043E\u0436\u0435\u0442\u0435 \u0431\u044B\u0441\u0442\u0440\u043E \u0438 \u0443\u0434\u043E\u0431\u043D\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u0413\u0418\u0411\u0414\u0414 \u043E\u043D\u043B\u0430\u0439\u043D \u0432\u0441\u0435\u0433\u043E \u0437\u0430 \u043F\u0430\u0440\u0443 \u043C\u0438\u043D\u0443\u0442. \u041E\u0442\u0447\u0435\u0442 \u0432\u043A\u043B\u044E\u0447\u0430\u0435\u0442 \u0448\u0442\u0440\u0430\u0444\u044B \u0437\u0430 \u043F\u0440\u0435\u0432\u044B\u0448\u0435\u043D\u0438\u0435 \u0441\u043A\u043E\u0440\u043E\u0441\u0442\u0438, \u043D\u0435\u043F\u0440\u0430\u0432\u0438\u043B\u044C\u043D\u0443\u044E \u043F\u0430\u0440\u043A\u043E\u0432\u043A\u0443 \u0438 \u0434\u0440\u0443\u0433\u0438\u0435 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F.")
              ]),
              createVNode("div", { class: "steps__steps" }, [
                createVNode(AppH3, { class: "steps__subtitle" }, {
                  default: withCtx(() => [
                    createTextVNode("\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414")
                  ]),
                  _: 1
                }),
                createVNode("p", { class: "steps__text" }, "\u0427\u0442\u043E\u0431\u044B \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0438 \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u0413\u0418\u0411\u0414\u0414, \u043D\u0443\u0436\u043D\u043E \u0432\u044B\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u043F\u044F\u0442\u044C \u043F\u0440\u043E\u0441\u0442\u044B\u0445 \u0448\u0430\u0433\u043E\u0432."),
                createVNode(StepsList, {
                  modelValue: unref(activeStep),
                  "onUpdate:modelValue": ($event) => isRef(activeStep) ? activeStep.value = $event : null,
                  steps: unref(steps)
                }, null, 8, ["modelValue", "onUpdate:modelValue", "steps"]),
                createVNode("p", { class: "steps__text steps__text_margin" }, "\u0427\u0442\u043E\u0431\u044B \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444, \u0443\u0431\u0435\u0434\u0438\u0442\u0435\u0441\u044C, \u0447\u0442\u043E \u0443 \u0432\u0430\u0441 \u0434\u043E\u0441\u0442\u0430\u0442\u043E\u0447\u043D\u043E \u0441\u0440\u0435\u0434\u0441\u0442\u0432 \u043D\u0430 \u0431\u0430\u043B\u0430\u043D\u0441\u0435 \u043B\u0438\u0447\u043D\u043E\u0433\u043E \u043A\u0430\u0431\u0438\u043D\u0435\u0442\u0430 \u043D\u0430\u0448\u0435\u0433\u043E \u0441\u0435\u0440\u0432\u0438\u0441\u0430. \u041E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u0440\u043E\u0438\u0437\u0432\u043E\u0434\u0438\u0442\u0441\u044F \u0441 \u0432\u0430\u0448\u0435\u0433\u043E \u0431\u0430\u043B\u0430\u043D\u0441\u0430 \u0432 Compas.pro."),
                createVNode("p", { class: "steps__text" }, "\u041F\u0440\u0438 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0435 \u043F\u043E \u0433\u043E\u0441 \u043D\u043E\u043C\u0435\u0440\u0443 \u0432\u044B \u043D\u0430\u0439\u0434\u0443\u0442\u0441\u044F \u0448\u0442\u0440\u0430\u0444\u044B \u0441 \u043A\u0430\u043C\u0435\u0440 \u0432\u0438\u0434\u0435\u043E\u0444\u0438\u043A\u0441\u0430\u0446\u0438\u0438, \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u043E\u0433\u043E \u0443\u0434\u043E\u0441\u0442\u043E\u0432\u0435\u0440\u0435\u043D\u0438\u044F - \u0432\u044B\u043F\u0438\u0441\u0430\u043D\u043D\u044B\u0435 \u0438\u043D\u0441\u043F\u0435\u043A\u0442\u043E\u0440\u0430\u043C\u0438 \u0413\u0418\u0411\u0414\u0414.")
              ]),
              createVNode(FansyBox, null, {
                default: withCtx(() => [
                  createVNode("div", {
                    href: "https://www.youtube.com/watch?v=jhFDyDgMVUI",
                    "data-fancybox": "",
                    class: "steps__video"
                  }, [
                    createVNode(YoutubeWhite, { class: "steps__video-play" })
                  ])
                ]),
                _: 1
              })
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/FinesList/Steps/Steps.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const MainSteps = _sfc_main$4;
const _sfc_main$3 = {
  __name: "PlusesFines",
  __ssrInlineRender: true,
  setup(__props) {
    let pluses = [
      {
        text: "\u0412\u0441\u044F \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044F \u0445\u0440\u0430\u043D\u0438\u0442\u0441\u044F \u0432 \u043E\u0434\u043D\u043E\u043C \u043C\u0435\u0441\u0442\u0435"
      },
      {
        text: "\u0423\u0434\u0430\u043B\u0435\u043D\u043D\u044B\u0439 \u0434\u043E\u0441\u0442\u0443\u043F \u043A \u043F\u0440\u043E\u0433\u0440\u0430\u043C\u043C\u0435"
      },
      {
        text: "\u0411\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u044C \u0438 \u0441\u043E\u0445\u0440\u0430\u043D\u043D\u043E\u0441\u0442\u044C \u0434\u0430\u043D\u043D\u044B\u0445"
      },
      {
        text: "\u0423\u0434\u043E\u0431\u043D\u044B\u0439 \u043C\u043E\u0431\u0438\u043B\u044C\u043D\u044B \u0438\u043D\u0442\u0435\u0440\u0444\u0435\u0439\u0441"
      },
      {
        text: "\u041D\u0435\u043E\u0433\u0440\u0430\u043D\u0438\u0447\u0435\u043D\u043D\u043E\u0435 \u043A\u043E\u043B-\u0432\u043E \u043C\u0430\u0448\u0438\u043D \u0438 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u0435\u0439"
      },
      {
        text: "\u0420\u0430\u0437\u043B\u0438\u0447\u043D\u044B\u0435 \u0443\u0440\u043E\u0432\u043D\u0438 \u0434\u043E\u0441\u0442\u0443\u043F\u0430 \u043A \u043F\u0440\u043E\u0433\u0440\u0430\u043C\u043C\u0435 \u0443 \u043A\u0430\u0436\u0434\u043E\u0433\u043E \u0441\u043E\u0442\u0440\u0443\u0434\u043D\u0438\u043A\u0430"
      },
      {
        text: "\u0420\u0443\u0447\u043D\u043E\u0435 \u0438 \u0430\u0432\u0442\u043E\u043C\u0430\u0442\u0438\u0447\u0435\u0441\u043A\u043E\u0435 \u043E\u0431\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u0435 \u0448\u0442\u0440\u0430\u0444\u043E\u0432"
      },
      {
        text: "\u041F\u043E\u043B\u043D\u044B\u0439 \u043F\u0430\u043A\u0435\u0442 \u0437\u0430\u043A\u0440\u044B\u0432\u0430\u044E\u0449\u0438\u0445 \u0434\u043E\u043A\u0443\u043C\u0435\u043D\u0442\u043E\u0432 \u0434\u043B\u044F \u0431\u0443\u0445\u0433\u0430\u043B\u0442\u0435\u0440\u0438\u0438"
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "pluses-fines section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041F\u043B\u044E\u0441\u044B \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u0430 \u043F\u043E \u0448\u0442\u0440\u0430\u0444\u0430\u043C \xAB\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E\xBB `);
                } else {
                  return [
                    createTextVNode(" \u041F\u043B\u044E\u0441\u044B \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u0430 \u043F\u043E \u0448\u0442\u0440\u0430\u0444\u0430\u043C \xAB\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E\xBB ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="pluses-fines__list"${_scopeId}><!--[-->`);
            ssrRenderList(unref(pluses), (item) => {
              _push2(`<div class="pluses-fines__item"${_scopeId}>${ssrInterpolate(item.text)}</div>`);
            });
            _push2(`<!--]--></div><div class="pluses__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(_component_NuxtLink, { to: "/auth/registration" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(AppButton, { class: "button_blue" }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(` \u041F\u043E\u043F\u0440\u043E\u0431\u043E\u0432\u0430\u0442\u044C \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E `);
                      } else {
                        return [
                          createTextVNode(" \u041F\u043E\u043F\u0440\u043E\u0431\u043E\u0432\u0430\u0442\u044C \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E ")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(AppButton, { class: "button_blue" }, {
                      default: withCtx(() => [
                        createTextVNode(" \u041F\u043E\u043F\u0440\u043E\u0431\u043E\u0432\u0430\u0442\u044C \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E ")
                      ]),
                      _: 1
                    })
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u041F\u043B\u044E\u0441\u044B \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u0430 \u043F\u043E \u0448\u0442\u0440\u0430\u0444\u0430\u043C \xAB\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E\xBB ")
                ]),
                _: 1
              }),
              createVNode("div", { class: "pluses-fines__list" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(pluses), (item) => {
                  return openBlock(), createBlock("div", { class: "pluses-fines__item" }, toDisplayString(item.text), 1);
                }), 256))
              ]),
              createVNode("div", { class: "pluses__actions" }, [
                createVNode(_component_NuxtLink, { to: "/auth/registration" }, {
                  default: withCtx(() => [
                    createVNode(AppButton, { class: "button_blue" }, {
                      default: withCtx(() => [
                        createTextVNode(" \u041F\u043E\u043F\u0440\u043E\u0431\u043E\u0432\u0430\u0442\u044C \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E ")
                      ]),
                      _: 1
                    })
                  ]),
                  _: 1
                })
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/FinesList/PlusesFines/PlusesFines.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const PlusesFines = _sfc_main$3;
const checkboxLink = `<div class="main-page__text">
	   \u042F \u043F\u043E\u043D\u0438\u043C\u0430\u044E \u0438 \u043F\u0440\u0438\u043D\u0438\u043C\u0430\u044E <a href="/docs/politics" class="main-page__link" target="_blank"> \u0443\u0441\u043B\u043E\u0432\u0438\u044F \u0438 \u043F\u043E\u043B\u0438\u0442\u0438\u043A\u0443 \u043A\u043E\u043D\u0444\u0438\u0434\u0435\u043D\u0446\u0438\u0430\u043B\u044C\u043D\u043E\u0441\u0442\u0438 </a> Compas
	  </div>`;
const _sfc_main$2 = {
  __name: "FinesList",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    const isShowRegistraion = ref(false);
    const route = useRoute();
    const userStore = useUserStore();
    const tableRole = ref(0);
    const { regData } = storeToRefs(userStore);
    const changeValue = (data) => {
      regData.value[data.key] = data.value;
    };
    const disabledButton = computed(() => {
      return !regData.value.confidence || regData.value.password == "" || regData.value.passwordConfirmation == "" || regData.value.email == "";
    });
    const registration = () => {
      if (route.query.tariff) {
        regData.value.tariff = route.query.tariff;
      }
      if (!userStore.regButtonLoad) {
        userStore.registration(regData.value, fields.value);
      }
    };
    useCommonStore();
    const fieldLabelsMap = {
      sts_number: "\u0421\u0432\u0438\u0434\u0435\u0442\u0435\u043B\u044C\u0441\u0442\u0432\u043E \u043E \u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0430\u0446\u0438\u0438 \u0422\u0421 *",
      number: "\u041D\u043E\u043C\u0435\u0440 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044F *",
      driver_license: "\u041D\u043E\u043C\u0435\u0440 \u0412\u0423 *",
      num_post: "\u041D\u043E\u043C\u0435\u0440 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F *",
      inn: "\u0418\u041D\u041D \u043A\u043E\u043C\u043F\u0430\u043D\u0438\u0438 *",
      kpp: "\u041A\u041F\u041F \u043A\u043E\u043C\u043F\u0430\u043D\u0438\u0438 *"
    };
    const finesStore = useFinesStore();
    const { fields, fines } = storeToRefs(finesStore);
    if (fields.value) {
      console.log(fields.value, "fields.value");
      const res = ([__temp, __restore] = withAsyncContext(() => api.callMethod("GET", `gibdd/check_by_req?` + new URLSearchParams(fields.value).toString(), {})), __temp = await __temp, __restore(), __temp);
      fines.value = res.map((i, idx) => {
        return { ...i, id: idx + 1 };
      });
    } else {
      console.log(fields.value, 123);
    }
    const tableSettings = ([__temp, __restore] = withAsyncContext(() => api.callMethod("GET", `table/fines`, {})), __temp = await __temp, __restore(), __temp);
    console.log(tableSettings, "tableSettings");
    const table = ref({
      tableKeys: tableSettings == null ? void 0 : tableSettings.fields,
      tableData: fines.value,
      socketRows: {
        header: [],
        body: []
      },
      // Сортировка по ключу
      sortItem: {
        key: tableSettings == null ? void 0 : tableSettings.sort_field,
        order: tableSettings == null ? void 0 : tableSettings.sort_order
      },
      tableFooter: {
        pages: 1,
        activePage: 1,
        count: 25
      },
      loaderState: ""
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "fines-list" }, _attrs))}>`);
      _push(ssrRenderComponent(AppH1, { class: "fines-list__title" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`\u0411\u044B\u0441\u0442\u0440\u0430\u044F \u043F\u0440\u043E\u0432\u0435\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432`);
          } else {
            return [
              createTextVNode("\u0411\u044B\u0441\u0442\u0440\u0430\u044F \u043F\u0440\u043E\u0432\u0435\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="fines-list__info"><!--[-->`);
      ssrRenderList(unref(fields), (value, key) => {
        _push(`<div class="fines-list__group"><p class="fines-list__label">${ssrInterpolate(fieldLabelsMap[key])}</p><p class="fines-list__text">${ssrInterpolate(value)}</p></div>`);
      });
      _push(`<!--]--><div class="fines-list__group"><p class="fines-list__label">\u0421\u043E\u0437\u0434\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430</p><p class="fines-list__text fines-list__text_link"> \u0417\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u0443\u0439\u0442\u0435 \u043F\u043E\u0440\u0442\u0430\u043B \u0434\u043B\u044F \u043F\u043E\u0441\u0442\u043E\u044F\u043D\u043D\u043E\u0433\u043E \u043E\u0442\u0441\u043B\u0435\u0436\u0438\u0432\u0430\u043D\u0438\u044F \u043C\u0430\u0448\u0438\u043D\u044B </p></div>`);
      if (unref(isShowRegistraion)) {
        _push(`<!--[-->`);
        _push(ssrRenderComponent(AppH1, { class: "main-page__form-title" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` \u0411\u044B\u0441\u0442\u0440\u0430\u044F \u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0430\u0446\u0438\u044F \u043D\u0430 \u043F\u043E\u0440\u0442\u0430\u043B\u0435 `);
            } else {
              return [
                createTextVNode(" \u0411\u044B\u0441\u0442\u0440\u0430\u044F \u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0430\u0446\u0438\u044F \u043D\u0430 \u043F\u043E\u0440\u0442\u0430\u043B\u0435 ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`<form class="main-page__form fines-list__form"><div class="auth__error" style="${ssrRenderStyle(unref(userStore).authError.status ? null : { display: "none" })}">${ssrInterpolate(unref(userStore).authError.text)}</div><div class="main-page__input-wrapper">`);
        _push(ssrRenderComponent(AppInput, {
          disabled: unref(userStore).regButtonLoad,
          class: "main-page__input main-page__input_substr",
          item: {
            id: 0,
            title: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
            value: unref(regData).domain,
            placeholder: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
            type: "text",
            key: "domain",
            substring: ".compas.pro"
          },
          mask: null,
          enabledAutocomplete: true,
          onKeyup: ($event) => !unref(disabledButton) ? registration() : null,
          onChangeValue: (data) => changeValue(data)
        }, null, _parent));
        if (unref(regData).domainError) {
          _push(`<!--[-->`);
          ssrRenderList(unref(regData).domainError, (error) => {
            _push(`<p class="warning-list__field-error">${ssrInterpolate(error)}</p>`);
          });
          _push(`<!--]-->`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="main-page__input-wrapper">`);
        _push(ssrRenderComponent(AppInput, {
          class: "main-page__input",
          item: {
            id: 0,
            title: "E-mail",
            value: unref(regData).email,
            placeholder: "E-mail",
            type: "text",
            key: "email"
          },
          required: true,
          mask: null,
          disabled: unref(userStore).regButtonLoad,
          enabledAutocomplete: true,
          onKeyup: ($event) => !unref(disabledButton) ? registration() : null,
          onChangeValue: (data) => changeValue(data)
        }, null, _parent));
        if (unref(regData).emailError) {
          _push(`<!--[-->`);
          ssrRenderList(unref(regData).emailError, (error) => {
            _push(`<p class="warning-list__field-error">${ssrInterpolate(error)}</p>`);
          });
          _push(`<!--]-->`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="main-page__input-wrapper">`);
        _push(ssrRenderComponent(AppInput, {
          class: "main-page__input",
          item: {
            id: 1,
            title: "\u041F\u0430\u0440\u043E\u043B\u044C",
            value: unref(regData).password,
            placeholder: "\u041F\u0430\u0440\u043E\u043B\u044C",
            type: "password",
            key: "password"
          },
          mask: null,
          required: true,
          disabled: unref(userStore).regButtonLoad,
          enabledAutocomplete: false,
          onKeyup: ($event) => !unref(disabledButton) ? registration() : null,
          onChangeValue: (data) => changeValue(data)
        }, null, _parent));
        if (unref(regData).passwordError) {
          _push(`<!--[-->`);
          ssrRenderList(unref(regData).passwordError, (error) => {
            _push(`<p class="warning-list__field-error">${ssrInterpolate(error)}</p>`);
          });
          _push(`<!--]-->`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div><div class="main-page__input-wrapper">`);
        _push(ssrRenderComponent(AppInput, {
          class: "main-page__input",
          item: {
            id: 1,
            title: "\u041F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u0438\u0435 \u043F\u0430\u0440\u043E\u043B\u044F",
            value: unref(regData).passwordConfirmation,
            placeholder: "\u041F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u0438\u0435 \u043F\u0430\u0440\u043E\u043B\u044F",
            type: "password",
            key: "passwordConfirmation"
          },
          mask: null,
          required: true,
          disabled: unref(userStore).regButtonLoad,
          enabledAutocomplete: false,
          onKeyup: ($event) => !unref(disabledButton) ? registration() : null,
          onChangeValue: (data) => changeValue(data)
        }, null, _parent));
        if (unref(regData).passwordConfirmationError) {
          _push(`<!--[-->`);
          ssrRenderList(unref(regData).passwordConfirmationError, (error) => {
            _push(`<p class="warning-list__field-error">${ssrInterpolate(error)}</p>`);
          });
          _push(`<!--]-->`);
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        _push(ssrRenderComponent(AppCheckbox, {
          class: "main-page__checkbox main-page__checkbox_long",
          item: {
            id: 2,
            title: checkboxLink,
            value: unref(regData).confidence,
            placeholder: "",
            type: "checkbox",
            key: "confidence",
            isHTML: true
          },
          disabled: unref(userStore).regButtonLoad,
          isTextClickable: false,
          onChangeValue: (data) => changeValue(data)
        }, null, _parent));
        _push(ssrRenderComponent(AppButton, {
          disabledOption: unref(disabledButton),
          class: [{ button_loading: unref(userStore).regButtonLoad }, "main-page__button button_blue"],
          onClick: registration
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(` \u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043F\u043E\u0440\u0442\u0430\u043B `);
            } else {
              return [
                createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043F\u043E\u0440\u0442\u0430\u043B ")
              ];
            }
          }),
          _: 1
        }, _parent));
        _push(`</form><!--]-->`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(AppTable, {
        class: "fines-list__table",
        isTrash: false,
        actionType: "views",
        slug: "equal",
        isPermanentEdit: false,
        table: unref(table),
        updateScrollButton: unref(tableRole),
        isDraggableRow: false,
        activeCategory: null,
        categories: [],
        pageTableOnly: false,
        isCanSort: false,
        isShowSettings: false,
        isHaveCategories: false,
        categoryType: "default"
      }, null, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/FinesList/FinesList/FinesList.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const FinesList = _sfc_main$2;
const _sfc_main$1 = {
  __name: "Main",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    let activeTab = ref({
      type: null,
      tab: "po-sts"
    });
    provide("activeTab", activeTab);
    watch(
      () => route.params.type,
      () => {
        if (route.params.type != activeTab.value.tab) {
          activeTab.value.tab = route.params.type;
        }
      }
    );
    let breadcrumbs = computed(() => [
      {
        title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0430",
        link: "/"
      },
      {
        title: "\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432",
        link: "/products/fines"
      },
      {
        title: "\u0411\u044B\u0441\u0442\u0440\u0430\u044F \u043F\u0440\u043E\u0432\u0435\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432",
        link: `/products/fines${activeTab.value.tab ? `/${activeTab.value.tab}` : null}`
      }
    ]);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_AppBreadcrambs = AppBreadcrambs;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(_component_AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      _push(ssrRenderComponent(FinesList, null, null, _parent));
      _push(ssrRenderComponent(MainBase, null, null, _parent));
      _push(ssrRenderComponent(MainSteps, null, null, _parent));
      _push(ssrRenderComponent(MainPluses, null, null, _parent));
      _push(ssrRenderComponent(CommonProgramm, {
        class: "main__programm",
        title: "\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0430\u0431\u0441\u043E\u043B\u044E\u0442\u043D\u043E \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u044B",
        desc: "\u0421\u0435\u0440\u0432\u0438\u0441 \xABCompas.pro\xBB \u0441\u043E\u0431\u0438\u0440\u0430\u0435\u0442 \u0434\u0430\u043D\u043D\u044B\u0435 \u0438\u0437 \u043E\u0444\u0438\u0446\u0438\u0430\u043B\u044C\u043D\u044B\u0445 \u0438\u0441\u0442\u043E\u0447\u043D\u0438\u043A\u043E\u0432: \u0413\u0418\u0411\u0414\u0414, \u041C\u0410\u0414\u0418, \u0410\u041C\u041F\u041F, \u0413\u0418\u0421 \u0413\u041C\u041F. \u041F\u043E\u0441\u043B\u0435 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0432\u044B \u043F\u043E\u043B\u0443\u0447\u0438\u0442\u0435 \u043E\u0431\u0449\u0435\u0435 \u043A\u043E\u043B\u0438\u0447\u0435\u0441\u0442\u0432\u043E \u0438 \u0441\u0443\u043C\u043C\u0443 \u0432\u0441\u0435\u0445 \u0448\u0442\u0440\u0430\u0444\u043E\u0432. \u041E\u0442\u0447\u0435\u0442 \u0442\u0430\u043A\u0436\u0435 \u0432\u043A\u043B\u044E\u0447\u0430\u0435\u0442 \u0438\u0441\u0442\u043E\u0440\u0438\u044E \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0438 \u0434\u0435\u0442\u0430\u043B\u044C\u043D\u0443\u044E \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044E \u043F\u043E \u043A\u0430\u0436\u0434\u043E\u043C\u0443 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044E \u0413\u0418\u0411\u0414\u0414."
      }, null, _parent));
      _push(ssrRenderComponent(PlusesFines, null, null, _parent));
      _push(ssrRenderComponent(CommonSocial, { class: "main__social" }, null, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/FinesList/Main.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const TemplateMain = _sfc_main$1;
const _sfc_main = {
  __name: "list",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043E\u043D\u043B\u0430\u0439\u043D | Compas.pro",
      meta: [
        {
          name: "description",
          content: "\u0411\u044B\u0441\u0442\u0440\u0430\u044F \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043E\u043D\u043B\u0430\u0439\u043D. \u0423\u0434\u043E\u0431\u043D\u044B\u0439 \u0441\u0435\u0440\u0432\u0438\u0441 \u0434\u043B\u044F \u043F\u043E\u0438\u0441\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u044B \u0448\u0442\u0440\u0430\u0444\u043E\u0432. \u042D\u043A\u043E\u043D\u043E\u043C\u044C\u0442\u0435 \u0432\u0440\u0435\u043C\u044F \u0438 \u0438\u0437\u0431\u0435\u0433\u0430\u0439\u0442\u0435 \u043F\u0440\u043E\u0431\u043B\u0435\u043C \u0441 \u043D\u0430\u0448\u0438\u043C\u0438 \u0431\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u044B\u043C\u0438 \u0438 \u043D\u0430\u0434\u0435\u0436\u043D\u044B\u043C\u0438 \u043E\u043D\u043B\u0430\u0439\u043D-\u043F\u043B\u0430\u0442\u0435\u0436\u0430\u043C\u0438."
        },
        {
          name: "robots",
          content: "noindex, nofollow"
        }
      ],
      link: [
        {
          rel: "canonical",
          href: `https://compas.pro/products/fines/list`
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(TemplateMain, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/products/fines/list.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=list-ef25a268.mjs.map
