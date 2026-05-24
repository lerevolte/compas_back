import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { useSSRContext, mergeProps, withCtx, createTextVNode, createVNode, withAsyncContext, ref, unref, toDisplayString, openBlock, createBlock, Fragment, renderList, createCommentVNode } from 'vue';
import { ssrRenderComponent, ssrRenderAttrs, ssrRenderList, ssrInterpolate } from 'vue/server-renderer';
import { A as AppSection } from './AppSection-1ea634ac.mjs';
import { A as AppH2, s as storeToRefs, u as useRoute } from './server.mjs';
import { SwiperSlide } from 'swiper/vue';
import { A as AppSlider } from './Slider-a943f5b9.mjs';
import { A as ArticleItem } from './ArticleItem-812dd48a.mjs';
import { u as useArticlesStore } from './index-e6d877f1.mjs';

const _sfc_main$1 = {
  __name: "ArticlesSlider",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    const articlesStore = useArticlesStore();
    const { categories, currentTitle, articlesList } = storeToRefs(articlesStore);
    const route = useRoute();
    articlesList.value.length == 0 ? ([__temp, __restore] = withAsyncContext(() => articlesStore.loadArticles()), __temp = await __temp, __restore(), __temp) : 0;
    const articles = ref(articlesList.value.filter((i) => i.slug != route.params.id));
    let countSlides = ref(3);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "articles-slider" }, _attrs))}>`);
      _push(ssrRenderComponent(AppSlider, {
        class: unref(countSlides) == 1 ? "swiper-slider_only" : "",
        options: { VisibleSlides: unref(countSlides), spaceBetween: 20, pagination: { clickable: true, dynamicBullets: true } }
      }, {
        slide: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<!--[-->`);
            ssrRenderList(unref(articles), ({ created_at, preview_picture, name, slug, views }) => {
              _push2(`<!--[-->`);
              if (name) {
                _push2(ssrRenderComponent(unref(SwiperSlide), { "virtual-index": _ctx.id }, {
                  default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                    var _a, _b;
                    if (_push3) {
                      _push3(`${ssrInterpolate(_ctx.id)} `);
                      _push3(ssrRenderComponent(ArticleItem, {
                        id: slug.value ? slug.value : slug,
                        image: (_a = preview_picture == null ? void 0 : preview_picture[0]) == null ? void 0 : _a.file,
                        title: name,
                        views,
                        date: created_at
                      }, null, _parent3, _scopeId2));
                    } else {
                      return [
                        createTextVNode(toDisplayString(_ctx.id) + " ", 1),
                        createVNode(ArticleItem, {
                          id: slug.value ? slug.value : slug,
                          image: (_b = preview_picture == null ? void 0 : preview_picture[0]) == null ? void 0 : _b.file,
                          title: name,
                          views,
                          date: created_at
                        }, null, 8, ["id", "image", "title", "views", "date"])
                      ];
                    }
                  }),
                  _: 2
                }, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(`<!--]-->`);
            });
            _push2(`<!--]-->`);
          } else {
            return [
              (openBlock(true), createBlock(Fragment, null, renderList(unref(articles), ({ created_at, preview_picture, name, slug, views }) => {
                return openBlock(), createBlock(Fragment, { key: _ctx.id }, [
                  name ? (openBlock(), createBlock(unref(SwiperSlide), {
                    key: 0,
                    "virtual-index": _ctx.id
                  }, {
                    default: withCtx(() => {
                      var _a;
                      return [
                        createTextVNode(toDisplayString(_ctx.id) + " ", 1),
                        createVNode(ArticleItem, {
                          id: slug.value ? slug.value : slug,
                          image: (_a = preview_picture == null ? void 0 : preview_picture[0]) == null ? void 0 : _a.file,
                          title: name,
                          views,
                          date: created_at
                        }, null, 8, ["id", "image", "title", "views", "date"])
                      ];
                    }),
                    _: 2
                  }, 1032, ["virtual-index"])) : createCommentVNode("", true)
                ], 64);
              }), 128))
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/ArticlesSlider/ArticlesSlider.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const ArticlesSlider = _sfc_main$1;
const _sfc_main = {
  __name: "ArticlesBlock",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "section_without-background articles-block" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u0442\u0430\u0442\u044C\u0438 \u043A\u043E\u0442\u043E\u0440\u044B\u0435 \u0432\u0430\u0441 \u0437\u0430\u0438\u0442\u043D\u0435\u0440\u0435\u0441\u0443\u044E\u0442`);
                } else {
                  return [
                    createTextVNode(" \u0421\u0442\u0430\u0442\u044C\u0438 \u043A\u043E\u0442\u043E\u0440\u044B\u0435 \u0432\u0430\u0441 \u0437\u0430\u0438\u0442\u043D\u0435\u0440\u0435\u0441\u0443\u044E\u0442")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(ArticlesSlider, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u0421\u0442\u0430\u0442\u044C\u0438 \u043A\u043E\u0442\u043E\u0440\u044B\u0435 \u0432\u0430\u0441 \u0437\u0430\u0438\u0442\u043D\u0435\u0440\u0435\u0441\u0443\u044E\u0442")
                ]),
                _: 1
              }),
              createVNode(ArticlesSlider)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/ArticlesBlock/ArticlesBlock.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const MainArticles = _sfc_main;
const vuImage = "" + publicAssetsURL("main/fines/preview-vu.png");
const stsImage = "" + publicAssetsURL("main/fines/preview-sts.png");
const gosImage = "" + publicAssetsURL("main/fines/preview-gos.png");
const postanovlenieImage = "" + publicAssetsURL("main/fines/preview-postanovlenie.png");
const innImage = "" + publicAssetsURL("main/fines/preview-inn.png");

export { MainArticles as M, gosImage as g, innImage as i, postanovlenieImage as p, stsImage as s, vuImage as v };
//# sourceMappingURL=preview-inn-d36097f7.mjs.map
