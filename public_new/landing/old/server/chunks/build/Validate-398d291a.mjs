import { useSSRContext, ref, mergeProps, computed, withCtx, withDirectives, createVNode, vShow, openBlock, createBlock, toDisplayString, createCommentVNode, watch, unref, createTextVNode, Fragment, renderList, isRef, createSlots, toRaw } from 'vue';
import { ssrRenderAttrs, ssrRenderSlot, ssrRenderComponent, ssrInterpolate, ssrRenderAttr, ssrRenderList, ssrRenderClass, ssrIncludeBooleanAttr, ssrRenderStyle } from 'vue/server-renderer';
import _ from 'lodash';
import { F as FormItem, h as FormLabel, p as FormValue, j as AppInput, k as AppButton, l as PopupScripts, i as AppPopup, P as PopupOption, t as IconArrow, q as commonScripts$1 } from './server.mjs';
import VueDatePicker from '@vuepic/vue-datepicker';
import draggable from 'vuedraggable';
import { ColorPicker as ColorPicker$1 } from 'vue-accessible-color-picker';
import { A as AppAutocomplete } from './Input-3345b1b6.mjs';
import { B as ButtonText } from './ButtonText-edbdf3ac.mjs';

const _sfc_main$j = {
  __name: "DateField",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        value: "",
        type: "text",
        focus: false,
        placeholder: "",
        substring: null,
        title: "Undefined title"
      },
      type: Object
    },
    disabled: {
      default: false,
      type: Boolean
    },
    isMultiple: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "changeValue"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    let localDate = ref(null);
    let rangeStart = ref("");
    let rangeEnd = ref("");
    const datepicker = ref(null);
    let presetDates = {
      plural: [
        {
          id: 0,
          title: "\u0421\u0435\u0433\u043E\u0434\u043D\u044F",
          day: [/* @__PURE__ */ new Date(), /* @__PURE__ */ new Date()]
        },
        {
          id: 1,
          title: "\u0417\u0430\u0432\u0442\u0440\u0430",
          day: [(/* @__PURE__ */ new Date()).setDate((/* @__PURE__ */ new Date()).getDate() + 1), (/* @__PURE__ */ new Date()).setDate((/* @__PURE__ */ new Date()).getDate() + 1)]
        },
        {
          id: 2,
          title: "\u0422\u0435\u043A\u0443\u0449\u0438\u0439 \u043C\u0435\u0441\u044F\u0446",
          day: [(/* @__PURE__ */ new Date()).setDate(1), new Date((/* @__PURE__ */ new Date()).getFullYear(), (/* @__PURE__ */ new Date()).getMonth() + 1, 0)]
        },
        {
          id: 3,
          title: "\u041F\u0440\u043E\u0448\u043B\u044B\u0439 \u043C\u0435\u0441\u044F\u0446",
          day: [new Date((/* @__PURE__ */ new Date()).getFullYear(), (/* @__PURE__ */ new Date()).getMonth() - 1, 1), new Date((/* @__PURE__ */ new Date()).getFullYear(), (/* @__PURE__ */ new Date()).getMonth(), 0)]
        },
        {
          id: 2,
          title: "\u0422\u0435\u043A\u0443\u0449\u0438\u0439 \u0433\u043E\u0434",
          day: [new Date((/* @__PURE__ */ new Date()).getFullYear(), 0, 1), new Date((/* @__PURE__ */ new Date()).getFullYear() + 1, 0, 0)]
        },
        {
          id: 3,
          title: "\u041F\u0440\u043E\u0448\u043B\u044B\u0439 \u0433\u043E\u0434",
          day: [new Date((/* @__PURE__ */ new Date()).getFullYear() - 1, 0, 1), new Date((/* @__PURE__ */ new Date()).getFullYear(), 0, 0)]
        }
      ],
      default: [
        {
          id: 0,
          title: "\u0412\u0447\u0435\u0440\u0430",
          day: (/* @__PURE__ */ new Date()).setDate((/* @__PURE__ */ new Date()).getDate() - 1)
        },
        {
          id: 1,
          title: "\u0421\u0435\u0433\u043E\u0434\u043D\u044F",
          day: /* @__PURE__ */ new Date()
        },
        {
          id: 2,
          title: "\u0417\u0430\u0432\u0442\u0440\u0430",
          day: (/* @__PURE__ */ new Date()).setDate((/* @__PURE__ */ new Date()).getDate() + 1)
        },
        {
          id: 3,
          title: "\u041F\u043E\u0441\u043B\u0435\u0437\u0430\u0432\u0442\u0440\u0430",
          day: (/* @__PURE__ */ new Date()).setDate((/* @__PURE__ */ new Date()).getDate() + 2)
        }
      ]
    };
    const changeValue = (value) => {
      const changeMultiple = (value2) => {
        let request = [];
        if (value2 != null) {
          localDate.value = value2;
          rangeStart.value = transformValue(value2[0]).split("-").reverse().join(".");
          rangeEnd.value = transformValue(value2[1]).split("-").reverse().join(".");
          request[0] = transformValue(localDate.value[0]);
          request[1] = transformValue(localDate.value[1]);
        } else {
          localDate.value = [rangeStart.value, rangeEnd.value];
          request[0] = rangeStart.value.split(".").reverse().join("-");
          request[1] = rangeEnd.value.split(".").reverse().join("-");
        }
        datepicker.value.closeMenu();
        setTimeout(() => {
          datepicker.value.openMenu();
        }, 1);
        emit("changeValue", { key: props.item.key, value: request });
      };
      const changeDefault = (value2) => {
        localDate.value = new Date(value2);
        let request = new Date(value2).toLocaleDateString("fr-CA", { year: "numeric", month: "2-digit", day: "2-digit" });
        emit("changeValue", { key: props.item.key, value: request });
      };
      if (props.isMultiple) {
        changeMultiple(value);
      } else {
        changeDefault(value);
      }
    };
    const setValue = () => {
      if (props.isMultiple) {
        localDate.value = Array.isArray(props.item.value) ? JSON.parse(JSON.stringify(props.item.value)) : [];
        if (Array.isArray(props.item.value) && props.item.value.length > 0) {
          rangeStart.value = transformValue(props.item.value[0]).split("-").reverse().join(".");
          rangeEnd.value = transformValue(props.item.value[1]).split("-").reverse().join(".");
        }
      } else {
        localDate.value = typeof props.item.value != "string" || [null, void 0].includes(props.item.value) ? null : JSON.parse(JSON.stringify(new Date(props.item.value)));
      }
    };
    const setClasses = (day) => {
      if (props.isMultiple) {
        if (rangeStart.value != null && rangeEnd.value != null) {
          return _.isEqual([transformValue(day.day[0]), transformValue(day.day[1])], [rangeStart.value.split(".").reverse().join("-"), rangeEnd.value.split(".").reverse().join("-")]) ? "datapicker__preset-item_active" : "";
        }
      }
    };
    const transformValue = (value) => {
      return value == null || value == "" ? null : new Date(value).toLocaleDateString("fr-CA", { year: "numeric", month: "2-digit", day: "2-digit" });
    };
    const callRangeAction = (data) => {
      const setDay = (data2) => {
        if (data2.key == "rangeStart") {
          localDate.value[0] = data2.value.split(".").reverse().join("-");
        } else {
          localDate.value[1] = data2.value.split(".").reverse().join("-");
        }
      };
      const setInputValue = (data2) => {
        if (data2.key == "rangeStart") {
          rangeStart.value = data2.value;
        } else {
          rangeEnd.value = data2.value;
        }
      };
      switch (data.action) {
        case "setDay":
          setDay(data.value);
          break;
        case "setInputValue":
          setInputValue(data.value);
          break;
      }
    };
    watch(() => props.item.value, () => {
      setValue();
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(unref(VueDatePicker), mergeProps({
        class: props.isMultiple ? "datepicker_multiple" : "",
        "auto-apply": "",
        "multi-calendars": props.isMultiple,
        range: props.isMultiple,
        locale: "ru",
        ref_key: "datepicker",
        ref: datepicker,
        position: "left",
        "hide-offset-dates": "",
        format: "dd.MM.yyyy",
        placeholder: "__.__.____",
        modelValue: unref(localDate),
        "onUpdate:modelValue": [($event) => isRef(localDate) ? localDate.value = $event : localDate = $event, (value) => changeValue(value)],
        "enable-time-picker": false,
        "max-time": { hours: 0, minutes: 0, seconds: 0 },
        "month-change-on-scroll": false,
        onOpen: () => _ctx.$emit("openDatepicker", true)
      }, _attrs), createSlots({
        "right-sidebar": withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="datapicker__preset-days"${_scopeId}><!--[-->`);
            ssrRenderList(unref(presetDates)[props.isMultiple ? "plural" : "default"], (day) => {
              _push2(`<div class="${ssrRenderClass([setClasses(day), "datapicker__preset-item"])}"${_scopeId}>${ssrInterpolate(day.title)}</div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "datapicker__preset-days" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(presetDates)[props.isMultiple ? "plural" : "default"], (day) => {
                  return openBlock(), createBlock("div", {
                    class: ["datapicker__preset-item", setClasses(day)],
                    onClick: ($event) => changeValue(day.day)
                  }, toDisplayString(day.title), 11, ["onClick"]);
                }), 256))
              ])
            ];
          }
        }),
        _: 2
      }, [
        props.isMultiple ? {
          name: "left-sidebar",
          fn: withCtx((_2, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="datapicker__footer"${_scopeId}><div class="datepicker__inputs"${_scopeId}>`);
              _push2(ssrRenderComponent(AppInput, {
                item: {
                  id: 0,
                  required: false,
                  substring: null,
                  type: "text",
                  title: null,
                  placeholder: "__.__.____",
                  value: unref(rangeStart),
                  key: "rangeStart",
                  focus: false
                },
                mask: "##.##.####",
                onChangeValue: (data) => callRangeAction({ action: "setInputValue", value: data }),
                onBlur: (event) => callRangeAction({ action: "setDay", value: { key: "rangeStart", value: event.target.value } })
              }, null, _parent2, _scopeId));
              _push2(` \u2014 `);
              _push2(ssrRenderComponent(AppInput, {
                item: {
                  id: 1,
                  required: false,
                  substring: null,
                  type: "text",
                  title: null,
                  placeholder: "__.__.____",
                  value: unref(rangeEnd),
                  key: "rangeEnd",
                  focus: false
                },
                mask: "##.##.####",
                onChangeValue: (data) => callRangeAction({ action: "setInputValue", value: data }),
                onBlur: (event) => callRangeAction({ action: "setDay", value: { key: "rangeEnd", value: event.target.value } })
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
              _push2(ssrRenderComponent(AppButton, {
                class: "button_blue",
                onClick: () => changeValue(null)
              }, {
                default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` \u041F\u0440\u0438\u043C\u0435\u043D\u0438\u0442\u044C `);
                  } else {
                    return [
                      createTextVNode(" \u041F\u0440\u0438\u043C\u0435\u043D\u0438\u0442\u044C ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              _push2(`</div>`);
            } else {
              return [
                createVNode("div", { class: "datapicker__footer" }, [
                  createVNode("div", { class: "datepicker__inputs" }, [
                    createVNode(AppInput, {
                      item: {
                        id: 0,
                        required: false,
                        substring: null,
                        type: "text",
                        title: null,
                        placeholder: "__.__.____",
                        value: unref(rangeStart),
                        key: "rangeStart",
                        focus: false
                      },
                      mask: "##.##.####",
                      onChangeValue: (data) => callRangeAction({ action: "setInputValue", value: data }),
                      onBlur: (event) => callRangeAction({ action: "setDay", value: { key: "rangeStart", value: event.target.value } })
                    }, null, 8, ["item", "onChangeValue", "onBlur"]),
                    createTextVNode(" \u2014 "),
                    createVNode(AppInput, {
                      item: {
                        id: 1,
                        required: false,
                        substring: null,
                        type: "text",
                        title: null,
                        placeholder: "__.__.____",
                        value: unref(rangeEnd),
                        key: "rangeEnd",
                        focus: false
                      },
                      mask: "##.##.####",
                      onChangeValue: (data) => callRangeAction({ action: "setInputValue", value: data }),
                      onBlur: (event) => callRangeAction({ action: "setDay", value: { key: "rangeEnd", value: event.target.value } })
                    }, null, 8, ["item", "onChangeValue", "onBlur"])
                  ]),
                  createVNode(AppButton, {
                    class: "button_blue",
                    onClick: () => changeValue(null)
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" \u041F\u0440\u0438\u043C\u0435\u043D\u0438\u0442\u044C ")
                    ]),
                    _: 1
                  }, 8, ["onClick"])
                ])
              ];
            }
          }),
          key: "0"
        } : void 0
      ]), _parent));
    };
  }
};
const _sfc_setup$j = _sfc_main$j.setup;
_sfc_main$j.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Date/DateField/DateField.vue");
  return _sfc_setup$j ? _sfc_setup$j(props, ctx) : void 0;
};
const DateField = _sfc_main$j;
const _sfc_main$i = {
  __name: "Date",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        value: "",
        type: "text",
        focus: false,
        placeholder: "",
        substring: null,
        title: "Undefined title"
      },
      type: Object
    },
    disabled: {
      default: false,
      type: Boolean
    },
    isMultiple: {
      default: false,
      type: Boolean
    },
    isReadOnly: {
      default: false,
      type: Boolean
    }
  },
  setup(__props) {
    const props = __props;
    const setValue = computed(() => {
      const transformValue = (value) => {
        return [null, void 0].includes(value) || value == "" ? null : new Date(value).toLocaleDateString("ru-RU", { year: "numeric", month: "2-digit", day: "2-digit" });
      };
      if (props.isMultiple) {
        return Array.isArray(props.item.value) ? `${transformValue(props.item.value[0])}-${transformValue(props.item.value[1])}` : null;
      } else {
        return transformValue(props.item.value);
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: "form-item__date",
        required: props.item.required,
        style: `--substring: ${props.item.substring != void 0 ? props.item.substring : ""}`
      }, _attrs), {
        default: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(FormLabel, {
              style: props.item.title != null && props.item.title != "" ? null : { display: "none" },
              title: props.item.title
            }, null, _parent2, _scopeId));
            if (props.isReadOnly) {
              _push2(ssrRenderComponent(FormValue, {
                isHTML: false,
                value: setValue.value
              }, null, _parent2, _scopeId));
            } else {
              _push2(ssrRenderComponent(DateField, {
                item: props.item,
                disabled: props.disabled,
                isMultiple: props.isMultiple,
                onOpenDatepicker: () => _ctx.$emit("openDatepicker", true),
                onChangeValue: (data) => _ctx.$emit("changeValue", data)
              }, null, _parent2, _scopeId));
            }
          } else {
            return [
              withDirectives(createVNode(FormLabel, {
                title: props.item.title
              }, null, 8, ["title"]), [
                [vShow, props.item.title != null && props.item.title != ""]
              ]),
              props.isReadOnly ? (openBlock(), createBlock(FormValue, {
                key: 0,
                isHTML: false,
                value: setValue.value
              }, null, 8, ["value"])) : (openBlock(), createBlock(DateField, {
                key: 1,
                item: props.item,
                disabled: props.disabled,
                isMultiple: props.isMultiple,
                onOpenDatepicker: () => _ctx.$emit("openDatepicker", true),
                onChangeValue: (data) => _ctx.$emit("changeValue", data)
              }, null, 8, ["item", "disabled", "isMultiple", "onOpenDatepicker", "onChangeValue"]))
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$i = _sfc_main$i.setup;
_sfc_main$i.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Date/Date.vue");
  return _sfc_setup$i ? _sfc_setup$i(props, ctx) : void 0;
};
const AppDate = _sfc_main$i;
const _sfc_main$h = {
  __name: "FansyBox",
  __ssrInlineRender: true,
  setup(__props) {
    const containerRef = ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        ref_key: "containerRef",
        ref: containerRef
      }, _attrs))}>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div>`);
    };
  }
};
const _sfc_setup$h = _sfc_main$h.setup;
_sfc_main$h.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppFansyBox/FansyBox.vue");
  return _sfc_setup$h ? _sfc_setup$h(props, ctx) : void 0;
};
const FansyBox = _sfc_main$h;
const _sfc_main$g = {
  __name: "Dots",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__dots" }, _attrs))}><svg width="3" height="13" fill="#a6b7d4"><path fill-rule="evenodd" d="M0 1.5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 5a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM1.5 10a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" clip-rule="evenodd"></path></svg></figure>`);
    };
  }
};
const _sfc_setup$g = _sfc_main$g.setup;
_sfc_main$g.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Dots/Dots.vue");
  return _sfc_setup$g ? _sfc_setup$g(props, ctx) : void 0;
};
const IconDots = _sfc_main$g;
const _sfc_main$f = {
  __name: "FansyBoxImageDetails",
  __ssrInlineRender: true,
  props: {
    image: {
      default: {
        id: 1649,
        url: "/",
        file: "/",
        extension: "png"
      },
      type: Object
    },
    id: {
      default: 0,
      type: Number
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const props = __props;
    const checkExtension = computed(() => {
      return ["png", "svg", "heic", "jpeg", "jpg", "webp", "pdf", "gif", "mp4", "mp3"].includes(props.image.extension);
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppPopup, mergeProps({
        class: "fancybox-item__details popup_actions",
        isCanSelect: false,
        closeByClick: true
      }, _attrs), {
        summary: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(IconDots, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(IconDots)
            ];
          }
        }),
        content: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(PopupOption, {
              "data-fancybox": `galleryClickView_${props.id}`,
              href: checkExtension.value ? props.image.file : props.image.url
            }, {
              default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041F\u043E\u0441\u043C\u043E\u0442\u0440\u0435\u0442\u044C `);
                } else {
                  return [
                    createTextVNode(" \u041F\u043E\u0441\u043C\u043E\u0442\u0440\u0435\u0442\u044C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(PopupOption, {
              onClick: () => emit("callAction", { action: "downloadFile", value: props.image })
            }, {
              default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u043A\u0430\u0447\u0430\u0442\u044C `);
                } else {
                  return [
                    createTextVNode(" \u0421\u043A\u0430\u0447\u0430\u0442\u044C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(PopupOption, {
              class: "popup__option_red",
              onClick: () => emit("callAction", { action: "deleteFile", value: props.image.id })
            }, {
              default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0423\u0434\u0430\u043B\u0438\u0442\u044C `);
                } else {
                  return [
                    createTextVNode(" \u0423\u0434\u0430\u043B\u0438\u0442\u044C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode(PopupOption, {
                "data-fancybox": `galleryClickView_${props.id}`,
                href: checkExtension.value ? props.image.file : props.image.url
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u041F\u043E\u0441\u043C\u043E\u0442\u0440\u0435\u0442\u044C ")
                ]),
                _: 1
              }, 8, ["data-fancybox", "href"]),
              createVNode(PopupOption, {
                onClick: () => emit("callAction", { action: "downloadFile", value: props.image })
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u0421\u043A\u0430\u0447\u0430\u0442\u044C ")
                ]),
                _: 1
              }, 8, ["onClick"]),
              createVNode(PopupOption, {
                class: "popup__option_red",
                onClick: () => emit("callAction", { action: "deleteFile", value: props.image.id })
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u0423\u0434\u0430\u043B\u0438\u0442\u044C ")
                ]),
                _: 1
              }, 8, ["onClick"])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$f = _sfc_main$f.setup;
_sfc_main$f.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppFansyBox/FansyBoxImage/FansyBoxImageDetails/FansyBoxImageDetails.vue");
  return _sfc_setup$f ? _sfc_setup$f(props, ctx) : void 0;
};
const FansyBoxImageDetails = _sfc_main$f;
const _sfc_main$e = {
  __name: "LoaderProgress",
  __ssrInlineRender: true,
  props: {
    progressImage: {
      default: 0,
      type: Number
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__loader-progress" }, _attrs))}><svg width="24" height="24"><circle cx="12" cy="12" r="10" style="${ssrRenderStyle(`stroke-dasharray: 62.8, 62.8; stroke-dashoffset: ${props.progressImage};`)}"></circle></svg></figure>`);
    };
  }
};
const _sfc_setup$e = _sfc_main$e.setup;
_sfc_main$e.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/LoaderProgress/LoaderProgress.vue");
  return _sfc_setup$e ? _sfc_setup$e(props, ctx) : void 0;
};
const IconLoaderProgress = _sfc_main$e;
const _sfc_main$d = {
  __name: "FansyBoxImage",
  __ssrInlineRender: true,
  props: {
    id: {
      default: 0,
      type: Number
    },
    image: {
      default: {
        id: 1649,
        url: "/",
        file: "/",
        extension: "png"
      },
      type: Object
    },
    isOneFile: {
      default: false,
      type: Boolean
    },
    isShowFileName: {
      default: false,
      type: Boolean
    },
    loading: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const setName = computed(() => {
      if (props.id != void 0 && props.image.name != void 0) {
        let name = props.image.name;
        const regExp = new RegExp(`\\.${props.image.extension.toLowerCase()}$`);
        if (props.image.extension !== "" && !regExp.test(name.toLowerCase())) {
          name += `.${props.image.extension}`;
        }
        if (name.length > 28) {
          return `${name.substr(0, 15)}...${name.substr(name.length - 7)}`;
        } else {
          return name;
        }
      } else {
        return null;
      }
    });
    const setHref = computed(() => {
      return ["png", "svg", "heic", "jpeg", "jpg", "webp", "pdf", "gif", "mp4", "mp3"].includes(props.image.extension) ? props.image.file : props.image.url;
    });
    const progressImage = computed(() => {
      return props.image.progress ? 62.8 - props.image.progress / 100 * 62.8 : 62.8;
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "fancybox-item fancybox__item" }, _attrs))}><a class="fancybox-item__link"${ssrRenderAttr("data-fancybox", `galleryClick_${props.id}`)}${ssrRenderAttr("href", setHref.value)}><figure class="ibg fancybox-item__img"><img${ssrRenderAttr("src", props.image.url)}${ssrRenderAttr("alt", props.image.name)}${ssrRenderAttr("title", props.image.name)}></figure>`);
      if (props.loading) {
        _push(ssrRenderComponent(IconLoaderProgress, { progressImage: progressImage.value }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      if (props.isShowFileName && ![null, void 0].includes(props.image.name)) {
        _push(`<div class="fancybox-item__title">${ssrInterpolate(setName.value)}</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</a>`);
      if (!props.isOneFile) {
        _push(ssrRenderComponent(FansyBoxImageDetails, {
          id: props.id,
          image: props.image,
          onCallAction: (data) => emit("callAction", data)
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$d = _sfc_main$d.setup;
_sfc_main$d.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppFansyBox/FansyBoxImage/FansyBoxImage.vue");
  return _sfc_setup$d ? _sfc_setup$d(props, ctx) : void 0;
};
const FansyBoxImage = _sfc_main$d;
const _sfc_main$c = {
  __name: "LoadFile",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<svg${ssrRenderAttrs(mergeProps({
        width: "22",
        height: "17",
        viewBox: "0 0 22 17",
        xmlns: "http://www.w3.org/2000/svg"
      }, _attrs))}><g fill="#A6B7D4" fill-rule="evenodd"><path d="M7.869 4H3a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-4.881V3H19a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H3a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h4.87l-.001 1z"></path><path d="M11.434 10.797 11.43 1.47a.465.465 0 0 0-.47-.465.474.474 0 0 0-.47.473l-.03 9.309-3.674-3.652a.494.494 0 0 0-.69-.007.47.47 0 0 0-.008.673l4.53 4.535a.5.5 0 0 0 .709-.001l4.567-4.605a.483.483 0 0 0-.011-.69.507.507 0 0 0-.708.01l-3.741 3.748z" stroke="#A6B7D4" stroke-width=".3"></path></g></svg>`);
    };
  }
};
const _sfc_setup$c = _sfc_main$c.setup;
_sfc_main$c.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/LoadFile/LoadFile.vue");
  return _sfc_setup$c ? _sfc_setup$c(props, ctx) : void 0;
};
const IconLoadFile = _sfc_main$c;
const _sfc_main$b = {
  __name: "Close",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "ibg icon__close" }, _attrs))}><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024"><path fill="currentColor" d="M764.288 214.592 512 466.88 259.712 214.592a31.936 31.936 0 0 0-45.12 45.12L466.752 512 214.528 764.224a31.936 31.936 0 1 0 45.12 45.184L512 557.184l252.288 252.288a31.936 31.936 0 0 0 45.12-45.12L557.12 512.064l252.288-252.352a31.936 31.936 0 1 0-45.12-45.184z"></path></svg></figure>`);
    };
  }
};
const _sfc_setup$b = _sfc_main$b.setup;
_sfc_main$b.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Close/Close.vue");
  return _sfc_setup$b ? _sfc_setup$b(props, ctx) : void 0;
};
const IconClose = _sfc_main$b;
const _sfc_main$a = {
  __name: "Upload",
  __ssrInlineRender: true,
  props: {
    isMultiple: {
      default: false,
      type: Boolean
    },
    buttonTitle: {
      default: "\u0417\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044C",
      type: String
    },
    otherIcon: {
      default: null,
      type: Object
    },
    isIcon: {
      default: false,
      type: Boolean
    },
    buttonIcon: {
      default: null,
      type: Object
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const dragover = ref(false);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: ["file-upload", dragover.value ? "file-upload_dragover" : ""]
      }, _attrs))}><input type="file" class="file-upload__input"${ssrIncludeBooleanAttr(props.isMultiple) ? " multiple" : ""}${ssrRenderAttr("title", props.buttonTitle)}>`);
      if (!props.isIcon || [null, void 0].includes(props.buttonIcon) || [null, void 0].includes(props.buttonIcon.file) || props.buttonIcon.file == "") {
        _push(`<div class="file-upload__button">`);
        _push(ssrRenderComponent(IconLoadFile, null, null, _parent));
        _push(`<span class="file-upload__button-title">${ssrInterpolate(props.buttonTitle)}</span></div>`);
      } else if (props.isIcon && ![null, void 0].includes(props.buttonIcon) && ![null, void 0].includes(props.buttonIcon.file) && props.buttonIcon.file != "") {
        _push(`<div class="file-upload__image-wrapper"><img class="file-upload__image"${ssrRenderAttr("src", props.buttonIcon.file)} alt="\u0418\u043A\u043E\u043D\u043A\u0430">`);
        _push(ssrRenderComponent(IconClose, {
          title: "\u0423\u0434\u0430\u043B\u0438\u0442\u044C \u0438\u043A\u043E\u043D\u043A\u0443",
          onClick: ($event) => emit("callAction", {
            action: "deleteIcon",
            value: props.buttonIcon.file
          })
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$a = _sfc_main$a.setup;
_sfc_main$a.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/File/Upload/Upload.vue");
  return _sfc_setup$a ? _sfc_setup$a(props, ctx) : void 0;
};
const Upload = _sfc_main$a;
const _sfc_main$9 = {
  __name: "Field",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 1517,
        title: "Undefined title",
        type: "file",
        key: "",
        required: false,
        options: null,
        focus: true,
        value: null,
        buttonName: ""
      },
      type: Object
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isShowFileName: {
      default: false,
      type: Boolean
    },
    isMultiple: {
      default: false,
      type: Boolean
    },
    isOneFile: {
      default: false,
      type: Boolean
    },
    isIcon: {
      default: false,
      type: Boolean
    },
    isDraggable: {
      default: true,
      type: Boolean
    },
    pageId: {
      default: null,
      type: Boolean
    }
  },
  emits: [
    "changeValue",
    "initEdit"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    const getValues = () => {
      if ([null, void 0].includes(props.item.value) || !Array.isArray(props.item.value)) {
        return [];
      } else {
        const localValues = props.item.value == null ? [] : props.item.value.filter((p) => ![null, void 0].includes(p) && !Array.isArray(p) && Object.keys(p).length !== 0 && typeof p != "string");
        return JSON.parse(JSON.stringify(localValues));
      }
    };
    const localImages = ref([]);
    const setClasses = computed(() => {
      return [
        localImages.value.length == 0 && props.isReadOnly ? "form-item__value_empty" : "",
        props.isOneFile ? "file-container_one-file" : ""
      ];
    });
    const callAction = (data) => {
      const supportedExtensions = ["png", "heic", "svg", "jpeg", "jpg", "webp", "pdf", "gif", "mp4", "xlsx", "xls", "mp3", "doc", "docx", "txt", "pptx"];
      const downloadFile = async () => {
        const imageSrc = data.value.file;
        const nameOfDownload = [null, void 0].includes(data.value.name) || data.value.name !== "" ? data.value.name : "my-image.png";
        try {
          const response = await fetch(imageSrc, {
            method: "GET",
            headers: {
              accept: "application/json"
            }
          });
          const blobImage = await response.blob();
          const href = URL.createObjectURL(blobImage);
          const anchorElement = document.createElement("a");
          anchorElement.href = href;
          anchorElement.download = nameOfDownload;
          document.body.appendChild(anchorElement);
          anchorElement.click();
          document.body.removeChild(anchorElement);
          window.URL.revokeObjectURL(href);
        } catch (error) {
          console.log(error);
        }
      };
      const deleteFile = (id) => {
        localImages.value = localImages.value.filter((item) => item.id !== id);
        emit("changeValue", { key: props.item.key, value: localImages.value });
      };
      const addFiles = () => {
        const uploadFile = async (data2, id) => {
          const preAddImage = (id2) => {
            const downloadingItem = {
              id: id2,
              name: "\u0417\u0430\u0433\u0440\u0443\u0437\u043A\u0430",
              url: null,
              file: null,
              extension: "",
              status: "loading"
            };
            localImages.value.splice(localImages.value.length, 0, downloadingItem);
          };
          const addImage = (image, id2) => {
            const currentImage = localImages.value.find((item) => item.id == id2);
            currentImage.id = image.id;
            currentImage.name = image.name;
            currentImage.url = image.url;
            currentImage.file = image.file;
            currentImage.extension = image.extension;
            currentImage.status = "success";
            emit("changeValue", { key: props.item.key, value: localImages.value });
          };
          const sendImage = () => {
            preAddImage(id);
            const ajax = new XMLHttpRequest();
            const localItem = localImages.value.find((item) => item.id == id);
            ajax.upload.onprogress = function(event) {
              localItem.progress = event.loaded / event.total * 100;
            };
            ajax.onloadend = function() {
              try {
                const responseObj = JSON.parse(ajax.response)[0];
                addImage(responseObj, id);
              } catch (error) {
                deleteFile(id);
                console.log("error", error);
              }
            };
            ajax.open("POST", `https://opt6.compas.pro/api/files/store?field_id=${props.item.id}&page_id=${props.pageId}`, true);
            ajax.setRequestHeader("Authorization", `Bearer `);
            ajax.send(data2);
          };
          sendImage();
        };
        data.value.forEach(async (file) => {
          if (!supportedExtensions.includes(file.name.split(".").splice(-1)[0].toLowerCase())) {
            commonScripts$1.showNotification({
              title: "\u041E\u0448\u0438\u0431\u043A\u0430 \u0437\u0430\u0433\u0440\u0443\u0437\u043A\u0438 \u0444\u0430\u0439\u043B\u0430",
              description: `\u041F\u043E\u0434\u0434\u0435\u0440\u0436\u0438\u0432\u0430\u0435\u043C\u044B\u0435\u0439 \u0444\u0430\u0439\u043B\u044B ${supportedExtensions.join(", ")}`
            }, "error");
            return;
          }
          const formData = new FormData();
          const id = (/* @__PURE__ */ new Date()).getTime();
          formData.append("files[]", file);
          await uploadFile(formData, id);
        });
      };
      const deleteIcon = (file) => {
        localImages.value = localImages.value.filter((item) => item.file != file);
        emit("changeValue", { key: props.item.key, value: localImages.value });
      };
      switch (data.action) {
        case "addFiles":
          addFiles();
          break;
        case "downloadFile":
          downloadFile();
          break;
        case "deleteFile":
          deleteFile(data.value);
          break;
        case "deleteIcon":
          deleteIcon(data.value);
          break;
        default:
          emit("initEdit", data);
          emit("changeValue", { key: props.item.key, value: localImages.value });
          break;
      }
    };
    watch(() => props.isReadOnly, () => {
      localImages.value = getValues();
    });
    watch(() => props.item.value, () => {
      localImages.value = getValues();
    }, {
      deep: true
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FansyBox, mergeProps({
        class: ["file__container file-container form-item__value", setClasses.value]
      }, _attrs), {
        default: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(unref(draggable), {
              tag: "div",
              modelValue: localImages.value,
              "onUpdate:modelValue": ($event) => localImages.value = $event,
              class: ["file__list file-list", props.isShowFileName ? "file-list_show-title" : ""],
              forceFallback: true,
              itemKey: "fileFields",
              key: props.item.key,
              disabled: props.isOneFile,
              handle: ".fancybox-item__link",
              draggable: props.isDraggable ? ".file-list__item:not(.file-list__item_undraggable)" : ".file-list__item-none",
              onEnd: (event) => callAction(event)
            }, {
              item: withCtx(({ element: image }, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  if (!props.isIcon) {
                    _push3(ssrRenderComponent(FansyBoxImage, {
                      class: ["file-list__item", image.status == "loading" ? "file-list__item_loading" : ""],
                      id: props.item.id,
                      isShowFileName: props.isShowFileName,
                      isOneFile: props.isOneFile,
                      image,
                      loading: image.status == "loading",
                      onCallAction: (data) => callAction(data)
                    }, null, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                } else {
                  return [
                    !props.isIcon ? (openBlock(), createBlock(FansyBoxImage, {
                      key: 0,
                      class: ["file-list__item", image.status == "loading" ? "file-list__item_loading" : ""],
                      id: props.item.id,
                      isShowFileName: props.isShowFileName,
                      isOneFile: props.isOneFile,
                      image,
                      loading: image.status == "loading",
                      onCallAction: (data) => callAction(data)
                    }, null, 8, ["id", "isShowFileName", "isOneFile", "image", "loading", "class", "onCallAction"])) : createCommentVNode("", true)
                  ];
                }
              }),
              footer: withCtx((_3, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(Upload, {
                    style: !props.isReadOnly ? null : { display: "none" },
                    buttonTitle: props.item.buttonName,
                    isMultiple: props.isMultiple,
                    isIcon: props.isIcon,
                    buttonIcon: props.isIcon ? localImages.value[0] : null,
                    onCallAction: (data) => callAction(data)
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    withDirectives(createVNode(Upload, {
                      buttonTitle: props.item.buttonName,
                      isMultiple: props.isMultiple,
                      isIcon: props.isIcon,
                      buttonIcon: props.isIcon ? localImages.value[0] : null,
                      onCallAction: (data) => callAction(data)
                    }, null, 8, ["buttonTitle", "isMultiple", "isIcon", "buttonIcon", "onCallAction"]), [
                      [vShow, !props.isReadOnly]
                    ])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            if (props.isOneFile && props.isReadOnly && localImages.value.length > 1) {
              _push2(`<div class="file-container__circle"${_scopeId}>${ssrInterpolate(localImages.value.length)}</div>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              (openBlock(), createBlock(unref(draggable), {
                tag: "div",
                modelValue: localImages.value,
                "onUpdate:modelValue": ($event) => localImages.value = $event,
                class: ["file__list file-list", props.isShowFileName ? "file-list_show-title" : ""],
                forceFallback: true,
                itemKey: "fileFields",
                key: props.item.key,
                disabled: props.isOneFile,
                handle: ".fancybox-item__link",
                draggable: props.isDraggable ? ".file-list__item:not(.file-list__item_undraggable)" : ".file-list__item-none",
                onEnd: (event) => callAction(event)
              }, {
                item: withCtx(({ element: image }) => [
                  !props.isIcon ? (openBlock(), createBlock(FansyBoxImage, {
                    key: 0,
                    class: ["file-list__item", image.status == "loading" ? "file-list__item_loading" : ""],
                    id: props.item.id,
                    isShowFileName: props.isShowFileName,
                    isOneFile: props.isOneFile,
                    image,
                    loading: image.status == "loading",
                    onCallAction: (data) => callAction(data)
                  }, null, 8, ["id", "isShowFileName", "isOneFile", "image", "loading", "class", "onCallAction"])) : createCommentVNode("", true)
                ]),
                footer: withCtx(() => [
                  withDirectives(createVNode(Upload, {
                    buttonTitle: props.item.buttonName,
                    isMultiple: props.isMultiple,
                    isIcon: props.isIcon,
                    buttonIcon: props.isIcon ? localImages.value[0] : null,
                    onCallAction: (data) => callAction(data)
                  }, null, 8, ["buttonTitle", "isMultiple", "isIcon", "buttonIcon", "onCallAction"]), [
                    [vShow, !props.isReadOnly]
                  ])
                ]),
                _: 1
              }, 8, ["modelValue", "onUpdate:modelValue", "class", "disabled", "draggable", "onEnd"])),
              props.isOneFile && props.isReadOnly && localImages.value.length > 1 ? (openBlock(), createBlock("div", {
                key: 0,
                class: "file-container__circle"
              }, toDisplayString(localImages.value.length), 1)) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/File/Field/Field.vue");
  return _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const FileField = _sfc_main$9;
const _sfc_main$8 = {
  __name: "File",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 1517,
        title: "Undefined title",
        type: "file",
        key: "",
        required: false,
        focus: true,
        value: null,
        buttonName: ""
      },
      type: Object
    },
    pageId: {
      default: null,
      type: Number
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isShowFileName: {
      default: false,
      type: Boolean
    },
    isMultiple: {
      default: false,
      type: Boolean
    },
    isOneFile: {
      default: false,
      type: Boolean
    },
    isIcon: {
      default: false,
      type: Boolean
    },
    isDraggable: {
      default: true,
      type: Boolean
    }
  },
  emits: [
    "changeValue",
    "initEdit"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: "file form-item__file",
        required: props.item.required
      }, _attrs), {
        default: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(FormLabel, {
              style: props.item.title != null && props.item.title != "" ? null : { display: "none" },
              title: props.item.title
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(FileField, {
              item: props.item,
              isReadOnly: props.isReadOnly,
              isShowFileName: props.isShowFileName,
              isMultiple: props.isMultiple,
              isOneFile: props.isOneFile,
              isIcon: props.isIcon,
              pageId: props.pageId,
              isDraggable: props.isDraggable,
              onChangeValue: (data) => emit("changeValue", data),
              onInitEdit: (event) => emit("initEdit", event)
            }, null, _parent2, _scopeId));
          } else {
            return [
              withDirectives(createVNode(FormLabel, {
                title: props.item.title
              }, null, 8, ["title"]), [
                [vShow, props.item.title != null && props.item.title != ""]
              ]),
              createVNode(FileField, {
                item: props.item,
                isReadOnly: props.isReadOnly,
                isShowFileName: props.isShowFileName,
                isMultiple: props.isMultiple,
                isOneFile: props.isOneFile,
                isIcon: props.isIcon,
                pageId: props.pageId,
                isDraggable: props.isDraggable,
                onChangeValue: (data) => emit("changeValue", data),
                onInitEdit: (event) => emit("initEdit", event)
              }, null, 8, ["item", "isReadOnly", "isShowFileName", "isMultiple", "isOneFile", "isIcon", "pageId", "isDraggable", "onChangeValue", "onInitEdit"])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/File/File.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const AppFile = _sfc_main$8;
const _sfc_main$7 = {
  __name: "StatusOption",
  __ssrInlineRender: true,
  props: {
    option: {
      default: {
        file: null,
        color: "#1f4fc6ff",
        text: "\u043D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E"
      },
      type: Object
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "status__option status-option" }, _attrs))}><div class="status-option__rect" style="${ssrRenderStyle(`--backgroundColor: ${props.option == null ? "#ccc" : props.option.color}`)}">`);
      if (props.option != null && ![null, void 0].includes(props.option.file) && props.option.file != "") {
        _push(`<img class="status-option__image"${ssrRenderAttr("src", props.option.file)}${ssrRenderAttr("alt", props.option.text)}>`);
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="status-option__text">${ssrInterpolate(props.option == null ? null : props.option.text)}</div></div>`);
    };
  }
};
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSelects/Status/StatusOption/StatusOption.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const StatusOption = _sfc_main$7;
const _sfc_main$6 = {
  __name: "ColorPicker",
  __ssrInlineRender: true,
  props: {
    color: {
      default: "#000",
      type: String
    },
    isCanSave: {
      default: false,
      type: Boolean
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: "form-item__colorpicker colorpicker",
        required: false
      }, _attrs), {
        default: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="colorpicker_preview" style="${ssrRenderStyle(`--previewColor: ${props.color}`)}"${_scopeId}></div>`);
            _push2(ssrRenderComponent(unref(ColorPicker$1), {
              "default-format": "hex",
              "visible-formats": ["hex"],
              color: ![null, void 0].includes(props.color) && props.color != "" ? props.color : "#000",
              onColorChange: (eventData) => _ctx.$emit("changeColor", eventData)
            }, null, _parent2, _scopeId));
            if (props.isCanSave) {
              _push2(ssrRenderComponent(AppButton, {
                class: "colorpicker__button button_blue",
                onClick: () => _ctx.$emit("saveHiddenColor", props.color)
              }, {
                default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` \u041F\u0440\u0438\u043C\u0435\u043D\u0438\u0442\u044C `);
                  } else {
                    return [
                      createTextVNode(" \u041F\u0440\u0438\u043C\u0435\u043D\u0438\u0442\u044C ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              createVNode("div", {
                class: "colorpicker_preview",
                style: `--previewColor: ${props.color}`
              }, null, 4),
              createVNode(unref(ColorPicker$1), {
                "default-format": "hex",
                "visible-formats": ["hex"],
                color: ![null, void 0].includes(props.color) && props.color != "" ? props.color : "#000",
                onColorChange: (eventData) => _ctx.$emit("changeColor", eventData)
              }, null, 8, ["color", "onColorChange"]),
              props.isCanSave ? (openBlock(), createBlock(AppButton, {
                key: 0,
                class: "colorpicker__button button_blue",
                onClick: () => _ctx.$emit("saveHiddenColor", props.color)
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u041F\u0440\u0438\u043C\u0435\u043D\u0438\u0442\u044C ")
                ]),
                _: 1
              }, 8, ["onClick"])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppColorPicker/ColorPicker.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const ColorPicker = _sfc_main$6;
const _sfc_main$5 = {
  __name: "StatusField",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        value: "",
        focus: false,
        required: false,
        title: "Undefined title",
        options: [{
          label: {
            id: 0,
            sort: 0,
            file: null,
            is_hidden: 0,
            field_id: null,
            color: "#000",
            text: ""
          },
          value: 0
        }]
      },
      type: Object
    },
    focus: {
      default: false,
      type: Boolean
    },
    isCanCreate: {
      default: false,
      type: Boolean
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isHaveNullOption: {
      default: false,
      type: Boolean
    },
    isCanSave: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "changeValue",
    "clickOutside"
  ],
  setup(__props, { emit: __emit }) {
    const popupRef = ref(null);
    let colorPicker = ref({
      state: false,
      color: "#b6b6b6"
    });
    let options = ref([]);
    let activeOption = ref(null);
    let visibileOptions = ref([]);
    const props = __props;
    const emit = __emit;
    const callActionColorPicker = (data) => {
      const changeColor = (color) => {
        colorPicker.value.color = color.cssColor;
      };
      const toggleColorPicker = (state) => {
        colorPicker.value.state = state;
        colorPicker.value.color = activeOption.value ? activeOption.value.color : "#b6b6b6";
        if (state) {
          popupRef.value.popupRef.classList.add("status__popup_colorpicker");
        } else {
          popupRef.value.popupRef.classList.remove("status__popup_colorpicker");
        }
      };
      const saveHiddenColor = () => {
        const createHiddenOption = () => {
          let dinamycId = (/* @__PURE__ */ new Date()).getTime();
          let hiddenOption = {
            label: {
              id: dinamycId,
              sort: 0,
              file: null,
              is_hidden: 1,
              field_id: props.item.id,
              color: colorPicker.value.color,
              text: null
            },
            is_new: true,
            value: dinamycId
          };
          options.value.push(hiddenOption);
          activeOption.value = hiddenOption.label;
          getHiddenOption();
        };
        const getHiddenOption = async () => {
        };
        createHiddenOption();
        toggleColorPicker(false);
        PopupScripts.hideDetails(popupRef.value.popupRef);
      };
      switch (data.action) {
        case "changeColor":
          changeColor(data.data);
          break;
        case "toggleColorPicker":
          toggleColorPicker(data.data);
          break;
        case "saveHiddenColor":
          saveHiddenColor();
          break;
      }
    };
    const changeValue = (option) => {
      activeOption.value = option == null ? null : option.label;
      PopupScripts.hideDetails(popupRef.value.popupRef);
      emit("changeValue", { key: props.item.key, value: option == null ? null : option.value });
    };
    const setActiveOption = () => {
      let findedOption = options.value == null ? null : options.value.find((option) => option.value == props.item.value);
      if ([null, void 0].includes(findedOption)) {
        if (options.value == null || options.value.length == 0) {
          activeOption.value = null;
        } else {
          activeOption.value = props.isHaveNullOption ? null : options.value[0].label;
        }
      } else {
        activeOption.value = findedOption.label;
      }
    };
    watch(() => props.focus, () => {
      setTimeout(() => {
        if (props.item.focus) {
          popupRef.value.popupRef.setAttribute("open", true);
          PopupScripts.setDropdownPosition(popupRef.value.popupRef);
        }
      }, 10);
    }, {
      deep: true
    });
    watch(() => props.item.options, () => {
      visibileOptions.value = props.item.options == null ? [] : props.item.options.filter((option) => option.label.is_hidden != 1);
    }, { deep: true });
    watch(() => props.item.value, () => {
      setActiveOption();
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppPopup, mergeProps({
        class: "status__popup",
        closeByClick: false,
        isCanSelect: true,
        ref_key: "popupRef",
        ref: popupRef,
        onClickOutside: () => {
          emit("clickOutside", true);
          callActionColorPicker({ action: "toggleColorPicker", data: false });
        },
        onClick: (event) => props.isReadOnly ? event.preventDefault() : null
      }, _attrs), {
        summary: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(StatusOption, {
              class: "status__preview",
              option: unref(activeOption)
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(StatusOption, {
                class: "status__preview",
                option: unref(activeOption)
              }, null, 8, ["option"])
            ];
          }
        }),
        content: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(PopupOption, {
              style: props.isHaveNullOption ? null : { display: "none" },
              onClick: () => changeValue(null)
            }, {
              default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E `);
                } else {
                  return [
                    createTextVNode(" \u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<!--[-->`);
            ssrRenderList(unref(visibileOptions), (option) => {
              _push2(ssrRenderComponent(PopupOption, {
                style: !unref(colorPicker).state ? null : { display: "none" },
                class: unref(activeOption) != null && unref(activeOption).id == option.value ? "popup__option_active" : "",
                onClick: () => changeValue(option)
              }, {
                default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(StatusOption, {
                      option: option.label
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(StatusOption, {
                        option: option.label
                      }, null, 8, ["option"])
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
            });
            _push2(`<!--]-->`);
            if (props.isCanCreate) {
              _push2(ssrRenderComponent(PopupOption, {
                class: ["popup-option__sublink", unref(colorPicker).state ? "popup-option__sublink_back" : ""],
                onClick: () => callActionColorPicker({ action: "toggleColorPicker", data: !unref(colorPicker).state })
              }, {
                default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` \u041F\u0430\u043B\u0438\u0442\u0440\u0430 \u0446\u0432\u0435\u0442\u043E\u0432 `);
                    _push3(ssrRenderComponent(IconArrow, null, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createTextVNode(" \u041F\u0430\u043B\u0438\u0442\u0440\u0430 \u0446\u0432\u0435\u0442\u043E\u0432 "),
                      createVNode(IconArrow)
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            if (unref(colorPicker).state) {
              _push2(ssrRenderComponent(PopupOption, { class: "popup__option_unhover" }, {
                default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(ColorPicker, {
                      isCanSave: props.isCanSave,
                      color: unref(colorPicker).color,
                      onChangeColor: (color) => callActionColorPicker({ action: "changeColor", data: color }),
                      onSaveHiddenColor: (color) => callActionColorPicker({ action: "saveHiddenColor", data: color })
                    }, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createVNode(ColorPicker, {
                        isCanSave: props.isCanSave,
                        color: unref(colorPicker).color,
                        onChangeColor: (color) => callActionColorPicker({ action: "changeColor", data: color }),
                        onSaveHiddenColor: (color) => callActionColorPicker({ action: "saveHiddenColor", data: color })
                      }, null, 8, ["isCanSave", "color", "onChangeColor", "onSaveHiddenColor"])
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              withDirectives(createVNode(PopupOption, {
                onClick: () => changeValue(null)
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E ")
                ]),
                _: 1
              }, 8, ["onClick"]), [
                [vShow, props.isHaveNullOption]
              ]),
              (openBlock(true), createBlock(Fragment, null, renderList(unref(visibileOptions), (option) => {
                return withDirectives((openBlock(), createBlock(PopupOption, {
                  class: unref(activeOption) != null && unref(activeOption).id == option.value ? "popup__option_active" : "",
                  onClick: () => changeValue(option)
                }, {
                  default: withCtx(() => [
                    createVNode(StatusOption, {
                      option: option.label
                    }, null, 8, ["option"])
                  ]),
                  _: 2
                }, 1032, ["class", "onClick"])), [
                  [vShow, !unref(colorPicker).state]
                ]);
              }), 256)),
              props.isCanCreate ? (openBlock(), createBlock(PopupOption, {
                key: 0,
                class: ["popup-option__sublink", unref(colorPicker).state ? "popup-option__sublink_back" : ""],
                onClick: () => callActionColorPicker({ action: "toggleColorPicker", data: !unref(colorPicker).state })
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u041F\u0430\u043B\u0438\u0442\u0440\u0430 \u0446\u0432\u0435\u0442\u043E\u0432 "),
                  createVNode(IconArrow)
                ]),
                _: 1
              }, 8, ["class", "onClick"])) : createCommentVNode("", true),
              unref(colorPicker).state ? (openBlock(), createBlock(PopupOption, {
                key: 1,
                class: "popup__option_unhover"
              }, {
                default: withCtx(() => [
                  createVNode(ColorPicker, {
                    isCanSave: props.isCanSave,
                    color: unref(colorPicker).color,
                    onChangeColor: (color) => callActionColorPicker({ action: "changeColor", data: color }),
                    onSaveHiddenColor: (color) => callActionColorPicker({ action: "saveHiddenColor", data: color })
                  }, null, 8, ["isCanSave", "color", "onChangeColor", "onSaveHiddenColor"])
                ]),
                _: 1
              })) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSelects/Status/StatusField/StatusField.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const StatusField = _sfc_main$5;
const _sfc_main$4 = {
  __name: "Status",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        value: "",
        focus: false,
        required: false,
        title: "Undefined title",
        options: [{
          label: {
            id: 0,
            sort: 0,
            file: null,
            is_hidden: 0,
            field_id: null,
            color: "#000",
            text: ""
          },
          value: 0
        }]
      },
      type: Object
    },
    isCanCreate: {
      default: false,
      type: Boolean
    },
    isCanSave: {
      default: false,
      type: Boolean
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isHaveNullOption: {
      default: false,
      type: Boolean
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: "form-item__status status",
        required: props.item.required,
        isReadOnly: props.isReadOnly
      }, _attrs), {
        default: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(FormLabel, {
              title: props.item.title
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(StatusField, {
              item: props.item,
              focus: props.item.focus,
              isReadOnly: props.isReadOnly,
              isCanCreate: props.isCanCreate,
              isCanSave: props.isCanSave,
              isHaveNullOption: props.isHaveNullOption,
              onChangeValue: (data) => _ctx.$emit("changeValue", data)
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(FormLabel, {
                title: props.item.title
              }, null, 8, ["title"]),
              createVNode(StatusField, {
                item: props.item,
                focus: props.item.focus,
                isReadOnly: props.isReadOnly,
                isCanCreate: props.isCanCreate,
                isCanSave: props.isCanSave,
                isHaveNullOption: props.isHaveNullOption,
                onChangeValue: (data) => _ctx.$emit("changeValue", data)
              }, null, 8, ["item", "focus", "isReadOnly", "isCanCreate", "isCanSave", "isHaveNullOption", "onChangeValue"])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSelects/Status/Status.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const AppStatus = _sfc_main$4;
const _sfc_main$3 = {
  __name: "TextareaField",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        value: "",
        type: "text",
        placeholder: "",
        title: "Undefined title"
      },
      type: Object
    },
    disabled: {
      default: false,
      type: Boolean
    },
    mask: {
      default: null,
      type: String
    },
    isUseEnter: {
      default: true,
      type: Boolean
    },
    isTableItem: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "changeValue"
  ],
  setup(__props, { emit: __emit }) {
    let mirrorText = ref("");
    let textareaRef = ref(null);
    const props = __props;
    watch(() => props.item.value, () => {
      if (!props.isUseEnter) {
        mirrorText.value = props.item.value != null ? String(props.item.value).replaceAll("\n", "") : props.item.value;
      } else {
        mirrorText.value = props.item.value;
      }
    });
    watch(() => props.item.focus, () => {
      if (props.item.focus) {
        textareaRef.value.focus();
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "textarea" }, _attrs))}><textarea type="text" autocomplete="off" autocorrect="off"${ssrIncludeBooleanAttr(props.disabled) ? " disabled" : ""}${ssrRenderAttr("placeholder", props.item.placeholder)}${ssrRenderAttr("id", `input_${props.item.id}`)}>${ssrInterpolate(unref(mirrorText))}</textarea><pre class="textarea__mirror">${ssrInterpolate(unref(mirrorText))}</pre></div>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Textarea/TextareaField/TextareaField.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const TextareaField = _sfc_main$3;
const _sfc_main$2 = {
  __name: "Textarea",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        title: "",
        value: "",
        type: "text",
        focus: false,
        substring: "",
        placeholder: ""
      },
      type: Object
    },
    disabled: {
      default: false,
      type: Boolean
    },
    mask: {
      default: null,
      type: String
    },
    isUseEnter: {
      default: true,
      type: Boolean
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isLink: {
      default: false,
      type: Boolean
    },
    isTableItem: {
      default: false,
      type: Boolean
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: ["form-item__textarea", [null, void 0].includes(props.item.value) || props.item.value == "" ? "form-item__textarea_empty" : ""],
        required: props.item.required
      }, _attrs), {
        default: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(FormLabel, {
              title: props.item.title,
              style: props.item.title != null && props.item.title != "" ? null : { display: "none" }
            }, null, _parent2, _scopeId));
            if (props.isReadOnly) {
              _push2(ssrRenderComponent(FormValue, {
                value: props.item.value,
                isHTML: true,
                isLink: props.isLink,
                link: props.item.external_link,
                substring: props.item.substring
              }, null, _parent2, _scopeId));
            } else {
              _push2(ssrRenderComponent(TextareaField, {
                item: props.item,
                mask: props.item.type == "number" ? "#######################" : props.mask,
                disabled: props.disabled,
                isUseEnter: props.isUseEnter,
                isTableItem: props.isTableItem,
                onFocus: (data) => _ctx.$emit("focus", data),
                onBlur: (data) => _ctx.$emit("blur", data),
                onChangeValue: (data) => _ctx.$emit("changeValue", data)
              }, null, _parent2, _scopeId));
            }
            if (![null, void 0].includes(props.item.substring) && props.item.substring != "") {
              _push2(`<span class="form-item__substring"${_scopeId}>${ssrInterpolate(props.item.substring)}</span>`);
            } else {
              _push2(`<!---->`);
            }
            if (props.item.key == "phone" && (_ctx.saveValueForCall != null && _ctx.saveValueForCall != "")) {
              _push2(`<a${ssrRenderAttr("href", `tel:${_ctx.saveValueForCall}`)} class="button-text button-text__action"${_scopeId}> \u041F\u043E\u0437\u0432\u043E\u043D\u0438\u0442\u044C </a>`);
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              withDirectives(createVNode(FormLabel, {
                title: props.item.title
              }, null, 8, ["title"]), [
                [vShow, props.item.title != null && props.item.title != ""]
              ]),
              props.isReadOnly ? (openBlock(), createBlock(FormValue, {
                key: 0,
                value: props.item.value,
                isHTML: true,
                isLink: props.isLink,
                link: props.item.external_link,
                substring: props.item.substring
              }, null, 8, ["value", "isLink", "link", "substring"])) : (openBlock(), createBlock(TextareaField, {
                key: 1,
                item: props.item,
                mask: props.item.type == "number" ? "#######################" : props.mask,
                disabled: props.disabled,
                isUseEnter: props.isUseEnter,
                isTableItem: props.isTableItem,
                onFocus: (data) => _ctx.$emit("focus", data),
                onBlur: (data) => _ctx.$emit("blur", data),
                onChangeValue: (data) => _ctx.$emit("changeValue", data)
              }, null, 8, ["item", "mask", "disabled", "isUseEnter", "isTableItem", "onFocus", "onBlur", "onChangeValue"])),
              ![null, void 0].includes(props.item.substring) && props.item.substring != "" ? (openBlock(), createBlock("span", {
                key: 2,
                class: "form-item__substring"
              }, toDisplayString(props.item.substring), 1)) : createCommentVNode("", true),
              props.item.key == "phone" && (_ctx.saveValueForCall != null && _ctx.saveValueForCall != "") ? (openBlock(), createBlock("a", {
                key: 3,
                href: `tel:${_ctx.saveValueForCall}`,
                class: "button-text button-text__action"
              }, " \u041F\u043E\u0437\u0432\u043E\u043D\u0438\u0442\u044C ", 8, ["href"])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Textarea/Textarea.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const AppTextarea = _sfc_main$2;
const _sfc_main$1 = {
  __name: "RelationItem",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        value: null,
        placeholder: null,
        focus: false,
        key: null,
        options: [],
        lockedOptions: []
      },
      type: Object
    },
    fieldId: {
      default: 0,
      type: Number
    },
    isCanCreate: {
      default: false,
      type: Boolean
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isAnotherTitle: {
      default: false,
      type: Boolean
    },
    isHaveLink: {
      default: true,
      type: Boolean
    }
  },
  emits: [
    "openLink",
    "callAction",
    "createOption",
    "clickOutside"
  ],
  setup(__props, { emit: __emit }) {
    let activeOption = ref(null);
    let localItem = ref(null);
    const loaderStatus = ref(false);
    const nullOption = {
      id: null,
      sort: 0,
      text: "\u041D\u0435 \u0432\u044B\u0431\u0440\u0430\u043D\u043E",
      color: "#a6b7d4",
      file: null
    };
    const props = __props;
    const emit = __emit;
    const callAction = (data) => {
      const setActiveOption = (value) => {
        let findedOption = localItem.value.options == null ? null : localItem.value.options.find((option) => option.value == value);
        if ([null, void 0].includes(findedOption)) {
          findedOption = props.item.options == null ? null : props.item.options.find((option) => option.value == value);
          if ([null, void 0].includes(findedOption)) {
            activeOption.value = nullOption;
            return nullOption;
          } else {
            activeOption.value = findedOption.label == void 0 ? nullOption : findedOption.label;
            localItem.value.options.push(findedOption);
            return findedOption;
          }
        } else {
          activeOption.value = findedOption.label;
          return findedOption;
        }
      };
      const getOptions = () => {
        const isEmpty = (obj) => {
          for (const prop in obj) {
            if (Object.hasOwn(obj, prop)) {
              return false;
            }
          }
          return true;
        };
        let options = props.item.options == null ? [] : props.item.options.filter((p) => p != null && typeof p == "object" && !Array.isArray(p) && !isEmpty(p)).sort((prev, next) => prev.label.sort - next.label.sort);
        return JSON.parse(JSON.stringify(options));
      };
      const changeValue = (value) => {
        let findedOption = setActiveOption(value);
        emit("callAction", { action: "changeValue", value: findedOption });
      };
      const searchOptions = async (value) => {
        try {
          loaderStatus.value = true;
          let request = await commonScripts$1.getInfoAutocomplete(value.value.toLowerCase(), props.fieldId);
          localItem.value.options = request;
        } catch (error) {
          console.log(error);
        } finally {
          loaderStatus.value = false;
        }
        if (props.isAnotherTitle) {
          emit("callAction", { action: "changeAnotherTitle", value: { key: props.item.anotherKey, value: value.value } });
        }
      };
      const openLink = () => {
        if (![null, void 0].includes(activeOption.value.id)) {
          emit("openLink", activeOption.value);
        } else {
          return;
        }
      };
      switch (data.action) {
        case "setActiveOption":
          setActiveOption(data.value);
          break;
        case "changeValue":
          changeValue(data.value);
          break;
        case "searchOptions":
          searchOptions(data.value);
          break;
        case "openLink":
          openLink(data.value);
          break;
        case "getOptions":
          return getOptions();
      }
    };
    watch(() => props.item.value, () => {
      localItem.value.value = JSON.parse(JSON.stringify(props.item.value));
      callAction({
        action: "setActiveOption",
        value: props.item.value
      });
    });
    watch(() => props.item.lockedOptions, () => {
      localItem.value.lockedOptions = props.item.lockedOptions;
    });
    return (_ctx, _push, _parent, _attrs) => {
      if (unref(localItem) != null) {
        _push(ssrRenderComponent(AppAutocomplete, mergeProps({
          class: ["relation__item", [[null, void 0].includes(unref(activeOption).id) ? "relation__item_empty" : "", !props.isHaveLink ? "relation__item_disabled" : ""]],
          item: unref(localItem),
          isReadOnly: props.isReadOnly,
          isCanCreate: props.isCanCreate,
          isShowId: true,
          loaderStatus: loaderStatus.value,
          anotherTitle: props.item.anotherTitle,
          isLink: ![null, void 0].includes(unref(activeOption).id),
          onClickOutside: () => emit("clickOutside", true),
          onCreateOption: (data) => emit("createOption", data),
          onOpenLink: () => callAction({ action: "openLink", value: unref(localItem) }),
          onChangeValue: (data) => callAction({ action: "changeValue", value: data.value }),
          onSearchOptions: (data) => callAction({ action: "searchOptions", value: data })
        }, _attrs), {
          icon: withCtx((_2, _push2, _parent2, _scopeId) => {
            if (_push2) {
              if (![null, void 0].includes(unref(activeOption)) && props.isHaveLink) {
                _push2(`<figure class="ibg relation__icon popup_prevent"${_scopeId}>`);
                if (![null, void 0].includes(unref(activeOption).file) && unref(activeOption).file != "") {
                  _push2(`<img${ssrRenderAttr("src", unref(activeOption).file)}${ssrRenderAttr("alt", unref(activeOption).text)}${_scopeId}>`);
                } else {
                  _push2(`<figcaption style="${ssrRenderStyle(`--backgroundColor: ${[null, void 0].includes(unref(activeOption).color) || unref(activeOption).color == "" ? "#a6b7d4" : unref(activeOption).color}`)}"${_scopeId}>${ssrInterpolate(String(unref(activeOption).text).substring(0, 1))}</figcaption>`);
                }
                _push2(`</figure>`);
              } else {
                _push2(`<!---->`);
              }
            } else {
              return [
                ![null, void 0].includes(unref(activeOption)) && props.isHaveLink ? (openBlock(), createBlock("figure", {
                  key: 0,
                  class: "ibg relation__icon popup_prevent",
                  onClick: (event) => {
                    event.preventDefault();
                    callAction({ action: "openLink", value: unref(localItem) });
                  }
                }, [
                  ![null, void 0].includes(unref(activeOption).file) && unref(activeOption).file != "" ? (openBlock(), createBlock("img", {
                    key: 0,
                    src: unref(activeOption).file,
                    alt: unref(activeOption).text
                  }, null, 8, ["src", "alt"])) : (openBlock(), createBlock("figcaption", {
                    key: 1,
                    style: `--backgroundColor: ${[null, void 0].includes(unref(activeOption).color) || unref(activeOption).color == "" ? "#a6b7d4" : unref(activeOption).color}`
                  }, toDisplayString(String(unref(activeOption).text).substring(0, 1)), 5))
                ], 8, ["onClick"])) : createCommentVNode("", true)
              ];
            }
          }),
          link: withCtx((_2, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(`<div class="relation__link popup_prevent"${_scopeId}></div>`);
            } else {
              return [
                createVNode("div", {
                  class: "relation__link popup_prevent",
                  onClick: (event) => {
                    event.preventDefault();
                    callAction({ action: "openLink", value: unref(localItem) });
                  }
                }, null, 8, ["onClick"])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSelects/Relation/RelationItem/RelationItem.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const RelationItem = _sfc_main$1;
const _sfc_main = {
  __name: "Relation",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        title: null,
        id: 0,
        required: false,
        value: {
          value: null,
          localOptions: []
        },
        placeholder: null,
        focus: false,
        key: null,
        options: [],
        lockedOptions: [],
        anotherTitle: null
      },
      type: Object
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isCanCreate: {
      default: true,
      type: Boolean
    },
    isMultiple: {
      default: true,
      type: Boolean
    },
    isAnotherTitle: {
      default: false,
      type: Boolean
    },
    loaderStatus: {
      default: false,
      type: Boolean
    },
    isHaveLink: {
      default: true,
      type: Boolean
    },
    isCanEdit: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "showAll",
    "openLink",
    "changeValue",
    "createOption",
    "clickOutside"
  ],
  setup(__props, { emit: __emit }) {
    let values = ref([]);
    let lockedOptions = ref([]);
    let localOptions = ref([]);
    let prevValue = ref(null);
    const props = __props;
    const emit = __emit;
    const callAction = (data, index) => {
      const changeValue = (value) => {
        var _a;
        if (props.loaderStatus === "save")
          return;
        prevValue.value = toRaw(values.value[index]);
        if (data.value.isNew) {
          values.value.push(null);
        } else {
          values.value[index] = (_a = value.value) != null ? _a : null;
          if (value.id != null) {
            localOptions.value.push(value);
          }
          localOptions.value = _.uniqBy(localOptions.value, (o) => {
            return o.value;
          }).filter((p) => values.value.includes(p.value));
          callAction({ action: "getOptions", value: true });
        }
        lockedOptions.value = Array.from(new Set(props.item.lockedOptions)).concat(values.value).filter((p) => p != prevValue.value);
        emit("changeValue", {
          key: props.item.key,
          value: {
            value: values.value,
            localOptions: localOptions.value,
            selectedOption: value.label,
            lockedOptions: lockedOptions.value
          }
        });
      };
      const showAll = () => {
        emit("showAll", {
          key: props.item.key,
          value: true
        });
      };
      const getValues = () => {
        if ([null, void 0].includes(props.item.value) || !Array.isArray(props.item.value.value) || props.item.value.value.length == 0) {
          values.value = [null];
        } else {
          let localValues = props.item.value.value;
          if (localValues.length == 0) {
            localValues = [null];
          }
          values.value = JSON.parse(JSON.stringify(localValues));
        }
        lockedOptions.value = Array.from(new Set(props.item.lockedOptions)).concat(values.value.filter((item) => item != null)).filter((p) => p != prevValue.value);
      };
      const getOptions = () => {
        let filteredLocalOptions = props.item.value != null && props.item.value.localOptions != null ? props.item.value.localOptions.filter((p) => ![null, void 0].includes(p) && typeof p == "object") : [];
        let options = JSON.parse(JSON.stringify(props.item.options.concat(filteredLocalOptions)));
        localOptions.value = _.uniqBy(options.filter((p) => p != null), (o) => {
          return o.value;
        });
      };
      const changeAnotherTitle = () => {
        emit("changeValue", data.value);
      };
      switch (data.action) {
        case "changeValue":
          changeValue(data.value);
          break;
        case "showAll":
          showAll();
          break;
        case "getValues":
          getValues();
          break;
        case "getOptions":
          getOptions();
          break;
        case "changeAnotherTitle":
          changeAnotherTitle();
          break;
      }
    };
    watch(() => props.item.value, () => {
      callAction({ action: "getValues", value: true });
    }, {
      deep: true
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: ["form-item__relation relation", unref(values).filter((p) => p != null).length == 0 ? "relation_empty" : ""],
        required: props.item.required
      }, _attrs), {
        default: withCtx((_2, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(FormLabel, {
              style: props.item.title != null && props.item.title != "" ? null : { display: "none" },
              title: props.item.title
            }, null, _parent2, _scopeId));
            _push2(`<div class="relation__list"${_scopeId}><!--[-->`);
            ssrRenderList(unref(values), (value, index) => {
              _push2(ssrRenderComponent(RelationItem, {
                class: unref(values).length > 5 && index < unref(values).length - 5 ? "relation__item_hidden" : "",
                item: {
                  id: index,
                  value,
                  placeholder: null,
                  focus: false,
                  key: props.item.key,
                  anotherKey: props.item.anotherKey,
                  options: unref(localOptions),
                  lockedOptions: unref(lockedOptions),
                  anotherTitle: props.item.anotherTitle,
                  related_table: props.item.related_table
                },
                fieldId: props.item.id,
                isReadOnly: props.isReadOnly,
                isHaveLink: props.isHaveLink,
                isCanCreate: props.isCanCreate,
                isAnotherTitle: props.isAnotherTitle,
                onOpenLink: (item) => props.isHaveLink ? emit("openLink", item) : null,
                onCallAction: (data) => callAction(data, index),
                onClickOutside: () => emit("clickOutside", true),
                onCreateOption: (data) => emit("createOption", data)
              }, null, _parent2, _scopeId));
            });
            _push2(`<!--]--></div><div class="relation__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(ButtonText, {
              style: unref(values).length >= 5 ? null : { display: "none" },
              onClick: () => callAction({ action: "showAll", value: true })
            }, {
              default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0412\u0441\u0435\u0433\u043E <span class="relation__actions-all"${_scopeId2}>${ssrInterpolate(unref(values).length)}</span>, \u043F\u043E\u0441\u043C\u043E\u0442\u0440\u0435\u0442\u044C \u0432\u0441\u0435 `);
                } else {
                  return [
                    createTextVNode(" \u0412\u0441\u0435\u0433\u043E "),
                    createVNode("span", { class: "relation__actions-all" }, toDisplayString(unref(values).length), 1),
                    createTextVNode(", \u043F\u043E\u0441\u043C\u043E\u0442\u0440\u0435\u0442\u044C \u0432\u0441\u0435 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            if (props.isCanEdit && props.isMultiple) {
              _push2(ssrRenderComponent(ButtonText, {
                onClick: () => callAction({ action: "changeValue", value: { value: null, isNew: true } })
              }, {
                default: withCtx((_3, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` + \u0414\u043E\u0431\u0430\u0432\u0438\u0442\u044C `);
                  } else {
                    return [
                      createTextVNode(" + \u0414\u043E\u0431\u0430\u0432\u0438\u0442\u044C ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
          } else {
            return [
              withDirectives(createVNode(FormLabel, {
                title: props.item.title
              }, null, 8, ["title"]), [
                [vShow, props.item.title != null && props.item.title != ""]
              ]),
              createVNode("div", { class: "relation__list" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(values), (value, index) => {
                  return openBlock(), createBlock(RelationItem, {
                    class: unref(values).length > 5 && index < unref(values).length - 5 ? "relation__item_hidden" : "",
                    item: {
                      id: index,
                      value,
                      placeholder: null,
                      focus: false,
                      key: props.item.key,
                      anotherKey: props.item.anotherKey,
                      options: unref(localOptions),
                      lockedOptions: unref(lockedOptions),
                      anotherTitle: props.item.anotherTitle,
                      related_table: props.item.related_table
                    },
                    fieldId: props.item.id,
                    isReadOnly: props.isReadOnly,
                    isHaveLink: props.isHaveLink,
                    isCanCreate: props.isCanCreate,
                    isAnotherTitle: props.isAnotherTitle,
                    onOpenLink: (item) => props.isHaveLink ? emit("openLink", item) : null,
                    onCallAction: (data) => callAction(data, index),
                    onClickOutside: () => emit("clickOutside", true),
                    onCreateOption: (data) => emit("createOption", data)
                  }, null, 8, ["class", "item", "fieldId", "isReadOnly", "isHaveLink", "isCanCreate", "isAnotherTitle", "onOpenLink", "onCallAction", "onClickOutside", "onCreateOption"]);
                }), 256))
              ]),
              createVNode("div", { class: "relation__actions" }, [
                withDirectives(createVNode(ButtonText, {
                  onClick: () => callAction({ action: "showAll", value: true })
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0412\u0441\u0435\u0433\u043E "),
                    createVNode("span", { class: "relation__actions-all" }, toDisplayString(unref(values).length), 1),
                    createTextVNode(", \u043F\u043E\u0441\u043C\u043E\u0442\u0440\u0435\u0442\u044C \u0432\u0441\u0435 ")
                  ]),
                  _: 1
                }, 8, ["onClick"]), [
                  [vShow, unref(values).length >= 5]
                ]),
                props.isCanEdit && props.isMultiple ? (openBlock(), createBlock(ButtonText, {
                  key: 0,
                  onClick: () => callAction({ action: "changeValue", value: { value: null, isNew: true } })
                }, {
                  default: withCtx(() => [
                    createTextVNode(" + \u0414\u043E\u0431\u0430\u0432\u0438\u0442\u044C ")
                  ]),
                  _: 1
                }, 8, ["onClick"])) : createCommentVNode("", true)
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSelects/Relation/Relation.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AppRelation = _sfc_main;
function ValidateField(field, value) {
  let error = {
    state: false,
    text: null
  };
  if (field.key == "email") {
    if (validateMailValue(value)) {
      return createError(error, "\u041D\u0435\u043A\u043E\u0440\u0440\u0435\u043A\u0442\u043D\u0430\u044F \u043F\u043E\u0447\u0442\u0430");
    }
  }
  if (field.required) {
    switch (field.type) {
      case "text":
        if (checkTextValue(field, value)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        break;
      case "relation":
        if (checkSelectValue(value)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        break;
      case "number":
        if (checkEmptyValue(value)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        break;
      case "date":
        if (checkEmptyValue(value)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        break;
      case "file":
        if (checkFileValue(value)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        break;
      case "select_dropdown":
        if (checkSelectValue(value)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        break;
      case "autocomplete":
        if (checkSelectValue(value)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        break;
      case "text_group":
        if (checkSubfieldsValue(field)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        break;
      case "address":
        if (checkingAddress(field, value, `map-autocomplete_${field.id}`)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        break;
      case "password":
        if (checkTextValue(field, value)) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u043E \u043A \u0437\u0430\u043F\u043E\u043B\u043D\u0435\u043D\u0438\u044E");
        }
        if (value.length < 8) {
          return createError(error, "\u041F\u043E\u043B\u0435 \u0434\u043E\u043B\u0436\u043D\u043E \u0441\u043E\u0434\u0435\u0440\u0436\u0430\u0442\u044C \u043D\u0435 \u043C\u0435\u043D\u0435\u0435 8 \u0441\u0438\u043C\u0432\u043E\u043B\u043E\u0432");
        }
        break;
    }
  }
  if (!checkEmptyValue(field.mask)) {
    if (validateField(field)) {
      return createError(error, `\u041D\u0435\u043A\u043E\u0440\u0440\u0435\u043A\u0442\u043D\u043E\u0435 \u0437\u043D\u0430\u0447\u0435\u043D\u0438\u0435, \u043F\u043E\u043B\u0435 \u0434\u043E\u043B\u0436\u043D\u043E \u0431\u044B\u0442\u044C \u0432 \u0444\u043E\u0440\u043C\u0430\u0442\u0435: ${field.mask.replaceAll("#", "7")}`);
    }
  }
  return error;
}
const createError = (error, text) => {
  error.state = true, error.text = text;
  return error;
};
const checkEmptyValue = (value) => {
  return [null, void 0, "Invalid Date"].includes(value) || String(value).trim() == "";
};
const checkTextValue = (field, value) => {
  if (field.is_external_link) {
    if (checkEmptyValue(value)) {
      return true;
    } else if (typeof value == "object" && checkEmptyValue(value.external_link) && checkEmptyValue(value.value)) {
      return true;
    }
  } else {
    if (checkEmptyValue(value)) {
      return true;
    } else if (typeof value == "object" && checkEmptyValue(value.value)) {
      return true;
    }
  }
  return false;
};
const validateMailValue = (value) => {
  if (value == null || value == "") {
    return false;
  } else if (/[а-яА-ЯЁё]/.test(typeof value == "object" ? value.value : value)) {
    return true;
  } else {
    return !/^(([^<>()\[\]\\.,;:\s@\"]+(\.[^<>()\[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(typeof value == "object" ? value.value : value);
  }
};
const checkFileValue = (value) => {
  return value == null || value.length == 0 || value.filter((p) => p != null && p != "").length == 0;
};
const checkSelectValue = (value) => {
  if (Array.isArray(value)) {
    return value.filter((option) => option != null).length == 0;
  } else {
    return checkEmptyValue(value);
  }
};
const checkSubfieldsValue = (field) => {
  if (field.subfields != void 0) {
    return !(field.subfields.filter((subfield) => !checkEmptyValue(subfield.value)).length == field.subfields.length);
  } else {
    return false;
  }
};
const validateField = (field, fieldValue) => {
  const createRegexpMask = (mask) => {
    let newReg = "^";
    mask = mask.replace(/[\s()\-+]/g, "");
    for (let symb of mask) {
      switch (symb) {
        case "#":
          newReg += `[0-9]{1}`;
          break;
        case "A":
          newReg += `[\u0430-\u044Fa-z\u0410-\u042FA-Z]{1}`;
          break;
        default:
          if (Number(symb) != NaN) {
            newReg += `[\\d]{1}`;
          }
          break;
      }
    }
    newReg += "$";
    return new RegExp(newReg);
  };
  const checkingMask = (value) => {
    {
      return false;
    }
  };
  createRegexpMask(field.mask);
  {
    return checkingMask();
  }
};
const checkingAddress = (field, value, elemId) => {
  let element = document.getElementById(elemId);
  let modal = document.querySelector(".modal-container");
  const checkAddress = (field2) => {
    if (value == null || typeof value == "string" || value.search == "")
      return true;
    if (value.search != void 0) {
      if (value.search != value.text) {
        return false;
      }
    }
    return true;
  };
  if (element != null) {
    let tableItem = element.closest(".table__item");
    if (!checkAddress()) {
      element.classList.add("field_error");
      if (modal != null) {
        modal.querySelectorAll(".modal__content")[0].scrollBy(0, element.getBoundingClientRect().top - 100);
      } else {
        tableItem.classList.add("table__item_clicked");
        window.scrollBy(0, element.getBoundingClientRect().top - 100);
      }
      setTimeout(() => {
        element.setAttribute("open", true);
      }, 10);
      return true;
    } else {
      element.classList.remove("field_error");
    }
  }
  return false;
};

export { AppRelation as A, FansyBox as F, IconDots as I, ValidateField as V, AppTextarea as a, AppStatus as b, AppDate as c, AppFile as d };
//# sourceMappingURL=Validate-398d291a.mjs.map
