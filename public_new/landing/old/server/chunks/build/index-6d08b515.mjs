import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { a as useHead, x as AppH1, A as AppH2 } from './server.mjs';
import { ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderAttr } from 'vue/server-renderer';
import { useSSRContext, unref, withCtx, createTextVNode, createVNode, openBlock, createBlock, Fragment, renderList, toDisplayString } from 'vue';
import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import { A as AppSection } from './AppSection-1ea634ac.mjs';
import { C as CommonSocial } from './Social-983a064f.mjs';
import { C as CompositeBlock } from './CompositeBlock-c42445ea.mjs';
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

const _imports_0 = "" + publicAssetsURL("icons/document.svg");
const requisitesPdf = "" + publicAssetsURL("pages/contacts/requisites.pdf");
const _sfc_main$1 = {
  __name: "Contacts",
  __ssrInlineRender: true,
  setup(__props) {
    let contacts = [
      // {
      // 	title: "Телефон",
      // 	text: "+7 495 118-00-12",
      // 	link: "tel:74951184422",
      // },
      {
        title: "Email",
        text: "info@compas.pro",
        link: "mailto:info@compas.pro"
      },
      {
        title: "Email \u0431\u0443\u0445\u0433\u0430\u043B\u0442\u0435\u0440\u0438\u0438",
        text: "bux@compas.pro",
        link: "mailto:bux@compas.pro"
      }
    ];
    let requisites = [
      {
        title: "\u041D\u0430\u0438\u043C\u0435\u043D\u043E\u0432\u0430\u043D\u0438\u0435",
        text: '\u041E\u041E\u041E \u041A\u041E\u041C\u041F\u0410\u0421 \u0414\u0410\u0419\u041D\u0410\u041C\u0418\u041A\u0421"'
      },
      {
        title: "\u041E\u0413\u0420\u041D",
        text: "1237700477365"
      },
      {
        title: "\u0411\u0418\u041A",
        text: "044525593"
      },
      {
        title: "\u0420\u0430\u0441\u0447\u0435\u0442\u043D\u044B\u0439 \u0441\u0447\u0435\u0442",
        text: "40702810502370019813"
      },
      {
        title: "\u0418\u041D\u041D",
        text: "9723204274"
      },
      {
        title: "\u0411\u0410\u041D\u041A",
        text: '\u0410\u041E "\u0410\u041B\u042C\u0424\u0410-\u0411\u0410\u041D\u041A"'
      },
      {
        title: "\u042E\u0440\u0438\u0434\u0438\u0447\u0435\u0441\u043A\u0438\u0439 \u0430\u0434\u0440\u0435\u0441",
        text: "\u0433 \u041C\u043E\u0441\u043A\u0432\u0430, \u0443\u043B \u0427\u0430\u0433\u0438\u043D\u0441\u043A\u0430\u044F, \u0434 4 \u0441\u0442\u0440 13, \u043A\u0432./\u043E\u0444. \u041F\u041E\u041C\u0415\u0429. 2/4"
      },
      {
        title: "\u041A\u041F\u041F",
        text: "1237700477365"
      },
      {
        title: "\u041A/\u0421",
        text: "30101810200000000593"
      }
    ];
    let breadcrumbs = [
      {
        title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0430",
        link: "/"
      },
      {
        title: "\u041A\u043E\u043D\u0442\u0430\u043A\u0442\u044B",
        link: `/contacts`
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      _push(ssrRenderComponent(AppH1, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u041A\u043E\u043D\u0442\u0430\u043A\u0442\u044B `);
          } else {
            return [
              createTextVNode(" \u041A\u043E\u043D\u0442\u0430\u043A\u0442\u044B ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(CompositeBlock, { class: "contacts__map contacts-map" }, {
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="contacts-map__list"${_scopeId}><!--[-->`);
            ssrRenderList(unref(contacts), (contact) => {
              _push2(`<div class="contacts-map__item"${_scopeId}><div class="contacts-map__title"${_scopeId}>${ssrInterpolate(contact.title)}:</div><a${ssrRenderAttr("href", contact.link)} class="contacts-map__desc"${_scopeId}>${ssrInterpolate(contact.text)}</a></div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "contacts-map__list" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(contacts), (contact) => {
                  return openBlock(), createBlock("div", { class: "contacts-map__item" }, [
                    createVNode("div", { class: "contacts-map__title" }, toDisplayString(contact.title) + ":", 1),
                    createVNode("a", {
                      href: contact.link,
                      class: "contacts-map__desc"
                    }, toDisplayString(contact.text), 9, ["href"])
                  ]);
                }), 256))
              ])
            ];
          }
        }),
        image: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<iframe src="https://yandex.ru/map-widget/v1/?um=constructor%3A63f826b3597df087fbd0145e275bd766f86465f342822801739c0dd6d19d7bbe&amp;source=constructor" width="500" height="400" frameborder="0"${_scopeId}></iframe>`);
          } else {
            return [
              createVNode("iframe", {
                src: "https://yandex.ru/map-widget/v1/?um=constructor%3A63f826b3597df087fbd0145e275bd766f86465f342822801739c0dd6d19d7bbe&source=constructor",
                width: "500",
                height: "400",
                frameborder: "0"
              })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(AppSection, { class: "section_without-background contacts__requisites contacts-requisites" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0420\u0435\u043A\u0432\u0438\u0437\u0438\u0442\u044B Compas Pro (\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E) <a${ssrRenderAttr("href", unref(requisitesPdf))} download target="_blank" class="contacts-requisites__link"${_scopeId2}><figure class="ibg contacts-requisites__document"${_scopeId2}><img${ssrRenderAttr("src", _imports_0)} alt="\u0421\u043A\u0430\u0447\u0430\u0442\u044C \u0440\u0435\u043A\u0432\u0438\u0437\u0438\u0442\u044B"${_scopeId2}><figcaption class="contacts-requisites__document-text"${_scopeId2}>\u0421\u043A\u0430\u0447\u0430\u0442\u044C</figcaption></figure></a>`);
                } else {
                  return [
                    createTextVNode(" \u0420\u0435\u043A\u0432\u0438\u0437\u0438\u0442\u044B Compas Pro (\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E) "),
                    createVNode("a", {
                      href: unref(requisitesPdf),
                      download: "",
                      target: "_blank",
                      class: "contacts-requisites__link"
                    }, [
                      createVNode("figure", { class: "ibg contacts-requisites__document" }, [
                        createVNode("img", {
                          src: _imports_0,
                          alt: "\u0421\u043A\u0430\u0447\u0430\u0442\u044C \u0440\u0435\u043A\u0432\u0438\u0437\u0438\u0442\u044B"
                        }),
                        createVNode("figcaption", { class: "contacts-requisites__document-text" }, "\u0421\u043A\u0430\u0447\u0430\u0442\u044C")
                      ])
                    ], 8, ["href"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<ul class="contacts-requisites__list"${_scopeId}><!--[-->`);
            ssrRenderList(unref(requisites), (requisite) => {
              _push2(`<li class="contacts-requisites__item"${_scopeId}><div class="contacts-requisites__title"${_scopeId}>${ssrInterpolate(requisite.title)}</div><div class="contacts-requisites__text"${_scopeId}>${ssrInterpolate(requisite.text)}</div></li>`);
            });
            _push2(`<!--]--></ul>`);
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u0420\u0435\u043A\u0432\u0438\u0437\u0438\u0442\u044B Compas Pro (\u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E) "),
                  createVNode("a", {
                    href: unref(requisitesPdf),
                    download: "",
                    target: "_blank",
                    class: "contacts-requisites__link"
                  }, [
                    createVNode("figure", { class: "ibg contacts-requisites__document" }, [
                      createVNode("img", {
                        src: _imports_0,
                        alt: "\u0421\u043A\u0430\u0447\u0430\u0442\u044C \u0440\u0435\u043A\u0432\u0438\u0437\u0438\u0442\u044B"
                      }),
                      createVNode("figcaption", { class: "contacts-requisites__document-text" }, "\u0421\u043A\u0430\u0447\u0430\u0442\u044C")
                    ])
                  ], 8, ["href"])
                ]),
                _: 1
              }),
              createVNode("ul", { class: "contacts-requisites__list" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(requisites), (requisite) => {
                  return openBlock(), createBlock("li", { class: "contacts-requisites__item" }, [
                    createVNode("div", { class: "contacts-requisites__title" }, toDisplayString(requisite.title), 1),
                    createVNode("div", { class: "contacts-requisites__text" }, toDisplayString(requisite.text), 1)
                  ]);
                }), 256))
              ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Contacts/Contacts.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const TemplateContacts = _sfc_main$1;
const _sfc_main = {
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u041A\u043E\u043D\u0442\u0430\u043A\u0442\u044B \u2014 \u0421\u0432\u044F\u0436\u0438\u0442\u0435\u0441\u044C \u0441 \u043D\u0430\u043C\u0438 \u0434\u043B\u044F \u043F\u043E\u043B\u0443\u0447\u0435\u043D\u0438\u044F \u043F\u043E\u0434\u0434\u0435\u0440\u0436\u043A\u0438 \u0438 \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u0438 | Compas.pro",
      meta: [
        {
          name: "description",
          content: "\u041D\u0443\u0436\u043D\u0430 \u043F\u043E\u043C\u043E\u0449\u044C \u0438\u043B\u0438 \u0434\u043E\u043F\u043E\u043B\u043D\u0438\u0442\u0435\u043B\u044C\u043D\u0430\u044F \u0438\u043D\u0444\u043E\u0440\u043C\u0430\u0446\u0438\u044F? \u0421\u0432\u044F\u0436\u0438\u0442\u0435\u0441\u044C \u0441 \u043D\u0430\u043C\u0438 \u0447\u0435\u0440\u0435\u0437 \u0443\u0434\u043E\u0431\u043D\u044B\u0435 \u043A\u0430\u043D\u0430\u043B\u044B \u2014 \u0442\u0435\u043B\u0435\u0444\u043E\u043D, email \u0438\u043B\u0438 \u0444\u043E\u0440\u043C\u0443 \u043E\u0431\u0440\u0430\u0442\u043D\u043E\u0439 \u0441\u0432\u044F\u0437\u0438. \u041C\u044B \u0432\u0441\u0435\u0433\u0434\u0430 \u0440\u0430\u0434\u044B \u043F\u043E\u043C\u043E\u0447\u044C \u0432\u0430\u043C \u0441 \u043B\u044E\u0431\u044B\u043C\u0438 \u0432\u043E\u043F\u0440\u043E\u0441\u0430\u043C\u0438, \u0441\u0432\u044F\u0437\u0430\u043D\u043D\u044B\u043C\u0438 \u0441 \u043D\u0430\u0448\u0438\u043C\u0438 \u0443\u0441\u043B\u0443\u0433\u0430\u043C\u0438 \u0438 \u043F\u0440\u043E\u0434\u0443\u043A\u0442\u0430\u043C\u0438."
        }
      ],
      link: [
        {
          rel: "canonical",
          href: `https://compas.pro/contacts`
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(TemplateContacts, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/contacts/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-6d08b515.mjs.map
