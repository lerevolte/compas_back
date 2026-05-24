import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { useSSRContext, defineComponent, toRefs, mergeProps, unref, withCtx, createTextVNode, toDisplayString, createVNode, openBlock, createBlock, Fragment, renderList } from 'vue';
import { ssrRenderAttrs, ssrRenderStyle, ssrRenderComponent, ssrInterpolate, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
import { A as AppH2, k as AppButton, n as navigateTo } from './server.mjs';
import { _ as _imports_0$2 } from './program-f89190ac.mjs';
import { A as AppSection } from './AppSection-1ea634ac.mjs';

const _imports_0$1 = "" + publicAssetsURL("icons/checkmark.svg");
const _sfc_main$3 = {
  __name: "CheckMark",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "ibg" }, _attrs))}><img${ssrRenderAttr("src", _imports_0$1)} alt=""></figure>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/CheckMark/CheckMark.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const AppCheckMark = _sfc_main$3;
const gibdd = "" + publicAssetsURL("main/base/gibdd.svg");
const parking = "" + publicAssetsURL("main/base/moscow-parking.svg");
const mugand = "" + publicAssetsURL("main/base/mugand.svg");
const madi = "" + publicAssetsURL("main/base/madi.svg");
const fspp = "" + publicAssetsURL("main/base/fspp.svg");
const infinity = "" + publicAssetsURL("main/pluses/infinity.svg");
const mileage = "" + publicAssetsURL("main/pluses/reducing-mileage.svg");
const protection = "" + publicAssetsURL("main/pluses/protection.svg");
const notification = "" + publicAssetsURL("main/pluses/notification.svg");
const receipt = "" + publicAssetsURL("main/pluses/receipt.svg");
const reduction = "" + publicAssetsURL("main/pluses/time-reduction.svg");
const _imports_0 = "" + publicAssetsURL("icons/youtube_white.svg");
const _sfc_main$2 = {
  __name: "YoutubeWhite",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "ibg" }, _attrs))}><img${ssrRenderAttr("src", _imports_0)} alt=""></figure>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/YoutubeWhite/YoutubeWhite.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const YoutubeWhite = _sfc_main$2;
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "Advantage",
  __ssrInlineRender: true,
  props: {
    background: {
      type: String,
      default: ""
    },
    text: {
      type: String,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const { background, text } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "programm-full__advantage" }, _attrs))}><div class="programm-full__checkmark" style="${ssrRenderStyle(`background-color:${unref(background) ? unref(background) : "#0584fe"}`)}">`);
      _push(ssrRenderComponent(AppCheckMark, { class: "programm-full__checkmark-image" }, null, _parent));
      _push(`</div><div class="programm-full__advantage-text">${ssrInterpolate(unref(text))}</div></div>`);
    };
  }
});
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/ProgrammBig/components/Advantage.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const advantages = [
  {
    text: "\u0414\u0430\u0442\u0430 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F"
  },
  {
    text: "\u0418\u043D\u0438\u0446\u0438\u0430\u0442\u043E\u0440 (\u043A\u0430\u043A\u043E\u0439 \u043E\u0440\u0433\u0430\u043D \u0432\u044B\u043F\u0438\u0441\u0430\u043B \u0448\u0442\u0440\u0430\u0444)"
  },
  {
    text: "\u0423\u0418\u041D (\u043D\u043E\u043C\u0435\u0440 \u043F\u043E\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F)"
  },
  {
    text: "\u041E\u043F\u0438\u0441\u0430\u043D\u0438\u0435 \u043F\u0440\u0430\u0432\u043E\u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F"
  },
  {
    text: "\u0421\u0442\u0430\u0442\u044C\u044F \u043F\u0440\u0430\u0432\u043E\u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F"
  },
  {
    text: "\u0421\u0443\u043C\u043C\u0430 \u0448\u0442\u0440\u0430\u0444\u0430"
  },
  {
    text: "\u0424\u043E\u0442\u043E \u043D\u0430\u0440\u0443\u0448\u0435\u043D\u0438\u044F (\u0435\u0441\u043B\u0438 \u0437\u0430\u0444\u0438\u043A\u0441\u0438\u0440\u043E\u0432\u0430\u043D\u043E \u043A\u0430\u043C\u0435\u0440\u043E\u0439)"
  },
  {
    text: "\u0421\u0442\u0430\u0442\u0443\u0441 \u2013 \u043E\u043F\u043B\u0430\u0447\u0435\u043D \u0438\u043B\u0438 \u043D\u0435\u0442"
  },
  {
    text: "\u041C\u043E\u043C\u0435\u043D\u0442\u0430\u043B\u044C\u043D\u0430\u044F \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u0430",
    background: "#089a1e"
  }
];
const _sfc_main = {
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
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "section_without-background section_gray programm-full" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="programm-full__top"${_scopeId}>`);
            _push2(ssrRenderComponent(AppH2, { class: "programm-full__title" }, {
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
            _push2(`<p class="programm-full__desc"${_scopeId}>${ssrInterpolate(props.desc)}</p></div><div class="programm-full__left"${_scopeId}><div class="programm-full__advantages"${_scopeId}><!--[-->`);
            ssrRenderList(unref(advantages), ({ text, background }) => {
              _push2(ssrRenderComponent(_sfc_main$1, {
                text,
                background
              }, null, _parent2, _scopeId));
            });
            _push2(`<!--]--></div><div class="programm-full__link"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              onClick: ($event) => ("navigateTo" in _ctx ? _ctx.navigateTo : unref(navigateTo))("/auth/registration"),
              class: "button_blue"
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E `);
                } else {
                  return [
                    createTextVNode(" \u041F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div></div><img class="programm-full__image"${ssrRenderAttr("src", _imports_0$2)} alt=""${_scopeId}>`);
          } else {
            return [
              createVNode("div", { class: "programm-full__top" }, [
                createVNode(AppH2, { class: "programm-full__title" }, {
                  default: withCtx(() => [
                    createTextVNode(toDisplayString(props.title), 1)
                  ]),
                  _: 1
                }),
                createVNode("p", { class: "programm-full__desc" }, toDisplayString(props.desc), 1)
              ]),
              createVNode("div", { class: "programm-full__left" }, [
                createVNode("div", { class: "programm-full__advantages" }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(unref(advantages), ({ text, background }) => {
                    return openBlock(), createBlock(_sfc_main$1, {
                      text,
                      background
                    }, null, 8, ["text", "background"]);
                  }), 256))
                ]),
                createVNode("div", { class: "programm-full__link" }, [
                  createVNode(AppButton, {
                    onClick: ($event) => ("navigateTo" in _ctx ? _ctx.navigateTo : unref(navigateTo))("/auth/registration"),
                    class: "button_blue"
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" \u041F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u043E ")
                    ]),
                    _: 1
                  }, 8, ["onClick"])
                ])
              ]),
              createVNode("img", {
                class: "programm-full__image",
                src: _imports_0$2,
                alt: ""
              })
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/ProgrammBig/Programm.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CommonProgramm = _sfc_main;

export { CommonProgramm as C, YoutubeWhite as Y, mugand as a, mileage as b, protection as c, reduction as d, fspp as f, gibdd as g, infinity as i, madi as m, notification as n, parking as p, receipt as r };
//# sourceMappingURL=Programm-1c3794ff.mjs.map
