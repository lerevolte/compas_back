import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { useSSRContext, mergeProps, withCtx, createTextVNode, unref, createVNode, openBlock, createBlock, Fragment, renderList } from 'vue';
import { ssrRenderComponent, ssrRenderList, ssrRenderAttr } from 'vue/server-renderer';
import { A as AppH2 } from './server.mjs';
import { A as AppSection } from './AppSection-1ea634ac.mjs';

const telegram = "" + publicAssetsURL("social/telegram.svg");
const vk = "" + publicAssetsURL("social/vk.svg");
const youtube = "" + publicAssetsURL("social/youtube.svg");
const _sfc_main = {
  __name: "Social",
  __ssrInlineRender: true,
  setup(__props) {
    let icons = [
      {
        name: "\u0422\u0435\u043B\u0435\u0433\u0440\u0430\u043C",
        link: "https://t.me/compas_pro",
        icon: telegram
      },
      {
        name: "\u0412\u041A\u043E\u043D\u0442\u0430\u043A\u0442\u0435",
        link: "https://vk.com/cmps_pr",
        icon: vk
      },
      {
        name: "YouTube",
        link: "https://www.youtube.com/@cmps-pro",
        icon: youtube
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "social section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041F\u0440\u0438\u0441\u043E\u0435\u0434\u0438\u043D\u044F\u0439\u0442\u0435\u0441\u044C \u043A Compas Pro \u0432 \u0441\u043E\u0446\u0441\u0435\u0442\u044F\u0445 `);
                } else {
                  return [
                    createTextVNode(" \u041F\u0440\u0438\u0441\u043E\u0435\u0434\u0438\u043D\u044F\u0439\u0442\u0435\u0441\u044C \u043A Compas Pro \u0432 \u0441\u043E\u0446\u0441\u0435\u0442\u044F\u0445 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="social__description"${_scopeId}>\u041F\u0443\u0431\u043B\u0438\u043A\u0443\u0435\u043C \u043E\u0431\u0443\u0447\u0430\u044E\u0449\u0438\u0435 \u043C\u0430\u0442\u0435\u0440\u0438\u0430\u043B\u044B \u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E, \u0430\u043D\u043E\u043D\u0441\u044B \u043C\u0435\u0440\u043E\u043F\u0440\u0438\u044F\u0442\u0438\u0439, \u0440\u0430\u0441\u0441\u043A\u0430\u0437\u044B\u0432\u0430\u0435\u043C \u043E\u0431 \u043E\u0431\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F\u0445 \u043F\u043B\u0430\u0442\u0444\u043E\u0440\u043C\u044B Compas</div><div class="social__icons"${_scopeId}><!--[-->`);
            ssrRenderList(unref(icons), (icon) => {
              _push2(`<a${ssrRenderAttr("href", icon.link)} target="_blank" class="social__icon-link"${_scopeId}><figure class="ibg social__icon"${_scopeId}><img${ssrRenderAttr("src", icon.icon)}${ssrRenderAttr("alt", icon.name)}${_scopeId}></figure></a>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u041F\u0440\u0438\u0441\u043E\u0435\u0434\u0438\u043D\u044F\u0439\u0442\u0435\u0441\u044C \u043A Compas Pro \u0432 \u0441\u043E\u0446\u0441\u0435\u0442\u044F\u0445 ")
                ]),
                _: 1
              }),
              createVNode("div", { class: "social__description" }, "\u041F\u0443\u0431\u043B\u0438\u043A\u0443\u0435\u043C \u043E\u0431\u0443\u0447\u0430\u044E\u0449\u0438\u0435 \u043C\u0430\u0442\u0435\u0440\u0438\u0430\u043B\u044B \u041A\u043E\u043C\u043F\u0430\u0441 \u041F\u0440\u043E, \u0430\u043D\u043E\u043D\u0441\u044B \u043C\u0435\u0440\u043E\u043F\u0440\u0438\u044F\u0442\u0438\u0439, \u0440\u0430\u0441\u0441\u043A\u0430\u0437\u044B\u0432\u0430\u0435\u043C \u043E\u0431 \u043E\u0431\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u044F\u0445 \u043F\u043B\u0430\u0442\u0444\u043E\u0440\u043C\u044B Compas"),
              createVNode("div", { class: "social__icons" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(icons), (icon) => {
                  return openBlock(), createBlock("a", {
                    href: icon.link,
                    target: "_blank",
                    class: "social__icon-link"
                  }, [
                    createVNode("figure", { class: "ibg social__icon" }, [
                      createVNode("img", {
                        src: icon.icon,
                        alt: icon.name
                      }, null, 8, ["src", "alt"])
                    ])
                  ], 8, ["href"]);
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
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/Social/Social.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CommonSocial = _sfc_main;

export { CommonSocial as C };
//# sourceMappingURL=Social-983a064f.mjs.map
