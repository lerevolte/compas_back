import { k as AppButton, e as __nuxt_component_0 } from './server.mjs';
import { useSSRContext, ref, mergeProps, unref, withCtx, createTextVNode, createVNode, toDisplayString, openBlock, createBlock, Fragment, renderList, withDirectives, vShow } from 'vue';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderList, ssrInterpolate, ssrRenderClass, ssrRenderStyle } from 'vue/server-renderer';
import { SwiperSlide } from 'swiper/vue';
import { A as AppSlider } from './Slider-a943f5b9.mjs';

const tariffs = [
  {
    id: 1,
    title: "\u0411\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u044B\u0439",
    subtitle: "6 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432",
    advantages: [
      {
        text: "\u0421\u043A\u0438\u0434\u043A\u0430 \u043D\u0430 \u043C\u0430\u0433\u0430\u0437\u0438\u043D \u043C\u043E\u0434\u0443\u043B\u0435\u0439: 0 %"
      },
      {
        text: "\u041A\u043E\u043B-\u0432\u043E \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0435\u0439: 5"
      },
      {
        text: "\u041E\u0431\u044C\u0435\u043C \u0445\u0440\u0430\u043D\u0438\u043B\u0438\u0449\u0430: 2.5 \u0433\u0431."
      }
    ],
    prices: [
      {
        value: 0,
        price: {
          old: null,
          "new": null
        }
      },
      {
        value: 1,
        price: {
          old: null,
          "new": null
        }
      },
      {
        value: 2,
        price: {
          old: null,
          "new": null
        }
      },
      {
        value: 3,
        price: {
          old: null,
          "new": null
        }
      }
    ],
    day_prices: [
      {
        value: 0,
        price: {
          old: null,
          "new": null
        }
      },
      {
        value: 1,
        price: {
          old: null,
          "new": null
        }
      },
      {
        value: 2,
        price: {
          old: null,
          "new": null
        }
      },
      {
        value: 3,
        price: {
          old: null,
          "new": null
        }
      }
    ],
    isShowMonthPay: false,
    discount_text: null
  },
  {
    id: 2,
    title: "\u0411\u0430\u0437\u043E\u0432\u044B\u0439 \u0442\u0430\u0440\u0438\u0444",
    subtitle: "7 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432",
    advantages: [
      {
        text: "\u0421\u043A\u0438\u0434\u043A\u0430 \u043D\u0430 \u043C\u0430\u0433\u0430\u0437\u0438\u043D \u043C\u043E\u0434\u0443\u043B\u0435\u0439: -10 %"
      },
      {
        text: "\u041A\u043E\u043B-\u0432\u043E \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0435\u0439: 10"
      },
      {
        text: "\u041E\u0431\u044C\u0435\u043C \u0445\u0440\u0430\u043D\u0438\u043B\u0438\u0449\u0430: 5 \u0433\u0431."
      }
    ],
    prices: [
      {
        value: 0,
        price: {
          old: 0,
          "new": 990
        }
      },
      {
        value: 1,
        price: {
          old: 990,
          "new": 891
        }
      },
      {
        value: 2,
        price: {
          old: 990,
          "new": 743
        }
      },
      {
        value: 3,
        price: {
          old: 990,
          "new": 594
        }
      }
    ],
    day_prices: [
      {
        value: 0,
        price: {
          old: 0,
          "new": 33
        }
      },
      {
        value: 1,
        price: {
          old: 1990,
          "new": 1791
        }
      },
      {
        value: 2,
        price: {
          old: 1990,
          "new": 1493
        }
      },
      {
        value: 3,
        price: {
          old: 1990,
          "new": 1194
        }
      }
    ],
    isShowMonthPay: true,
    discount_text: "\u0421\u043A\u0438\u0434\u043A\u0430 10% \u043F\u0440\u0438 \u043E\u043F\u043B\u0430\u0442\u0435 \u043D\u0430 1 \u0433\u043E\u0434"
  },
  {
    id: 3,
    title: "\u0411\u0438\u0437\u043D\u0435\u0441",
    subtitle: "11 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432",
    advantages: [
      {
        text: "\u0421\u043A\u0438\u0434\u043A\u0430 \u043D\u0430 \u043C\u0430\u0433\u0430\u0437\u0438\u043D \u043C\u043E\u0434\u0443\u043B\u0435\u0439: -20 %"
      },
      {
        text: "\u041A\u043E\u043B-\u0432\u043E \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0435\u0439: 40"
      },
      {
        text: "\u041E\u0431\u044C\u0435\u043C \u0445\u0440\u0430\u043D\u0438\u043B\u0438\u0449\u0430: 50 \u0433\u0431."
      }
    ],
    prices: [
      {
        value: 0,
        price: {
          old: 0,
          "new": 1490
        }
      },
      {
        value: 1,
        price: {
          old: 1490,
          "new": 1341
        }
      },
      {
        value: 2,
        price: {
          old: 1490,
          "new": 1118
        }
      },
      {
        value: 3,
        price: {
          old: 1490,
          "new": 894
        }
      }
    ],
    day_prices: [
      {
        value: 0,
        price: {
          old: 0,
          "new": 49
        }
      },
      {
        value: 1,
        price: {
          old: 1990,
          "new": 1791
        }
      },
      {
        value: 2,
        price: {
          old: 1990,
          "new": 1493
        }
      },
      {
        value: 3,
        price: {
          old: 1990,
          "new": 1194
        }
      }
    ],
    isShowMonthPay: true,
    discount_text: "\u0421\u043A\u0438\u0434\u043A\u0430 20% \u043F\u0440\u0438 \u043E\u043F\u043B\u0430\u0442\u0435 \u043D\u0430 1 \u0433\u043E\u0434"
  },
  {
    id: 4,
    title: "\u041F\u0440\u043E\u0444\u0435\u0441\u0441\u0438\u043E\u043D\u0430\u043B\u044C\u043D\u044B\u0439",
    subtitle: "14 \u0438\u043D\u0441\u0442\u0440\u0443\u043C\u0435\u043D\u0442\u043E\u0432",
    advantages: [
      {
        text: "\u0421\u043A\u0438\u0434\u043A\u0430 \u043D\u0430 \u043C\u0430\u0433\u0430\u0437\u0438\u043D \u043C\u043E\u0434\u0443\u043B\u0435\u0439: -40 %"
      },
      {
        text: "\u041A\u043E\u043B-\u0432\u043E \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0435\u0439: 100"
      },
      {
        text: "\u041E\u0431\u044C\u0435\u043C \u0445\u0440\u0430\u043D\u0438\u043B\u0438\u0449\u0430: 100 \u0433\u0431."
      }
    ],
    prices: [
      {
        value: 0,
        price: {
          old: 0,
          "new": 1990
        }
      },
      {
        value: 1,
        price: {
          old: 1990,
          "new": 1791
        }
      },
      {
        value: 2,
        price: {
          old: 1990,
          "new": 1493
        }
      },
      {
        value: 3,
        price: {
          old: 1990,
          "new": 1194
        }
      }
    ],
    day_prices: [
      {
        value: 0,
        price: {
          old: 0,
          "new": 67
        }
      },
      {
        value: 1,
        price: {
          old: 1990,
          "new": 1791
        }
      },
      {
        value: 2,
        price: {
          old: 1990,
          "new": 1493
        }
      },
      {
        value: 3,
        price: {
          old: 1990,
          "new": 1194
        }
      }
    ],
    isShowMonthPay: true,
    discount_text: "\u0421\u043A\u0438\u0434\u043A\u0430 40% \u043F\u0440\u0438 \u043E\u043F\u043B\u0430\u0442\u0435 \u043D\u0430 1 \u0433\u043E\u0434"
  }
];
const _sfc_main = {
  __name: "TariffsSlider",
  __ssrInlineRender: true,
  setup(__props) {
    let countSlides = ref(3);
    let slides = ref(tariffs);
    const setDayPrice = (item) => {
      return item.day_prices[0].price;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "tariffs-slider" }, _attrs))}>`);
      _push(ssrRenderComponent(AppSlider, {
        class: unref(countSlides) == 1 ? "swiper-slider_only" : "",
        options: { VisibleSlides: unref(countSlides), spaceBetween: 25, pagination: { clickable: true, dynamicBullets: true } }
      }, {
        slide: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<!--[-->`);
            ssrRenderList(unref(slides), (slide) => {
              _push2(ssrRenderComponent(unref(SwiperSlide), {
                key: slide.id,
                "virtual-index": slide.id
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`<div class="tariffs-slider__item"${_scopeId2}><div class="tariffs-slider__title"${_scopeId2}>${ssrInterpolate(slide.title)}</div><ul class="tariffs-slider__advantages"${_scopeId2}><!--[-->`);
                    ssrRenderList(slide.advantages, (advantage) => {
                      _push3(`<li class="tariffs-slider__advantage"${_scopeId2}>${ssrInterpolate(advantage.text)}</li>`);
                    });
                    _push3(`<!--]--></ul><div class="tariffs-slider__price tariffs-slider__price_day"${_scopeId2}><div class="tariffs-slider__values"${_scopeId2}><div class="${ssrRenderClass([[null, 0].includes(setDayPrice(slide).new) ? "tariffs-slider__value_free" : "", "tariffs-slider__value tariffs-slider__value_new"])}"${_scopeId2}>${ssrInterpolate(setDayPrice(slide).new)} \u0440\u0443\u0431 </div><div class="tariffs-slider__value tariffs-slider__value_old" style="${ssrRenderStyle(setDayPrice(slide).old && setDayPrice(slide).old != 0 ? null : { display: "none" })}"${_scopeId2}>${ssrInterpolate(setDayPrice(slide).old)} \u0440\u0443\u0431 </div></div><div class="${ssrRenderClass([!slide.discount_text ? "tariffs-slider__discount_none" : "", "tariffs-slider__discount"])}"${_scopeId2}> \u0426\u0435\u043D\u0430 \u0437\u0430 \u043C\u0435\u0441\u044F\u0446 ${ssrInterpolate(setDayPrice(slide).new * 30)} \u0440\u0443\u0431 </div></div>`);
                    _push3(ssrRenderComponent(_component_NuxtLink, {
                      to: `/auth/registration?tariff=${slide.id}`
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(ssrRenderComponent(AppButton, { class: "button_blue" }, {
                            default: withCtx((_4, _push5, _parent5, _scopeId4) => {
                              if (_push5) {
                                _push5(` \u041F\u043E\u043F\u0440\u043E\u0431\u043E\u0432\u0430\u0442\u044C `);
                              } else {
                                return [
                                  createTextVNode(" \u041F\u043E\u043F\u0440\u043E\u0431\u043E\u0432\u0430\u0442\u044C ")
                                ];
                              }
                            }),
                            _: 2
                          }, _parent4, _scopeId3));
                        } else {
                          return [
                            createVNode(AppButton, { class: "button_blue" }, {
                              default: withCtx(() => [
                                createTextVNode(" \u041F\u043E\u043F\u0440\u043E\u0431\u043E\u0432\u0430\u0442\u044C ")
                              ]),
                              _: 1
                            })
                          ];
                        }
                      }),
                      _: 2
                    }, _parent3, _scopeId2));
                    _push3(`</div>`);
                  } else {
                    return [
                      createVNode("div", { class: "tariffs-slider__item" }, [
                        createVNode("div", { class: "tariffs-slider__title" }, toDisplayString(slide.title), 1),
                        createVNode("ul", { class: "tariffs-slider__advantages" }, [
                          (openBlock(true), createBlock(Fragment, null, renderList(slide.advantages, (advantage) => {
                            return openBlock(), createBlock("li", { class: "tariffs-slider__advantage" }, toDisplayString(advantage.text), 1);
                          }), 256))
                        ]),
                        createVNode("div", { class: "tariffs-slider__price tariffs-slider__price_day" }, [
                          createVNode("div", { class: "tariffs-slider__values" }, [
                            createVNode("div", {
                              class: ["tariffs-slider__value tariffs-slider__value_new", [null, 0].includes(setDayPrice(slide).new) ? "tariffs-slider__value_free" : ""]
                            }, toDisplayString(setDayPrice(slide).new) + " \u0440\u0443\u0431 ", 3),
                            withDirectives(createVNode("div", { class: "tariffs-slider__value tariffs-slider__value_old" }, toDisplayString(setDayPrice(slide).old) + " \u0440\u0443\u0431 ", 513), [
                              [vShow, setDayPrice(slide).old && setDayPrice(slide).old != 0]
                            ])
                          ]),
                          createVNode("div", {
                            class: ["tariffs-slider__discount", !slide.discount_text ? "tariffs-slider__discount_none" : ""]
                          }, " \u0426\u0435\u043D\u0430 \u0437\u0430 \u043C\u0435\u0441\u044F\u0446 " + toDisplayString(setDayPrice(slide).new * 30) + " \u0440\u0443\u0431 ", 3)
                        ]),
                        createVNode(_component_NuxtLink, {
                          to: `/auth/registration?tariff=${slide.id}`
                        }, {
                          default: withCtx(() => [
                            createVNode(AppButton, { class: "button_blue" }, {
                              default: withCtx(() => [
                                createTextVNode(" \u041F\u043E\u043F\u0440\u043E\u0431\u043E\u0432\u0430\u0442\u044C ")
                              ]),
                              _: 1
                            })
                          ]),
                          _: 2
                        }, 1032, ["to"])
                      ])
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
            });
            _push2(`<!--]-->`);
          } else {
            return [
              (openBlock(true), createBlock(Fragment, null, renderList(unref(slides), (slide) => {
                return openBlock(), createBlock(unref(SwiperSlide), {
                  key: slide.id,
                  "virtual-index": slide.id
                }, {
                  default: withCtx(() => [
                    createVNode("div", { class: "tariffs-slider__item" }, [
                      createVNode("div", { class: "tariffs-slider__title" }, toDisplayString(slide.title), 1),
                      createVNode("ul", { class: "tariffs-slider__advantages" }, [
                        (openBlock(true), createBlock(Fragment, null, renderList(slide.advantages, (advantage) => {
                          return openBlock(), createBlock("li", { class: "tariffs-slider__advantage" }, toDisplayString(advantage.text), 1);
                        }), 256))
                      ]),
                      createVNode("div", { class: "tariffs-slider__price tariffs-slider__price_day" }, [
                        createVNode("div", { class: "tariffs-slider__values" }, [
                          createVNode("div", {
                            class: ["tariffs-slider__value tariffs-slider__value_new", [null, 0].includes(setDayPrice(slide).new) ? "tariffs-slider__value_free" : ""]
                          }, toDisplayString(setDayPrice(slide).new) + " \u0440\u0443\u0431 ", 3),
                          withDirectives(createVNode("div", { class: "tariffs-slider__value tariffs-slider__value_old" }, toDisplayString(setDayPrice(slide).old) + " \u0440\u0443\u0431 ", 513), [
                            [vShow, setDayPrice(slide).old && setDayPrice(slide).old != 0]
                          ])
                        ]),
                        createVNode("div", {
                          class: ["tariffs-slider__discount", !slide.discount_text ? "tariffs-slider__discount_none" : ""]
                        }, " \u0426\u0435\u043D\u0430 \u0437\u0430 \u043C\u0435\u0441\u044F\u0446 " + toDisplayString(setDayPrice(slide).new * 30) + " \u0440\u0443\u0431 ", 3)
                      ]),
                      createVNode(_component_NuxtLink, {
                        to: `/auth/registration?tariff=${slide.id}`
                      }, {
                        default: withCtx(() => [
                          createVNode(AppButton, { class: "button_blue" }, {
                            default: withCtx(() => [
                              createTextVNode(" \u041F\u043E\u043F\u0440\u043E\u0431\u043E\u0432\u0430\u0442\u044C ")
                            ]),
                            _: 1
                          })
                        ]),
                        _: 2
                      }, 1032, ["to"])
                    ])
                  ]),
                  _: 2
                }, 1032, ["virtual-index"]);
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
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/TariffsSlider/TariffsSlider.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const TariffsSlider = _sfc_main;

export { TariffsSlider as T };
//# sourceMappingURL=TariffsSlider-31bd220c.mjs.map
