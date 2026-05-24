import { useSSRContext, mergeProps, withCtx, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, createCommentVNode, withAsyncContext, ref, unref, Fragment, renderList } from 'vue';
import { ssrRenderComponent, ssrInterpolate, ssrRenderAttrs, ssrRenderList } from 'vue/server-renderer';
import { A as AppSection } from './AppSection-1ea634ac.mjs';
import { A as AppH2, u as useRoute, s as storeToRefs } from './server.mjs';
import { SwiperSlide } from 'swiper/vue';
import { A as AppSlider } from './Slider-a943f5b9.mjs';
import { u as useQuestionsStore, Q as QuestionItem } from './index-c8ee539a.mjs';

const _sfc_main$2 = {
  __name: "QuestionsSlider",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    useRoute();
    const questionsStore = useQuestionsStore();
    const { categories, questionsList, questionDetail } = storeToRefs(questionsStore);
    !questionsList.value.length ? ([__temp, __restore] = withAsyncContext(() => questionsStore.loadQuestions()), __temp = await __temp, __restore(), __temp) : 0;
    console.log(questionsList.value);
    let countSlides = ref(3);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "questions-slider" }, _attrs))}>`);
      if (unref(questionsList)) {
        _push(ssrRenderComponent(AppSlider, {
          class: unref(countSlides) == 1 ? "swiper-slider_only" : "",
          options: { VisibleSlides: unref(countSlides), centeredSlidesBounds: true, spaceBetween: 20, pagination: { clickable: true, dynamicBullets: true } }
        }, {
          slide: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<!--[-->`);
              ssrRenderList(unref(questionsList), ({ created_at, slug, detail_picture, detail_text, name, views }) => {
                _push2(`<!--[-->`);
                if (detail_text) {
                  _push2(ssrRenderComponent(unref(SwiperSlide), {
                    key: _ctx.id,
                    "virtual-index": _ctx.id
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      var _a, _b, _c, _d;
                      if (_push3) {
                        _push3(ssrRenderComponent(QuestionItem, {
                          image: (_a = detail_picture == null ? void 0 : detail_picture[0]) == null ? void 0 : _a.file,
                          title: name,
                          answer: (_b = detail_text == null ? void 0 : detail_text[0]) == null ? void 0 : _b.body,
                          views,
                          date: created_at,
                          id: slug.value
                        }, null, _parent3, _scopeId2));
                      } else {
                        return [
                          createVNode(QuestionItem, {
                            image: (_c = detail_picture == null ? void 0 : detail_picture[0]) == null ? void 0 : _c.file,
                            title: name,
                            answer: (_d = detail_text == null ? void 0 : detail_text[0]) == null ? void 0 : _d.body,
                            views,
                            date: created_at,
                            id: slug.value
                          }, null, 8, ["image", "title", "answer", "views", "date", "id"])
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
                (openBlock(true), createBlock(Fragment, null, renderList(unref(questionsList), ({ created_at, slug, detail_picture, detail_text, name, views }) => {
                  return openBlock(), createBlock(Fragment, null, [
                    detail_text ? (openBlock(), createBlock(unref(SwiperSlide), {
                      key: _ctx.id,
                      "virtual-index": _ctx.id
                    }, {
                      default: withCtx(() => {
                        var _a, _b;
                        return [
                          createVNode(QuestionItem, {
                            image: (_a = detail_picture == null ? void 0 : detail_picture[0]) == null ? void 0 : _a.file,
                            title: name,
                            answer: (_b = detail_text == null ? void 0 : detail_text[0]) == null ? void 0 : _b.body,
                            views,
                            date: created_at,
                            id: slug.value
                          }, null, 8, ["image", "title", "answer", "views", "date", "id"])
                        ];
                      }),
                      _: 2
                    }, 1032, ["virtual-index"])) : createCommentVNode("", true)
                  ], 64);
                }), 256))
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/QuestionsSlider/QuestionsSlider.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const QuestionsSlider = _sfc_main$2;
const _sfc_main$1 = {
  __name: "QuestionsBlock",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "questions-block section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0427\u0430\u0441\u0442\u043E \u0437\u0430\u0434\u0430\u0432\u0430\u0435\u043C\u044B\u0435 \u0432\u043E\u043F\u0440\u043E\u0441\u044B `);
                } else {
                  return [
                    createTextVNode(" \u0427\u0430\u0441\u0442\u043E \u0437\u0430\u0434\u0430\u0432\u0430\u0435\u043C\u044B\u0435 \u0432\u043E\u043F\u0440\u043E\u0441\u044B ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(QuestionsSlider, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(" \u0427\u0430\u0441\u0442\u043E \u0437\u0430\u0434\u0430\u0432\u0430\u0435\u043C\u044B\u0435 \u0432\u043E\u043F\u0440\u043E\u0441\u044B ")
                ]),
                _: 1
              }),
              createVNode(QuestionsSlider)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/QuestionsBlock/QuestionsBlock.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const MainQuestions = _sfc_main$1;
const _sfc_main = {
  __name: "Companies",
  __ssrInlineRender: true,
  props: {
    list: {
      default: [],
      type: Array
    },
    desc: {
      default: null,
      type: String
    },
    title: {
      default: null,
      type: String
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "companies section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(AppH2, null, {
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
            if (props.desc) {
              _push2(`<div class="companies__desc"${_scopeId}>${ssrInterpolate(props.desc)}</div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode(AppH2, null, {
                default: withCtx(() => [
                  createTextVNode(toDisplayString(props.title), 1)
                ]),
                _: 1
              }),
              props.desc ? (openBlock(), createBlock("div", {
                key: 0,
                class: "companies__desc"
              }, toDisplayString(props.desc), 1)) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/Companies/Companies.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const MainCompanies = _sfc_main;

export { MainCompanies as M, MainQuestions as a };
//# sourceMappingURL=Companies-75bbb9ed.mjs.map
