import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { useSSRContext, toRefs, mergeProps, unref, inject, ref, onUnmounted, computed, watchEffect, provide, withCtx, createTextVNode, toDisplayString, createVNode, withModifiers, openBlock, createBlock, Fragment, renderList, withDirectives, vShow, withAsyncContext, resolveDynamicComponent, createCommentVNode } from 'vue';
import { u as useRoute, a as useHead, m as IconPasswordEye, A as AppH2, j as AppInput, k as AppButton, s as storeToRefs, _ as _export_sfc, b as api, n as navigateTo, o as AppWarning$1, g as AppSelect } from './server.mjs';
import { ssrRenderComponent, ssrRenderAttrs, ssrInterpolate, ssrRenderAttr, ssrRenderStyle, ssrRenderList, ssrRenderClass, ssrRenderVNode } from 'vue/server-renderer';
import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import { u as useAsyncData } from './asyncData-2f1fb5f7.mjs';
import { M as MainArticles, s as stsImage, v as vuImage, p as postanovlenieImage, g as gosImage, i as innImage } from './preview-inn-d36097f7.mjs';
import { C as CommonSocial } from './Social-983a064f.mjs';
import { u as useDayjs } from './dayjs-ce9ed7b6.mjs';
import { d as defaultImage } from './defaultBg-d9f025d0.mjs';
import { u as useArticlesStore } from './index-e6d877f1.mjs';
import { M as MainAbout } from './WrapText-f5da5fca.mjs';
import { _ as _imports_0 } from './youtube_blue-a00a4300.mjs';
import { F as FansyBox, V as ValidateField, A as AppRelation, a as AppTextarea, b as AppStatus, c as AppDate, d as AppFile } from './Validate-398d291a.mjs';
import { A as AppSection } from './AppSection-1ea634ac.mjs';
import { u as useFinesStore } from './finesStore-67f46f86.mjs';
import { Q as Question } from './QuestionFull-b10f870c.mjs';
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
import 'swiper/vue';
import './Slider-a943f5b9.mjs';
import 'swiper';
import './ArticleItem-812dd48a.mjs';
import 'lodash';
import '@vuepic/vue-datepicker';
import 'vue-accessible-color-picker';
import './Input-3345b1b6.mjs';
import './ButtonText-edbdf3ac.mjs';

