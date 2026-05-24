import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { a as useHead, u as useRoute, w as useUserStore, s as storeToRefs, y as useCommonStore, x as AppH1, k as AppButton, A as AppH2, e as __nuxt_component_0, b as api, n as navigateTo, o as AppWarning$1, j as AppInput, g as AppSelect } from './server.mjs';
import { ssrRenderComponent, ssrRenderAttr, ssrRenderList, ssrInterpolate } from 'vue/server-renderer';
import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import { useSSRContext, ref, provide, watch, computed, unref, watchEffect, mergeProps, withCtx, createTextVNode, createVNode, openBlock, createBlock, Fragment, renderList, toDisplayString, inject, createCommentVNode } from 'vue';
import { _ as _imports_0 } from './youtube_blue-a00a4300.mjs';
import { F as FansyBox, V as ValidateField, A as AppRelation, a as AppTextarea, b as AppStatus, c as AppDate, d as AppFile } from './Validate-398d291a.mjs';
import { A as AppSection } from './AppSection-1ea634ac.mjs';
import { M as MainArticles, s as stsImage, v as vuImage, p as postanovlenieImage, g as gosImage, i as innImage } from './preview-inn-d36097f7.mjs';
import { M as MainCompanies, a as MainQuestions } from './Companies-75bbb9ed.mjs';
import { C as CommonSocial } from './Social-983a064f.mjs';
import { T as TariffsSlider } from './TariffsSlider-31bd220c.mjs';
import 'node:async_hooks';
import 'vue-bundle-renderer/runtime';
import '../runtime.mjs';
import 'node:http';
import 'node:https';
import 'node:fs';
import 'node:path';
import 'node:url';
import 'devalue';
import '@unhead/ssr';
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
import 'lodash';
import '@vuepic/vue-datepicker';
import 'vue-accessible-color-picker';
import './Input-3345b1b6.mjs';
import './ButtonText-edbdf3ac.mjs';
import 'swiper/vue';
import './Slider-a943f5b9.mjs';
import 'swiper';
import './ArticleItem-812dd48a.mjs';
import './dayjs-ce9ed7b6.mjs';
import './index-e6d877f1.mjs';
import './index-c8ee539a.mjs';

