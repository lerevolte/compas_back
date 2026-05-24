import { a as useHead, x as AppH1, A as AppH2, k as AppButton, e as __nuxt_component_0 } from './server.mjs';
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttr } from 'vue/server-renderer';
import { useSSRContext, unref, withCtx, createTextVNode, createVNode, mergeProps, toDisplayString } from 'vue';
import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import { _ as _imports_0 } from './program-f89190ac.mjs';
import { C as CompositeBlock } from './CompositeBlock-c42445ea.mjs';
import { A as AppSection } from './AppSection-1ea634ac.mjs';
import { T as TariffsSlider } from './TariffsSlider-31bd220c.mjs';
import { A as AppTable } from './AppTable-9ca02910.mjs';
import { C as CommonSocial } from './Social-983a064f.mjs';
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
import 'swiper/vue';
import './Slider-a943f5b9.mjs';
import 'swiper';
import 'lodash/isEqual.js';
import './Validate-398d291a.mjs';
import 'lodash';
import '@vuepic/vue-datepicker';
import 'vue-accessible-color-picker';
import './Input-3345b1b6.mjs';
import './ButtonText-edbdf3ac.mjs';
import 'lodash/throttle.js';
import './Field-d36cf7e6.mjs';
import './dayjs-ce9ed7b6.mjs';
import 'lodash-es';
import 'lodash/isEmpty.js';

