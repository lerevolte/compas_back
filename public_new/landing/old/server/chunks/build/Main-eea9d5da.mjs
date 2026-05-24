import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import { useSSRContext, ref, provide, watch, computed, unref, mergeModels, toRefs, useModel, mergeProps, withCtx, createTextVNode, createVNode, openBlock, createBlock, Fragment, renderList, toDisplayString, watchEffect, withModifiers, withDirectives, vShow, isRef, inject, createCommentVNode } from 'vue';
import { u as useRoute, B as AppTabs, n as navigateTo, A as AppH2, y as useCommonStore, x as AppH1, j as AppInput, k as AppButton, C as AppH3, _ as _export_sfc, e as __nuxt_component_0, b as api, o as AppWarning$1, g as AppSelect } from './server.mjs';
import { ssrRenderComponent, ssrRenderAttrs, ssrRenderList, ssrRenderAttr, ssrInterpolate, ssrRenderClass } from 'vue/server-renderer';
import { A as AppSection } from './AppSection-1ea634ac.mjs';
import { C as CommonProgramm, Y as YoutubeWhite, g as gibdd, p as parking, f as fspp, m as madi, a as mugand, i as infinity, b as mileage, c as protection, n as notification, r as receipt, d as reduction } from './Programm-1c3794ff.mjs';
import { _ as _imports_0 } from './youtube_blue-a00a4300.mjs';
import { F as FansyBox, V as ValidateField, A as AppRelation, a as AppTextarea, b as AppStatus, c as AppDate, d as AppFile } from './Validate-398d291a.mjs';
import { u as useFinesStore } from './finesStore-67f46f86.mjs';
import { M as MainArticles, s as stsImage, v as vuImage, p as postanovlenieImage, g as gosImage, i as innImage } from './preview-inn-d36097f7.mjs';
import { M as MainAbout } from './WrapText-f5da5fca.mjs';
import { M as MainCompanies, a as MainQuestions } from './Companies-75bbb9ed.mjs';
import { C as CommonSocial } from './Social-983a064f.mjs';

