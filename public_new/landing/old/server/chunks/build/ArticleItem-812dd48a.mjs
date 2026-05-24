import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { _ as _export_sfc, m as IconPasswordEye, e as __nuxt_component_0 } from './server.mjs';
import { u as useDayjs } from './dayjs-ce9ed7b6.mjs';
import { toRefs, mergeProps, unref, withCtx, createVNode, toDisplayString, useSSRContext } from 'vue';
import { ssrRenderComponent, ssrRenderAttr, ssrInterpolate } from 'vue/server-renderer';

const defaultImgarticle = "" + publicAssetsURL("articles/article.png");
const _sfc_main = {
  __name: "ArticleItem",
  __ssrInlineRender: true,
  props: {
    title: {
      type: String,
      required: true
    },
    date: {
      type: String,
      required: true
    },
    image: {
      type: String
    },
    views: {
      type: Number,
      default: 0
    },
    id: {
      type: String,
      default: ""
    }
  },
  setup(__props) {
    const dayjs = useDayjs();
    const props = __props;
    const { title, views, image, date, id } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(ssrRenderComponent(_component_NuxtLink, mergeProps({
        class: "article__item",
        to: `/articles/${unref(id)}`
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="article__top" data-v-dab46759${_scopeId}><img class="article__icon"${ssrRenderAttr("src", unref(image) ? unref(image) : unref(defaultImgarticle))}${ssrRenderAttr("alt", unref(title))} data-v-dab46759${_scopeId}></div><div class="article__body" data-v-dab46759${_scopeId}><div class="article__subtitle" data-v-dab46759${_scopeId}>${ssrInterpolate(unref(title))}</div><div class="article-slder__views" data-v-dab46759${_scopeId}>`);
            _push2(ssrRenderComponent(IconPasswordEye, null, null, _parent2, _scopeId));
            _push2(`<span data-v-dab46759${_scopeId}>${ssrInterpolate(unref(views))}</span></div><div class="article-slder__date" data-v-dab46759${_scopeId}>${ssrInterpolate(unref(dayjs)(unref(date)).locale("ru").format("D MMMM YYYY"))}</div></div>`);
          } else {
            return [
              createVNode("div", { class: "article__top" }, [
                createVNode("img", {
                  class: "article__icon",
                  src: unref(image) ? unref(image) : unref(defaultImgarticle),
                  alt: unref(title)
                }, null, 8, ["src", "alt"])
              ]),
              createVNode("div", { class: "article__body" }, [
                createVNode("div", { class: "article__subtitle" }, toDisplayString(unref(title)), 1),
                createVNode("div", { class: "article-slder__views" }, [
                  createVNode(IconPasswordEye),
                  createVNode("span", null, toDisplayString(unref(views)), 1)
                ]),
                createVNode("div", { class: "article-slder__date" }, toDisplayString(unref(dayjs)(unref(date)).locale("ru").format("D MMMM YYYY")), 1)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/ArticleItem/ArticleItem.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const ArticleItem = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-dab46759"]]);

export { ArticleItem as A };
//# sourceMappingURL=ArticleItem-812dd48a.mjs.map
