import { useSSRContext, toRefs, computed, mergeProps, unref, mergeModels, useModel, withCtx, openBlock, createBlock, Fragment, renderList, createVNode } from 'vue';
import { ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderClass, ssrRenderComponent, ssrRenderSlot } from 'vue/server-renderer';
import { u as useRoute, I as IconTriangle, g as AppSelect, _ as _export_sfc } from './server.mjs';

const _sfc_main$7 = {};
function _sfc_ssrRender(_ctx, _push, _parent, _attrs) {
  _push(`<button${ssrRenderAttrs(mergeProps({ class: "show-more" }, _attrs))} data-v-baa4ca10>\u041F\u043E\u043A\u0430\u0437\u0430\u0442\u044C \u0435\u0449\u0451</button>`);
}
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPagination/components/ShowMore/ShowMore.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const ShowMore = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["ssrRender", _sfc_ssrRender], ["__scopeId", "data-v-baa4ca10"]]);
const _sfc_main$6 = {
  __name: "PaginationList",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "pagination__list" }, _attrs))}>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div>`);
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPagination/components/Pagination/PaginationList/PaginationList.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const PaginationList = _sfc_main$6;
const _sfc_main$5 = {
  __name: "PaginationItem",
  __ssrInlineRender: true,
  props: {
    item: {
      default: 1
    },
    activePage: {
      default: -1,
      type: Number
    }
  },
  emits: ["callAction"],
  setup(__props, { emit: __emit }) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "pagination__item" }, _attrs))}>${ssrInterpolate(__props.item)}</div>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPagination/components/Pagination/PaginationItem/PaginationItem.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const PaginationItem = _sfc_main$5;
const _sfc_main$4 = {
  __name: "PaginationListLarge",
  __ssrInlineRender: true,
  props: {
    activePage: {
      default: 1,
      type: Number
    },
    totalPages: {
      default: 1,
      type: Number
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const endItems = (item) => {
      let Arr = [];
      for (let i = 0; i < 5; i++) {
        Arr.push(props.totalPages - i);
      }
      return Arr.reverse()[item - 1];
    };
    return (_ctx, _push, _parent, _attrs) => {
      if (props.activePage < 5) {
        _push(ssrRenderComponent(PaginationList, mergeProps({
          class: "pagination__list_grid",
          style: `--grid-columns-short: 7`
        }, _attrs), {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<!--[-->`);
              ssrRenderList(5, (item) => {
                _push2(ssrRenderComponent(PaginationItem, {
                  item,
                  activePage: props.activePage,
                  onCallAction: (data) => emit("callAction", data),
                  class: item == props.activePage ? "pagination__item_active" : ""
                }, null, _parent2, _scopeId));
              });
              _push2(`<!--]-->`);
              _push2(ssrRenderComponent(PaginationItem, {
                class: "pagination__item_disabled",
                item: "...",
                activePage: props.activePage
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(PaginationItem, {
                item: props.totalPages,
                activePage: props.activePage,
                onCallAction: (data) => emit("callAction", data)
              }, null, _parent2, _scopeId));
            } else {
              return [
                (openBlock(), createBlock(Fragment, null, renderList(5, (item) => {
                  return createVNode(PaginationItem, {
                    item,
                    activePage: props.activePage,
                    onCallAction: (data) => emit("callAction", data),
                    class: item == props.activePage ? "pagination__item_active" : ""
                  }, null, 8, ["item", "activePage", "onCallAction", "class"]);
                }), 64)),
                createVNode(PaginationItem, {
                  class: "pagination__item_disabled",
                  item: "...",
                  activePage: props.activePage
                }, null, 8, ["activePage"]),
                createVNode(PaginationItem, {
                  item: props.totalPages,
                  activePage: props.activePage,
                  onCallAction: (data) => emit("callAction", data)
                }, null, 8, ["item", "activePage", "onCallAction"])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else if (props.activePage >= 5 && props.activePage <= props.totalPages - 4) {
        _push(ssrRenderComponent(PaginationList, mergeProps({
          class: "pagination__list_grid",
          style: `--grid-columns-short: 7`
        }, _attrs), {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(PaginationItem, {
                item: 1,
                activePage: props.activePage,
                onCallAction: (data) => emit("callAction", data)
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(PaginationItem, {
                class: "pagination__item_disabled",
                item: "...",
                activePage: props.activePage
              }, null, _parent2, _scopeId));
              _push2(`<!--[-->`);
              ssrRenderList(3, (item) => {
                _push2(ssrRenderComponent(PaginationItem, {
                  item: props.activePage - 2 + item,
                  activePage: props.activePage,
                  onCallAction: (data) => emit("callAction", data),
                  class: props.activePage - 2 + item == props.activePage ? "pagination__item_active" : ""
                }, null, _parent2, _scopeId));
              });
              _push2(`<!--]-->`);
              _push2(ssrRenderComponent(PaginationItem, {
                class: "pagination__item_disabled",
                item: "...",
                activePage: props.activePage
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(PaginationItem, {
                item: props.totalPages,
                activePage: props.activePage,
                onCallAction: (data) => emit("callAction", data)
              }, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(PaginationItem, {
                  item: 1,
                  activePage: props.activePage,
                  onCallAction: (data) => emit("callAction", data)
                }, null, 8, ["activePage", "onCallAction"]),
                createVNode(PaginationItem, {
                  class: "pagination__item_disabled",
                  item: "...",
                  activePage: props.activePage
                }, null, 8, ["activePage"]),
                (openBlock(), createBlock(Fragment, null, renderList(3, (item) => {
                  return createVNode(PaginationItem, {
                    item: props.activePage - 2 + item,
                    activePage: props.activePage,
                    onCallAction: (data) => emit("callAction", data),
                    class: props.activePage - 2 + item == props.activePage ? "pagination__item_active" : ""
                  }, null, 8, ["item", "activePage", "onCallAction", "class"]);
                }), 64)),
                createVNode(PaginationItem, {
                  class: "pagination__item_disabled",
                  item: "...",
                  activePage: props.activePage
                }, null, 8, ["activePage"]),
                createVNode(PaginationItem, {
                  item: props.totalPages,
                  activePage: props.activePage,
                  onCallAction: (data) => emit("callAction", data)
                }, null, 8, ["item", "activePage", "onCallAction"])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(ssrRenderComponent(PaginationList, mergeProps({
          class: "pagination__list_grid",
          style: `--grid-columns-short: 7`
        }, _attrs), {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(PaginationItem, {
                item: 1,
                activePage: props.activePage,
                onCallAction: (data) => emit("callAction", data)
              }, null, _parent2, _scopeId));
              _push2(ssrRenderComponent(PaginationItem, {
                class: "pagination__item_disabled",
                item: "...",
                activePage: props.activePage
              }, null, _parent2, _scopeId));
              _push2(`<!--[-->`);
              ssrRenderList(5, (item) => {
                _push2(ssrRenderComponent(PaginationItem, {
                  item: endItems(item),
                  activePage: props.activePage,
                  onCallAction: (data) => emit("callAction", data),
                  class: endItems(item) == props.activePage ? "pagination__item_active" : ""
                }, null, _parent2, _scopeId));
              });
              _push2(`<!--]-->`);
            } else {
              return [
                createVNode(PaginationItem, {
                  item: 1,
                  activePage: props.activePage,
                  onCallAction: (data) => emit("callAction", data)
                }, null, 8, ["activePage", "onCallAction"]),
                createVNode(PaginationItem, {
                  class: "pagination__item_disabled",
                  item: "...",
                  activePage: props.activePage
                }, null, 8, ["activePage"]),
                (openBlock(), createBlock(Fragment, null, renderList(5, (item) => {
                  return createVNode(PaginationItem, {
                    item: endItems(item),
                    activePage: props.activePage,
                    onCallAction: (data) => emit("callAction", data),
                    class: endItems(item) == props.activePage ? "pagination__item_active" : ""
                  }, null, 8, ["item", "activePage", "onCallAction", "class"]);
                }), 64))
              ];
            }
          }),
          _: 1
        }, _parent));
      }
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPagination/components/Pagination/PaginationListLarge/PaginationListLarge.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const PaginationListLarge = _sfc_main$4;
const _sfc_main$3 = {
  __name: "PaginationListShort",
  __ssrInlineRender: true,
  props: {
    activePage: {
      default: 1,
      type: Number
    },
    totalPages: {
      default: 1,
      type: Number
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(PaginationList, mergeProps({
        class: "pagination__list_grid",
        style: `--grid-columns-short: ${props.totalPages}`
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<!--[-->`);
            ssrRenderList(props.totalPages, (item) => {
              _push2(ssrRenderComponent(PaginationItem, {
                item,
                activePage: props.activePage,
                class: [item == props.activePage ? "pagination__item_active" : "", props.totalPages == 1 ? "pagination__item_alone" : ""],
                onCallAction: (data) => _ctx.$emit("callAction", data)
              }, null, _parent2, _scopeId));
            });
            _push2(`<!--]-->`);
          } else {
            return [
              (openBlock(true), createBlock(Fragment, null, renderList(props.totalPages, (item) => {
                return openBlock(), createBlock(PaginationItem, {
                  item,
                  activePage: props.activePage,
                  class: [item == props.activePage ? "pagination__item_active" : "", props.totalPages == 1 ? "pagination__item_alone" : ""],
                  onCallAction: (data) => _ctx.$emit("callAction", data)
                }, null, 8, ["item", "activePage", "class", "onCallAction"]);
              }), 256))
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPagination/components/Pagination/PaginationListShort/PaginationListShort.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const PaginationListShort = _sfc_main$3;
const _sfc_main$2 = {
  __name: "Pagination",
  __ssrInlineRender: true,
  props: {
    activePage: {
      default: 1,
      type: Number
    },
    totalPages: {
      default: 1,
      type: Number
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "pagination" }, _attrs))} data-v-96a57dcc><div class="pagination__title" data-v-96a57dcc>\u0421\u0442\u0440\u0430\u043D\u0438\u0446\u0430:</div>`);
      if (props.totalPages <= 7) {
        _push(ssrRenderComponent(PaginationListShort, {
          activePage: props.activePage,
          totalPages: props.totalPages,
          onCallAction: (data) => _ctx.$emit("callAction", data)
        }, null, _parent));
      } else {
        _push(ssrRenderComponent(PaginationListLarge, {
          activePage: props.activePage,
          totalPages: props.totalPages,
          onCallAction: (data) => _ctx.$emit("callAction", data)
        }, null, _parent));
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPagination/components/Pagination/Pagination.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const Pagination = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-96a57dcc"]]);
const _sfc_main$1 = {
  __name: "AppPagination",
  __ssrInlineRender: true,
  props: /* @__PURE__ */ mergeModels({
    totalPages: {
      type: Number,
      required: true
    },
    perPageOptions: {
      default: [
        {
          label: "25",
          value: 25
        },
        {
          label: "50",
          value: 50
        },
        {
          label: "100",
          value: 100
        }
      ]
    }
  }, {
    "modelValue": {},
    "modelModifiers": {},
    "perPage": {},
    "perPageModifiers": {}
  }),
  emits: /* @__PURE__ */ mergeModels(["showMore"], ["update:modelValue", "update:perPage"]),
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const props = __props;
    const { totalPages, perPageOptions } = toRefs(props);
    const activePage = useModel(__props, "modelValue");
    const perPage = useModel(__props, "perPage");
    const changePage = (data) => {
      activePage.value = data.value;
    };
    const changeOnPage = (data) => {
      perPage.value = data.value;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "pagination-full" }, _attrs))}>`);
      _push(ssrRenderComponent(ShowMore, {
        onClick: ($event) => emit("showMore")
      }, null, _parent));
      _push(`<div class="pagination-full__wrapper">`);
      _push(ssrRenderComponent(Pagination, {
        totalPages: unref(totalPages),
        activePage: activePage.value,
        onCallAction: changePage
      }, null, _parent));
      _push(ssrRenderComponent(AppSelect, {
        class: "pagination-full__select",
        item: {
          id: 0,
          key: "visibleElems",
          value: perPage.value,
          focus: false,
          required: false,
          title: "\u041D\u0430 \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0435:",
          lockedOptions: [],
          options: unref(perPageOptions)
        },
        isFiltered: false,
        isHaveNullOption: false,
        onChangeValue: changeOnPage
      }, null, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppPagination/AppPagination.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const AppPagination = _sfc_main$1;
const _sfc_main = {
  __name: "AppNav",
  __ssrInlineRender: true,
  props: {
    title: { type: String, required: true },
    categories: { type: Array, required: true },
    navParam: { type: String, default: "category" },
    path: { type: String, default: "" }
  },
  setup(__props) {
    const props = __props;
    const { categories, title, navParam, path } = toRefs(props);
    computed(() => categories.value.find((category) => {
      var _a;
      return (_a = category.children) == null ? void 0 : _a.some((child) => route.fullPath.includes(child.value));
    }));
    const route = useRoute();
    let activeNav = computed(() => route.params[navParam.value] ? route.params[navParam.value] : "all");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<nav${ssrRenderAttrs(mergeProps({ class: "nav" }, _attrs))}><div class="nav__header">${ssrInterpolate(unref(title))}</div><div class="nav__list"><!--[-->`);
      ssrRenderList(unref(categories), (category) => {
        _push(`<!--[--><div class="${ssrRenderClass([{ nav__item_active: category.value == unref(activeNav), nav__item_main: category == null ? void 0 : category.isMain }, "nav__item"])}">${ssrInterpolate(category.title)} `);
        if (category.children.length > 0) {
          _push(ssrRenderComponent(IconTriangle, {
            onClick: ($event) => category.isOpen = !category.isOpen,
            class: "nav__item-triangle"
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
        if (category.isOpen) {
          _push(`<!--[-->`);
          ssrRenderList(category.children, (child) => {
            _push(`<div class="${ssrRenderClass([{ nav__item_active: child.value == unref(activeNav) }, "nav__item nav__item_child"])}">${ssrInterpolate(child.title)}</div>`);
          });
          _push(`<!--]-->`);
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      });
      _push(`<!--]--></div></nav>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppNav/AppNav.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AppNav = _sfc_main;

export { AppNav as A, AppPagination as a };
//# sourceMappingURL=AppNav-b6ff05a6.mjs.map