const _sfc_main$2 = {
  __name: "Programm",
  __ssrInlineRender: true,
  props: {
    title: {
      default: null,
      type: String
    },
    desc: {
      default: null,
      type: String
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(ssrRenderComponent(CompositeBlock, mergeProps({ class: "programm" }, _attrs), {
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, { class: "programm__title" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(`${ssrInterpolate(props.title)}`);
                } else {
                  return [
                    createTextVNode(toDisplayString(props.title), 1)
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="programm__desc"${_scopeId}>${ssrInterpolate(props.desc)}</div>`);
            _push2(ssrRenderComponent(_component_NuxtLink, {
              class: "programm__link",
              to: "/auth/registration"
            }, {
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
          } else {
            return [
              createVNode(AppH2, { class: "programm__title" }, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(props.title), 1)
                ]),
                _: 1
              }),
              createVNode("div", { class: "programm__desc" }, toDisplayString(props.desc), 1),
              createVNode(_component_NuxtLink, {
                class: "programm__link",
                to: "/auth/registration"
              }, {
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
            ];
          }
        }),
        image: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<figure class="ibg programm__image"${_scopeId}><img${ssrRenderAttr("src", _imports_0)} alt=""${_scopeId}></figure>`);
          } else {
            return [
              createVNode("figure", { class: "ibg programm__image" }, [
                createVNode("img", {
                  src: _imports_0,
                  alt: ""
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/Programm/Programm.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const CommonProgramm = _sfc_main$2;
const _sfc_main$1 = {
  __name: "Tariffs",
  __ssrInlineRender: true,
  setup(__props) {
    let table = {
      // Таблица
      tableKeys: [
        {
          id: 1,
          title: "",
          key: "module",
          width: "200px",
          enabled: true,
          sort_order: null,
          type: "text",
          is_plural: 0,
          external_link: "",
          is_external_link: 0,
          is_link: 0,
          required: 0,
          fixed: true,
          index: 0,
          fixTarget: "0px",
          read_only: 0,
          unit: null,
          mask: null,
          can_edit: 0,
          color: "",
          is_hidden: 0,
          visible_always: 0,
          options: []
        },
        {
          id: 2,
          isHTMLTitle: true,
          alternativeTitle: `
                    <div class="table-cell__title">
                        \u0411\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u044B\u0439
                    </div> <!--  
                    <div class="table-cell__subtitle">
                        6 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432
                    </div>
                    <div class="table-cell__desc">
                        \u041C\u0438\u043D\u0438\u043C\u0430\u043B\u044C\u043D\u044B\u0439 \u043D\u0430\u0431\u043E\u0440 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432 \u0434\u043B\u044F \u0441\u0442\u0430\u0440\u0442\u0430 \u0441\u043A\u0432\u043E\u0437\u043D\u043E\u0439 \u0430\u043D\u0430\u043B\u0438\u0442\u0438\u043A\u0438
                    </div> -->
                `,
          title: "\u0411\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u044B\u0439",
          key: "free",
          width: "300px",
          enabled: true,
          sort_order: null,
          type: "text",
          is_plural: 0,
          external_link: "",
          is_external_link: 0,
          is_link: 0,
          required: 0,
          fixed: false,
          index: 0,
          fixTarget: "0px",
          read_only: 0,
          unit: null,
          mask: null,
          can_edit: 0,
          color: "",
          is_hidden: 0,
          visible_always: 0,
          options: []
        },
        {
          id: 3,
          isHTMLTitle: true,
          alternativeTitle: `
                    <div class="table-cell__title">
                        \u0411\u0430\u0437\u043E\u0432\u044B\u0439 \u0442\u0430\u0440\u0438\u0444
                    </div> <!--
                    <div class="table-cell__subtitle">
                        7 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432
                    </div>
                    <div class="table-cell__desc">
                        \u041E\u043F\u0442\u0438\u043C\u0430\u043B\u044C\u043D\u044B\u0439 \u043D\u0430\u0431\u043E\u0440 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432 \u0434\u043B\u044F \u0441\u043A\u0432\u043E\u0437\u043D\u043E\u0439 \u0430\u043D\u0430\u043B\u0438\u0442\u0438\u043A\u0438, \u0440\u043E\u0441\u0442\u0430 \u0442\u0440\u0430\u0444\u0438\u043A\u0430, \u0437\u0430\u044F\u0432\u043E\u043A \u0438 \u043F\u0440\u043E\u0434\u0430\u0436
                    </div> -->
                `,
          title: "\u0411\u0430\u0437\u043E\u0432\u044B\u0439 \u0442\u0430\u0440\u0438\u0444",
          key: "base",
          width: "300px",
          enabled: true,
          sort_order: null,
          type: "text",
          is_plural: 0,
          external_link: "",
          is_external_link: 0,
          is_link: 0,
          required: 0,
          fixed: false,
          index: 0,
          fixTarget: "0px",
          read_only: 0,
          unit: null,
          mask: null,
          can_edit: 0,
          color: "",
          is_hidden: 0,
          visible_always: 0,
          options: []
        },
        {
          id: 2,
          isHTMLTitle: true,
          alternativeTitle: `
                    <div class="table-cell__title">
                        \u0411\u0438\u0437\u043D\u0435\u0441
                    </div> 
                    <!--
                    <div class="table-cell__subtitle">
                        11 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432
                    </div>
                    <div class="table-cell__desc">
                        \u041C\u0430\u043A\u0441\u0438\u043C\u0430\u043B\u044C\u043D\u044B\u0439 \u043D\u0430\u0431\u043E\u0440 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432 \u0434\u043B\u044F \u0441\u043A\u0432\u043E\u0437\u043D\u043E\u0439 \u0430\u043D\u0430\u043B\u0438\u0442\u0438\u043A\u0438, \u0440\u043E\u0441\u0442\u0430 \u0442\u0440\u0430\u0444\u0438\u043A\u0430, \u0437\u0430\u044F\u0432\u043E\u043A \u0438 \u043F\u0440\u043E\u0434\u0430\u0436
                    </div>
                    -->
                `,
          title: "\u0411\u0438\u0437\u043D\u0435\u0441",
          key: "business",
          width: "300px",
          enabled: true,
          sort_order: null,
          type: "text",
          is_plural: 0,
          external_link: "",
          is_external_link: 0,
          is_link: 0,
          required: 0,
          fixed: false,
          index: 0,
          fixTarget: "0px",
          read_only: 0,
          unit: null,
          mask: null,
          can_edit: 0,
          color: "",
          is_hidden: 0,
          visible_always: 0,
          options: []
        },
        {
          id: 2,
          isHTMLTitle: true,
          alternativeTitle: `
                    <div class="table-cell__title">
                        \u041F\u0440\u043E\u0444\u0435\u0441\u0441\u0438\u043E\u043D\u0430\u043B\u044C\u043D\u044B\u0439
                    </div> <!--
                    <div class="table-cell__subtitle">
                        14 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432
                    </div>
                    <div class="table-cell__desc">
                        \u041C\u0430\u043A\u0441\u0438\u043C\u0430\u043B\u044C\u043D\u044B\u0439 \u043D\u0430\u0431\u043E\u0440 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432 \u0434\u043B\u044F \u0441\u043A\u0432\u043E\u0437\u043D\u043E\u0439 \u0430\u043D\u0430\u043B\u0438\u0442\u0438\u043A\u0438, \u0440\u043E\u0441\u0442\u0430 \u0442\u0440\u0430\u0444\u0438\u043A\u0430, \u0437\u0430\u044F\u0432\u043E\u043A \u0438 \u043F\u0440\u043E\u0434\u0430\u0436
                    </div> -->
                `,
          title: "\u041F\u0440\u043E\u0444\u0435\u0441\u0441\u0438\u043E\u043D\u0430\u043B\u044C\u043D\u044B\u0439",
          key: "prof",
          width: "300px",
          enabled: true,
          sort_order: null,
          type: "text",
          is_plural: 0,
          external_link: "",
          is_external_link: 0,
          is_link: 0,
          required: 0,
          fixed: false,
          index: 0,
          fixTarget: "0px",
          read_only: 0,
          unit: null,
          mask: null,
          can_edit: 0,
          color: "",
          is_hidden: 0,
          visible_always: 0,
          options: []
        }
      ],
      tableData: [
        {
          module: "\u041A\u043E\u043B-\u0432\u043E \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0435\u0439",
          free: "5",
          base: "10",
          business: "40",
          prof: "100"
        },
        {
          module: "\u041C\u0430\u0433\u0430\u0437\u0438\u043D \u043C\u043E\u0434\u0443\u043B\u0435\u0439",
          free: "0%",
          base: "-10%",
          business: "-20%",
          prof: "-40%"
        },
        {
          module: "\u041E\u0431\u044C\u0435\u043C \u0445\u0440\u0430\u043D\u0438\u043B\u0438\u0449\u0430",
          free: "2.5 \u0433\u0431.",
          base: "5 \u0433\u0431.",
          business: "50 \u0433\u0431.",
          prof: "100 \u0433\u0431."
        },
        {
          module: "\u041C\u044F\u0433\u043A\u043E\u0435 \u0443\u0434\u0430\u043B\u0435\u043D\u0438\u0435",
          free: false,
          base: true,
          business: true,
          prof: true
        },
        {
          module: '<div class="table__cell-group"> \u0428\u0442\u0440\u0430\u0444\u044B \u0413\u0418\u0411\u0414\u0414 <span class="table-cell__desc">\u041A\u043E\u043C\u0438\u0441\u0441\u0438\u044F \u0437\u0430 \u043E\u043F\u043B\u0430\u0442\u0443</span> </div>',
          free: "3%",
          base: "2.7%",
          business: "2.4%",
          prof: "1.8%"
        },
        {
          module: "\u0410\u0432\u0442\u043E\u043F\u0430\u0440\u043A",
          free: true,
          base: true,
          business: true,
          prof: true
        },
        {
          module: "\u0421\u043E\u0442\u0440\u0443\u0434\u043D\u0438\u043A\u0438",
          free: true,
          base: true,
          business: true,
          prof: true
        },
        {
          module: "\u041A\u043E\u043C\u043F\u0430\u043D\u0438\u0438",
          free: true,
          base: true,
          business: true,
          prof: true
        }
        // {
        //     module: 'Мягкое удаление',
        //     free: true,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Товары',
        //     free: true,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Мобильное приложение',
        //     free: true,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Задачи',
        //     free: true,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Маршруты',
        //     free: true,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Пробег',
        //     free: true,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Должностные инструкции',
        //     free: true,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Аналитика',
        //     free: false,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Зарплаты',
        //     free: false,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Аварийный фонд',
        //     free: false,
        //     base: true,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Склады',
        //     free: false,
        //     base: false,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Ремонт и тех. обслуживания',
        //     free: false,
        //     base: false,
        //     business: true,
        //     prof: true
        // },
        // {
        //     module: 'Путевые листы',
        //     free: false,
        //     base: false,
        //     business: true,
        //     prof: true
        // }
      ],
      socketRows: {
        header: [],
        body: []
      },
      // Сортировка по ключу
      sortItem: {
        key: null,
        order: null
      },
      tableFooter: {
        pages: 1,
        activePage: 1,
        count: 25
      },
      loaderState: ""
    };
    let breadcrumbs = [
      {
        title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0430",
        link: "/"
      },
      {
        title: "\u0422\u0430\u0440\u0438\u0444\u044B",
        link: `/tariffs`
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      _push(ssrRenderComponent(AppH1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0422\u0430\u0440\u0438\u0444\u044B `);
          } else {
            return [
              createTextVNode(" \u0422\u0430\u0440\u0438\u0444\u044B ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(CommonProgramm, {
        class: "tariffs__programm",
        title: "\u041F\u0440\u043E\u0433\u0440\u0430\u043C\u043C\u0430 \u0443\u0434\u043E\u0431\u043D\u0430, \u0438\u043D\u0442\u0443\u0438\u0442\u0438\u0432\u043D\u043E \u043F\u043E\u043D\u044F\u0442\u043D\u0430 \u0438 \u043F\u0440\u043E\u0441\u0442\u0430",
        desc: "\u0415\u0441\u043B\u0438 \u0443 \u0412\u0430\u0441 \u0432\u043E\u0437\u043D\u0438\u043A\u043D\u0443\u0442 \u0412\u043E\u043F\u0440\u043E\u0441\u044B \u0437\u0430\u0431\u043E\u0442\u043B\u0438\u0432\u0430\u044F \u0442\u0435\u0445\u043F\u043E\u0434\u0434\u0435\u0440\u0436\u043A\u0430 \u043C\u0430\u043A\u0441\u0438\u043C\u0430\u043B\u044C\u043D\u043E \u0431\u044B\u0441\u0442\u0440\u043E \u0440\u0435\u0448\u0438\u0442 \u0438\u0445 \u043F\u043E\u0434\u043A\u043B\u044E\u0447\u0438\u0432\u0448\u0438\u0441\u044C \u043D\u0430 \u043F\u0440\u044F\u043C\u0443\u044E \u043D\u0430 \u0432\u0430\u0448 \u043F\u043E\u0440\u0442\u0430\u043B."
      }, null, _parent));
      _push(ssrRenderComponent(AppSection, { class: "tarification section_without-background" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041A\u0430\u043A \u043F\u0440\u043E\u0438\u0441\u0445\u043E\u0434\u0438\u0442 \u0442\u0430\u0440\u0438\u0444\u0438\u043A\u0430\u0446\u0438\u044F? `);
                } else {
                  return [
                    createTextVNode(" \u041A\u0430\u043A \u043F\u0440\u043E\u0438\u0441\u0445\u043E\u0434\u0438\u0442 \u0442\u0430\u0440\u0438\u0444\u0438\u043A\u0430\u0446\u0438\u044F? ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="tarification__desc"${_scopeId}><p class="tarification__text"${_scopeId}>\u041F\u043E\u043C\u0438\u043C\u043E \u043E\u0447\u0435\u0432\u0438\u0434\u043D\u044B\u0445 \u0440\u0430\u0437\u043B\u0438\u0447\u0438\u0439 \u0442\u0430\u0440\u0438\u0444\u043D\u044B\u0445 \u043F\u043B\u0430\u043D\u043E\u0432 \u043F\u043E \u043A\u043E\u043B-\u0432\u0443 \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0435\u0439 \u0438\u043B\u0438 \u043E\u0431\u044A\u0435\u043C\u0443 \u0445\u0440\u0430\u043D\u0438\u043B\u0438\u0449\u0430 \u0435\u0441\u0442\u044C \u043F\u043E\u0436\u0430\u043B\u0443\u0439 \u0441\u0430\u043C\u043E\u0435 \u0432\u0430\u0436\u043D\u043E\u0435 \u0440\u0430\u0437\u043B\u0438\u0447\u0438\u0435 \u0432 \u043F\u0440\u043E\u0446\u0435\u043D\u0442\u0435 \u0441\u043A\u0438\u0434\u043A\u0438 \u043D\u0430 \u043A\u043E\u043C\u0438\u0441\u0441\u0438\u044E \u0437\u0430 \u043E\u043F\u043B\u0430\u0442\u0443 \u0448\u0442\u0440\u0430\u0444\u043E\u0432. \u0415\u0441\u043B\u0438 \u0443 \u0432\u0430\u0441 \u043C\u043D\u043E\u0433\u043E \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0438\u043B\u0438 \u0431\u043E\u043B\u044C\u0448\u043E\u0439 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A \u0440\u0435\u043A\u043E\u043C\u0435\u043D\u0434\u0443\u0435\u043C \u043F\u0435\u0440\u0435\u0439\u0442\u0438 \u043D\u0430 \u043F\u0440\u043E\u0444\u0435\u0441\u0441\u0438\u043E\u043D\u0430\u043B\u044C\u043D\u044B\u0439 \u0442\u0430\u0440\u0438\u0444 \u0438 \u043F\u043B\u0430\u0442\u0438\u0442\u044C \u043A\u043E\u043C\u0438\u0441\u0441\u0438\u044E \u0432\u0441\u0435\u0433\u043E 1,8%, \u0435\u0441\u043B\u0438 \u0443 \u0432\u0430\u0441 \u043C\u0430\u043B\u043E \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0438\u043B\u0438 \u043E\u0442\u0441\u043B\u0435\u0436\u0438\u0432\u0430\u0435\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u043E\u0434\u043D\u043E\u0439 \u043C\u0430\u0448\u0438\u043D\u0435, \u0440\u0435\u043A\u043E\u043C\u0435\u043D\u0434\u0443\u0435\u043C \u043E\u0441\u0442\u0430\u0442\u044C\u0441\u044F \u043D\u0430 \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E\u043C \u0442\u0430\u0440\u0438\u0444\u0435.</p></div>`);
            _push2(ssrRenderComponent(TariffsSlider, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u041A\u0430\u043A \u043F\u0440\u043E\u0438\u0441\u0445\u043E\u0434\u0438\u0442 \u0442\u0430\u0440\u0438\u0444\u0438\u043A\u0430\u0446\u0438\u044F? ")
                ]),
                _: 1
              }),
              createVNode("div", { class: "tarification__desc" }, [
                createVNode("p", { class: "tarification__text" }, "\u041F\u043E\u043C\u0438\u043C\u043E \u043E\u0447\u0435\u0432\u0438\u0434\u043D\u044B\u0445 \u0440\u0430\u0437\u043B\u0438\u0447\u0438\u0439 \u0442\u0430\u0440\u0438\u0444\u043D\u044B\u0445 \u043F\u043B\u0430\u043D\u043E\u0432 \u043F\u043E \u043A\u043E\u043B-\u0432\u0443 \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0435\u0439 \u0438\u043B\u0438 \u043E\u0431\u044A\u0435\u043C\u0443 \u0445\u0440\u0430\u043D\u0438\u043B\u0438\u0449\u0430 \u0435\u0441\u0442\u044C \u043F\u043E\u0436\u0430\u043B\u0443\u0439 \u0441\u0430\u043C\u043E\u0435 \u0432\u0430\u0436\u043D\u043E\u0435 \u0440\u0430\u0437\u043B\u0438\u0447\u0438\u0435 \u0432 \u043F\u0440\u043E\u0446\u0435\u043D\u0442\u0435 \u0441\u043A\u0438\u0434\u043A\u0438 \u043D\u0430 \u043A\u043E\u043C\u0438\u0441\u0441\u0438\u044E \u0437\u0430 \u043E\u043F\u043B\u0430\u0442\u0443 \u0448\u0442\u0440\u0430\u0444\u043E\u0432. \u0415\u0441\u043B\u0438 \u0443 \u0432\u0430\u0441 \u043C\u043D\u043E\u0433\u043E \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0438\u043B\u0438 \u0431\u043E\u043B\u044C\u0448\u043E\u0439 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A \u0440\u0435\u043A\u043E\u043C\u0435\u043D\u0434\u0443\u0435\u043C \u043F\u0435\u0440\u0435\u0439\u0442\u0438 \u043D\u0430 \u043F\u0440\u043E\u0444\u0435\u0441\u0441\u0438\u043E\u043D\u0430\u043B\u044C\u043D\u044B\u0439 \u0442\u0430\u0440\u0438\u0444 \u0438 \u043F\u043B\u0430\u0442\u0438\u0442\u044C \u043A\u043E\u043C\u0438\u0441\u0441\u0438\u044E \u0432\u0441\u0435\u0433\u043E 1,8%, \u0435\u0441\u043B\u0438 \u0443 \u0432\u0430\u0441 \u043C\u0430\u043B\u043E \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0438\u043B\u0438 \u043E\u0442\u0441\u043B\u0435\u0436\u0438\u0432\u0430\u0435\u0442\u0435 \u0448\u0442\u0440\u0430\u0444\u044B \u043F\u043E \u043E\u0434\u043D\u043E\u0439 \u043C\u0430\u0448\u0438\u043D\u0435, \u0440\u0435\u043A\u043E\u043C\u0435\u043D\u0434\u0443\u0435\u043C \u043E\u0441\u0442\u0430\u0442\u044C\u0441\u044F \u043D\u0430 \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E\u043C \u0442\u0430\u0440\u0438\u0444\u0435.")
              ]),
              createVNode(TariffsSlider)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(AppSection, { class: "tariffs-equal section_without-background" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u0440\u0430\u0432\u043D\u0435\u043D\u0438\u0435 \u0442\u0430\u0440\u0438\u0444\u043E\u0432 `);
                } else {
                  return [
                    createTextVNode(" \u0421\u0440\u0430\u0432\u043D\u0435\u043D\u0438\u0435 \u0442\u0430\u0440\u0438\u0444\u043E\u0432 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppTable, {
              class: "tariffs-equal__table section_without-background",
              isTrash: false,
              actionType: "view",
              slug: "equal",
              isPermanentEdit: false,
              table: unref(table),
              activeCategory: null,
              categories: [],
              isCanSort: false,
              pageTableOnly: false,
              isHaveCategories: false,
              categoryType: "default"
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u0421\u0440\u0430\u0432\u043D\u0435\u043D\u0438\u0435 \u0442\u0430\u0440\u0438\u0444\u043E\u0432 ")
                ]),
                _: 1
              }),
              createVNode(AppTable, {
                class: "tariffs-equal__table section_without-background",
                isTrash: false,
                actionType: "view",
                slug: "equal",
                isPermanentEdit: false,
                table: unref(table),
                activeCategory: null,
                categories: [],
                isCanSort: false,
                pageTableOnly: false,
                isHaveCategories: false,
                categoryType: "default"
              }, null, 8, ["table"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(CommonSocial, null, null, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Tariffs/Tariffs.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const TemplateTariffs = _sfc_main$1;
const _sfc_main = {
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0422\u0430\u0440\u0438\u0444\u044B \u043D\u0430 \u0443\u0441\u043B\u0443\u0433\u0438 \u043E\u0442\u0441\u043B\u0435\u0436\u0438\u0432\u0430\u043D\u0438\u044F \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0438 \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u044F \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u043E\u043C | Compas.pro",
      meta: [
        {
          name: "description",
          content: "\u0421\u0440\u0430\u0432\u043D\u0438\u0442\u0435 \u0442\u0430\u0440\u0438\u0444\u044B \u043D\u0430 \u0443\u0441\u043B\u0443\u0433\u0438 \u043E\u0442\u0441\u043B\u0435\u0436\u0438\u0432\u0430\u043D\u0438\u044F \u0448\u0442\u0440\u0430\u0444\u043E\u0432, \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u044F \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u043E\u043C \u0438 \u0441\u043E\u0442\u0440\u0443\u0434\u043D\u0438\u043A\u0430\u043C\u0438. \u0423\u0437\u043D\u0430\u0439\u0442\u0435, \u043A\u0430\u043A\u043E\u0439 \u043F\u043B\u0430\u043D \u043F\u043E\u0434\u0445\u043E\u0434\u0438\u0442 \u0438\u043C\u0435\u043D\u043D\u043E \u0432\u0430\u043C \u2014 \u043E\u0442 \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E\u0433\u043E \u0434\u043E \u043F\u0440\u043E\u0444\u0435\u0441\u0441\u0438\u043E\u043D\u0430\u043B\u044C\u043D\u043E\u0433\u043E \u0442\u0430\u0440\u0438\u0444\u0430 \u0441 \u043C\u0438\u043D\u0438\u043C\u0430\u043B\u044C\u043D\u043E\u0439 \u043A\u043E\u043C\u0438\u0441\u0441\u0438\u0435\u0439 \u0437\u0430 \u043E\u043F\u043B\u0430\u0442\u0443 \u0448\u0442\u0440\u0430\u0444\u043E\u0432."
        }
      ],
      link: [{
        rel: "canonical",
        href: "https://compas.pro/tariffs"
      }]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(TemplateTariffs, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/tariffs/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-143e0a9f.mjs.map
