import { useSSRContext, ref, inject, watch, mergeProps, withCtx, unref, renderSlot, withDirectives, createVNode, toDisplayString, vShow, createTextVNode, withModifiers, openBlock, createBlock, createCommentVNode, Fragment, renderList } from 'vue';
import { ssrRenderComponent, ssrRenderSlot, ssrRenderStyle, ssrInterpolate, ssrRenderList, ssrRenderAttrs } from 'vue/server-renderer';
import { F as FormItem, h as FormLabel, i as AppPopup, j as AppInput, k as AppButton, P as PopupOption, l as PopupScripts } from './server.mjs';

const _sfc_main$1 = {
  __name: "AppLoader",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "loader" }, _attrs))}></div>`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppLoader/AppLoader.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const AppLoader = _sfc_main$1;
const _sfc_main = {
  __name: "Input",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        required: false,
        title: "Autocomlete title",
        value: null,
        placeholder: null,
        focus: false,
        key: null,
        options: [],
        lockedOptions: []
      },
      type: Object
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isCanCreate: {
      default: false,
      type: Boolean
    },
    isLink: {
      default: false,
      type: Boolean
    },
    isShowId: {
      default: false,
      type: Boolean
    },
    anotherTitle: {
      default: null,
      type: String
    },
    loaderStatus: {
      default: false,
      type: Boolean
    },
    isShowLabel: {
      default: true,
      type: Boolean
    },
    placeholder: {
      default: "\u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E",
      type: String
    },
    isShowSubstring: {
      default: true,
      type: Boolean
    },
    isShowButton: {
      default: false,
      type: Boolean
    },
    isCountDistance: {
      default: false,
      type: Boolean
    },
    isLoading: {
      default: false,
      type: Boolean
    },
    isShowNotSelected: {
      default: true,
      type: Boolean
    }
  },
  emits: ["openLink", "changeValue", "createOption", "clickOutside", "searchOptions", "clickButton"],
  setup(__props, { expose: __expose, emit: __emit }) {
    const props = __props;
    const popupRef = ref(null);
    const nullOption = {
      id: null,
      sort: 0,
      text: props.placeholder
    };
    let activeOption = ref(nullOption);
    let search = ref(null);
    let options = ref([]);
    ref([]);
    let isDisableButton = ref(false);
    inject("actionState", () => ref(null), true);
    const emit = __emit;
    const preventClick = () => {
      if (props.isReadOnly || popupRef.value.popupRef.closest(".popup_prevent") != null) {
        popupRef.value.popupRef.removeAttribute("open");
      } else {
        if (popupRef.value.popupRef.closest(".form-item__substring") == null) {
          popupRef.value.popupRef.setAttribute("open", true);
        } else {
          setTimeout(() => {
            if (popupRef.value.popupRef.hasAttribute("open")) {
              popupRef.value.popupRef.removeAttribute("open");
              popupRef.value.popupRef.classList.remove("popup_up");
              popupRef.value.popupRef.classList.remove("popup_visible");
              popupRef.value.popupRef.classList.remove("popup_right");
            } else {
              popupRef.value.popupRef.setAttribute("open", true);
            }
          }, 5);
        }
      }
    };
    const onInput = (data) => {
      const isOpen = popupRef.value.popupRef.classList.contains("popup_visible");
      if (!isOpen) {
        popupRef.value.popupRef.classList.add("popup_visible");
      }
      callAction({ action: "searchOptions", value: data.value });
    };
    const reset = () => {
      callAction({ action: "changeValue", value: null });
    };
    const clickButton = () => {
      isDisableButton.value = true;
      emit("clickButton", { key: props.item.key, value: activeOption.value, search: search.value });
    };
    const callAction = (data) => {
      const getOptions = () => {
        const isEmpty = (obj) => {
          for (const prop in obj) {
            if (Object.hasOwn(obj, prop)) {
              return false;
            }
          }
          return true;
        };
        let localOptions = props.item.options == null ? [] : props.item.options.filter((p) => p != null && typeof p == "object" && !Array.isArray(p) && !isEmpty(p)).sort((prev, next) => prev.label.sort - next.label.sort);
        options.value = JSON.parse(JSON.stringify(localOptions));
      };
      const createOption = () => {
        PopupScripts.hideDetails(popupRef.value.popupRef);
        emit("createOption", {
          key: props.item.key,
          value: true
        });
      };
      const setActiveOption = (value) => {
        search.value = "";
        if (typeof value == "string") {
          search.value = value;
          activeOption.value = { ...nullOption, text: value };
        } else if (value === null || !(value == null ? void 0 : value.text)) {
          activeOption.value = nullOption;
        } else {
          activeOption.value = value;
          search.value = value.text;
        }
      };
      const searchOptions = (value) => {
        emit("changeValue", {
          key: props.item.key,
          search: value
        });
        search.value = value;
        if (!popupRef.value.popupRef.hasAttribute("open")) {
          popupRef.value.popupRef.setAttribute("open", true);
        }
        emit("searchOptions", { key: props.item.key, value: search.value });
      };
      const changeValue = (value) => {
        if (value == null || ![null, void 0].includes(props.item.lockedOptions) && !props.item.lockedOptions.includes(value) || props.item.type == "address") {
          search.value = null;
          setActiveOption(value);
          setTimeout(() => {
            emit("changeValue", {
              key: props.item.key,
              value,
              search: search.value
            });
            (value == null ? void 0 : value.text) && callAction({ action: "searchOptions", value: value.text });
            PopupScripts.hideDetails(popupRef.value.popupRef);
          }, 10);
        } else if (value == null) {
          emit("changeValue", null);
        }
      };
      switch (data.action) {
        case "createOption":
          createOption();
          break;
        case "setActiveOption":
          setActiveOption(data.value);
          break;
        case "searchOptions":
          searchOptions(data.value);
          break;
        case "changeValue":
          changeValue(data.value);
          break;
        case "getOptions":
          getOptions();
          break;
      }
    };
    watch(
      () => props.item.options,
      () => {
        callAction({
          action: "getOptions",
          value: null
        });
      }
    );
    watch(
      () => search.value,
      () => {
        isDisableButton.value = false;
      }
    );
    watch(
      () => props.item.value,
      () => {
        callAction({
          action: "getOptions",
          value: null
        });
        callAction({
          action: "setActiveOption",
          value: props.item.value
        });
      }
    );
    __expose({
      popupRef,
      reset
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: ["form-item__autocomplete autocomplete", [[null, void 0].includes(props.item.value) || props.item.value == "" ? "autocomplete_empty" : "", props.isCountDistance ? "distance__autocomplete" : ""]],
        required: props.item.required
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (__props.isShowLabel) {
              _push2(ssrRenderComponent(FormLabel, {
                style: props.item.title != null && props.item.title != "" ? null : { display: "none" },
                title: props.item.title
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(ssrRenderComponent(AppPopup, {
              ref_key: "popupRef",
              ref: popupRef,
              class: ["autocomplete__popup", { autocomplete__popup_countDistance: props.isCountDistance }],
              isHaveParent: true,
              closeByClick: false,
              isReadOnly: props.isReadOnly,
              isCanSelect: true,
              onClickOutside: () => emit("clickOutside", true),
              onClick: () => !props.isCountDistance && preventClick()
            }, {
              summary: withCtx((_2, _push3, _parent3, _scopeId2) => {
                var _a, _b;
                if (_push3) {
                  ssrRenderSlot(_ctx.$slots, "icon", {}, null, _push3, _parent3, _scopeId2);
                  _push3(ssrRenderComponent(AppInput, {
                    class: ["autocomplete__input", { autocomplete__input: props.isCountDistance }],
                    onClick: () => props.isCountDistance && preventClick(),
                    item: {
                      id: props.item.id,
                      title: null,
                      type: "text",
                      focus: false,
                      key: props.item.key,
                      placeholder: props.item.placeholder,
                      value: props.isReadOnly ? unref(activeOption).text == "\u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E" && props.item.type == "address" ? null : unref(activeOption).text : unref(search),
                      substring: props.isReadOnly ? null : unref(activeOption).id == null ? " " : `ID: ${unref(activeOption).id}`
                    },
                    mask: null,
                    disabled: false,
                    isLink: props.isLink,
                    isReadOnly: props.isReadOnly,
                    enabledAutocomplete: false,
                    isShowSubstring: props.isShowSubstring,
                    onOpenLink: (item) => emit("openLink", item),
                    onChangeValue: (data) => onInput(data),
                    onMousedown: (event) => props.isReadOnly ? null : event.target.classList.contains("popup_prevent") ? event.preventDefault() : null
                  }, {
                    default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        ssrRenderSlot(_ctx.$slots, "link", {}, null, _push4, _parent4, _scopeId3);
                        _push4(`<div class="autocomplete__active-option" style="${ssrRenderStyle(!props.isReadOnly && ([null, void 0].includes(unref(search)) || unref(search) == "") ? null : { display: "none" })}"${_scopeId3}>${ssrInterpolate(unref(activeOption).text)}</div>`);
                      } else {
                        return [
                          renderSlot(_ctx.$slots, "link"),
                          withDirectives(createVNode("div", { class: "autocomplete__active-option" }, toDisplayString(unref(activeOption).text), 513), [
                            [vShow, !props.isReadOnly && ([null, void 0].includes(unref(search)) || unref(search) == "")]
                          ])
                        ];
                      }
                    }),
                    _: 3
                  }, _parent3, _scopeId2));
                  if (props.isShowButton) {
                    _push3(ssrRenderComponent(AppButton, {
                      onClick: clickButton,
                      disabled: unref(isDisableButton) || ((_a = unref(search)) == null ? void 0 : _a.length) === 0,
                      class: ["autocomplete__button button_blue", { button_loading: __props.isLoading }],
                      style: props.isCountDistance ? { height: "45px" } : ""
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(`\u0420\u0430\u0441\u0447\u0438\u0442\u0430\u0442\u044C`);
                        } else {
                          return [
                            createTextVNode("\u0420\u0430\u0441\u0447\u0438\u0442\u0430\u0442\u044C")
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                } else {
                  return [
                    renderSlot(_ctx.$slots, "icon"),
                    createVNode(AppInput, {
                      class: ["autocomplete__input", { autocomplete__input: props.isCountDistance }],
                      onClick: withModifiers(() => props.isCountDistance && preventClick(), ["prevent"]),
                      item: {
                        id: props.item.id,
                        title: null,
                        type: "text",
                        focus: false,
                        key: props.item.key,
                        placeholder: props.item.placeholder,
                        value: props.isReadOnly ? unref(activeOption).text == "\u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E" && props.item.type == "address" ? null : unref(activeOption).text : unref(search),
                        substring: props.isReadOnly ? null : unref(activeOption).id == null ? " " : `ID: ${unref(activeOption).id}`
                      },
                      mask: null,
                      disabled: false,
                      isLink: props.isLink,
                      isReadOnly: props.isReadOnly,
                      enabledAutocomplete: false,
                      isShowSubstring: props.isShowSubstring,
                      onOpenLink: (item) => emit("openLink", item),
                      onChangeValue: (data) => onInput(data),
                      onMousedown: (event) => props.isReadOnly ? null : event.target.classList.contains("popup_prevent") ? event.preventDefault() : null
                    }, {
                      default: withCtx(() => [
                        renderSlot(_ctx.$slots, "link"),
                        withDirectives(createVNode("div", { class: "autocomplete__active-option" }, toDisplayString(unref(activeOption).text), 513), [
                          [vShow, !props.isReadOnly && ([null, void 0].includes(unref(search)) || unref(search) == "")]
                        ])
                      ]),
                      _: 3
                    }, 8, ["onClick", "class", "item", "isLink", "isReadOnly", "isShowSubstring", "onOpenLink", "onChangeValue", "onMousedown"]),
                    props.isShowButton ? (openBlock(), createBlock(AppButton, {
                      key: 0,
                      onClick: withModifiers(clickButton, ["stop"]),
                      disabled: unref(isDisableButton) || ((_b = unref(search)) == null ? void 0 : _b.length) === 0,
                      class: ["autocomplete__button button_blue", { button_loading: __props.isLoading }],
                      style: props.isCountDistance ? { height: "45px" } : ""
                    }, {
                      default: withCtx(() => [
                        createTextVNode("\u0420\u0430\u0441\u0447\u0438\u0442\u0430\u0442\u044C")
                      ]),
                      _: 1
                    }, 8, ["disabled", "class", "style"])) : createCommentVNode("", true)
                  ];
                }
              }),
              content: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  if (__props.loaderStatus) {
                    _push3(ssrRenderComponent(AppLoader, { class: "popup__loader" }, null, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  if (props.isShowNotSelected) {
                    _push3(ssrRenderComponent(PopupOption, {
                      onClick: () => callAction({ action: "changeValue", value: null })
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(` \u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E `);
                        } else {
                          return [
                            createTextVNode(" \u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E ")
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(`<!--[-->`);
                  ssrRenderList(unref(options), (option) => {
                    _push3(ssrRenderComponent(PopupOption, {
                      class: ["popup-option__root", (option.value == unref(activeOption).id ? "popup__option_active" : "", ![null, void 0].includes(props.item.lockedOptions) && props.item.lockedOptions.includes(option.value) ? "popup__option_disabled" : "")],
                      onClick: () => callAction({ action: "changeValue", value: option.value })
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(`<div class="popup-option__text"${_scopeId3}>${ssrInterpolate(option.label.text)}</div><span class="popup-option__substring" style="${ssrRenderStyle(props.isShowId ? null : { display: "none" })}"${_scopeId3}> ID: ${ssrInterpolate(option.label.id)}</span>`);
                        } else {
                          return [
                            createVNode("div", { class: "popup-option__text" }, toDisplayString(option.label.text), 1),
                            withDirectives(createVNode("span", { class: "popup-option__substring" }, " ID: " + toDisplayString(option.label.id), 513), [
                              [vShow, props.isShowId]
                            ])
                          ];
                        }
                      }),
                      _: 2
                    }, _parent3, _scopeId2));
                  });
                  _push3(`<!--]-->`);
                  if (__props.isCanCreate) {
                    _push3(ssrRenderComponent(PopupOption, {
                      class: "popup__option_create",
                      onClick: () => callAction({ action: "createOption", value: true })
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(` \u0421\u043E\u0437\u0434\u0430\u0442\u044C `);
                        } else {
                          return [
                            createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C ")
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                } else {
                  return [
                    __props.loaderStatus ? (openBlock(), createBlock(AppLoader, {
                      key: 0,
                      class: "popup__loader"
                    })) : createCommentVNode("", true),
                    props.isShowNotSelected ? (openBlock(), createBlock(PopupOption, {
                      key: 1,
                      onClick: () => callAction({ action: "changeValue", value: null })
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" \u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E ")
                      ]),
                      _: 1
                    }, 8, ["onClick"])) : createCommentVNode("", true),
                    (openBlock(true), createBlock(Fragment, null, renderList(unref(options), (option) => {
                      return openBlock(), createBlock(PopupOption, {
                        class: ["popup-option__root", (option.value == unref(activeOption).id ? "popup__option_active" : "", ![null, void 0].includes(props.item.lockedOptions) && props.item.lockedOptions.includes(option.value) ? "popup__option_disabled" : "")],
                        onClick: () => callAction({ action: "changeValue", value: option.value })
                      }, {
                        default: withCtx(() => [
                          createVNode("div", { class: "popup-option__text" }, toDisplayString(option.label.text), 1),
                          withDirectives(createVNode("span", { class: "popup-option__substring" }, " ID: " + toDisplayString(option.label.id), 513), [
                            [vShow, props.isShowId]
                          ])
                        ]),
                        _: 2
                      }, 1032, ["class", "onClick"]);
                    }), 256)),
                    __props.isCanCreate ? (openBlock(), createBlock(PopupOption, {
                      key: 2,
                      class: "popup__option_create",
                      onClick: withModifiers(() => callAction({ action: "createOption", value: true }), ["prevent"])
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C ")
                      ]),
                      _: 1
                    }, 8, ["onClick"])) : createCommentVNode("", true)
                  ];
                }
              }),
              _: 3
            }, _parent2, _scopeId));
          } else {
            return [
              __props.isShowLabel ? withDirectives((openBlock(), createBlock(FormLabel, {
                key: 0,
                title: props.item.title
              }, null, 8, ["title"])), [
                [vShow, props.item.title != null && props.item.title != ""]
              ]) : createCommentVNode("", true),
              createVNode(AppPopup, {
                ref_key: "popupRef",
                ref: popupRef,
                class: ["autocomplete__popup", { autocomplete__popup_countDistance: props.isCountDistance }],
                isHaveParent: true,
                closeByClick: false,
                isReadOnly: props.isReadOnly,
                isCanSelect: true,
                onClickOutside: () => emit("clickOutside", true),
                onClick: withModifiers(() => !props.isCountDistance && preventClick(), ["prevent"])
              }, {
                summary: withCtx(() => {
                  var _a;
                  return [
                    renderSlot(_ctx.$slots, "icon"),
                    createVNode(AppInput, {
                      class: ["autocomplete__input", { autocomplete__input: props.isCountDistance }],
                      onClick: withModifiers(() => props.isCountDistance && preventClick(), ["prevent"]),
                      item: {
                        id: props.item.id,
                        title: null,
                        type: "text",
                        focus: false,
                        key: props.item.key,
                        placeholder: props.item.placeholder,
                        value: props.isReadOnly ? unref(activeOption).text == "\u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E" && props.item.type == "address" ? null : unref(activeOption).text : unref(search),
                        substring: props.isReadOnly ? null : unref(activeOption).id == null ? " " : `ID: ${unref(activeOption).id}`
                      },
                      mask: null,
                      disabled: false,
                      isLink: props.isLink,
                      isReadOnly: props.isReadOnly,
                      enabledAutocomplete: false,
                      isShowSubstring: props.isShowSubstring,
                      onOpenLink: (item) => emit("openLink", item),
                      onChangeValue: (data) => onInput(data),
                      onMousedown: (event) => props.isReadOnly ? null : event.target.classList.contains("popup_prevent") ? event.preventDefault() : null
                    }, {
                      default: withCtx(() => [
                        renderSlot(_ctx.$slots, "link"),
                        withDirectives(createVNode("div", { class: "autocomplete__active-option" }, toDisplayString(unref(activeOption).text), 513), [
                          [vShow, !props.isReadOnly && ([null, void 0].includes(unref(search)) || unref(search) == "")]
                        ])
                      ]),
                      _: 3
                    }, 8, ["onClick", "class", "item", "isLink", "isReadOnly", "isShowSubstring", "onOpenLink", "onChangeValue", "onMousedown"]),
                    props.isShowButton ? (openBlock(), createBlock(AppButton, {
                      key: 0,
                      onClick: withModifiers(clickButton, ["stop"]),
                      disabled: unref(isDisableButton) || ((_a = unref(search)) == null ? void 0 : _a.length) === 0,
                      class: ["autocomplete__button button_blue", { button_loading: __props.isLoading }],
                      style: props.isCountDistance ? { height: "45px" } : ""
                    }, {
                      default: withCtx(() => [
                        createTextVNode("\u0420\u0430\u0441\u0447\u0438\u0442\u0430\u0442\u044C")
                      ]),
                      _: 1
                    }, 8, ["disabled", "class", "style"])) : createCommentVNode("", true)
                  ];
                }),
                content: withCtx(() => [
                  __props.loaderStatus ? (openBlock(), createBlock(AppLoader, {
                    key: 0,
                    class: "popup__loader"
                  })) : createCommentVNode("", true),
                  props.isShowNotSelected ? (openBlock(), createBlock(PopupOption, {
                    key: 1,
                    onClick: () => callAction({ action: "changeValue", value: null })
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" \u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E ")
                    ]),
                    _: 1
                  }, 8, ["onClick"])) : createCommentVNode("", true),
                  (openBlock(true), createBlock(Fragment, null, renderList(unref(options), (option) => {
                    return openBlock(), createBlock(PopupOption, {
                      class: ["popup-option__root", (option.value == unref(activeOption).id ? "popup__option_active" : "", ![null, void 0].includes(props.item.lockedOptions) && props.item.lockedOptions.includes(option.value) ? "popup__option_disabled" : "")],
                      onClick: () => callAction({ action: "changeValue", value: option.value })
                    }, {
                      default: withCtx(() => [
                        createVNode("div", { class: "popup-option__text" }, toDisplayString(option.label.text), 1),
                        withDirectives(createVNode("span", { class: "popup-option__substring" }, " ID: " + toDisplayString(option.label.id), 513), [
                          [vShow, props.isShowId]
                        ])
                      ]),
                      _: 2
                    }, 1032, ["class", "onClick"]);
                  }), 256)),
                  __props.isCanCreate ? (openBlock(), createBlock(PopupOption, {
                    key: 2,
                    class: "popup__option_create",
                    onClick: withModifiers(() => callAction({ action: "createOption", value: true }), ["prevent"])
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C ")
                    ]),
                    _: 1
                  }, 8, ["onClick"])) : createCommentVNode("", true)
                ]),
                _: 3
              }, 8, ["class", "isReadOnly", "onClickOutside", "onClick"])
            ];
          }
        }),
        _: 3
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppAutocomplete/Input.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AppAutocomplete = _sfc_main;

export { AppAutocomplete as A, AppLoader as a };
//# sourceMappingURL=Input-3345b1b6.mjs.map
