import { m as IconPasswordEye, e as __nuxt_component_0 } from './server.mjs';
import { useSSRContext, toRefs, ref, mergeProps, unref, withCtx, createTextVNode } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr, ssrInterpolate, ssrRenderComponent } from 'vue/server-renderer';

const _sfc_main = {
  __name: "QuestionFull",
  __ssrInlineRender: true,
  props: {
    title: { type: String },
    answer: { type: String },
    image: { type: String },
    id: { type: String },
    date: { type: String },
    views: { type: Number },
    isShowMore: { type: Boolean, default: false },
    readingTime: { type: Number }
  },
  setup(__props) {
    const props = __props;
    const { image, answer, title, isShowMore, date, id, views, readingTime } = toRefs(props);
    ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "question" }, _attrs))}>`);
      if (unref(image)) {
        _push(`<img${ssrRenderAttr("src", unref(image))} alt="image" class="question__image">`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="question__body"><h1 class="question__title">${ssrInterpolate(unref(title))}</h1><p class="question__text"></p>`);
      if (unref(isShowMore)) {
        _push(ssrRenderComponent(_component_NuxtLink, {
          href: `/questions/${unref(id)}`,
          class: "question__more"
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`\u041F\u043E\u0434\u0440\u043E\u0431\u043D\u0435\u0435`);
            } else {
              return [
                createTextVNode("\u041F\u043E\u0434\u0440\u043E\u0431\u043D\u0435\u0435")
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="question__info"><p class="question__views views">`);
      _push(ssrRenderComponent(IconPasswordEye, { class: "header__views-eye" }, null, _parent));
      _push(`<span>${ssrInterpolate(unref(views) ? unref(views) : 0)}</span></p><div class="header__duration duration"> \u0427\u0438\u0442\u0430\u0442\u044C \u0432\u043E\u043F\u0440\u043E\u0441-\u043E\u0442\u0432\u0435\u0442: <span class="duration_black">${ssrInterpolate(unref(readingTime))} \u043C\u0438\u043D</span></div></div></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/QuestionFull/QuestionFull.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const Question = _sfc_main;

export { Question as Q };
//# sourceMappingURL=QuestionFull-b10f870c.mjs.map