const defaultAvatar = "" + publicAssetsURL("articles/defaultAvatar.png");
const _sfc_main$8 = {
  __name: "Header",
  __ssrInlineRender: true,
  props: {
    title: {
      type: String,
      required: true
    },
    image: {
      type: String,
      required: true
    },
    authorAvatar: {
      type: String,
      required: true
    },
    authorName: {
      type: String,
      required: true
    },
    authorDesc: {
      type: String,
      required: true
    },
    authorColor: {
      type: String,
      required: true
    },
    date: {
      type: String,
      required: true
    },
    update: {
      type: String,
      default: false
    },
    views: {
      type: Number,
      required: true
    },
    readingTime: {
      type: Number,
      required: true
    }
  },
  setup(__props) {
    const dayjs = useDayjs();
    const props = __props;
    const { authorAvatar, authorDesc, authorName, authorColor, date, update, image, title, views, readingTime } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "header" }, _attrs))} data-v-3d1d0374><div class="header__top" data-v-3d1d0374><h1 class="header__title" data-v-3d1d0374>${ssrInterpolate(unref(title))}</h1><figure class="ibg header__image" data-v-3d1d0374><img${ssrRenderAttr("src", unref(image) ? unref(image) : unref(defaultImage))}${ssrRenderAttr("alt", unref(title))} data-v-3d1d0374></figure></div><div class="header__bottom" data-v-3d1d0374><div class="header__author" data-v-3d1d0374>`);
      if (unref(authorAvatar)) {
        _push(`<img${ssrRenderAttr("src", unref(authorAvatar) ? unref(authorAvatar) : unref(defaultAvatar))}${ssrRenderAttr("alt", unref(authorName))} class="header__author-avatar" data-v-3d1d0374>`);
      } else {
        _push(`<div style="${ssrRenderStyle(`background:${unref(authorColor)};`)}" class="header__author-avatar" data-v-3d1d0374>${ssrInterpolate(unref(authorName)[0])}</div>`);
      }
      _push(`<div class="header__author-info" data-v-3d1d0374><div class="header__author-name" data-v-3d1d0374>${ssrInterpolate(unref(authorName))}</div><div class="header__author-desc" data-v-3d1d0374>${ssrInterpolate(unref(authorDesc))}</div></div></div><div class="header__info" data-v-3d1d0374><div class="header__date date" data-v-3d1d0374>${ssrInterpolate(unref(dayjs)(unref(date)).locale("ru").format("D MMMM YYYY"))} `);
      if (unref(update) && unref(dayjs)(unref(update)).startOf("date") != unref(dayjs)(unref(date)).startOf("date")) {
        _push(`<div data-v-3d1d0374>(<span class="header__date-green" data-v-3d1d0374>\u041E\u0431\u043D\u043E\u0432\u043B\u0435\u043D\u043E</span> ${ssrInterpolate(unref(dayjs)(unref(update)).locale("ru").format("DD.MM.YYYY"))})</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="header__views views" data-v-3d1d0374>`);
      _push(ssrRenderComponent(IconPasswordEye, { class: "header__views-eye" }, null, _parent));
      _push(`<span data-v-3d1d0374>${ssrInterpolate(unref(views))}</span></div><div class="header__duration duration" data-v-3d1d0374> \u0427\u0438\u0442\u0430\u0442\u044C \u0441\u0442\u0430\u0442\u044C\u044E: <span class="duration_black" data-v-3d1d0374>${ssrInterpolate(unref(readingTime))} \u043C\u0438\u043D</span></div></div></div></div>`);
    };
  }
};
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ArticleOne/components/Header/Header.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const Header = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["__scopeId", "data-v-3d1d0374"]]);
const _sfc_main$7 = {
  __name: "Nav",
  __ssrInlineRender: true,
  setup(__props) {
    inject("$articleContent");
    const $articleWrapper = inject("$articleWrapper");
    let docsNav = ref([]);
    const docsNavRef = ref(null);
    const headers = ref([]);
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
      if ($articleWrapper.value.getBoundingClientRect().top - 20 < 0) {
        docsNavRef.value.classList.add("nav_fixed");
      } else {
        docsNavRef.value.classList.remove("docs-nav_fixed");
        docsNavRef.value.classList.remove("nav_fixed");
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<nav${ssrRenderAttrs(mergeProps({
        class: "nav",
        ref_key: "docsNavRef",
        ref: docsNavRef
      }, _attrs))} data-v-103bb899><div class="nav__header" data-v-103bb899>\u0421\u043E\u0434\u0435\u0440\u0436\u0430\u043D\u0438\u0435</div><div class="nav__list" data-v-103bb899><!--[-->`);
      ssrRenderList(unref(docsNav), (item) => {
        _push(`<a${ssrRenderAttr("href", item.link)} class="${ssrRenderClass([{ nav__item_H3: item.nodeName == "H3", nav__item_H4: item.nodeName == "H4", nav__item_scrolled: item.isScrolled, nav__item_active: item.isActive }, "nav__item"])}" data-v-103bb899>${ssrInterpolate(item.text)}</a>`);
      });
      _push(`<!--]--></div></nav>`);
    };
  }
};
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ArticleOne/components/Nav/Nav.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const Nav = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["__scopeId", "data-v-103bb899"]]);
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ArticleOne/components/Fines/Warning/Validation/Validation.vue");
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ArticleOne/components/Fines/Warning/Warning.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const FinesWarning = _sfc_main$5;
const _sfc_main$4 = {
  __name: "Fines",
  __ssrInlineRender: true,
  props: {
    type: {
      type: String
    }
  },
  setup(__props) {
    const finesStore = useFinesStore();
    const props = __props;
    const { type } = toRefs(props);
    const previewImage = {
      "check-sts": stsImage,
      "check-vu": vuImage,
      "check-num_post": postanovlenieImage,
      "check-gos": gosImage,
      registration: gosImage,
      "check-inn": innImage
    };
    const titleMap = {
      "check-sts": "\u043F\u043E \u0421\u0422\u0421",
      "check-vu": "\u043F\u043E \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u044C\u0441\u043A\u043E\u043C\u0443 \u0443\u0434\u043E\u0441\u0442\u043E\u0432\u0435\u0440\u0435\u043D\u0438\u044E",
      "check-num_post": "\u043F\u043E \u043D\u043E\u043C\u0435\u0440\u0443 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F",
      "check-gos": "\u043F\u043E \u0433\u043E\u0441. \u043D\u043E\u043C\u0435\u0440\u0443",
      registration: "\u043F\u043E \u0433\u043E\u0441. \u043D\u043E\u043C\u0435\u0440\u0443",
      "check-inn": "\u043F\u043E \u0418\u041D\u041D"
    };
    let fields = computed(() => {
      switch (type.value) {
        case "check-sts": {
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
        case "check-vu": {
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
        case "check-num_post": {
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
        case "registration":
        case "check-gos": {
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
        case "check-inn": {
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
      form.value = [...fields.value];
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
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, { class: "fines__title fines__title_show" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 ${ssrInterpolate(titleMap[unref(type)])} \u0432 1 \u043A\u043B\u0438\u043A `);
                } else {
                  return [
                    createTextVNode(" \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 " + toDisplayString(titleMap[unref(type)]) + " \u0432 1 \u043A\u043B\u0438\u043A ", 1)
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<form class="fines__form" data-v-b9039adb${_scopeId}><!--[-->`);
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
            _push2(`<!--]--><div class="fines__actions" data-v-b9039adb${_scopeId}>`);
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
                        _push4(`<figure class="ibg fines__icon" data-v-b9039adb${_scopeId3}><img${ssrRenderAttr("src", _imports_0)} alt="\u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435" data-v-b9039adb${_scopeId3}></figure> \u041E \u0441\u0435\u0440\u0432\u0438\u0441\u0435 <span class="button-text" data-v-b9039adb${_scopeId3}> (1 \u043C\u0438\u043D 20 \u0441\u0435\u043A) </span>`);
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
            _push2(`</div><div class="fines__politics" data-v-b9039adb${_scopeId}>\u041D\u0430\u0436\u0438\u043C\u0430\u044F \xAB\u041F\u0440\u043E\u0432\u0435\u0440\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444\u044B\xBB \u0432\u044B \u0441\u043E\u0433\u043B\u0430\u0448\u0430\u0435\u0442\u0435\u0441\u044C \u0441 \u043F\u043E\u043B\u0438\u0442\u0438\u043A\u043E\u0439 \u043E\u0431\u0440\u0430\u0431\u043E\u0442\u043A\u0438 \u043F\u0435\u0440\u0441\u043E\u043D\u0430\u043B\u044C\u043D\u044B\u0445 \u0434\u0430\u043D\u043D\u044B\u0445 \u0438 \u043F\u0440\u0438\u043D\u0438\u043C\u0430\u0435\u0442\u0435 \u043E\u0444\u0435\u0440\u0442\u0443</div></form><figure class="ibg fines__image" data-v-b9039adb${_scopeId}><img${ssrRenderAttr("src", previewImage == null ? void 0 : previewImage[unref(type)])} alt="\u041F\u0440\u043E\u0432\u0435\u0440\u044C\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u0438 \u0437\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u0443\u0439\u0442\u0435\u0441\u044C \u0432 1 \u043A\u043B\u0438\u043A" data-v-b9039adb${_scopeId}></figure>`);
            _push2(ssrRenderComponent(FinesWarning, {
              onCallAction: (data) => saveChanges()
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(AppH2, { class: "fines__title fines__title_show" }, {
                default: withCtx(() => [
                  createTextVNode(" \u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 " + toDisplayString(titleMap[unref(type)]) + " \u0432 1 \u043A\u043B\u0438\u043A ", 1)
                ]),
                _: 1
              }),
              createVNode("form", {
                class: "fines__form",
                onClick: withModifiers(() => {
                }, ["prevent"])
              }, [
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
                    [
                      vShow,
                      unref(form).find((i) => ["sts", "vu", "uin", "gos"].includes(i.key)) && unref(form)[0].value != "" || ["number", "certificate", "sts", "vu", "uin", "gos", "inn", "kpp"].includes(item.key) || unref(form)[0].value != "" && unref(form)[1].value != ""
                    ]
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
                  src: previewImage == null ? void 0 : previewImage[unref(type)],
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ArticleOne/components/Fines/Fines.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const registration = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["__scopeId", "data-v-b9039adb"]]);
const _sfc_main$3 = {
  __name: "InterestItem",
  __ssrInlineRender: true,
  props: {
    image: {
      type: String,
      required: true
    },
    title: {
      type: String,
      required: true
    },
    desc: {
      type: String,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const { desc, image, title } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "item" }, _attrs))} data-v-07e6b16e><img${ssrRenderAttr("src", unref(image))}${ssrRenderAttr("alt", unref(title))} class="item__image" data-v-07e6b16e><div class="item__info" data-v-07e6b16e><h4 class="item__title" data-v-07e6b16e>${ssrInterpolate(unref(title))}</h4><p class="item__desc" data-v-07e6b16e>${ssrInterpolate(unref(desc))}</p></div></div>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ArticleOne/components/InterestItems/InterestItem/InterestItem.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const InterestItem = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["__scopeId", "data-v-07e6b16e"]]);
const _sfc_main$2 = {
  __name: "InterestItems",
  __ssrInlineRender: true,
  props: {
    title: {
      type: String,
      required: true
    },
    items: {
      type: Array,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const { items, title } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "items__wrapper" }, _attrs))} data-v-35997f80>`);
      _push(ssrRenderComponent(AppH2, { class: "items__title" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(unref(title))}`);
          } else {
            return [
              createTextVNode(toDisplayString(unref(title)), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="items" data-v-35997f80><!--[-->`);
      ssrRenderList(unref(items), ({ title: title2, desc, image }) => {
        _push(ssrRenderComponent(InterestItem, {
          title: title2,
          desc,
          image
        }, null, _parent));
      });
      _push(`<!--]--></div></div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ArticleOne/components/InterestItems/InterestItems.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const interestItems = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-35997f80"]]);
const _sfc_main$1 = {
  __name: "ArticleOne",
  __ssrInlineRender: true,
  async setup(__props) {
    var _a2, _b2, _c2;
    var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l;
    let __temp, __restore;
    const route = useRoute();
    const articlesStore = useArticlesStore();
    const { articlesList, articleDetail } = storeToRefs(articlesStore);
    const $articleWrapper = ref(null);
    const $articleContent = ref(null);
    provide("$articleWrapper", $articleWrapper);
    provide("$articleContent", $articleContent);
    [__temp, __restore] = withAsyncContext(async () => useAsyncData("articles", async () => articlesList.value.length == 0 ? await articlesStore.loadArticles() : 0)), await __temp, __restore();
    [__temp, __restore] = withAsyncContext(async () => useAsyncData("article", async () => await articlesStore.loadArticle(route.params.id))), await __temp, __restore();
    const conmponentsMap = {
      wrap: MainAbout,
      registration,
      "check-sts": registration,
      "check-vu": registration,
      "check-num_post": registration,
      "check-gos": registration,
      "check-inn": registration,
      interestItems,
      question: Question
    };
    let { created_at, user_id, updated_at, detail_picture, name, views, detail_text, seo_description, seo_title, reading_time } = articleDetail.value;
    const author = {
      name: (_a2 = (_d = (_c = (_b = (_a = user_id == null ? void 0 : user_id.value) == null ? void 0 : _a.localOptions) == null ? void 0 : _b[0]) == null ? void 0 : _c.label) == null ? void 0 : _d.text) != null ? _a2 : "\u0422\u0435\u043C\u0443\u0440 \u041A\u0438\u0441\u0435\u043B\u0435\u0432",
      desc: "\u042D\u043A\u0441\u043F\u0435\u0440\u0442 \u043A\u043E\u043C\u043F\u0430\u043D\u0438\u0438 \u041A\u043E\u043C\u043F\u0430\u0441 \u0414\u0430\u0439\u043D\u0430\u043C\u0438\u043A\u0441",
      avatar: (_b2 = (_h = (_g = (_f = (_e = user_id == null ? void 0 : user_id.value) == null ? void 0 : _e.localOptions) == null ? void 0 : _f[0]) == null ? void 0 : _g.label) == null ? void 0 : _h.file) != null ? _b2 : "",
      color: (_c2 = (_l = (_k = (_j = (_i = user_id == null ? void 0 : user_id.value) == null ? void 0 : _i.localOptions) == null ? void 0 : _j[0]) == null ? void 0 : _k.label) == null ? void 0 : _l.color) != null ? _c2 : ""
    };
    let breadcrumbs = [
      {
        title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0430",
        link: "/"
      },
      {
        title: "\u0421\u0442\u0430\u0442\u044C\u0438",
        link: "/articles"
      },
      {
        title: name == null ? void 0 : name.value,
        link: "/articles/za-chto-vypisan-shtraf"
      }
    ];
    useHead({
      title: `${seo_title.value.value ? seo_title.value.value : seo_title.value} | \u0421\u0442\u0430\u0442\u044C\u0438 | Compas.pro`,
      meta: [
        {
          name: "description",
          content: seo_description.value.value ? seo_description.value.value : seo_description.value
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a22, _b22, _c22, _d2;
      const _component_AppBreadcrambs = AppBreadcrambs;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(_component_AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      if (unref(articleDetail)) {
        _push(`<div class="article"><div class="article__left">`);
        _push(ssrRenderComponent(Header, {
          title: (_a22 = unref(name)) == null ? void 0 : _a22.value,
          image: (_c22 = (_b22 = unref(detail_picture).value) == null ? void 0 : _b22[0]) == null ? void 0 : _c22.file,
          authorAvatar: author == null ? void 0 : author.avatar,
          authorName: author == null ? void 0 : author.name,
          authorDesc: author == null ? void 0 : author.desc,
          authorColor: author == null ? void 0 : author.color,
          date: unref(created_at).value,
          update: unref(updated_at).value,
          views: (_d2 = unref(views)) == null ? void 0 : _d2.value,
          readingTime: unref(reading_time).value
        }, null, _parent));
        _push(`<div class="article__content"><!--[-->`);
        ssrRenderList(unref(detail_text).value, ({ type, body, image, title, items, answer, views: views2, id, date }) => {
          ssrRenderVNode(_push, createVNode(resolveDynamicComponent(conmponentsMap == null ? void 0 : conmponentsMap[type]), {
            text: body,
            title,
            items,
            image,
            answer,
            views: views2,
            id,
            date,
            type,
            isShowMore: true
          }, null), _parent);
        });
        _push(`<!--]--></div></div><div class="article__right">`);
        if (unref(detail_text)) {
          _push(ssrRenderComponent(Nav, null, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</div></div>`);
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(MainArticles, null, null, _parent));
      _push(ssrRenderComponent(CommonSocial, { class: "article__social" }, null, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/ArticleOne/ArticleOne.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const ArticleOne = _sfc_main$1;
const _sfc_main = {
  __name: "[id]",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    useHead({
      // title: "Вопросы ответы | Compas.pro",
      // meta: [
      // 	{
      // 		name: "description",
      // 		content: "Описание.",
      // 	},
      // ],
      link: [
        {
          rel: "canonical",
          href: `https://compas.pro/articles/${route.params.id}`
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(ArticleOne, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/articles/[id].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_id_-225ebbaa.mjs.map