const _sfc_main$6 = {
  __name: "Validation",
  __ssrInlineRender: true,
  emits: ["callAction"],
  setup(__props, { emit: __emit }) {
    const isShow = inject("isShow");
    inject("activeId");
    const invalidFields = inject("invalidFields");
    const form = inject("form");
    const emit = __emit;
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    const changeValue = (id, data) => {
      let findedField = form.value.find((p) => p.key == data.key);
      findedField.value = data.value;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        class: "warning__validation",
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u041E\u0448\u0438\u0431\u043A\u0430 \u0432\u0430\u043B\u0438\u0434\u0430\u0446\u0438\u0438 `);
          } else {
            return [
              createTextVNode(" \u041E\u0448\u0438\u0431\u043A\u0430 \u0432\u0430\u043B\u0438\u0434\u0430\u0446\u0438\u0438 ")
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="warning__text"${_scopeId}>\u041D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u043E \u0437\u0430\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u044B\u0435 \u043F\u043E\u043B\u044F</div><div class="warning__list warning-list"${_scopeId}><!--[-->`);
            ssrRenderList(unref(invalidFields), (item) => {
              _push2(`<div class="warning-list__field"${_scopeId}>`);
              if (item.field.type == "relation") {
                _push2(ssrRenderComponent(AppRelation, {
                  item: {
                    key: item.field.key,
                    type: item.field.type,
                    id: item.field.id,
                    title: item.field.title,
                    substring: item.field.unit,
                    value: item.field.value,
                    is_link: item.field.is_link,
                    is_plural: item.field.is_plural,
                    hiddenOptions: item.field.choosed,
                    required: Boolean(item.field.required),
                    related_table: item.field.related_table,
                    is_external_link: item.field.is_external_link,
                    options: ["status", "relation"].includes(item.field.type) ? item.field.options : null,
                    external_link: item.field.value != void 0 ? item.field.value.external_link : null
                  },
                  isCanCreate: true,
                  isUseEnter: false,
                  enabledAutocomplete: false,
                  isReadOnly: false,
                  isMultiple: Boolean(item.field.is_plural),
                  onChangeValue: (data) => changeValue(item.field.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.field.type == "text" && item.field.is_plural) {
                _push2(ssrRenderComponent(AppTextarea, {
                  item: {
                    key: item.field.key,
                    type: item.field.type,
                    id: item.field.id,
                    title: item.field.title,
                    substring: item.field.unit,
                    value: [null, void 0].includes(item.field.value) ? null : typeof item.field.value == "object" ? item.field.value.value : item.field.value,
                    is_link: item.field.is_link,
                    is_plural: item.field.is_plural,
                    hiddenOptions: item.field.choosed,
                    related_table: item.field.related_table,
                    is_external_link: item.field.is_external_link,
                    required: Boolean(item.field.required),
                    options: ["status", "relation"].includes(item.field.type) ? item.field.options : null,
                    external_link: item.field.value != void 0 ? item.field.value.external_link : null
                  },
                  isCanCreate: true,
                  isUseEnter: false,
                  enabledAutocomplete: false,
                  isReadOnly: false,
                  isMultiple: Boolean(item.field.is_plural),
                  onChangeValue: (data) => changeValue(item.field.id, data)
                }, null, _parent2, _scopeId));
              } else if (["number", "password", "email"].includes(item.field.type) || item.field.type == "text" && !item.field.is_plural) {
                _push2(ssrRenderComponent(AppInput, {
                  item: {
                    key: item.field.key,
                    type: item.field.type,
                    id: item.field.id,
                    title: item.field.title,
                    substring: item.field.unit,
                    value: [null, void 0].includes(item.field.value) ? null : typeof item.field.value == "object" ? item.field.value.value : item.field.value,
                    is_link: item.field.is_link,
                    is_plural: item.field.is_plural,
                    hiddenOptions: item.field.choosed,
                    related_table: item.field.related_table,
                    is_external_link: item.field.is_external_link,
                    required: Boolean(item.field.required),
                    placeholder: item.field.placeholder,
                    options: ["status", "relation"].includes(item.field.type) ? item.field.options : null,
                    external_link: item.field.value != void 0 ? item.field.value.external_link : null
                  },
                  mask: item.field.mask,
                  isCanCreate: true,
                  isUseEnter: false,
                  enabledAutocomplete: false,
                  isReadOnly: false,
                  isMultiple: Boolean(item.field.is_plural),
                  onChangeValue: (data) => changeValue(item.field.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.field.type == "status") {
                _push2(ssrRenderComponent(AppStatus, {
                  item: {
                    key: item.field.key,
                    type: item.field.type,
                    id: item.field.id,
                    title: item.field.title,
                    substring: item.field.unit,
                    value: item.field.value,
                    is_link: item.field.is_link,
                    is_plural: item.field.is_plural,
                    hiddenOptions: item.field.choosed,
                    related_table: item.field.related_table,
                    is_external_link: false,
                    options: item.field.options,
                    required: Boolean(item.field.required),
                    external_link: null
                  },
                  isCanCreate: false,
                  isHaveNullOption: false,
                  isUseEnter: false,
                  enabledAutocomplete: false,
                  isReadOnly: false,
                  isMultiple: Boolean(item.field.is_plural),
                  onChangeValue: (data) => changeValue(item.field.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.field.type == "select_dropdown") {
                _push2(ssrRenderComponent(AppSelect, {
                  item: {
                    id: item.field.id,
                    key: item.field.key,
                    value: item.field.value,
                    focus: false,
                    required: Boolean(item.field.required),
                    title: item.field.title,
                    options: item.field.options,
                    lockedOptions: []
                  },
                  isReadOnly: false,
                  isHaveNullOption: true,
                  isMultiple: Boolean(item.field.is_plural),
                  isFiltered: true,
                  onChangeValue: (data) => changeValue(item.field.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.field.type == "date") {
                _push2(ssrRenderComponent(AppDate, {
                  item: {
                    id: item.field.id,
                    required: Boolean(item.field.required),
                    title: item.field.title,
                    placeholder: null,
                    value: [null, void 0].includes(item.field.value) ? null : String(item.field.value),
                    key: item.field.key,
                    focus: false
                  },
                  isMultiple: Boolean(item.field.is_plural),
                  isReadOnly: false,
                  onChangeValue: (data) => changeValue(item.field.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.field.type == "file") {
                _push2(ssrRenderComponent(AppFile, {
                  item: {
                    id: item.field.id,
                    title: item.field.title,
                    key: item.field.key,
                    required: Boolean(item.field.required),
                    buttonName: null,
                    value: [null, void 0].includes(item.field.value) ? null : item.field.value
                  },
                  isReadOnly: false,
                  isShowFileName: false,
                  isMultiple: true,
                  isOneFile: false,
                  onChangeValue: (data) => changeValue(item.field.id, data)
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="warning-list__field-error"${_scopeId}>${ssrInterpolate(item.error)}</div></div>`);
            });
            _push2(`<!--]--></div><div class="warning__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              class: "button_blue",
              onClick: () => emit("callAction", true)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C `);
                } else {
                  return [
                    createTextVNode(" \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C ")
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
                  _push3(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
                } else {
                  return [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "warning__text" }, "\u041D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u043E \u0437\u0430\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u044B\u0435 \u043F\u043E\u043B\u044F"),
              createVNode("div", { class: "warning__list warning-list" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(invalidFields), (item) => {
                  return openBlock(), createBlock("div", { class: "warning-list__field" }, [
                    item.field.type == "relation" ? (openBlock(), createBlock(AppRelation, {
                      key: 0,
                      item: {
                        key: item.field.key,
                        type: item.field.type,
                        id: item.field.id,
                        title: item.field.title,
                        substring: item.field.unit,
                        value: item.field.value,
                        is_link: item.field.is_link,
                        is_plural: item.field.is_plural,
                        hiddenOptions: item.field.choosed,
                        required: Boolean(item.field.required),
                        related_table: item.field.related_table,
                        is_external_link: item.field.is_external_link,
                        options: ["status", "relation"].includes(item.field.type) ? item.field.options : null,
                        external_link: item.field.value != void 0 ? item.field.value.external_link : null
                      },
                      isCanCreate: true,
                      isUseEnter: false,
                      enabledAutocomplete: false,
                      isReadOnly: false,
                      isMultiple: Boolean(item.field.is_plural),
                      onChangeValue: (data) => changeValue(item.field.id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : item.field.type == "text" && item.field.is_plural ? (openBlock(), createBlock(AppTextarea, {
                      key: 1,
                      item: {
                        key: item.field.key,
                        type: item.field.type,
                        id: item.field.id,
                        title: item.field.title,
                        substring: item.field.unit,
                        value: [null, void 0].includes(item.field.value) ? null : typeof item.field.value == "object" ? item.field.value.value : item.field.value,
                        is_link: item.field.is_link,
                        is_plural: item.field.is_plural,
                        hiddenOptions: item.field.choosed,
                        related_table: item.field.related_table,
                        is_external_link: item.field.is_external_link,
                        required: Boolean(item.field.required),
                        options: ["status", "relation"].includes(item.field.type) ? item.field.options : null,
                        external_link: item.field.value != void 0 ? item.field.value.external_link : null
                      },
                      isCanCreate: true,
                      isUseEnter: false,
                      enabledAutocomplete: false,
                      isReadOnly: false,
                      isMultiple: Boolean(item.field.is_plural),
                      onChangeValue: (data) => changeValue(item.field.id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : ["number", "password", "email"].includes(item.field.type) || item.field.type == "text" && !item.field.is_plural ? (openBlock(), createBlock(AppInput, {
                      key: 2,
                      item: {
                        key: item.field.key,
                        type: item.field.type,
                        id: item.field.id,
                        title: item.field.title,
                        substring: item.field.unit,
                        value: [null, void 0].includes(item.field.value) ? null : typeof item.field.value == "object" ? item.field.value.value : item.field.value,
                        is_link: item.field.is_link,
                        is_plural: item.field.is_plural,
                        hiddenOptions: item.field.choosed,
                        related_table: item.field.related_table,
                        is_external_link: item.field.is_external_link,
                        required: Boolean(item.field.required),
                        placeholder: item.field.placeholder,
                        options: ["status", "relation"].includes(item.field.type) ? item.field.options : null,
                        external_link: item.field.value != void 0 ? item.field.value.external_link : null
                      },
                      mask: item.field.mask,
                      isCanCreate: true,
                      isUseEnter: false,
                      enabledAutocomplete: false,
                      isReadOnly: false,
                      isMultiple: Boolean(item.field.is_plural),
                      onChangeValue: (data) => changeValue(item.field.id, data)
                    }, null, 8, ["item", "mask", "isMultiple", "onChangeValue"])) : item.field.type == "status" ? (openBlock(), createBlock(AppStatus, {
                      key: 3,
                      item: {
                        key: item.field.key,
                        type: item.field.type,
                        id: item.field.id,
                        title: item.field.title,
                        substring: item.field.unit,
                        value: item.field.value,
                        is_link: item.field.is_link,
                        is_plural: item.field.is_plural,
                        hiddenOptions: item.field.choosed,
                        related_table: item.field.related_table,
                        is_external_link: false,
                        options: item.field.options,
                        required: Boolean(item.field.required),
                        external_link: null
                      },
                      isCanCreate: false,
                      isHaveNullOption: false,
                      isUseEnter: false,
                      enabledAutocomplete: false,
                      isReadOnly: false,
                      isMultiple: Boolean(item.field.is_plural),
                      onChangeValue: (data) => changeValue(item.field.id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : item.field.type == "select_dropdown" ? (openBlock(), createBlock(AppSelect, {
                      key: 4,
                      item: {
                        id: item.field.id,
                        key: item.field.key,
                        value: item.field.value,
                        focus: false,
                        required: Boolean(item.field.required),
                        title: item.field.title,
                        options: item.field.options,
                        lockedOptions: []
                      },
                      isReadOnly: false,
                      isHaveNullOption: true,
                      isMultiple: Boolean(item.field.is_plural),
                      isFiltered: true,
                      onChangeValue: (data) => changeValue(item.field.id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : item.field.type == "date" ? (openBlock(), createBlock(AppDate, {
                      key: 5,
                      item: {
                        id: item.field.id,
                        required: Boolean(item.field.required),
                        title: item.field.title,
                        placeholder: null,
                        value: [null, void 0].includes(item.field.value) ? null : String(item.field.value),
                        key: item.field.key,
                        focus: false
                      },
                      isMultiple: Boolean(item.field.is_plural),
                      isReadOnly: false,
                      onChangeValue: (data) => changeValue(item.field.id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : item.field.type == "file" ? (openBlock(), createBlock(AppFile, {
                      key: 6,
                      item: {
                        id: item.field.id,
                        title: item.field.title,
                        key: item.field.key,
                        required: Boolean(item.field.required),
                        buttonName: null,
                        value: [null, void 0].includes(item.field.value) ? null : item.field.value
                      },
                      isReadOnly: false,
                      isShowFileName: false,
                      isMultiple: true,
                      isOneFile: false,
                      onChangeValue: (data) => changeValue(item.field.id, data)
                    }, null, 8, ["item", "onChangeValue"])) : createCommentVNode("", true),
                    createVNode("div", { class: "warning-list__field-error" }, toDisplayString(item.error), 1)
                  ]);
                }), 256))
              ]),
              createVNode("div", { class: "warning__actions" }, [
                createVNode(AppButton, {
                  class: "button_blue",
                  onClick: () => emit("callAction", true)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C ")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(AppButton, {
                  onClick: () => showWarning(false)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
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
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/MainPage/Fines/Warning/Validation/Validation.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const WarningValidation = _sfc_main$6;
const _sfc_main$5 = {
  __name: "Warning",
  __ssrInlineRender: true,
  setup(__props) {
    const isShow = inject("isShow");
    return (_ctx, _push, _parent, _attrs) => {
      if (unref(isShow).state && unref(isShow).type == "validation") {
        _push(ssrRenderComponent(WarningValidation, _attrs, null, _parent));
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/MainPage/Fines/Warning/Warning.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const FinesWarning = _sfc_main$5;
const defaultImage = "" + publicAssetsURL("main/fines/main-preview.png");
const _sfc_main$4 = {
  __name: "Fines",
  __ssrInlineRender: true,
  setup(__props) {
    const userStore = useUserStore();
    const route = useRoute();
    const { regData } = storeToRefs(userStore);
    computed(() => {
      return !regData.value.confidence || regData.value.password == "" || regData.value.passwordConfirmation == "" || regData.value.email == "";
    });
    const commonStore = useCommonStore();
    const previewImage = {
      "po-sts": stsImage,
      "po-voditelskomu-udostovereniyu": vuImage,
      "po-nomeru-postanovleniya": postanovlenieImage,
      "po-nomeru-avto": gosImage,
      "po-inn": innImage
    };
    let fields = computed(() => {
      switch (route.params.type) {
        case "po-sts": {
          return [
            {
              title: "\u041D\u043E\u043C\u0435\u0440 \u0421\u0422\u0421",
              key: "sts",
              name: "sts_number",
              type: "text",
              mask: "## XX ######",
              value: "",
              required: true,
              placeholder: "00 AA 000000",
              class: "input_line"
            }
          ];
        }
        case "po-voditelskomu-udostovereniyu": {
          return [
            {
              title: "\u041D\u043E\u043C\u0435\u0440 \u0412\u0423",
              key: "vu",
              name: "driver_license",
              type: "text",
              mask: "## ## ######",
              value: "",
              required: true,
              placeholder: "00 00 000000",
              class: "input_line"
            }
          ];
        }
        case "po-nomeru-postanovleniya": {
          return [
            {
              title: "\u041D\u043E\u043C\u0435\u0440 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F",
              key: "uin",
              name: "num_post",
              type: "number",
              mask: "####################",
              value: "",
              required: true,
              placeholder: "00000000000000000000",
              class: "input_line"
            }
          ];
        }
        case "po-nomeru-avto": {
          return [
            {
              title: "\u0413\u043E\u0441. \u043D\u043E\u043C\u0435\u0440 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044F",
              key: "gos",
              name: "number",
              type: "text",
              mask: "A ### AA ###",
              value: "",
              required: true,
              placeholder: "A 000 AA 777",
              class: "input_line"
            },
            {
              title: "\u041D\u043E\u043C\u0435\u0440 \u0421\u0422\u0421",
              key: "sts",
              name: "sts_number",
              type: "text",
              mask: "## XX ######",
              value: "",
              required: true,
              placeholder: "00 AA 000000",
              class: "input_line"
            }
          ];
        }
        case "po-inn": {
          return [
            {
              title: "\u0418\u041D\u041D \u043A\u043E\u043C\u043F\u0430\u043D\u0438\u0438",
              key: "inn",
              name: "inn",
              type: "text",
              mask: "############",
              value: "",
              required: true,
              placeholder: "000000000000",
              class: "input_line"
            },
            {
              title: "\u041A\u041F\u041F \u043A\u043E\u043C\u043F\u0430\u043D\u0438\u0438",
              key: "kpp",
              name: "kpp",
              type: "text",
              mask: "#########",
              value: "",
              required: true,
              placeholder: "000000000000",
              class: "input_line"
            }
          ];
        }
        default: {
          return [
            {
              title: "\u0413\u043E\u0441. \u043D\u043E\u043C\u0435\u0440 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044F",
              key: "number",
              name: "number",
              type: "text",
              mask: "A ### AA ###",
              value: "",
              required: true,
              placeholder: "A 000 AA 777",
              class: "input_line"
            },
            {
              title: "\u041D\u043E\u043C\u0435\u0440 \u0421\u0422\u0421",
              key: "certificate",
              name: "sts_number",
              type: "text",
              mask: "## XX ######",
              value: "",
              required: true,
              placeholder: "00 AA 000000",
              class: "input_line"
            }
          ];
        }
      }
    });
    let form = ref([]);
    watchEffect(() => {
      form.value = [
        ...fields.value
        // {
        // 	title: "Электронная почта для входа",
        // 	key: "email",
        // 	name: "email",
        // 	type: "email",
        // 	mask: null,
        // 	value: "",
        // 	required: true,
        // 	placeholder: "mail@compas.pro",
        // 	class: "input_line",
        // },
        // {
        // 	title: "Пароль для входа",
        // 	key: "password",
        // 	name: "password",
        // 	type: "password",
        // 	mask: null,
        // 	value: "",
        // 	required: true,
        // 	placeholder: null,
        // 	class: "input_line",
        // },
        // {
        // 	title: "Повторить пароль для входа",
        // 	key: "repeatPassword",
        // 	name: "password_confirmation",
        // 	type: "password",
        // 	mask: null,
        // 	value: "",
        // 	required: true,
        // 	placeholder: null,
        // 	class: "input_line",
        // },
      ];
    });
    const formData = computed(() => {
      const data = {};
      for (let item of form.value) {
        const trimmedValue = item.value.replace(/\s+/g, "");
        data[item.name] = trimmedValue;
      }
      return data;
    });
    let invalidFields = ref([]);
    let isShow = ref(false);
    const isLoading = ref(false);
    const saveChanges = () => {
      const initSave = async () => {
        if (invalidFields.value.length > 0) {
          isShow.value = {
            state: true,
            type: "validation"
          };
        } else {
          isShow.value = {
            state: false,
            type: null
          };
          isLoading.value = true;
          try {
            const { domain, success, token, url } = await api.callMethod("POST", `registration`, { ...formData.value, tariff: 1 });
            if (success) {
              const isInside = commonStore.accounts.find((i) => i.toLowerCase() == domain.toLowerCase());
              !isInside && commonStore.accounts.push(domain.toLowerCase());
              navigateTo(`https://${domain}.compas.pro${url ? url : ""}/?token=${token}`, { external: true });
              for (let elem of form.value) {
                elem.value = "";
              }
            }
          } catch (error) {
          } finally {
            isLoading.value = false;
          }
        }
      };
      const checkingFields = () => {
        const validateField = (field) => {
          let error = ValidateField(field, field.value);
          if (error.state) {
            return error.text;
          } else {
            return false;
          }
        };
        for (let field of form.value) {
          let error = validateField(field);
          if (error) {
            invalidFields.value.push({
              field,
              error
            });
          } else {
            let repeatPassword = form.value.find((p) => p.key == "repeatPassword");
            let password = form.value.find((p) => p.key == "password");
            if ((field.type == "password" || field.type == "repeatPassword") && password.value != repeatPassword.value) {
              invalidFields.value.push({
                field,
                error: "\u041F\u0430\u0440\u043E\u043B\u0438 \u0434\u043E\u043B\u0436\u043D\u044B \u0441\u043E\u0432\u043F\u0430\u0434\u0430\u0442\u044C"
              });
            }
          }
        }
      };
      invalidFields.value = [];
      checkingFields();
      initSave();
    };
    provide("form", form);
    provide("isShow", isShow);
    provide("invalidFields", invalidFields);
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "main-page section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b, _c, _d;
          if (_push2) {
            _push2(`<form class="main-page__form"${_scopeId}><div class="main-page__container"${_scopeId}>`);
            _push2(ssrRenderComponent(AppH1, { class: "main-page__form-title" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E - \u0443\u0434\u043E\u0431\u043D\u043E\u0435 \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u0435 \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043E\u043C <p class="main-page__form-subtitle"${_scopeId2}>\u041F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u043E\u0442\u0441\u043B\u0435\u0436\u0438\u0432\u0430\u0442\u044C \u0438 \u043E\u043F\u043B\u0430\u0447\u0438\u0432\u0430\u0442\u044C \u0432\u0441\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u043B\u0438\u0447\u043D\u043E\u0439 \u043C\u0430\u0448\u0438\u043D\u0435 \u0438\u043B\u0438 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u0443 \u043C\u0430\u0448\u0438\u043D.</p>`);
                } else {
                  return [
                    createTextVNode("\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E - \u0443\u0434\u043E\u0431\u043D\u043E\u0435 \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u0435 \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043E\u043C "),
                    createVNode("p", { class: "main-page__form-subtitle" }, "\u041F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u043E\u0442\u0441\u043B\u0435\u0436\u0438\u0432\u0430\u0442\u044C \u0438 \u043E\u043F\u043B\u0430\u0447\u0438\u0432\u0430\u0442\u044C \u0432\u0441\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u043B\u0438\u0447\u043D\u043E\u0439 \u043C\u0430\u0448\u0438\u043D\u0435 \u0438\u043B\u0438 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u0443 \u043C\u0430\u0448\u0438\u043D.")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="main-page__form-actions main-page__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(FansyBox, { class: "main-page__fansy-box" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(AppButton, {
                    class: "main-page__button",
                    "data-fancybox": `finesBlock`,
                    href: ""
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`<figure class="ibg main-page__icon"${_scopeId3}><img${ssrRenderAttr("src", _imports_0)} alt="\u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435"${_scopeId3}></figure> \u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435 <span class="button-text"${_scopeId3}> (1 \u043C\u0438\u043D 20 \u0441\u0435\u043A) </span>`);
                      } else {
                        return [
                          createVNode("figure", { class: "ibg main-page__icon" }, [
                            createVNode("img", {
                              src: _imports_0,
                              alt: "\u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435"
                            })
                          ]),
                          createTextVNode(" \u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435 "),
                          createVNode("span", { class: "button-text" }, " (1 \u043C\u0438\u043D 20 \u0441\u0435\u043A) ")
                        ];
                      }
                    }),
                    _: 1
                  }, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(AppButton, {
                      class: "main-page__button",
                      "data-fancybox": `finesBlock`,
                      href: ""
                    }, {
                      default: withCtx(() => [
                        createVNode("figure", { class: "ibg main-page__icon" }, [
                          createVNode("img", {
                            src: _imports_0,
                            alt: "\u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435"
                          })
                        ]),
                        createTextVNode(" \u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435 "),
                        createVNode("span", { class: "button-text" }, " (1 \u043C\u0438\u043D 20 \u0441\u0435\u043A) ")
                      ]),
                      _: 1
                    })
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div></form><figure class="ibg main-page__image"${_scopeId}><img${ssrRenderAttr("src", previewImage[(_a = unref(route).params) == null ? void 0 : _a.type] ? previewImage[(_b = unref(route).params) == null ? void 0 : _b.type] : unref(defaultImage))} alt="\u041F\u0440\u043E\u0432\u0435\u0440\u044C\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u0438 \u0437\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u0443\u0439\u0442\u0435\u0441\u044C \u0432 1 \u043A\u043B\u0438\u043A"${_scopeId}></figure>`);
            _push2(ssrRenderComponent(FinesWarning, {
              onCallAction: (data) => saveChanges()
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode("form", { class: "main-page__form" }, [
                createVNode("div", { class: "main-page__container" }, [
                  createVNode(AppH1, { class: "main-page__form-title" }, {
                    default: withCtx(() => [
                      createTextVNode("\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E - \u0443\u0434\u043E\u0431\u043D\u043E\u0435 \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u0435 \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043E\u043C "),
                      createVNode("p", { class: "main-page__form-subtitle" }, "\u041F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u043E\u0442\u0441\u043B\u0435\u0436\u0438\u0432\u0430\u0442\u044C \u0438 \u043E\u043F\u043B\u0430\u0447\u0438\u0432\u0430\u0442\u044C \u0432\u0441\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u043B\u0438\u0447\u043D\u043E\u0439 \u043C\u0430\u0448\u0438\u043D\u0435 \u0438\u043B\u0438 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u0443 \u043C\u0430\u0448\u0438\u043D.")
                    ]),
                    _: 1
                  }),
                  createVNode("div", { class: "main-page__form-actions main-page__actions" }, [
                    createVNode(FansyBox, { class: "main-page__fansy-box" }, {
                      default: withCtx(() => [
                        createVNode(AppButton, {
                          class: "main-page__button",
                          "data-fancybox": `finesBlock`,
                          href: ""
                        }, {
                          default: withCtx(() => [
                            createVNode("figure", { class: "ibg main-page__icon" }, [
                              createVNode("img", {
                                src: _imports_0,
                                alt: "\u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435"
                              })
                            ]),
                            createTextVNode(" \u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435 "),
                            createVNode("span", { class: "button-text" }, " (1 \u043C\u0438\u043D 20 \u0441\u0435\u043A) ")
                          ]),
                          _: 1
                        })
                      ]),
                      _: 1
                    })
                  ])
                ])
              ]),
              createVNode("figure", { class: "ibg main-page__image" }, [
                createVNode("img", {
                  src: previewImage[(_c = unref(route).params) == null ? void 0 : _c.type] ? previewImage[(_d = unref(route).params) == null ? void 0 : _d.type] : unref(defaultImage),
                  alt: "\u041F\u0440\u043E\u0432\u0435\u0440\u044C\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u0438 \u0437\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u0443\u0439\u0442\u0435\u0441\u044C \u0432 1 \u043A\u043B\u0438\u043A"
                }, null, 8, ["src"])
              ]),
              createVNode(FinesWarning, {
                onCallAction: (data) => saveChanges()
              }, null, 8, ["onCallAction"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/MainPage/Fines/Fines.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const MainFines = _sfc_main$4;
const _sfc_main$3 = {
  __name: "Tariffs",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "main__tariffs section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0422\u0430\u0440\u0438\u0444\u044B `);
                } else {
                  return [
                    createTextVNode(" \u0422\u0430\u0440\u0438\u0444\u044B ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(TariffsSlider, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u0422\u0430\u0440\u0438\u0444\u044B ")
                ]),
                _: 1
              }),
              createVNode(TariffsSlider)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Tariffs/Tariffs.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const MainTariffs = _sfc_main$3;
const _sfc_main$2 = {
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
        text: "\u0420\u0430\u0437\u043B\u0438\u0447\u043D\u044B\u0435 \u0443\u0440\u043E\u0432\u043D\u0438 \u0434\u043E\u0441\u0442\u0443\u043F\u0430 \u043A \u043F\u0440\u043E\u0433\u0440\u0430\u043C\u043C\u0435 \u0443 \u043A\u0430\u0436\u0434\u043E\u0433\u043E \u0441\u043E\u0442\u0440\u0443\u0434\u043D\u0438\u043A\u0430"
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
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/MainPage/PlusesFines/PlusesFines.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const PlusesFines = _sfc_main$2;
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
      }
    ]);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_AppBreadcrambs = AppBreadcrambs;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(_component_AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      _push(ssrRenderComponent(MainFines, null, null, _parent));
      _push(ssrRenderComponent(MainCompanies, {
        list: _ctx.fines,
        title: "\u0423\u0436\u0435 \u0431\u043E\u043B\u0435\u0435 1000 \u043A\u043B\u0438\u0435\u043D\u0442\u043E\u0432 \u043F\u043E\u043B\u044C\u0437\u0443\u044E\u0442\u0441\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u043E\u043C \u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E."
      }, null, _parent));
      _push(ssrRenderComponent(PlusesFines, null, null, _parent));
      _push(ssrRenderComponent(MainTariffs, null, null, _parent));
      _push(ssrRenderComponent(MainQuestions, null, null, _parent));
      _push(ssrRenderComponent(MainArticles, { class: "main__questinos" }, null, _parent));
      _push(ssrRenderComponent(CommonSocial, { class: "main__social" }, null, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/MainPage/Main.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const TemplateMain = _sfc_main$1;
const _sfc_main = {
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u041E\u043D\u043B\u0430\u0439\u043D-\u0441\u0435\u0440\u0432\u0438\u0441 \u0434\u043B\u044F \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u0435 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u043E\u043C | Compas.pro",
      meta: [
        {
          name: "description",
          content: "Compas.pro \u2014 \u0443\u0434\u043E\u0431\u043D\u044B\u0439 \u043E\u043D\u043B\u0430\u0439\u043D-\u0441\u0435\u0440\u0432\u0438\u0441 \u0434\u043B\u044F \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u044F \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u043E\u043C. \u041A\u043E\u043D\u0442\u0440\u043E\u043B\u0438\u0440\u0443\u0439\u0442\u0435 \u0441\u043E\u0441\u0442\u043E\u044F\u043D\u0438\u0435 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u0430 \u0432 \u043E\u0434\u043D\u043E\u043C \u043C\u0435\u0441\u0442\u0435 \u0438 \u044D\u043A\u043E\u043D\u043E\u043C\u044C\u0442\u0435 \u0432\u0440\u0435\u043C\u044F \u0438 \u0434\u0435\u043D\u044C\u0433\u0438!"
        }
      ],
      link: [
        {
          rel: "canonical",
          href: "https://compas.pro/"
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-921546a4.mjs.map