const _sfc_main$9 = {
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
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Base/Base.vue");
  return _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const MainBase = _sfc_main$9;
const _sfc_main$8 = {
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
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Fines/Warning/Validation/Validation.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const WarningValidation = _sfc_main$8;
const _sfc_main$7 = {
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
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Fines/Warning/Warning.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const FinesWarning = _sfc_main$7;
const defaultImage = "" + publicAssetsURL("main/fines/preview.png");
const _sfc_main$6 = {
  __name: "Fines",
  __ssrInlineRender: true,
  setup(__props) {
    const finesStore = useFinesStore();
    useCommonStore();
    const route = useRoute();
    const previewImage = {
      "po-sts": stsImage,
      "po-voditelskomu-udostovereniyu": vuImage,
      "po-nomeru-postanovleniya": postanovlenieImage,
      "po-nomeru-avto": gosImage,
      "po-inn": innImage
    };
    const titleMap = {
      "po-sts": "\u043F\u043E \u0421\u0422\u0421",
      "po-voditelskomu-udostovereniyu": "\u043F\u043E \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u043E\u043C\u0443 \u0443\u0434\u043E\u0441\u0442\u043E\u0432\u0435\u0440\u0435\u043D\u0438\u044E",
      "po-nomeru-postanovleniya": "\u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F",
      "po-nomeru-avto": "\u043F\u043E \u0433\u043E\u0441. \u043D\u043E\u043C\u0435\u0440\u0443",
      "po-inn": "\u043F\u043E \u0418\u041D\u041D"
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
              placeholder: "00 XX 000000",
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
              type: "text",
              mask: "#########################",
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
              mask: "##########",
              value: "",
              required: true,
              placeholder: "0000000000",
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
              placeholder: "000000000",
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
    const changeValue = (data) => {
      let findIndex = form.value.findIndex((p) => p.key == data.key);
      form.value[findIndex].value = data.value;
    };
    const saveChanges = async () => {
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
          const res = await api.callMethod("GET", `gibdd/check_by_req?` + new URLSearchParams(formData.value).toString(), { ...formData.value, tariff: 1 });
          if (Array.isArray(res)) {
            finesStore.fields = formData.value;
            finesStore.fines = res;
            new Promise((res2) => {
              return navigateTo("/products/fines/list");
            }).then(() => {
              isLoading.value = false;
            });
            for (let elem of form.value) {
              elem.value = "";
            }
          }
        }
      };
      const checkingFields = async () => {
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
      try {
        invalidFields.value = [];
        await checkingFields();
        await initSave();
      } catch (error) {
        console.log(error);
      } finally {
      }
    };
    provide("form", form);
    provide("isShow", isShow);
    provide("invalidFields", invalidFields);
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "fines section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a, _b, _c, _d;
          if (_push2) {
            _push2(ssrRenderComponent(AppH1, { class: "fines__title" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 ${ssrInterpolate(titleMap[unref(route).params.type])} \u0432 1 \u043A\u043B\u0438\u043A `);
                } else {
                  return [
                    createTextVNode(" \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 " + toDisplayString(titleMap[unref(route).params.type]) + " \u0432 1 \u043A\u043B\u0438\u043A ", 1)
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<form class="fines__form"${_scopeId}>`);
            _push2(ssrRenderComponent(AppH1, { class: "fines__form-title" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 ${ssrInterpolate(titleMap[unref(route).params.type])} \u0432 1 \u043A\u043B\u0438\u043A `);
                } else {
                  return [
                    createTextVNode(" \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 " + toDisplayString(titleMap[unref(route).params.type]) + " \u0432 1 \u043A\u043B\u0438\u043A ", 1)
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<!--[-->`);
            ssrRenderList(unref(form), (item) => {
              _push2(ssrRenderComponent(AppInput, {
                class: item.class,
                style: unref(form).find((i) => ["sts", "vu", "uin", "gos"].includes(i.key)) && unref(form)[0].value != "" || ["number", "certificate", "sts", "vu", "uin", "gos", "inn", "kpp"].includes(item.key) || unref(form)[0].value != "" && unref(form)[1].value != "" ? null : { display: "none" },
                item: {
                  focus: false,
                  id: 0,
                  placeholder: item.placeholder,
                  key: item.key,
                  type: item.type,
                  title: item.title,
                  substring: null,
                  required: item.required,
                  external_link: null,
                  value: item.value
                },
                disabled: unref(isLoading),
                mask: item.mask,
                isLink: null,
                isReadOnly: false,
                enabledAutocomplete: false,
                onChangeValue: (data) => changeValue(data)
              }, null, _parent2, _scopeId));
            });
            _push2(`<!--]--><div class="fines__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              class: ["button_blue", { button_loading: unref(isLoading) }],
              onClick: ($event) => saveChanges(),
              disabled: unref(isLoading)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B `);
                } else {
                  return [
                    createTextVNode(" \u041F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(FansyBox, { class: "fines__fansy-box" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(AppButton, {
                    class: "fines__button",
                    "data-fancybox": `finesBlock`,
                    href: ""
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        _push4(`<figure class="ibg fines__icon"${_scopeId3}><img${ssrRenderAttr("src", _imports_0)} alt="\u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435"${_scopeId3}></figure> \u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435 <span class="button-text"${_scopeId3}> (1 \u043C\u0438\u043D 20 \u0441\u0435\u043A) </span>`);
                      } else {
                        return [
                          createVNode("figure", { class: "ibg fines__icon" }, [
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
                      class: "fines__button",
                      "data-fancybox": `finesBlock`,
                      href: ""
                    }, {
                      default: withCtx(() => [
                        createVNode("figure", { class: "ibg fines__icon" }, [
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
            _push2(`</div><div class="fines__politics"${_scopeId}>\u041D\u0430\u0436\u0438\u043C\u0430\u044F \xAB\u041F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B\xBB \u0432\u044B \u0441\u043E\u0433\u043B\u0430\u0448\u0430\u0435\u0442\u0435\u0441\u044C \u0441 \u043F\u043E\u043B\u0438\u0442\u0438\u043A\u043E\u0439 \u043E\u0431\u0440\u0430\u0431\u043E\u0442\u043A\u0438 \u043F\u0435\u0440\u0441\u043E\u043D\u0430\u043B\u044C\u043D\u044B\u0445 \u0434\u0430\u043D\u043D\u044B\u0445 \u0438 \u043F\u0440\u0438\u043D\u0438\u043C\u0430\u0435\u0442\u0435 \u043E\u0444\u0435\u0440\u0442\u0443</div></form><figure class="ibg fines__image"${_scopeId}><img${ssrRenderAttr("src", previewImage[(_a = unref(route).params) == null ? void 0 : _a.type] ? previewImage[(_b = unref(route).params) == null ? void 0 : _b.type] : unref(defaultImage))} alt="\u041F\u0440\u043E\u0432\u0435\u0440\u044C\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u0438 \u0437\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u0443\u0439\u0442\u0435\u0441\u044C \u0432 1 \u043A\u043B\u0438\u043A"${_scopeId}></figure>`);
            _push2(ssrRenderComponent(FinesWarning, {
              onCallAction: (data) => saveChanges()
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(AppH1, { class: "fines__title" }, {
                default: withCtx(() => [
                  createTextVNode(" \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 " + toDisplayString(titleMap[unref(route).params.type]) + " \u0432 1 \u043A\u043B\u0438\u043A ", 1)
                ]),
                _: 1
              }),
              createVNode("form", {
                class: "fines__form",
                onClick: withModifiers(() => {
                }, ["prevent"])
              }, [
                createVNode(AppH1, { class: "fines__form-title" }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 " + toDisplayString(titleMap[unref(route).params.type]) + " \u0432 1 \u043A\u043B\u0438\u043A ", 1)
                  ]),
                  _: 1
                }),
                (openBlock(true), createBlock(Fragment, null, renderList(unref(form), (item) => {
                  return withDirectives((openBlock(), createBlock(AppInput, {
                    class: item.class,
                    item: {
                      focus: false,
                      id: 0,
                      placeholder: item.placeholder,
                      key: item.key,
                      type: item.type,
                      title: item.title,
                      substring: null,
                      required: item.required,
                      external_link: null,
                      value: item.value
                    },
                    disabled: unref(isLoading),
                    mask: item.mask,
                    isLink: null,
                    isReadOnly: false,
                    enabledAutocomplete: false,
                    onChangeValue: (data) => changeValue(data)
                  }, null, 8, ["class", "item", "disabled", "mask", "onChangeValue"])), [
                    [vShow, unref(form).find((i) => ["sts", "vu", "uin", "gos"].includes(i.key)) && unref(form)[0].value != "" || ["number", "certificate", "sts", "vu", "uin", "gos", "inn", "kpp"].includes(item.key) || unref(form)[0].value != "" && unref(form)[1].value != ""]
                  ]);
                }), 256)),
                createVNode("div", { class: "fines__actions" }, [
                  createVNode(AppButton, {
                    class: ["button_blue", { button_loading: unref(isLoading) }],
                    onClick: ($event) => saveChanges(),
                    disabled: unref(isLoading)
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" \u041F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B ")
                    ]),
                    _: 1
                  }, 8, ["onClick", "disabled", "class"]),
                  createVNode(FansyBox, { class: "fines__fansy-box" }, {
                    default: withCtx(() => [
                      createVNode(AppButton, {
                        class: "fines__button",
                        "data-fancybox": `finesBlock`,
                        href: ""
                      }, {
                        default: withCtx(() => [
                          createVNode("figure", { class: "ibg fines__icon" }, [
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
                ]),
                createVNode("div", { class: "fines__politics" }, "\u041D\u0430\u0436\u0438\u043C\u0430\u044F \xAB\u041F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B\xBB \u0432\u044B \u0441\u043E\u0433\u043B\u0430\u0448\u0430\u0435\u0442\u0435\u0441\u044C \u0441 \u043F\u043E\u043B\u0438\u0442\u0438\u043A\u043E\u0439 \u043E\u0431\u0440\u0430\u0431\u043E\u0442\u043A\u0438 \u043F\u0435\u0440\u0441\u043E\u043D\u0430\u043B\u044C\u043D\u044B\u0445 \u0434\u0430\u043D\u043D\u044B\u0445 \u0438 \u043F\u0440\u0438\u043D\u0438\u043C\u0430\u0435\u0442\u0435 \u043E\u0444\u0435\u0440\u0442\u0443")
              ], 8, ["onClick"]),
              createVNode("figure", { class: "ibg fines__image" }, [
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
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Fines/Fines.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const MainFines = _sfc_main$6;
const _sfc_main$5 = {
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
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Pluses/Pluses.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const MainPluses = _sfc_main$5;
const _sfc_main$4 = {
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
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Steps/components/Step/Step.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const Step = _sfc_main$4;
const _sfc_main$3 = {
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
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "steps__list" }, _attrs))} data-v-7bd8da1d><!--[-->`);
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
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Steps/components/StepsList/StepsList.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const StepsList = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["__scopeId", "data-v-7bd8da1d"]]);
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
const _sfc_main$2 = {
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
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Steps/Steps.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const MainSteps = _sfc_main$2;
const _sfc_main$1 = {
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
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/PlusesFines/PlusesFines.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const PlusesFines = _sfc_main$1;
const aboutJson = {
  "default": {
    text: "<h2>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414</h2><p>\u0415\u0436\u0435\u0433\u043E\u0434\u043D\u043E \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u0438 \u043F\u043E\u043B\u0443\u0447\u0430\u044E\u0442 \u0448\u0442\u0440\u0430\u0444\u044B \u043D\u0430 \u0441\u043E\u0442\u043D\u0438 \u043C\u0438\u043B\u043B\u0438\u043E\u043D\u043E\u0432 \u0440\u0443\u0431\u043B\u0435\u0439 \u0438 92% \u043E\u0442 \u0432\u0441\u0435\u0433\u043E \u043A\u043E\u043B\u0438\u0447\u0435\u0441\u0442\u0432\u0430 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u0439 \u0444\u0438\u043A\u0441\u0438\u0440\u0443\u044E\u0442\u0441\u044F \u0434\u043E\u0440\u043E\u0436\u043D\u044B\u043C\u0438 \u043A\u0430\u043C\u0435\u0440\u0430\u043C\u0438. \u041E\u0441\u043D\u043E\u0432\u043D\u044B\u0435 \u043F\u0440\u0438\u0447\u0438\u043D\u044B \u043D\u0430\u043B\u043E\u0436\u0435\u043D\u0438\u044F \u0448\u0442\u0440\u0430\u0444\u043E\u0432:</p><ul>  <li>\u043F\u0440\u0435\u0432\u044B\u0448\u0435\u043D\u0438\u0435 \u0441\u043A\u043E\u0440\u043E\u0441\u0442\u0438 \u0434\u0432\u0438\u0436\u0435\u043D\u0438\u044F \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043D\u043E\u0433\u043E \u0441\u0440\u0435\u0434\u0441\u0442\u0432\u0430;</li>  <li>\u0438\u0433\u043D\u043E\u0440\u0438\u0440\u043E\u0432\u0430\u043D\u0438\u0435 \u0442\u0440\u0435\u0431\u043E\u0432\u0430\u043D\u0438\u0439 \u0434\u043E\u0440\u043E\u0436\u043D\u044B\u0445 \u0437\u043D\u0430\u043A\u043E\u0432 \u0438 \u0440\u0430\u0437\u043C\u0435\u0442\u043A\u0438;</li>  <li>\u043D\u0435\u043F\u0440\u0438\u0441\u0442\u0435\u0433\u043D\u0443\u0442\u044B\u0439 \u0440\u0435\u043C\u0435\u043D\u044C \u0431\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u0438 (\u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u0438\u0439 \u0438 \u043F\u0430\u0441\u0441\u0430\u0436\u0438\u0440\u0441\u043A\u0438\u0439);</li>  <li>\u043F\u0440\u043E\u0435\u0437\u0434 \u043D\u0430 \u0437\u0430\u043F\u0440\u0435\u0449\u0430\u044E\u0449\u0438\u0439 \u0441\u0438\u0433\u043D\u0430\u043B \u0441\u0432\u0435\u0442\u043E\u0444\u043E\u0440\u0430;</li>  <li>\u043D\u0435\u043F\u0440\u0430\u0432\u0438\u043B\u044C\u043D\u0430\u044F \u043F\u0430\u0440\u043A\u043E\u0432\u043A\u0430, \u0442\u043E \u0435\u0441\u0442\u044C \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u0435 \u043F\u0440\u0430\u0432\u0438\u043B \u0440\u0430\u0437\u043C\u0435\u0449\u0435\u043D\u0438\u044F \u0430\u0432\u0442\u043E \u043D\u0430 \u043F\u0440\u043E\u0435\u0437\u0436\u0435\u0439 \u0447\u0430\u0441\u0442\u0438.</li></ul><p>\u0425\u043E\u0442\u0438\u0442\u0435 \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C, \u043D\u0435\u0442 \u043B\u0438 \u043D\u0430 \u0432\u0430\u0448\u0435\u043C \u043B\u0438\u0447\u043D\u043E\u043C \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u0435 \u0438\u043B\u0438 \u043A\u043E\u043C\u043C\u0435\u0440\u0447\u0435\u0441\u043A\u043E\u043C \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u043E\u0432? \u0412\u043E\u0441\u043F\u043E\u043B\u044C\u0437\u0443\u0439\u0442\u0435\u0441\u044C \u043D\u0430\u0448\u0438\u043C \u0441\u0435\u0440\u0432\u0438\u0441\u043E\u043C. \u041F\u0440\u0435\u0434\u043E\u0441\u0442\u0430\u0432\u043B\u044F\u0435\u043C \u0442\u043E\u043B\u044C\u043A\u043E \u0430\u043A\u0442\u0443\u0430\u043B\u044C\u043D\u0443\u044E \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044E, \u043F\u0440\u0438\u0447\u0435\u043C \u043F\u0440\u0430\u043A\u0442\u0438\u0447\u0435\u0441\u043A\u0438 \u0432 \u043E\u0434\u0438\u043D \u043A\u043B\u0438\u043A.</p><h3>\u0417\u0430\u0447\u0435\u043C \u043F\u0440\u043E\u0432\u0435\u0440\u044F\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B</h3><p>\u041D\u0430 \u043E\u043F\u043B\u0430\u0442\u0443 \u0448\u0442\u0440\u0430\u0444\u0430 \u043E\u0442 \u0413\u0418\u0411\u0414\u0414 \u0434\u0430\u0435\u0442\u0441\u044F 60 \u0434\u043D\u0435\u0439 \u0441\u043E \u0434\u043D\u044F \u0432\u0441\u0442\u0443\u043F\u043B\u0435\u043D\u0438\u044F \u0435\u0433\u043E \u0432 \u0437\u0430\u043A\u043E\u043D\u043D\u0443\u044E \u0441\u0438\u043B\u0443. \u0415\u0441\u043B\u0438 \u044D\u0442\u043E\u0433\u043E \u043D\u0435 \u0441\u0434\u0435\u043B\u0430\u0442\u044C, \u0441\u0443\u043C\u043C\u0430 \u0443\u0432\u0435\u043B\u0438\u0447\u0438\u0442\u0441\u044F \u0432 2 \u0440\u0430\u0437\u0430, \u0432\u043E\u0437\u043C\u043E\u0436\u043D\u044B \u0441\u0430\u043D\u043A\u0446\u0438\u0438 \u0432 \u0432\u0438\u0434\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u044B\u0445 \u0440\u0430\u0431\u043E\u0442 \u0438 \u0434\u0430\u0436\u0435 \u0430\u0434\u043C\u0438\u043D\u0438\u0441\u0442\u0440\u0430\u0442\u0438\u0432\u043D\u043E\u0433\u043E \u0430\u0440\u0435\u0441\u0442\u0430. \u0427\u0442\u043E\u0431\u044B \u0441\u0438\u0442\u0443\u0430\u0446\u0438\u044F \u043D\u0435 \u0443\u0441\u0443\u0433\u0443\u0431\u0438\u043B\u0430\u0441\u044C, \u0441\u043E\u0432\u0435\u0442\u0443\u0435\u043C \u043F\u0435\u0440\u0438\u043E\u0434\u0438\u0447\u0435\u0441\u043A\u0438 \u043F\u0440\u043E\u0432\u0435\u0440\u044F\u0442\u044C \u043D\u0430\u043B\u0438\u0447\u0438\u0435 \u0448\u0442\u0440\u0430\u0444\u043E\u0432.</p><p>\u0412\u043E\u0441\u043F\u043E\u043B\u044C\u0437\u0443\u0439\u0442\u0435\u0441\u044C \u043D\u0430\u0448\u0438\u043C \u0441\u0435\u0440\u0432\u0438\u0441\u043E\u043C \u043F\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0435 \u0448\u0442\u0440\u0430\u0444\u043E\u0432, \u0435\u0441\u043B\u0438:</p><ul>  <li>\u0431\u0443\u043C\u0430\u0436\u043D\u0430\u044F \u043A\u043E\u043F\u0438\u044F \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F \u043F\u043E\u0447\u0435\u043C\u0443-\u0442\u043E \u043D\u0435 \u043F\u0440\u0438\u0448\u043B\u0430 \u0437\u0430\u043A\u0430\u0437\u043D\u044B\u043C \u043F\u0438\u0441\u044C\u043C\u043E\u043C (\u043D\u0430\u043F\u0440\u0438\u043C\u0435\u0440, \u0431\u044B\u043B\u0430 \u0443\u0442\u0435\u0440\u044F\u043D\u0430);</li>  <li>\u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044F \u043E \u0448\u0442\u0440\u0430\u0444\u0435 \u043E\u0442 \u0413\u0418\u0411\u0414\u0414 \u043D\u0435 \u043E\u0431\u043D\u043E\u0432\u0438\u043B\u0430\u0441\u044C \u043D\u0430 \u043F\u043E\u0440\u0442\u0430\u043B\u0435 \u0413\u043E\u0441\u0443\u0441\u043B\u0443\u0433 \u0438\u0437-\u0437\u0430 \u0442\u0435\u0445\u043D\u0438\u0447\u0435\u0441\u043A\u043E\u0433\u043E \u0441\u0431\u043E\u044F;</li>  <li>\u0432 \u0431\u0430\u0437\u0435 \u0413\u043E\u0441\u0430\u0432\u0442\u043E\u0438\u043D\u0441\u043F\u0435\u043A\u0446\u0438\u0438 \u043D\u0435\u0432\u0435\u0440\u043D\u043E \u0443\u043A\u0430\u0437\u0430\u043D\u044B \u0434\u0430\u043D\u043D\u044B\u0435 \u043E \u0441\u043E\u0431\u0441\u0442\u0432\u0435\u043D\u043D\u0438\u043A\u0435 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044F: \u0424\u0418\u041E, \u043A\u043E\u043D\u0442\u0430\u043A\u0442\u043D\u0430\u044F \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044F, \u0441\u0432\u0435\u0434\u0435\u043D\u0438\u044F \u043E \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u0435.</li></ul><h3>\u0427\u0442\u043E \u0434\u0435\u043B\u0430\u0442\u044C, \u0435\u0441\u043B\u0438 \u0448\u0442\u0440\u0430\u0444 \u043D\u0435 \u043F\u0440\u0438\u0448\u0435\u043B \u0432\u043E\u0432\u0440\u0435\u043C\u044F</h3><p>\u0428\u0442\u0440\u0430\u0444\u044B \u043E\u0442 \u0413\u0418\u0411\u0414\u0414 \u043D\u0435 \u0432\u0441\u0435\u0433\u0434\u0430 \u043F\u0440\u0438\u0445\u043E\u0434\u044F\u0442 \u0432\u043E\u0432\u0440\u0435\u043C\u044F \u0438\u0437-\u0437\u0430 \u0447\u0435\u043B\u043E\u0432\u0435\u0447\u0435\u0441\u043A\u043E\u0433\u043E \u0444\u0430\u043A\u0442\u043E\u0440\u0430, \u0442\u0435\u0445\u043D\u0438\u0447\u0435\u0441\u043A\u043E\u0433\u043E \u0441\u0431\u043E\u044F, \u043F\u043E \u0434\u0440\u0443\u0433\u0438\u043C \u043F\u0440\u0438\u0447\u0438\u043D\u0430\u043C. \u0421\u0430\u043C \u0436\u0435 \u043F\u0440\u043E\u0446\u0435\u0441\u0441 \u0432\u044B\u0441\u0442\u0430\u0432\u043B\u0435\u043D\u0438\u044F \u0448\u0442\u0440\u0430\u0444\u0430 \u0432\u044B\u0433\u043B\u044F\u0434\u0438\u0442 \u0442\u0430\u043A:</p><ul>  <li>\u0418\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044F \u043F\u043E \u0444\u0438\u043A\u0441\u0430\u0446\u0438\u0438 \u043F\u0440\u0430\u0432\u043E\u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F \u043D\u0430\u043F\u0440\u0430\u0432\u043B\u044F\u0435\u0442\u0441\u044F \u0432 \u0426\u0410\u0424\u0410\u041F \u0432 \u0442\u043E\u0442 \u0436\u0435 \u0434\u0435\u043D\u044C. \u0424\u043E\u0442\u043E \u0441 \u043C\u043E\u0431\u0438\u043B\u044C\u043D\u044B\u0445 \u0438 \u043F\u0435\u0440\u0435\u0434\u0432\u0438\u0436\u043D\u044B\u0445 \u043A\u0430\u043C\u0435\u0440 \u043C\u043E\u0433\u0443\u0442 \u043F\u0435\u0440\u0435\u0434\u0430\u0432\u0430\u0442\u044C\u0441\u044F \u0434\u043E 2 \u0434\u043D\u0435\u0439.</li>  <li>\u041E\u0431\u0440\u0430\u0431\u043E\u0442\u043A\u0430 \u0438 \u0432\u044B\u043D\u0435\u0441\u0435\u043D\u0438\u0435 \u0440\u0435\u0448\u0435\u043D\u0438\u044F \u0437\u0430\u043D\u0438\u043C\u0430\u044E\u0442 \u0434\u043E 15 \u0434\u043D\u0435\u0439.</li>  <li>\u041D\u0430 \u043E\u0442\u043F\u0440\u0430\u0432\u043A\u0443 \u0448\u0442\u0440\u0430\u0444\u0430 \u0430\u0434\u0440\u0435\u0441\u0430\u0442\u0443 \u0434\u0430\u0435\u0442\u0441\u044F 3 \u0434\u043D\u044F.</li></ul><p>\u041D\u0430 \u043D\u0430\u0448\u0435\u043C \u0441\u0435\u0440\u0432\u0438\u0441\u0435 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044F \u043E \u0448\u0442\u0440\u0430\u0444\u0435 \u043F\u043E\u044F\u0432\u043B\u044F\u0435\u0442\u0441\u044F \u0441\u0440\u0430\u0437\u0443 \u0436\u0435 \u043F\u043E\u0441\u043B\u0435 \u043E\u0431\u0440\u0430\u0431\u043E\u0442\u043A\u0438 \u0432\u044B\u043D\u0435\u0441\u0435\u043D\u0438\u044F \u0440\u0435\u0448\u0435\u043D\u0438\u044F. \u041A\u043B\u0438\u0435\u043D\u0442\u044B \u043F\u043E\u043B\u0443\u0447\u0430\u044E\u0442 \u043F\u0438\u0441\u044C\u043C\u0430 \u043C\u0430\u043A\u0441\u0438\u043C\u0443\u043C \u0447\u0435\u0440\u0435\u0437 18 \u0434\u043D\u0435\u0439: 3 \u0434\u043D\u044F \u043D\u0430 \u043E\u0442\u043F\u0440\u0430\u0432\u043A\u0443 \u0438 \u0434\u043E 15 \u0434\u043D\u0435\u0439 \u043D\u0430 \u0434\u043E\u0441\u0442\u0430\u0432\u043A\u0443.</p><p>\u0415\u0441\u043B\u0438 \u0436\u0435 \u0432\u044B \u0437\u043D\u0430\u0435\u0442\u0435, \u0447\u0442\u043E \u043D\u0430\u0440\u0443\u0448\u0438\u043B\u0438, \u043D\u043E \u0448\u0442\u0440\u0430\u0444 \u043D\u0435 \u043F\u0440\u0438\u0445\u043E\u0434\u0438\u0442, \u0437\u0430\u0439\u0434\u0438\u0442\u0435 \u043D\u0430 \u043E\u0444\u0438\u0446\u0438\u0430\u043B\u044C\u043D\u044B\u0439 \u0441\u0430\u0439\u0442 \u0413\u0418\u0411\u0414\u0414 \u0438\u043B\u0438 \u043F\u0440\u043E\u0432\u0435\u0440\u044C\u0442\u0435 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044E \u0432 \u0441\u0432\u043E\u0435\u043C \u0430\u043A\u043A\u0430\u0443\u043D\u0442\u0435 \u043D\u0430 \u0413\u043E\u0441\u0443\u0441\u043B\u0443\u0433\u0430\u0445. \u041D\u043E \u0443\u0434\u043E\u0431\u043D\u0435\u0435 \u0432\u0441\u0435\u0433\u043E \u0443\u0431\u0435\u0434\u0438\u0442\u044C\u0441\u044F \u0432 \u043D\u0430\u043B\u0438\u0447\u0438\u0438 \u0448\u0442\u0440\u0430\u0444\u0430 \u043D\u0430 \u043D\u0430\u0448\u0435\u043C \u0441\u0430\u0439\u0442\u0435. \u0414\u043B\u044F \u044D\u0442\u043E\u0433\u043E \u0432\u0430\u043C \u043D\u0443\u0436\u043D\u043E \u0432\u0432\u0435\u0441\u0442\u0438 \u043C\u0438\u043D\u0438\u043C\u0430\u043B\u044C\u043D\u044B\u0439 \u043D\u0430\u0431\u043E\u0440 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u0438: \u043D\u043E\u043C\u0435\u0440 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044F, \u0440\u0435\u0433\u0438\u043E\u043D, \u0441\u0432\u0438\u0434\u0435\u0442\u0435\u043B\u044C\u0441\u0442\u0432\u043E \u043E \u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0430\u0446\u0438\u0438 \u0422\u0421.</p><h3>\u041A\u0430\u043A \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444</h3><p>\u041F\u0435\u0440\u0435\u0434 \u043E\u043F\u043B\u0430\u0442\u043E\u0439 \u043D\u0443\u0436\u043D\u043E \u0443\u0431\u0435\u0434\u0438\u0442\u044C\u0441\u044F, \u0441\u043E\u043E\u0442\u0432\u0435\u0442\u0441\u0442\u0432\u0443\u0435\u0442 \u043B\u0438 \u0437\u0430\u044F\u0432\u043B\u0435\u043D\u043D\u0430\u044F \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044F \u0440\u0435\u0430\u043B\u044C\u043D\u043E\u0439: \u0441\u0432\u0435\u0440\u044C\u0442\u0435 \u043D\u043E\u043C\u0435\u0440 \u0421\u0422\u0421 \u0438 \u043D\u043E\u043C\u0435\u0440 \u0441\u0432\u043E\u0438\u0445 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u0438\u0445 \u043F\u0440\u0430\u0432, \u043F\u0440\u043E\u0432\u0435\u0440\u044C\u0442\u0435, \u0432\u0430\u0448\u0430 \u043B\u0438 \u043C\u0430\u0448\u0438\u043D\u0430 \u043D\u0430 \u0444\u043E\u0442\u043E (\u0435\u0441\u043B\u0438 \u043D\u0435\u0442, \u0435\u0441\u0442\u044C 10 \u0434\u043D\u0435\u0439 \u043D\u0430 \u043E\u0441\u043F\u0430\u0440\u0438\u0432\u0430\u043D\u0438\u0435), \u0432\u0441\u043F\u043E\u043C\u043D\u0438\u0442\u0435, \u0434\u0435\u0439\u0441\u0442\u0432\u0438\u0442\u0435\u043B\u044C\u043D\u043E \u0431\u044B\u043B\u0438 \u043B\u0438 \u0432\u044B \u043D\u0430 \u043C\u0435\u0441\u0442\u0435 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F \u041F\u0414\u0414.</p><p>\u0415\u0441\u043B\u0438 \u0441 \u043F\u0435\u0440\u0435\u0447\u0438\u0441\u043B\u0435\u043D\u043D\u044B\u043C\u0438 \u0432\u043E\u043F\u0440\u043E\u0441\u0430\u043C\u0438 \u0432\u0441\u0435 \u043D\u043E\u0440\u043C\u0430\u043B\u044C\u043D\u043E, \u043F\u0435\u0440\u0435\u0445\u043E\u0434\u0438\u0442\u0435 \u043A \u043E\u043F\u043B\u0430\u0442\u0435. \u0418 \u0447\u0435\u043C \u0440\u0430\u043D\u044C\u0448\u0435 \u0432\u044B \u044D\u0442\u043E \u0441\u0434\u0435\u043B\u0430\u0435\u0442\u0435, \u0442\u0435\u043C \u043B\u0443\u0447\u0448\u0435. \u0420\u0435\u0430\u043B\u0438\u0437\u043E\u0432\u0430\u0442\u044C \u044D\u0442\u043E \u043C\u043E\u0436\u043D\u043E \u0447\u0435\u0440\u0435\u0437 \u0413\u043E\u0441\u0443\u0441\u043B\u0443\u0433\u0438, \u0441\u0430\u0439\u0442 \u0413\u043E\u0441\u0430\u0432\u0442\u043E\u0438\u043D\u0441\u043F\u0435\u043A\u0446\u0438\u0438, \u043F\u0440\u0438\u043B\u043E\u0436\u0435\u043D\u0438\u0435 \u0441\u0432\u043E\u0435\u0433\u043E \u0431\u0430\u043D\u043A\u0430, \u043F\u043B\u0430\u0442\u0435\u0436\u043D\u044B\u0439 \u0442\u0435\u0440\u043C\u0438\u043D\u0430\u043B, \u043D\u0430\u0448 \u0441\u0435\u0440\u0432\u0438\u0441.</p><p>\u0417\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u043E\u0432\u0430\u043D\u043D\u044B\u0435 \u0443 \u043D\u0430\u0441 \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0438 \u0432\u0441\u0435\u0433\u0434\u0430 \u043F\u043E\u043B\u0443\u0447\u0430\u044E\u0442 \u0443\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u044F \u043E \u043F\u043E\u0433\u0430\u0448\u0435\u043D\u0438\u0438 \u0437\u0430\u0434\u043E\u043B\u0436\u0435\u043D\u043D\u043E\u0441\u0442\u0438, \u0447\u0442\u043E \u0434\u043E\u0441\u0442\u0430\u0442\u043E\u0447\u043D\u043E \u0443\u0434\u043E\u0431\u043D\u043E. \u041F\u043E\u0441\u043B\u0435 \u043E\u043F\u043B\u0430\u0442\u044B \u0431\u0430\u043D\u043A\u043E\u0432\u0441\u043A\u043E\u0439 \u043A\u0430\u0440\u0442\u043E\u0439 \u043A\u0432\u0438\u0442\u0430\u043D\u0446\u0438\u044F \u043C\u043E\u043C\u0435\u043D\u0442\u0430\u043B\u044C\u043D\u043E \u043F\u0440\u0438\u0434\u0435\u0442 \u043D\u0430 \u0432\u0430\u0448\u0443 \u044D\u043B\u0435\u043A\u0442\u0440\u043E\u043D\u043D\u0443\u044E \u043F\u043E\u0447\u0442\u0443 \u0438 \u0432 \u043B\u0438\u0447\u043D\u044B\u0439 \u043A\u0430\u0431\u0438\u043D\u0435\u0442.</p><h3>\u041A\u0430\u043A \u043F\u043E\u043B\u0443\u0447\u0438\u0442\u044C \u0441\u043A\u0438\u0434\u043A\u0443 50% \u043F\u0440\u0438 \u043E\u043F\u043B\u0430\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u0430</h3><p>\u041D\u0430\u0447\u0438\u043D\u0430\u044F \u0441 1 \u044F\u043D\u0432\u0430\u0440\u044F 2016 \u0433\u043E\u0434\u0430, \u0441\u043E\u0433\u043B\u0430\u0441\u043D\u043E \u043F. 1.3 \u0441\u0442. 32.2 \u041A\u043E\u0410\u041F \u0420\u0424, \u0443 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u0435\u0439 \u0435\u0441\u0442\u044C \u0432\u043E\u0437\u043C\u043E\u0436\u043D\u043E\u0441\u0442\u044C \u043E\u043F\u043B\u0430\u0447\u0438\u0432\u0430\u0442\u044C \u043B\u0438\u0448\u044C \u043F\u043E\u043B\u043E\u0432\u0438\u043D\u0443 \u0432\u044B\u043F\u0438\u0441\u0430\u043D\u043D\u043E\u0439 \u0441\u0443\u043C\u043C\u044B \u0448\u0442\u0440\u0430\u0444\u0430 \u0437\u0430 \u043F\u0440\u0435\u0432\u044B\u0448\u0435\u043D\u0438\u0435 \u0441\u043A\u043E\u0440\u043E\u0441\u0442\u0438, \u043D\u0435\u043F\u0440\u0438\u0441\u0442\u0435\u0433\u043D\u0443\u0442\u044B\u0439 \u0440\u0435\u043C\u0435\u043D\u044C, \u0435\u0437\u0434\u0443 \u043D\u0430 \u043C\u0430\u0448\u0438\u043D\u0435 \u0431\u0435\u0437 \u0437\u043D\u0430\u043A\u043E\u0432, \u043D\u0435\u0441\u043E\u0431\u043B\u044E\u0434\u0435\u043D\u0438\u0435 \u0440\u0430\u0437\u043C\u0435\u0442\u043A\u0438, \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u0435 \u043F\u0440\u0430\u0432\u0438\u043B \u043F\u0440\u0438 \u043E\u0431\u0433\u043E\u043D\u0435 \u0438 \u0432 \u043D\u0435\u043A\u043E\u0442\u043E\u0440\u044B\u0445 \u0434\u0440\u0443\u0433\u0438\u0445 \u0441\u043B\u0443\u0447\u0430\u044F\u0445.</p><p>\u041F\u0440\u043E\u0449\u0435 \u0438 \u0443\u0434\u043E\u0431\u043D\u0435\u0435 \u044D\u0442\u043E \u0434\u0435\u043B\u0430\u0442\u044C \u0447\u0435\u0440\u0435\u0437 \u043D\u0430\u0448 \u0441\u0435\u0440\u0432\u0438\u0441:</p><ul>  <li>\u0412\u0430\u043C \u043D\u0435 \u043D\u0443\u0436\u043D\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u044F\u0442\u044C \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u044E \u0448\u0442\u0440\u0430\u0444\u0430 \u0438 \u0443\u0431\u0435\u0436\u0434\u0430\u0442\u044C\u0441\u044F \u0432 \u0442\u043E\u043C, \u0440\u0430\u0441\u043F\u0440\u043E\u0441\u0442\u0440\u0430\u043D\u044F\u0435\u0442\u0441\u044F \u043B\u0438 \u043D\u0430 \u043D\u0435\u0433\u043E \u0441\u043A\u0438\u0434\u043A\u0430.</li>  <li>\u0412\u044B \u0443\u0432\u0438\u0434\u0438\u0442\u0435 \u043F\u0435\u0440\u0435\u0434 \u0441\u043E\u0431\u043E\u0439 \u0441\u0443\u043C\u043C\u0443 \u0448\u0442\u0440\u0430\u0444\u0430 \u0443\u0436\u0435 \u0441 \u0443\u0447\u0435\u0442\u043E\u043C \u0441\u043A\u0438\u0434\u043A\u0438.</li>  <li>\u041E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u043C\u043E\u0436\u043D\u043E \u043A\u0430\u0440\u0442\u043E\u0439 \u043E\u043D\u043B\u0430\u0439\u043D.</li></ul><p><strong>\u0412\u0410\u0416\u041D\u041E!</strong> \u0421\u043A\u0438\u0434\u043A\u0430 \u0434\u0435\u0439\u0441\u0442\u0432\u0443\u0435\u0442 20 \u0434\u043D\u0435\u0439. \u0415\u0441\u043B\u0438 \u0432\u044B \u043F\u043E \u0443\u0432\u0430\u0436\u0438\u0442\u0435\u043B\u044C\u043D\u043E\u0439 \u043F\u0440\u0438\u0447\u0438\u043D\u0435 \u043F\u0440\u043E\u043F\u0443\u0441\u0442\u0438\u043B\u0438 \u044D\u0442\u043E\u0442 \u0441\u0440\u043E\u043A, \u043F\u043E\u0434\u0430\u0439\u0442\u0435 \u0445\u043E\u0434\u0430\u0442\u0430\u0439\u0441\u0442\u0432\u043E \u0432 \u0413\u0418\u0411\u0414\u0414 \u0441 \u043F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0430\u044E\u0449\u0438\u043C\u0438 \u0434\u043E\u043A\u0443\u043C\u0435\u043D\u0442\u0430\u043C\u0438 \u0438 \u043B\u044C\u0433\u043E\u0442\u043D\u044B\u0439 \u043F\u0435\u0440\u0438\u043E\u0434 \u0431\u0443\u0434\u0435\u0442 \u043F\u0440\u043E\u0434\u043B\u0435\u043D.</p>"
  },
  "po-sts": {
    text: "<h2>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u0421\u0422\u0421</h2><p>\u041E\u0434\u043D\u0438\u043C \u0438\u0437 \u0441\u0430\u043C\u044B\u0445 \u0443\u0434\u043E\u0431\u043D\u044B\u0445 \u0441\u043F\u043E\u0441\u043E\u0431\u043E\u0432 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u044F\u0432\u043B\u044F\u0435\u0442\u0441\u044F \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u0435 \u043D\u043E\u043C\u0435\u0440\u0430 \u0441\u0432\u0438\u0434\u0435\u0442\u0435\u043B\u044C\u0441\u0442\u0432\u0430 \u043E \u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0430\u0446\u0438\u0438 \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043D\u043E\u0433\u043E \u0441\u0440\u0435\u0434\u0441\u0442\u0432\u0430 (\u0421\u0422\u0421). \u042D\u0442\u043E\u0442 \u0443\u043D\u0438\u043A\u0430\u043B\u044C\u043D\u044B\u0439 \u043D\u043E\u043C\u0435\u0440, \u0443\u043A\u0430\u0437\u0430\u043D\u043D\u044B\u0439 \u0432 \u0434\u043E\u043A\u0443\u043C\u0435\u043D\u0442\u0435 \u043D\u0430 \u0432\u0430\u0448 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044C, \u043F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u0431\u044B\u0441\u0442\u0440\u043E \u0438 \u0442\u043E\u0447\u043D\u043E \u0443\u0437\u043D\u0430\u0442\u044C \u043E \u043D\u0430\u043B\u0438\u0447\u0438\u0438 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u0439 \u0438 \u0437\u0430\u0434\u043E\u043B\u0436\u0435\u043D\u043D\u043E\u0441\u0442\u0435\u0439, \u0441\u0432\u044F\u0437\u0430\u043D\u043D\u044B\u0445 \u0441 \u0432\u0430\u0448\u0438\u043C \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043D\u044B\u043C \u0441\u0440\u0435\u0434\u0441\u0442\u0432\u043E\u043C.</p><h2>\u0417\u0430\u0447\u0435\u043C \u043F\u0440\u043E\u0432\u0435\u0440\u044F\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u0421\u0422\u0421</h2><p>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u0421\u0422\u0421 \u043D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u0430 \u0434\u043B\u044F \u0442\u043E\u0433\u043E, \u0447\u0442\u043E\u0431\u044B \u0438\u0437\u0431\u0435\u0436\u0430\u0442\u044C \u043D\u0435\u043F\u0440\u0438\u044F\u0442\u043D\u044B\u0445 \u043F\u043E\u0441\u043B\u0435\u0434\u0441\u0442\u0432\u0438\u0439, \u0442\u0430\u043A\u0438\u0445 \u043A\u0430\u043A \u0443\u0434\u0432\u043E\u0435\u043D\u0438\u0435 \u0441\u0443\u043C\u043C\u044B \u0448\u0442\u0440\u0430\u0444\u0430, \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u044B\u0435 \u0440\u0430\u0431\u043E\u0442\u044B \u0438\u043B\u0438 \u0430\u0434\u043C\u0438\u043D\u0438\u0441\u0442\u0440\u0430\u0442\u0438\u0432\u043D\u044B\u0439 \u0430\u0440\u0435\u0441\u0442. \u0421 \u043F\u043E\u043C\u043E\u0449\u044C\u044E \u0441\u0435\u0440\u0432\u0438\u0441\u0430 Compas.pro, \u0432\u044B \u043C\u043E\u0436\u0435\u0442\u0435 \u043C\u0433\u043D\u043E\u0432\u0435\u043D\u043D\u043E \u043F\u043E\u043B\u0443\u0447\u0438\u0442\u044C \u0430\u043A\u0442\u0443\u0430\u043B\u044C\u043D\u0443\u044E \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044E \u043E \u0448\u0442\u0440\u0430\u0444\u0430\u0445, \u0434\u0430\u0436\u0435 \u0435\u0441\u043B\u0438 \u0431\u0443\u043C\u0430\u0436\u043D\u043E\u0435 \u0443\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u0435 \u043D\u0435 \u043F\u0440\u0438\u0448\u043B\u043E \u043F\u043E \u043F\u043E\u0447\u0442\u0435 \u0438\u043B\u0438 \u0434\u0430\u043D\u043D\u044B\u0435 \u043D\u0430 \u0413\u043E\u0441\u0443\u0441\u043B\u0443\u0433\u0430\u0445 \u043D\u0435 \u043E\u0431\u043D\u043E\u0432\u043B\u0435\u043D\u044B. \u0414\u043E\u0441\u0442\u0430\u0442\u043E\u0447\u043D\u043E \u043F\u0440\u043E\u0441\u0442\u043E \u0432\u0432\u0435\u0441\u0442\u0438 \u043D\u043E\u043C\u0435\u0440 \u0421\u0422\u0421 \u0432\u0430\u0448\u0435\u0433\u043E \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044F \u0438 \u0440\u0435\u0433\u0438\u043E\u043D, \u0447\u0442\u043E\u0431\u044B \u0443\u0437\u043D\u0430\u0442\u044C \u043E \u043D\u0430\u043B\u0438\u0447\u0438\u0438 \u0448\u0442\u0440\u0430\u0444\u043E\u0432.</p><h2>\u041A\u0430\u043A \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444 \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u0421\u0422\u0421</h2><p>\u041F\u0435\u0440\u0435\u0434 \u0442\u0435\u043C \u043A\u0430\u043A \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444, \u0443\u0431\u0435\u0434\u0438\u0442\u0435\u0441\u044C, \u0447\u0442\u043E \u0432\u0441\u0435 \u0434\u0430\u043D\u043D\u044B\u0435 \u043A\u043E\u0440\u0440\u0435\u043A\u0442\u043D\u044B. \u0421\u0432\u0435\u0440\u044C\u0442\u0435 \u043D\u043E\u043C\u0435\u0440 \u0421\u0422\u0421, \u043D\u043E\u043C\u0435\u0440 \u0432\u0430\u0448\u0438\u0445 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u0438\u0445 \u043F\u0440\u0430\u0432, \u0430 \u0442\u0430\u043A\u0436\u0435 \u0443\u0431\u0435\u0434\u0438\u0442\u0435\u0441\u044C, \u0447\u0442\u043E \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044C \u043D\u0430 \u0444\u043E\u0442\u043E\u0433\u0440\u0430\u0444\u0438\u0438 \u0434\u0435\u0439\u0441\u0442\u0432\u0438\u0442\u0435\u043B\u044C\u043D\u043E \u0432\u0430\u0448. \u0415\u0441\u043B\u0438 \u0432\u0441\u0435 \u0441\u043E\u043E\u0442\u0432\u0435\u0442\u0441\u0442\u0432\u0443\u0435\u0442, \u0432\u044B \u043C\u043E\u0436\u0435\u0442\u0435 \u0441\u0440\u0430\u0437\u0443 \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444 \u0447\u0435\u0440\u0435\u0437 \u043D\u0430\u0448 \u0441\u0435\u0440\u0432\u0438\u0441. \u041E\u043F\u043B\u0430\u0442\u0430 \u043E\u043D\u043B\u0430\u0439\u043D \u0431\u0430\u043D\u043A\u043E\u0432\u0441\u043A\u043E\u0439 \u043A\u0430\u0440\u0442\u043E\u0439 \u2014 \u0441\u0430\u043C\u044B\u0439 \u0431\u044B\u0441\u0442\u0440\u044B\u0439 \u0438 \u0443\u0434\u043E\u0431\u043D\u044B\u0439 \u0441\u043F\u043E\u0441\u043E\u0431, \u0430 \u043A\u0432\u0438\u0442\u0430\u043D\u0446\u0438\u044F \u043E\u0431 \u043E\u043F\u043B\u0430\u0442\u0435 \u043C\u0433\u043D\u043E\u0432\u0435\u043D\u043D\u043E \u043F\u0440\u0438\u0434\u0435\u0442 \u043D\u0430 \u0432\u0430\u0448\u0443 \u044D\u043B\u0435\u043A\u0442\u0440\u043E\u043D\u043D\u0443\u044E \u043F\u043E\u0447\u0442\u0443.</p><h2>\u0423\u0434\u043E\u0431\u0441\u0442\u0432\u043E \u0438 \u0432\u044B\u0433\u043E\u0434\u044B \u043E\u0442 \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F \u043D\u0430\u0448\u0435\u0433\u043E \u0441\u0435\u0440\u0432\u0438\u0441\u0430</h2><p>\u041D\u0430\u0448 \u0441\u0435\u0440\u0432\u0438\u0441 \u043F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u043D\u0435 \u0442\u043E\u043B\u044C\u043A\u043E \u0431\u044B\u0441\u0442\u0440\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u0421\u0422\u0421, \u043D\u043E \u0438 \u0432\u043E\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u044C\u0441\u044F \u0441\u043A\u0438\u0434\u043A\u043E\u0439 50% \u043F\u0440\u0438 \u043E\u043F\u043B\u0430\u0442\u0435 \u0432 \u0442\u0435\u0447\u0435\u043D\u0438\u0435 20 \u0434\u043D\u0435\u0439 \u0441 \u043C\u043E\u043C\u0435\u043D\u0442\u0430 \u0432\u044B\u043D\u0435\u0441\u0435\u043D\u0438\u044F \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F. \u0412\u0430\u043C \u043D\u0435 \u043D\u0443\u0436\u043D\u043E \u0431\u0435\u0441\u043F\u043E\u043A\u043E\u0438\u0442\u044C\u0441\u044F \u043E \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u044F\u0445 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0438\u043B\u0438 \u0443\u0441\u043B\u043E\u0432\u0438\u044F\u0445 \u043F\u043E\u043B\u0443\u0447\u0435\u043D\u0438\u044F \u0441\u043A\u0438\u0434\u043A\u0438 \u2014 \u0441\u0438\u0441\u0442\u0435\u043C\u0430 \u0430\u0432\u0442\u043E\u043C\u0430\u0442\u0438\u0447\u0435\u0441\u043A\u0438 \u043F\u043E\u043A\u0430\u0436\u0435\u0442 \u0432\u0430\u043C \u0430\u043A\u0442\u0443\u0430\u043B\u044C\u043D\u0443\u044E \u0441\u0443\u043C\u043C\u0443 \u0441 \u0443\u0447\u0435\u0442\u043E\u043C \u0432\u0441\u0435\u0445 \u043B\u044C\u0433\u043E\u0442.</p>"
  },
  "po-voditelskomu-udostovereniyu": {
    text: "<h2>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u043E\u043C\u0443 \u0443\u0434\u043E\u0441\u0442\u043E\u0432\u0435\u0440\u0435\u043D\u0438\u044E (\u0412\u0423)</h2><p>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u043E\u043C\u0443 \u0443\u0434\u043E\u0441\u0442\u043E\u0432\u0435\u0440\u0435\u043D\u0438\u044E (\u0412\u0423) \u2014 \u044D\u0442\u043E \u043F\u0440\u043E\u0441\u0442\u043E\u0439 \u0438 \u0443\u0434\u043E\u0431\u043D\u044B\u0439 \u0441\u043F\u043E\u0441\u043E\u0431 \u0443\u0437\u043D\u0430\u0442\u044C \u043E \u043D\u0430\u043B\u0438\u0447\u0438\u0438 \u043D\u0435\u043E\u043F\u043B\u0430\u0447\u0435\u043D\u043D\u044B\u0445 \u0448\u0442\u0440\u0430\u0444\u043E\u0432. \u0421 \u043F\u043E\u043C\u043E\u0449\u044C\u044E \u043D\u0430\u0448\u0435\u0433\u043E \u0441\u0435\u0440\u0432\u0438\u0441\u0430 Compas.pro \u0432\u044B \u043C\u043E\u0436\u0435\u0442\u0435 \u0431\u044B\u0441\u0442\u0440\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C, \u0435\u0441\u0442\u044C \u043B\u0438 \u0443 \u0432\u0430\u0441 \u0430\u0434\u043C\u0438\u043D\u0438\u0441\u0442\u0440\u0430\u0442\u0438\u0432\u043D\u044B\u0435 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F, \u0438, \u043F\u0440\u0438 \u043D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u043E\u0441\u0442\u0438, \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0438\u0445 \u0432 \u043D\u0435\u0441\u043A\u043E\u043B\u044C\u043A\u043E \u043A\u043B\u0438\u043A\u043E\u0432.</p><h2>\u041F\u043E\u0447\u0435\u043C\u0443 \u0432\u0430\u0436\u043D\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u044F\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0412\u0423?</h2><p>\u041A\u0430\u0436\u0434\u044B\u0439 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C \u043E\u0431\u044F\u0437\u0430\u043D \u0441\u0432\u043E\u0435\u0432\u0440\u0435\u043C\u0435\u043D\u043D\u043E \u043E\u043F\u043B\u0430\u0447\u0438\u0432\u0430\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B, \u0447\u0442\u043E\u0431\u044B \u0438\u0437\u0431\u0435\u0436\u0430\u0442\u044C \u0434\u043E\u043F\u043E\u043B\u043D\u0438\u0442\u0435\u043B\u044C\u043D\u044B\u0445 \u0441\u0430\u043D\u043A\u0446\u0438\u0439, \u0442\u0430\u043A\u0438\u0445 \u043A\u0430\u043A \u043D\u0430\u0447\u0438\u0441\u043B\u0435\u043D\u0438\u0435 \u043F\u0435\u043D\u0438, \u0432\u0440\u0435\u043C\u0435\u043D\u043D\u043E\u0435 \u043E\u0433\u0440\u0430\u043D\u0438\u0447\u0435\u043D\u0438\u0435 \u043D\u0430 \u0432\u044B\u0435\u0437\u0434 \u0437\u0430 \u0433\u0440\u0430\u043D\u0438\u0446\u0443 \u0438\u043B\u0438 \u0434\u0430\u0436\u0435 \u043F\u0440\u0438\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043A\u0430 \u0434\u0435\u0439\u0441\u0442\u0432\u0438\u044F \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u0438\u0445 \u043F\u0440\u0430\u0432. \u0420\u0435\u0433\u0443\u043B\u044F\u0440\u043D\u0430\u044F \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E\u043C\u043E\u0433\u0430\u0435\u0442 \u0432\u043E\u0432\u0440\u0435\u043C\u044F \u0443\u0437\u043D\u0430\u0442\u044C \u043E \u043D\u0430\u043B\u043E\u0436\u0435\u043D\u043D\u044B\u0445 \u0448\u0442\u0440\u0430\u0444\u0430\u0445 \u0438 \u0438\u0437\u0431\u0435\u0436\u0430\u0442\u044C \u043D\u0435\u043F\u0440\u0438\u044F\u0442\u043D\u044B\u0445 \u043F\u043E\u0441\u043B\u0435\u0434\u0441\u0442\u0432\u0438\u0439.</p><h2>\u041A\u0430\u043A \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0412\u0423 \u0447\u0435\u0440\u0435\u0437 Compas.pro?</h2><ol>  <li>\u041F\u0435\u0440\u0435\u0439\u0434\u0438\u0442\u0435 \u043D\u0430 \u043D\u0430\u0448 \u0441\u0430\u0439\u0442 Compas.pro.</li>  <li>\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u043D\u043E\u043C\u0435\u0440 \u0432\u0430\u0448\u0435\u0433\u043E \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u043E\u0433\u043E \u0443\u0434\u043E\u0441\u0442\u043E\u0432\u0435\u0440\u0435\u043D\u0438\u044F \u0432 \u0441\u043E\u043E\u0442\u0432\u0435\u0442\u0441\u0442\u0432\u0443\u044E\u0449\u0435\u0435 \u043F\u043E\u043B\u0435.</li>  <li>\u041D\u0430\u0436\u043C\u0438\u0442\u0435 \u043A\u043D\u043E\u043F\u043A\u0443 '\u041F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B'. \u041D\u0430\u0448 \u0441\u0435\u0440\u0432\u0438\u0441 \u0430\u0432\u0442\u043E\u043C\u0430\u0442\u0438\u0447\u0435\u0441\u043A\u0438 \u043E\u0431\u0440\u0430\u0431\u043E\u0442\u0430\u0435\u0442 \u0437\u0430\u043F\u0440\u043E\u0441 \u0438 \u043F\u043E\u043A\u0430\u0436\u0435\u0442 \u0432\u0441\u0435 \u043D\u0435\u043E\u043F\u043B\u0430\u0447\u0435\u043D\u043D\u044B\u0435 \u0448\u0442\u0440\u0430\u0444\u044B, \u0435\u0441\u043B\u0438 \u0442\u0430\u043A\u043E\u0432\u044B\u0435 \u0438\u043C\u0435\u044E\u0442\u0441\u044F.</li>  <li>\u041F\u0440\u043E\u0441\u043C\u043E\u0442\u0440\u0438\u0442\u0435 \u0440\u0435\u0437\u0443\u043B\u044C\u0442\u0430\u0442\u044B \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0438 \u043F\u0440\u0438 \u043D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u043E\u0441\u0442\u0438 \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u043E\u043D\u043B\u0430\u0439\u043D.</li></ol><h2>\u041F\u0440\u0435\u0438\u043C\u0443\u0449\u0435\u0441\u0442\u0432\u0430 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0447\u0435\u0440\u0435\u0437 Compas.pro</h2><ul>  <li><strong>\u0423\u0434\u043E\u0431\u0441\u0442\u0432\u043E \u0438 \u043F\u0440\u043E\u0441\u0442\u043E\u0442\u0430 \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F:</strong> \u0412\u0441\u044F \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044F \u043F\u043E \u0448\u0442\u0440\u0430\u0444\u0430\u043C \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u0430 \u0432 \u043E\u0434\u043D\u043E\u043C \u043C\u0435\u0441\u0442\u0435. \u041D\u0435\u0442 \u043D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u043E\u0441\u0442\u0438 \u043F\u043E\u0441\u0435\u0449\u0430\u0442\u044C \u0440\u0430\u0437\u043B\u0438\u0447\u043D\u044B\u0435 \u0441\u0430\u0439\u0442\u044B \u0438\u043B\u0438 \u0438\u043D\u0441\u0442\u0430\u043D\u0446\u0438\u0438.</li>  <li><strong>\u0411\u044B\u0441\u0442\u0440\u0430\u044F \u043E\u043F\u043B\u0430\u0442\u0430:</strong> \u0412\u043E\u0437\u043C\u043E\u0436\u043D\u043E\u0441\u0442\u044C \u043E\u043F\u043B\u0430\u0442\u044B \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043E\u043D\u043B\u0430\u0439\u043D \u0441 \u043F\u043E\u043C\u043E\u0449\u044C\u044E \u0431\u0430\u043D\u043A\u043E\u0432\u0441\u043A\u043E\u0439 \u043A\u0430\u0440\u0442\u044B \u0438\u043B\u0438 \u0434\u0440\u0443\u0433\u0438\u0445 \u0443\u0434\u043E\u0431\u043D\u044B\u0445 \u043C\u0435\u0442\u043E\u0434\u043E\u0432.</li>  <li><strong>\u0411\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u044C \u0434\u0430\u043D\u043D\u044B\u0445:</strong> \u041C\u044B \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u0443\u0435\u043C \u0441\u043E\u0432\u0440\u0435\u043C\u0435\u043D\u043D\u044B\u0435 \u0442\u0435\u0445\u043D\u043E\u043B\u043E\u0433\u0438\u0438 \u0448\u0438\u0444\u0440\u043E\u0432\u0430\u043D\u0438\u044F \u0434\u043B\u044F \u0437\u0430\u0449\u0438\u0442\u044B \u0432\u0430\u0448\u0438\u0445 \u043B\u0438\u0447\u043D\u044B\u0445 \u0434\u0430\u043D\u043D\u044B\u0445 \u0438 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u0438 \u043E \u0448\u0442\u0440\u0430\u0444\u0430\u0445.</li>  <li><strong>\u041E\u043F\u043E\u0432\u0435\u0449\u0435\u043D\u0438\u044F:</strong> \u041F\u043E\u0441\u043B\u0435 \u043E\u043F\u043B\u0430\u0442\u044B \u0432\u044B \u043F\u043E\u043B\u0443\u0447\u0438\u0442\u0435 \u0443\u0432\u0435\u0434\u043E\u043C\u043B\u0435\u043D\u0438\u0435 \u043E \u0442\u043E\u043C, \u0447\u0442\u043E \u043F\u043B\u0430\u0442\u0435\u0436 \u0431\u044B\u043B \u0443\u0441\u043F\u0435\u0448\u043D\u043E \u043E\u0431\u0440\u0430\u0431\u043E\u0442\u0430\u043D.</li>"
  },
  "po-inn": {
    text: "<h2>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u0418\u041D\u041D \u0434\u043B\u044F \u043E\u0440\u0433\u0430\u043D\u0438\u0437\u0430\u0446\u0438\u0439</h2><p>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u0418\u041D\u041D \u2014 \u044D\u0442\u043E \u044D\u0444\u0444\u0435\u043A\u0442\u0438\u0432\u043D\u044B\u0439 \u0441\u043F\u043E\u0441\u043E\u0431 \u0434\u043B\u044F \u044E\u0440\u0438\u0434\u0438\u0447\u0435\u0441\u043A\u0438\u0445 \u043B\u0438\u0446 \u043A\u043E\u043D\u0442\u0440\u043E\u043B\u0438\u0440\u043E\u0432\u0430\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B, \u043D\u0430\u0447\u0438\u0441\u043B\u0435\u043D\u043D\u044B\u0435 \u043D\u0430 \u0438\u0445 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A. \u0421 \u043F\u043E\u043C\u043E\u0449\u044C\u044E \u043D\u0430\u0448\u0435\u0433\u043E \u0441\u0435\u0440\u0432\u0438\u0441\u0430 Compas.pro \u0432\u044B \u043C\u043E\u0436\u0435\u0442\u0435 \u0431\u044B\u0441\u0442\u0440\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0432\u0441\u0435 \u0430\u0434\u043C\u0438\u043D\u0438\u0441\u0442\u0440\u0430\u0442\u0438\u0432\u043D\u044B\u0435 \u043F\u0440\u0430\u0432\u043E\u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F, \u0441\u0432\u044F\u0437\u0430\u043D\u043D\u044B\u0435 \u0441 \u0432\u0430\u0448\u0438\u043C\u0438 \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043D\u044B\u043C\u0438 \u0441\u0440\u0435\u0434\u0441\u0442\u0432\u0430\u043C\u0438, \u0438 \u043E\u043F\u0435\u0440\u0430\u0442\u0438\u0432\u043D\u043E \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0438\u0445.</p><h3>\u041F\u043E\u0447\u0435\u043C\u0443 \u0432\u0430\u0436\u043D\u0430 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u0418\u041D\u041D?</h3><p>\u041E\u0440\u0433\u0430\u043D\u0438\u0437\u0430\u0446\u0438\u0438 \u0441 \u0431\u043E\u043B\u044C\u0448\u0438\u043C \u043A\u043E\u043B\u0438\u0447\u0435\u0441\u0442\u0432\u043E\u043C \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u0435\u0439 \u0434\u043E\u043B\u0436\u043D\u044B \u0440\u0435\u0433\u0443\u043B\u044F\u0440\u043D\u043E \u0441\u043B\u0435\u0434\u0438\u0442\u044C \u0437\u0430 \u043D\u0430\u043B\u0438\u0447\u0438\u0435\u043C \u0448\u0442\u0440\u0430\u0444\u043E\u0432, \u0447\u0442\u043E\u0431\u044B \u0438\u0437\u0431\u0435\u0436\u0430\u0442\u044C \u0434\u043E\u043F\u043E\u043B\u043D\u0438\u0442\u0435\u043B\u044C\u043D\u044B\u0445 \u0441\u0430\u043D\u043A\u0446\u0438\u0439 \u0438 \u0444\u0438\u043D\u0430\u043D\u0441\u043E\u0432\u044B\u0445 \u043F\u043E\u0442\u0435\u0440\u044C. \u041D\u0430\u043A\u043E\u043F\u043B\u0435\u043D\u043D\u044B\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u043C\u043E\u0433\u0443\u0442 \u043F\u0440\u0438\u0432\u0435\u0441\u0442\u0438 \u043A \u0443\u0432\u0435\u043B\u0438\u0447\u0435\u043D\u0438\u044E \u0440\u0430\u0441\u0445\u043E\u0434\u043E\u0432 \u0437\u0430 \u0441\u0447\u0435\u0442 \u043D\u0430\u0447\u0438\u0441\u043B\u0435\u043D\u0438\u044F \u043F\u0435\u043D\u0435\u0439, \u0430 \u0432 \u043D\u0435\u043A\u043E\u0442\u043E\u0440\u044B\u0445 \u0441\u043B\u0443\u0447\u0430\u044F\u0445 \u0434\u0430\u0436\u0435 \u043A \u043E\u0433\u0440\u0430\u043D\u0438\u0447\u0435\u043D\u0438\u044F\u043C \u043D\u0430 \u0432\u044B\u0435\u0437\u0434 \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043D\u044B\u0445 \u0441\u0440\u0435\u0434\u0441\u0442\u0432 \u0437\u0430 \u0433\u0440\u0430\u043D\u0438\u0446\u0443.</p><h3>\u041F\u0440\u0435\u0438\u043C\u0443\u0449\u0435\u0441\u0442\u0432\u0430 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043F\u043E \u0418\u041D\u041D \u0434\u043B\u044F \u044E\u0440\u0438\u0434\u0438\u0447\u0435\u0441\u043A\u0438\u0445 \u043B\u0438\u0446</h3><ul><li><strong>\u041A\u043E\u043D\u0442\u0440\u043E\u043B\u044C \u0432\u0441\u0435\u0445 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u0435\u0439 \u0441\u0440\u0430\u0437\u0443:</strong> \u041E\u0434\u0438\u043D \u0437\u0430\u043F\u0440\u043E\u0441 \u043F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u043F\u043E\u043B\u0443\u0447\u0438\u0442\u044C \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044E \u043F\u043E \u0432\u0441\u0435\u043C \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043D\u044B\u043C \u0441\u0440\u0435\u0434\u0441\u0442\u0432\u0430\u043C \u043E\u0440\u0433\u0430\u043D\u0438\u0437\u0430\u0446\u0438\u0438.</li><li><strong>\u042D\u043A\u043E\u043D\u043E\u043C\u0438\u044F \u0432\u0440\u0435\u043C\u0435\u043D\u0438:</strong> \u0412\u0430\u043C \u043D\u0435 \u043D\u0443\u0436\u043D\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u044F\u0442\u044C \u043A\u0430\u0436\u0434\u044B\u0439 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044C \u043F\u043E \u043E\u0442\u0434\u0435\u043B\u044C\u043D\u043E\u0441\u0442\u0438. \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u043F\u043E \u0418\u041D\u041D \u043E\u0431\u0435\u0441\u043F\u0435\u0447\u0438\u0432\u0430\u0435\u0442 \u043F\u043E\u043B\u043D\u044B\u0439 \u043A\u043E\u043D\u0442\u0440\u043E\u043B\u044C \u0437\u0430 \u0448\u0442\u0440\u0430\u0444\u0430\u043C\u0438.</li><li><strong>\u0423\u0434\u043E\u0431\u043D\u0430\u044F \u043E\u043F\u043B\u0430\u0442\u0430:</strong> \u0412\u0441\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u043C\u043E\u0436\u043D\u043E \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0432 \u043E\u0434\u0438\u043D \u043A\u043B\u0438\u043A, \u043C\u0438\u043D\u0438\u043C\u0438\u0437\u0438\u0440\u0443\u044F \u0430\u0434\u043C\u0438\u043D\u0438\u0441\u0442\u0440\u0430\u0442\u0438\u0432\u043D\u044B\u0435 \u0437\u0430\u0442\u0440\u0430\u0442\u044B.</li><li><strong>\u0411\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u044C \u0434\u0430\u043D\u043D\u044B\u0445:</strong> \u041C\u044B \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u0443\u0435\u043C \u0441\u043E\u0432\u0440\u0435\u043C\u0435\u043D\u043D\u044B\u0435 \u0442\u0435\u0445\u043D\u043E\u043B\u043E\u0433\u0438\u0438 \u0437\u0430\u0449\u0438\u0442\u044B \u0434\u043B\u044F \u043E\u0431\u0435\u0441\u043F\u0435\u0447\u0435\u043D\u0438\u044F \u0431\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u0438 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u0438 \u0432\u0430\u0448\u0435\u0439 \u043A\u043E\u043C\u043F\u0430\u043D\u0438\u0438.</li></ul><p>\u0421\u0435\u0440\u0432\u0438\u0441 Compas.pro \u0443\u043F\u0440\u043E\u0449\u0430\u0435\u0442 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0443 \u0438 \u043E\u043F\u043B\u0430\u0442\u0443 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0434\u043B\u044F \u044E\u0440\u0438\u0434\u0438\u0447\u0435\u0441\u043A\u0438\u0445 \u043B\u0438\u0446. \u041A\u043E\u043D\u0442\u0440\u043E\u043B\u0438\u0440\u0443\u0439\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0418\u041D\u041D \u0438 \u0438\u0437\u0431\u0435\u0433\u0430\u0439\u0442\u0435 \u0444\u0438\u043D\u0430\u043D\u0441\u043E\u0432\u044B\u0445 \u0440\u0438\u0441\u043A\u043E\u0432, \u0441\u0432\u044F\u0437\u0430\u043D\u043D\u044B\u0445 \u0441 \u043D\u0430\u043A\u043E\u043F\u043B\u0435\u043D\u043D\u044B\u043C\u0438 \u0434\u043E\u043B\u0433\u0430\u043C\u0438!</p>"
  },
  "po-nomeru-avto": {
    text: "<h2>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u0433\u043E\u0441\u043D\u043E\u043C\u0435\u0440\u0443 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044F</h2><p>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u0433\u043E\u0441\u043D\u043E\u043C\u0435\u0440\u0443 \u2014 \u044D\u0442\u043E \u0431\u044B\u0441\u0442\u0440\u044B\u0439 \u0438 \u0443\u0434\u043E\u0431\u043D\u044B\u0439 \u0441\u043F\u043E\u0441\u043E\u0431 \u0443\u0437\u043D\u0430\u0442\u044C \u043E \u043D\u0430\u043B\u0438\u0447\u0438\u0438 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0434\u043B\u044F \u0432\u0430\u0448\u0435\u0433\u043E \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044F. \u041E\u0434\u043D\u0430\u043A\u043E, \u0434\u043B\u044F \u0437\u0430\u0449\u0438\u0442\u044B \u043A\u043E\u043D\u0444\u0438\u0434\u0435\u043D\u0446\u0438\u0430\u043B\u044C\u043D\u044B\u0445 \u0434\u0430\u043D\u043D\u044B\u0445, \u043E\u0434\u043D\u043E\u0439 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u0438 \u043E \u0433\u043E\u0441\u043D\u043E\u043C\u0435\u0440\u0435 \u043D\u0435\u0434\u043E\u0441\u0442\u0430\u0442\u043E\u0447\u043D\u043E. \u0414\u043B\u044F \u043F\u043E\u043B\u043D\u043E\u0439 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0432\u0430\u043C \u0442\u0430\u043A\u0436\u0435 \u043F\u043E\u043D\u0430\u0434\u043E\u0431\u0438\u0442\u0441\u044F \u043D\u043E\u043C\u0435\u0440 \u0441\u0432\u0438\u0434\u0435\u0442\u0435\u043B\u044C\u0441\u0442\u0432\u0430 \u043E \u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0430\u0446\u0438\u0438 \u0442\u0440\u0430\u043D\u0441\u043F\u043E\u0440\u0442\u043D\u043E\u0433\u043E \u0441\u0440\u0435\u0434\u0441\u0442\u0432\u0430 (\u0421\u0422\u0421).</p><h3>\u041C\u043E\u0436\u043D\u043E \u043B\u0438 \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u0442\u043E\u043B\u044C\u043A\u043E \u043F\u043E \u0433\u043E\u0441\u043D\u043E\u043C\u0435\u0440\u0443?</h3><p>\u041D\u0435\u0442, \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0442\u043E\u043B\u044C\u043A\u043E \u043F\u043E \u0433\u043E\u0441\u043D\u043E\u043C\u0435\u0440\u0443 \u043D\u0435\u0432\u043E\u0437\u043C\u043E\u0436\u043D\u0430. \u0413\u043E\u0441\u043D\u043E\u043C\u0435\u0440 \u044F\u0432\u043B\u044F\u0435\u0442\u0441\u044F \u043E\u0431\u0449\u0435\u0434\u043E\u0441\u0442\u0443\u043F\u043D\u043E\u0439 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u0435\u0439, \u0438 \u0434\u043B\u044F \u043E\u0431\u0435\u0441\u043F\u0435\u0447\u0435\u043D\u0438\u044F \u0431\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u0438 \u0442\u0440\u0435\u0431\u0443\u0435\u0442\u0441\u044F \u0442\u0430\u043A\u0436\u0435 \u0443\u043A\u0430\u0437\u0430\u0442\u044C \u043D\u043E\u043C\u0435\u0440 \u0421\u0422\u0421. \u042D\u0442\u043E \u0433\u0430\u0440\u0430\u043D\u0442\u0438\u0440\u0443\u0435\u0442, \u0447\u0442\u043E \u0434\u0430\u043D\u043D\u044B\u0435 \u043E \u0448\u0442\u0440\u0430\u0444\u0430\u0445 \u0434\u043E\u0441\u0442\u0443\u043F\u043D\u044B \u0442\u043E\u043B\u044C\u043A\u043E \u0432\u043B\u0430\u0434\u0435\u043B\u044C\u0446\u0443 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u044F \u0438\u043B\u0438 \u0443\u043F\u043E\u043B\u043D\u043E\u043C\u043E\u0447\u0435\u043D\u043D\u044B\u043C \u043B\u0438\u0446\u0430\u043C.</p><h3>\u041F\u0440\u0435\u0438\u043C\u0443\u0449\u0435\u0441\u0442\u0432\u0430 \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u0430 Compas.pro</h3><ul><li><strong>\u0422\u043E\u0447\u043D\u043E\u0441\u0442\u044C \u0438 \u0441\u043A\u043E\u0440\u043E\u0441\u0442\u044C.</strong> \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F \u043E\u0431\u0435\u0441\u043F\u0435\u0447\u0438\u0432\u0430\u0435\u0442 \u0430\u043A\u0442\u0443\u0430\u043B\u044C\u043D\u044B\u0435 \u0438 \u0442\u043E\u0447\u043D\u044B\u0435 \u0434\u0430\u043D\u043D\u044B\u0435 \u043E \u0448\u0442\u0440\u0430\u0444\u0430\u0445.</li><li><strong>\u0423\u0434\u043E\u0431\u043D\u0430\u044F \u043E\u043F\u043B\u0430\u0442\u0430.</strong> \u0412\u043E\u0437\u043C\u043E\u0436\u043D\u043E\u0441\u0442\u044C \u043E\u043F\u043B\u0430\u0442\u044B \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043E\u043D\u043B\u0430\u0439\u043D, \u043D\u0435 \u0432\u044B\u0445\u043E\u0434\u044F \u0438\u0437 \u0434\u043E\u043C\u0430.</li><li><strong>\u0411\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u044C \u0434\u0430\u043D\u043D\u044B\u0445.</strong> \u0421\u043E\u0432\u0440\u0435\u043C\u0435\u043D\u043D\u044B\u0435 \u0442\u0435\u0445\u043D\u043E\u043B\u043E\u0433\u0438\u0438 \u0448\u0438\u0444\u0440\u043E\u0432\u0430\u043D\u0438\u044F \u043D\u0430\u0434\u0435\u0436\u043D\u043E \u0437\u0430\u0449\u0438\u0449\u0430\u044E\u0442 \u0432\u0430\u0448\u0438 \u0434\u0430\u043D\u043D\u044B\u0435.</li><li><strong>\u0418\u0441\u0442\u043E\u0440\u0438\u044F \u043F\u043B\u0430\u0442\u0435\u0436\u0435\u0439.</strong> \u0414\u043E\u0441\u0442\u0443\u043F \u043A \u043A\u0432\u0438\u0442\u0430\u043D\u0446\u0438\u044F\u043C \u0438 \u0438\u0441\u0442\u043E\u0440\u0438\u0438 \u043E\u043F\u043B\u0430\u0442\u044B \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0441\u043E\u0445\u0440\u0430\u043D\u044F\u0435\u0442\u0441\u044F \u0432 \u043B\u0438\u0447\u043D\u043E\u043C \u043A\u0430\u0431\u0438\u043D\u0435\u0442\u0435.</li></ul>"
  },
  "po-nomeru-postanovleniya": {
    text: "<h2>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F</h2><p>\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F \u2014 \u044D\u0442\u043E \u0443\u0434\u043E\u0431\u043D\u044B\u0439 \u0438 \u0442\u043E\u0447\u043D\u044B\u0439 \u0441\u043F\u043E\u0441\u043E\u0431 \u0443\u0437\u043D\u0430\u0442\u044C \u043E \u043D\u0430\u043B\u0438\u0447\u0438\u0438 \u043D\u0435\u043E\u043F\u043B\u0430\u0447\u0435\u043D\u043D\u043E\u0433\u043E \u0448\u0442\u0440\u0430\u0444\u0430. \u0421\u0435\u0440\u0432\u0438\u0441 <strong>Compas.pro</strong> \u043F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u043E\u043F\u0435\u0440\u0430\u0442\u0438\u0432\u043D\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444 \u0438 \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0435\u0433\u043E \u043E\u043D\u043B\u0430\u0439\u043D, \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u0443\u044F \u0443\u043D\u0438\u043A\u0430\u043B\u044C\u043D\u044B\u0439 \u043D\u043E\u043C\u0435\u0440 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F, \u0443\u043A\u0430\u0437\u0430\u043D\u043D\u044B\u0439 \u0432 \u0434\u043E\u043A\u0443\u043C\u0435\u043D\u0442\u0435 \u043E \u043F\u0440\u0430\u0432\u043E\u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u0438.</p><h3>\u0427\u0442\u043E \u0442\u0430\u043A\u043E\u0435 \u043D\u043E\u043C\u0435\u0440 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F?</h3><p><strong>\u041D\u043E\u043C\u0435\u0440 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F</strong> \u2014 \u044D\u0442\u043E \u0443\u043D\u0438\u043A\u0430\u043B\u044C\u043D\u044B\u0439 \u0438\u0434\u0435\u043D\u0442\u0438\u0444\u0438\u043A\u0430\u0442\u043E\u0440, \u043F\u0440\u0438\u0441\u0432\u0430\u0438\u0432\u0430\u0435\u043C\u044B\u0439 \u043A\u0430\u0436\u0434\u043E\u043C\u0443 \u0430\u0434\u043C\u0438\u043D\u0438\u0441\u0442\u0440\u0430\u0442\u0438\u0432\u043D\u043E\u043C\u0443 \u043F\u0440\u0430\u0432\u043E\u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044E. \u042D\u0442\u043E\u0442 \u043D\u043E\u043C\u0435\u0440 \u0443\u043A\u0430\u0437\u0430\u043D \u0432 \u0434\u043E\u043A\u0443\u043C\u0435\u043D\u0442\u0435, \u043A\u043E\u0442\u043E\u0440\u044B\u0439 \u0432\u044B\u0434\u0430\u0435\u0442\u0441\u044F \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044E \u043F\u0440\u0438 \u0444\u0438\u043A\u0441\u0430\u0446\u0438\u0438 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F \u043F\u0440\u0430\u0432\u0438\u043B \u0434\u043E\u0440\u043E\u0436\u043D\u043E\u0433\u043E \u0434\u0432\u0438\u0436\u0435\u043D\u0438\u044F. \u041D\u043E\u043C\u0435\u0440 \u043F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u043E\u0434\u043D\u043E\u0437\u043D\u0430\u0447\u043D\u043E \u0438\u0434\u0435\u043D\u0442\u0438\u0444\u0438\u0446\u0438\u0440\u043E\u0432\u0430\u0442\u044C \u043A\u043E\u043D\u043A\u0440\u0435\u0442\u043D\u044B\u0439 \u0448\u0442\u0440\u0430\u0444 \u0438 \u043F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0435\u0433\u043E \u0441\u0442\u0430\u0442\u0443\u0441.</p><h3>\u041F\u043E\u0447\u0435\u043C\u0443 \u0432\u0430\u0436\u043D\u043E \u043F\u0440\u043E\u0432\u0435\u0440\u044F\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F?</h3><p>\u0428\u0442\u0440\u0430\u0444\u044B, \u0432\u044B\u043F\u0438\u0441\u0430\u043D\u043D\u044B\u0435 \u043D\u0430 \u043C\u0435\u0441\u0442\u0435 \u0438\u043D\u0441\u043F\u0435\u043A\u0442\u043E\u0440\u043E\u043C \u0413\u0418\u0411\u0414\u0414, \u043C\u043E\u0433\u0443\u0442 \u043D\u0435 \u0441\u0440\u0430\u0437\u0443 \u043F\u043E\u044F\u0432\u0438\u0442\u044C\u0441\u044F \u0432 \u043E\u0431\u0449\u0435\u0439 \u0431\u0430\u0437\u0435 \u0434\u0430\u043D\u043D\u044B\u0445. \u042D\u0442\u043E \u043C\u043E\u0436\u0435\u0442 \u043F\u0440\u043E\u0438\u0437\u043E\u0439\u0442\u0438, \u0435\u0441\u043B\u0438 \u0432 \u0441\u0438\u0441\u0442\u0435\u043C\u0435 \u043D\u0435 \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u044B \u0432\u0441\u0435 \u0434\u0430\u043D\u043D\u044B\u0435 \u043E \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u043E\u043C \u0443\u0434\u043E\u0441\u0442\u043E\u0432\u0435\u0440\u0435\u043D\u0438\u0438. \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u0430 \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F \u043F\u043E\u0437\u0432\u043E\u043B\u044F\u0435\u0442 \u0438\u0437\u0431\u0435\u0436\u0430\u0442\u044C \u0437\u0430\u0434\u0435\u0440\u0436\u0435\u043A \u0432 \u043E\u0431\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u0438 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u0438 \u0438 \u0432\u043E\u0432\u0440\u0435\u043C\u044F \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444, \u0438\u0437\u0431\u0435\u0433\u0430\u044F \u043D\u0430\u0447\u0438\u0441\u043B\u0435\u043D\u0438\u044F \u043F\u0435\u043D\u0438 \u0438 \u0434\u0440\u0443\u0433\u0438\u0445 \u0441\u0430\u043D\u043A\u0446\u0438\u0439.</p><h3>\u041F\u0440\u0435\u0438\u043C\u0443\u0449\u0435\u0441\u0442\u0432\u0430 \u0438\u0441\u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u043D\u0438\u044F \u0441\u0435\u0440\u0432\u0438\u0441\u0430 Compas.pro</h3><ul><li><strong>\u0422\u043E\u0447\u043D\u043E\u0441\u0442\u044C \u0438 \u0441\u043A\u043E\u0440\u043E\u0441\u0442\u044C.</strong> \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F \u0433\u0430\u0440\u0430\u043D\u0442\u0438\u0440\u0443\u0435\u0442 \u0430\u043A\u0442\u0443\u0430\u043B\u044C\u043D\u044B\u0435 \u0434\u0430\u043D\u043D\u044B\u0435 \u043E \u0448\u0442\u0440\u0430\u0444\u0430\u0445.</li><li><strong>\u0423\u0434\u043E\u0431\u043D\u0430\u044F \u043E\u043F\u043B\u0430\u0442\u0430.</strong> \u0412\u043E\u0437\u043C\u043E\u0436\u043D\u043E\u0441\u0442\u044C \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B \u043E\u043D\u043B\u0430\u0439\u043D \u0432 \u043F\u0430\u0440\u0443 \u043A\u043B\u0438\u043A\u043E\u0432, \u043D\u0435 \u0432\u044B\u0445\u043E\u0434\u044F \u0438\u0437 \u0434\u043E\u043C\u0430.</li><li><strong>\u0411\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u043E\u0441\u0442\u044C \u0434\u0430\u043D\u043D\u044B\u0445.</strong> \u0421\u043E\u0432\u0440\u0435\u043C\u0435\u043D\u043D\u044B\u0435 \u0442\u0435\u0445\u043D\u043E\u043B\u043E\u0433\u0438\u0438 \u0448\u0438\u0444\u0440\u043E\u0432\u0430\u043D\u0438\u044F \u0437\u0430\u0449\u0438\u0449\u0430\u044E\u0442 \u0432\u0430\u0448\u0438 \u0434\u0430\u043D\u043D\u044B\u0435.</li><li><strong>\u0418\u0441\u0442\u043E\u0440\u0438\u044F \u043F\u043B\u0430\u0442\u0435\u0436\u0435\u0439.</strong> \u0414\u043E\u0441\u0442\u0443\u043F \u043A \u043A\u0432\u0438\u0442\u0430\u043D\u0446\u0438\u044F\u043C \u043E\u0431 \u043E\u043F\u043B\u0430\u0442\u0435 \u0438 \u0438\u0441\u0442\u043E\u0440\u0438\u0438 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0447\u0435\u0440\u0435\u0437 \u043B\u0438\u0447\u043D\u044B\u0439 \u043A\u0430\u0431\u0438\u043D\u0435\u0442.</li></ul><p>\u041D\u0435 \u0436\u0434\u0438\u0442\u0435, \u0432\u043E\u0441\u043F\u043E\u043B\u044C\u0437\u0443\u0439\u0442\u0435\u0441\u044C <strong>Compas.pro</strong> \u0434\u043B\u044F \u0431\u044B\u0441\u0442\u0440\u043E\u0439 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0438 \u043E\u043F\u043B\u0430\u0442\u044B \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F!</p>"
  }
};
const _sfc_main = {
  __name: "Main",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    let activeTab = ref({
      type: null,
      tab: "po-sts"
    });
    provide("activeTab", activeTab);
    const tabs = [
      { id: 1, title: "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0421\u0422\u0421", tab: "po-sts", enabled: true },
      { id: 2, title: "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0412\u0423", tab: "po-voditelskomu-udostovereniyu", enabled: true },
      { id: 3, title: "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044E", tab: "po-nomeru-postanovleniya", enabled: true },
      { id: 4, title: "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0433\u043E\u0441. \u043D\u043E\u043C\u0435\u0440\u0443", tab: "po-nomeru-avto", enabled: true },
      { id: 5, title: "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0418\u041D\u041D", tab: "po-inn", enabled: true }
    ];
    const titleMap = {
      "po-sts": "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0421\u0422\u0421",
      "po-voditelskomu-udostovereniyu": "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0412\u0423",
      "po-nomeru-postanovleniya": "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044E",
      "po-nomeru-avto": "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0433\u043E\u0441. \u043D\u043E\u043C\u0435\u0440\u0443",
      "po-inn": "\u0428\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u0418\u041D\u041D"
    };
    const changeTab = async (tab) => {
      activeTab.value.tab = tab.value;
      await navigateTo(`/products/fines${activeTab.value.tab ? `/${activeTab.value.tab}` : ""}`);
    };
    watch(
      () => route.params.type,
      () => {
        if (route.params.type != activeTab.value.tab) {
          activeTab.value.tab = route.params.type;
        }
      }
    );
    let breadcrumbs = computed(
      () => activeTab.value.tab ? [
        {
          title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0430",
          link: "/"
        },
        {
          title: "\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432",
          link: "/products/fines"
        },
        {
          title: titleMap[activeTab.value.tab],
          link: `/products/fines${activeTab.value.tab ? `/${activeTab.value.tab}` : null}`
        }
      ] : [
        {
          title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0430",
          link: "/"
        },
        {
          title: "\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432",
          link: "/products/fines"
        }
      ]
    );
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      const _component_AppBreadcrambs = AppBreadcrambs;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(_component_AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      _push(ssrRenderComponent(AppTabs, {
        class: "main__links",
        tabs,
        isShowActions: false,
        onCallAction: (tab) => changeTab(tab)
      }, null, _parent));
      _push(`<hr class="main__line">`);
      _push(ssrRenderComponent(MainFines, null, null, _parent));
      _push(ssrRenderComponent(MainCompanies, {
        list: _ctx.fines,
        title: "\u0423\u0436\u0435 1000 \u043A\u043B\u0438\u0435\u043D\u0442\u043E\u0432 \u043E\u043F\u043B\u0430\u0442\u0438\u043B\u0438 \u0431\u043E\u043B\u0435\u0435 50 000 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043D\u0430 50% \u0434\u0435\u0448\u0435\u0432\u043B\u0435 \u0447\u0435\u043C \u044D\u0442\u043E \u0434\u0435\u043B\u0430\u043B\u0438 \u0440\u0430\u043D\u044C\u0448\u0435."
      }, null, _parent));
      _push(ssrRenderComponent(MainBase, null, null, _parent));
      _push(ssrRenderComponent(MainSteps, null, null, _parent));
      _push(ssrRenderComponent(MainPluses, null, null, _parent));
      _push(ssrRenderComponent(CommonProgramm, {
        class: "main__programm",
        title: "\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0430\u0431\u0441\u043E\u043B\u044E\u0442\u043D\u043E \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u044B",
        desc: "\u0421\u0435\u0440\u0432\u0438\u0441 \xABCompas.pro\xBB \u0441\u043E\u0431\u0438\u0440\u0430\u0435\u0442 \u0434\u0430\u043D\u043D\u044B\u0435 \u0438\u0437 \u043E\u0444\u0438\u0446\u0438\u0430\u043B\u044C\u043D\u044B\u0445 \u0438\u0441\u0442\u043E\u0447\u043D\u0438\u043A\u043E\u0432: \u0413\u0418\u0411\u0414\u0414, \u041C\u0410\u0414\u0418, \u0410\u041C\u041F\u041F, \u0413\u0418\u0421 \u0413\u041C\u041F. \u041F\u043E\u0441\u043B\u0435 \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0438 \u0432\u044B \u043F\u043E\u043B\u0443\u0447\u0438\u0442\u0435 \u043E\u0431\u0449\u0435\u0435 \u043A\u043E\u043B\u0438\u0447\u0435\u0441\u0442\u0432\u043E \u0438 \u0441\u0443\u043C\u043C\u0443 \u0432\u0441\u0435\u0445 \u0448\u0442\u0440\u0430\u0444\u043E\u0432. \u041E\u0442\u0447\u0435\u0442 \u0442\u0430\u043A\u0436\u0435 \u0432\u043A\u043B\u044E\u0447\u0430\u0435\u0442 \u0438\u0441\u0442\u043E\u0440\u0438\u044E \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0438 \u0434\u0435\u0442\u0430\u043B\u044C\u043D\u0443\u044E \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044E \u043F\u043E \u043A\u0430\u0436\u0434\u043E\u043C\u0443 \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044E \u0413\u0418\u0411\u0414\u0414."
      }, null, _parent));
      _push(ssrRenderComponent(PlusesFines, null, null, _parent));
      _push(ssrRenderComponent(MainAbout, {
        text: ((_a = unref(route).params) == null ? void 0 : _a.type) in unref(aboutJson) ? unref(aboutJson)[unref(route).params.type].text : unref(aboutJson).default.text
      }, null, _parent));
      _push(ssrRenderComponent(MainQuestions, null, null, _parent));
      _push(ssrRenderComponent(MainArticles, { class: "main__questinos" }, null, _parent));
      _push(ssrRenderComponent(CommonSocial, { class: "main__social" }, null, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Main/Main.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const TemplateMain = _sfc_main;

export { TemplateMain as T };
//# sourceMappingURL=Main-eea9d5da.mjs.map
