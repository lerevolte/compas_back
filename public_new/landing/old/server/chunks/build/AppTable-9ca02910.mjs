import { useSSRContext, ref, provide, computed, watch, resolveComponent, mergeProps, unref, withCtx, createTextVNode, createVNode, openBlock, createBlock, createCommentVNode, renderSlot, withDirectives, vShow, inject, toDisplayString, isRef, Fragment, renderList, onUnmounted, nextTick, toRaw } from 'vue';
import { ssrRenderComponent, ssrRenderSlot, ssrRenderClass, ssrRenderAttrs, ssrInterpolate, ssrRenderList, ssrRenderStyle, ssrGetDirectiveProps, ssrRenderAttr } from 'vue/server-renderer';
import isEqual from 'lodash/isEqual.js';
import { I as IconDots, A as AppRelation, a as AppTextarea, b as AppStatus, d as AppFile, c as AppDate, V as ValidateField } from './Validate-398d291a.mjs';
import { d as defineStore, v as persistedState, b as api, q as commonScripts$1, n as navigateTo, w as useUserStore, k as AppButton, y as useCommonStore, g as AppSelect, G as PopupSave, i as AppPopup, P as PopupOption, H as IconSettings, t as IconArrow, J as IconDrag, z as AppCheckbox, F as FormItem, h as FormLabel, p as FormValue, K as IconDelete, l as PopupScripts, o as AppWarning$1, j as AppInput, I as IconTriangle, D as useRequestEvent, E as useRuntimeConfig, e as __nuxt_component_0 } from './server.mjs';
import throttle from 'lodash/throttle.js';
import draggable from 'vuedraggable';
import { clickOutSide } from '@mahdikhashan/vue3-click-outside';
import { A as AppMap$1 } from './Field-d36cf7e6.mjs';
import isEmpty from 'lodash/isEmpty.js';
import { B as ButtonText } from './ButtonText-edbdf3ac.mjs';
import { a as AppLoader } from './Input-3345b1b6.mjs';
import { F as getRequestURL, j as joinURL } from '../runtime.mjs';
import { A as AppSection } from './AppSection-1ea634ac.mjs';

function useRequestURL() {
  {
    const url = getRequestURL(useRequestEvent());
    url.pathname = joinURL(useRuntimeConfig().app.baseURL, url.pathname);
    return url;
  }
}
const resizeTable = {
  // Ресайз таблицы
  resizableGrid(table, fields) {
    const setListeners = (div) => {
      let info = {
        pageX: 0,
        curCol: void 0,
        nxtCol: 0,
        index: 0,
        curColWidth: 0,
        prevDown: null
      };
      if (div == null || div.getAttribute("setListener") === "true")
        return;
      div.addEventListener("mousedown", function(e) {
        const paddingDiff = (col) => {
          const getStyleVal = (elm, css) => {
            return window.getComputedStyle(elm, null).getPropertyValue(css);
          };
          if (getStyleVal(col, "box-sizing") == "border-box") {
            return 0;
          }
          let padLeft = getStyleVal(col, "padding-left");
          let padRight = getStyleVal(col, "padding-right");
          return parseInt(padLeft) + parseInt(padRight);
        };
        info.curCol = info.prevDown = e.target.closest(".table__item");
        info.index = [...table.querySelectorAll("thead .table__item")].findIndex((p) => p.getAttribute("data-key") == info.curCol.getAttribute("data-key"));
        info.nxtCol = info.curCol.nextElementSibling;
        info.pageX = e.pageX;
        table.classList.add("table_resizing");
        document.body.classList.add("body__unselected");
        let padding = paddingDiff(info.curCol);
        info.curCol.querySelector(".table-item__border").classList.add("table-item__border_changing");
        info.curColWidth = info.curCol.offsetWidth - padding;
      });
      div.addEventListener("mouseover", function(e) {
        let hoverElem = e.target.closest(".table__item_fixed");
        if (hoverElem != null) {
          hoverElem.classList.add("table__item_hover");
        }
      });
      document.addEventListener("mousemove", (e) => {
        const resizeCell = (e2, info2) => {
          if (info2.curCol) {
            let diffX = info2.curColWidth + e2.pageX - info2.pageX;
            if (diffX >= 40) {
              onMouseMoveThrottle(table, tableHeader, sectionBody, info2.curCol, diffX, info2);
            }
          }
        };
        resizeCell(e, info);
      });
      document.addEventListener("mouseup", (e) => {
        function clearCell() {
          if (info.curCol != void 0) {
            let borderBlock = info.curCol.querySelector(".table-item__border");
            borderBlock.classList.remove("table-item__border_changing");
            borderBlock.style.height = "";
            info.nxtCol != void 0 ? info.nxtCol.classList.remove("changeWidth") : "";
            info.curCol.classList.remove("changeWidth");
            info.curCol.classList.remove("table__item_resizing");
            document.body.classList.remove("body__cursor-style");
            info.prevDown.classList.remove("table__item_hover");
            info.prevDown = null;
            info.curCol = void 0;
            info.nxtCol = void 0;
            info.pageX = void 0;
            info.curColWidth = void 0;
            setTimeout(() => {
              table.classList.remove("table_resizing");
              document.body.classList.remove("body__unselected");
            }, 10);
          }
        }
        clearCell();
      });
      div.setAttribute("setListener", "true");
    };
    let tableHeaders = table ? table.querySelectorAll(".table__header") : null;
    let sectionBody = table ? table.parentNode : null;
    let tableHeader = table ? table.querySelector(".table__header") : null;
    if ([null, void 0].includes(tableHeaders))
      return;
    for (let localTableHeader of tableHeaders) {
      let row = localTableHeader.querySelector("tr");
      let cols = row ? row.children : void 0;
      if (!cols)
        return;
      this.setDefaultWidth(cols, fields);
      this.setCellsWidth(table);
      if (localTableHeader.offsetWidth <= sectionBody.offsetWidth) {
        setCellsWidthDefference(localTableHeader, sectionBody);
      }
      for (let i = 0; i < cols.length; i++) {
        let div = cols[i].querySelector(".table-item__border");
        setListeners(div);
      }
    }
  },
  // Установка изначального положения ячеек если ширина таблицы меньше секции
  setCellsWidth(table) {
    this.setStickyClass(table);
  },
  // Установка цвета у зафиксированного столбца
  setStickyClass(table) {
    let rows = table.querySelectorAll(".table__row");
    let fixedFields = [];
    let fieldPos = null;
    if (rows.length == 0)
      return;
    let scrolledArea = table.parentNode.scrollLeft;
    for (let row of rows) {
      fixedFields = row.querySelectorAll(".table__item_fixed:not(.table__item_hidden)");
      for (let cell of fixedFields) {
        fieldPos = cell.getBoundingClientRect().left - table.parentNode.getBoundingClientRect().left;
        if (scrolledArea != 0 && fieldPos == cell.style.getPropertyValue("--fixTarget").replace("px", "")) {
          cell.classList.add("table__item_sticky");
        } else {
          cell.classList.remove("table__item_sticky");
        }
      }
    }
  },
  // Установить значения ширин при импорте
  setDefaultWidth(cells, fields) {
    let width = null;
    for (let cell of cells) {
      width = fields.find((p) => p.key == cell.getAttribute("data-key")).width;
      setCellWidth(cell, width);
      setTimeout(() => {
        setVisibleTitle(cell);
      }, 100);
    }
  }
};
const onMouseMoveThrottle = throttle(async function(table, tableHeader, sectionBody, cell, width, info) {
  setCellWidth(cell, width);
  setVisibleTitle(cell);
  let data = table.querySelectorAll("tbody .table__row");
  let rowFields = [];
  data.forEach((row) => {
    rowFields = row.querySelectorAll(".table__item");
    setCellWidth([...rowFields][info.index], width);
  });
  if (!cell.classList.contains("changeWidth")) {
    cell.classList.add("changeWidth");
    cell.classList.add("table__item_resizing");
  }
}, 10);
const setVisibleTitle = (cell) => {
  var _a;
  let span = (_a = cell.querySelector("span")) != null ? _a : cell.querySelector(".form-item__title");
  let parentItem = span.closest(".table-item__content");
  if (!cell.classList.contains("table__item_hidden")) {
    if (cell.offsetWidth <= 55) {
      span.style.display = "none";
      if (cell.querySelector(".form-item__checkbox")) {
        parentItem.style.setProperty("justify-content", "space-between");
      } else {
        parentItem.style.setProperty("justify-content", "end");
      }
    } else {
      if (cell.closest(".table__item_required")) {
        span.style.display = "inline";
      } else {
        span.style.display = "flex";
      }
      parentItem.style.setProperty("justify-content", "space-between");
    }
  }
};
const setCellsWidthDefference = (tableHeader, sectionBody) => {
  let changingCell = tableHeader.querySelector("th.changeWidth");
  let cells = tableHeader.querySelectorAll("th:not(.changeWidth):not(.table__item_hidden)");
  let widthData = null;
  const getProportional\u0421ells = (cells2, tableHeader2, sectionBody2) => {
    let widthData2 = [];
    for (let cell of cells2) {
      let part = getWidthPercent(cell);
      let width = getPartWidth(part.toFixed(2));
      widthData2.push(width);
    }
    return widthData2;
    function getWidthPercent(cell) {
      return cell.offsetWidth * 100 / (tableHeader2.offsetWidth + 5);
    }
    function getPartWidth(part) {
      return sectionBody2.offsetWidth * part / 100;
    }
  };
  const updateWidth = (changingCell2, array, sectionBody2) => {
    let summaryWidth = changingCell2 == null ? 0 : changingCell2.offsetWidth;
    for (let i = 0; i <= array.length - 1; i++) {
      summaryWidth += array[i];
    }
    if (summaryWidth < sectionBody2.offsetWidth) {
      array[array.length - 1] += sectionBody2.offsetWidth - summaryWidth;
    }
    return array;
  };
  widthData = getProportional\u0421ells(cells, tableHeader, sectionBody);
  widthData = updateWidth(changingCell, widthData, sectionBody);
  for (let i = 0; i <= cells.length - 1; i++) {
    setCellWidth(cells[i], widthData[i]);
    setTimeout(() => {
      setVisibleTitle(cells[i]);
    }, 10);
  }
};
const setCellWidth = (cell, width) => {
  if (typeof width == "string") {
    width = Number(width.replace("px", ""));
  }
  cell.style.setProperty("--defaultWidth", `${width.toFixed(2)}px`);
};
const scripts = {
  // Установка полей для сортировке в мобильной версии
  setMobileSortOptions(fields) {
    return fields.map((field) => {
      return {
        label: field.title,
        value: field.key
      };
    }).filter((field) => !["isChoose", "actions", "iconDelete", "iconDrag"].includes(field.value));
  },
  // Сортировка таблицы
  sortTable(emit, sortItem, data) {
    let request = {
      key: String(data.value),
      order: data.value == sortItem.key ? sortItem.order == "asc" ? "desc" : "asc" : "desc"
    };
    emit("callAction", {
      action: "getTableData",
      value: request
    });
    return request;
  },
  // Изменение видимости колонок
  changeVisibleColumn(fields, menu, tableRef, data) {
    let findedOption = fields.find((option) => option.key == data.key);
    findedOption[menu.activeTab.tab] = data.value;
    menu.showSaves(true);
    nextTick(() => {
      let findedCell = tableRef ? tableRef.querySelector(`th[data-key="${data.key}"]`) : void 0;
      if (findedCell) {
        if (findedCell.offsetWidth <= 50) {
          findedCell.querySelector("span").style.display = "none";
        } else {
          findedCell.querySelector("span").style.display = "flex";
        }
      }
    }, 10);
    return fields;
  },
  // Скачивание Экселя
  downloadExcel(emit, fields) {
    emit("callAction", {
      action: "downloadExcel",
      value: fields.filter((field) => field.enabled).map((field) => {
        return `fields[]=${field.key}`;
      })
    });
  },
  // Сохранение настроек
  saveSettings(emit, tableRef, fields, sortItem, menu, role) {
    menu.showSaves(false);
    let tableFields = tableRef ? tableRef.querySelectorAll(".table__header:not(.table__header_copy) .table__item:not(.table__item_hidden)") : void 0;
    if (tableRef) {
      tableFields.forEach((element) => {
        let findedField = fields.find((p) => p.key == element.getAttribute("data-key"));
        findedField.width = `${element.offsetWidth}px`;
      });
    }
    emit("callAction", {
      action: "saveFields",
      value: {
        sortItem,
        role,
        fields
      }
    });
  },
  // Установка колонок после перетаскивания
  setColumnAfterDragEnd(fields, tableRef, menu, value) {
    fields = value.to.__draggable_component__.modelValue;
    let row = tableRef.querySelector("tr");
    let cols = row ? row.children : void 0;
    resizeTable.setDefaultWidth(cols, fields);
    menu.showSaves(true);
    document.querySelectorAll("#clone-elem").forEach((element) => {
      element.remove();
    });
    return fields;
  }
};
const _sfc_main$J = {
  __name: "Top",
  __ssrInlineRender: true,
  props: {
    tableTitle: {
      default: null,
      type: String
    },
    permissions: {
      default: {},
      type: Object
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    let options = ref([]);
    const draggableRef = ref(null);
    const menu = inject("menu");
    const fields = inject("fields");
    const sortItem = inject("sortItem");
    const tableRef = inject("tableRef");
    const is_admin = inject("is_admin");
    const isMobile = inject("isMobile");
    const emit = __emit;
    const props = __props;
    const changeTab = (tab) => {
      setTimeout(() => {
        menu.value.changeTab(tab);
      }, 10);
    };
    const tabAction = (event, tab) => {
      PopupScripts.hideDetails(event.target.closest(".table-top__item_settings"));
      emit("callAction", {
        action: tab.action,
        value: null
      });
    };
    watch(() => fields.value, async () => {
      options.value = await scripts.setMobileSortOptions(fields.value);
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "table-template__header table-top" }, _attrs))}>`);
      ssrRenderSlot(_ctx.$slots, "top", {}, null, _push, _parent);
      _push(`<div class="table__title">${ssrInterpolate(props.tableTitle)}</div><div class="table-top__actions">`);
      if (unref(isMobile)) {
        _push(ssrRenderComponent(AppSelect, {
          class: ["table-top__item table-top__select", unref(sortItem).order == "asc" ? "table-top__select_up" : ""],
          item: {
            id: 0,
            key: "sortTable",
            value: unref(sortItem).key,
            focus: false,
            required: false,
            title: null,
            lockedOptions: [],
            options: unref(options)
          },
          isFiltered: false,
          isMultiple: false,
          isReadOnly: false,
          isHaveNullOption: false,
          onChangeValue: (data) => sortItem.value = unref(scripts).sortTable(emit, unref(sortItem), data)
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(PopupSave, {
        class: "table-top__item",
        style: ((_a = unref(menu)) == null ? void 0 : _a.saves.isShow) ? null : { display: "none" },
        onSaveSettings: (role) => unref(scripts).saveSettings(emit, unref(tableRef), unref(fields), unref(sortItem), unref(menu), role)
      }, null, _parent));
      if (unref(is_admin) || props.permissions.export_p != "N") {
        _push(ssrRenderComponent(AppPopup, {
          isCanSelect: false,
          class: "table-top__item table-top__item_excel",
          closeByClick: true
        }, {
          summary: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(IconDots, null, null, _parent2, _scopeId));
            } else {
              return [
                createVNode(IconDots)
              ];
            }
          }),
          content: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(PopupOption, {
                onClick: () => unref(scripts).downloadExcel(emit, unref(fields))
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` \u0421\u043A\u0430\u0447\u0430\u0442\u044C Excel `);
                  } else {
                    return [
                      createTextVNode(" \u0421\u043A\u0430\u0447\u0430\u0442\u044C Excel ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(PopupOption, {
                  onClick: () => unref(scripts).downloadExcel(emit, unref(fields))
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0421\u043A\u0430\u0447\u0430\u0442\u044C Excel ")
                  ]),
                  _: 1
                }, 8, ["onClick"])
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(AppPopup, {
        isCanSelect: false,
        class: "table-top__item table-top__item_settings",
        closeByClick: false,
        onClickOutside: () => changeTab(null)
      }, {
        summary: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(IconSettings, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(IconSettings)
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a2, _b, _c, _d;
          if (_push2) {
            if (((_a2 = unref(menu)) == null ? void 0 : _a2.activeTab) == null) {
              _push2(`<!--[-->`);
              ssrRenderList((_b = unref(menu)) == null ? void 0 : _b.tabs, (tab) => {
                _push2(`<!--[-->`);
                if (tab.isAction) {
                  _push2(ssrRenderComponent(PopupOption, {
                    class: "popup-option__sublink",
                    onClick: (event) => tabAction(event, tab)
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(tab.title)}`);
                      } else {
                        return [
                          createTextVNode(toDisplayString(tab.title), 1)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                } else {
                  _push2(ssrRenderComponent(PopupOption, {
                    class: "popup-option__sublink",
                    onClick: () => changeTab(tab)
                  }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(`${ssrInterpolate(tab.title)} `);
                        _push3(ssrRenderComponent(IconArrow, null, null, _parent3, _scopeId2));
                      } else {
                        return [
                          createTextVNode(toDisplayString(tab.title) + " ", 1),
                          createVNode(IconArrow)
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                }
                _push2(`<!--]-->`);
              });
              _push2(`<!--]-->`);
            } else {
              _push2(`<!--[-->`);
              _push2(ssrRenderComponent(PopupOption, {
                class: "popup-option__sublink popup-option__sublink_back",
                onClick: () => changeTab(null)
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(unref(menu).activeTab.title)} `);
                    _push3(ssrRenderComponent(IconArrow, null, null, _parent3, _scopeId2));
                  } else {
                    return [
                      createTextVNode(toDisplayString(unref(menu).activeTab.title) + " ", 1),
                      createVNode(IconArrow)
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
              if (unref(menu).activeTab.tab == "order") {
                _push2(ssrRenderComponent(unref(draggable), {
                  ref_key: "draggableRef",
                  ref: draggableRef,
                  class: "popup-option__draggable",
                  group: "table-top__item",
                  itemKey: "table-top__item",
                  modelValue: unref(fields),
                  "onUpdate:modelValue": ($event) => isRef(fields) ? fields.value = $event : null,
                  handle: ".icon__draggable",
                  onEnd: (event) => unref(fields).value = unref(scripts).setColumnAfterDragEnd(unref(fields), unref(tableRef), unref(menu), event),
                  onStart: (event) => unref(draggableRef).targetDomElement.classList.add("popup-option__draggable_dragging")
                }, {
                  item: withCtx(({ element: option }, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(ssrRenderComponent(PopupOption, {
                        class: "popup-option__sublink",
                        style: option.enabled ? null : { display: "none" },
                        onDragstart: (event) => {
                          var _a3;
                          return (_a3 = unref(commonScripts$1)) == null ? void 0 : _a3.cloningDraggableComponent(event, ["popup-option__draggable"]);
                        }
                      }, {
                        default: withCtx((_2, _push4, _parent4, _scopeId3) => {
                          if (_push4) {
                            _push4(ssrRenderComponent(IconDrag, null, null, _parent4, _scopeId3));
                            _push4(` ${ssrInterpolate(option.title)}`);
                          } else {
                            return [
                              createVNode(IconDrag),
                              createTextVNode(" " + toDisplayString(option.title), 1)
                            ];
                          }
                        }),
                        _: 2
                      }, _parent3, _scopeId2));
                    } else {
                      return [
                        withDirectives(createVNode(PopupOption, {
                          class: "popup-option__sublink",
                          onDragstart: (event) => {
                            var _a3;
                            return (_a3 = unref(commonScripts$1)) == null ? void 0 : _a3.cloningDraggableComponent(event, ["popup-option__draggable"]);
                          }
                        }, {
                          default: withCtx(() => [
                            createVNode(IconDrag),
                            createTextVNode(" " + toDisplayString(option.title), 1)
                          ]),
                          _: 2
                        }, 1032, ["onDragstart"]), [
                          [vShow, option.enabled]
                        ])
                      ];
                    }
                  }),
                  _: 1
                }, _parent2, _scopeId));
              } else {
                _push2(`<!--[-->`);
                ssrRenderList(unref(menu).activeTab.tab == "enabled" ? unref(fields) : unref(fields).filter((p) => p.enabled), (option) => {
                  _push2(ssrRenderComponent(PopupOption, { class: "popup__option_checkbox" }, {
                    default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                      if (_push3) {
                        _push3(ssrRenderComponent(AppCheckbox, {
                          item: {
                            id: option.id,
                            title: option.title,
                            type: "checkbox",
                            disabled: false,
                            value: unref(menu).activeTab.tab == "enabled" ? option.enabled : option.fixed,
                            options: null,
                            key: option.key
                          },
                          onChangeValue: (data) => fields.value = unref(scripts).changeVisibleColumn(unref(fields), unref(menu), unref(tableRef), data)
                        }, null, _parent3, _scopeId2));
                      } else {
                        return [
                          createVNode(AppCheckbox, {
                            item: {
                              id: option.id,
                              title: option.title,
                              type: "checkbox",
                              disabled: false,
                              value: unref(menu).activeTab.tab == "enabled" ? option.enabled : option.fixed,
                              options: null,
                              key: option.key
                            },
                            onChangeValue: (data) => fields.value = unref(scripts).changeVisibleColumn(unref(fields), unref(menu), unref(tableRef), data)
                          }, null, 8, ["item", "onChangeValue"])
                        ];
                      }
                    }),
                    _: 2
                  }, _parent2, _scopeId));
                });
                _push2(`<!--]-->`);
              }
              _push2(`<!--]-->`);
            }
          } else {
            return [
              ((_c = unref(menu)) == null ? void 0 : _c.activeTab) == null ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList((_d = unref(menu)) == null ? void 0 : _d.tabs, (tab) => {
                return openBlock(), createBlock(Fragment, null, [
                  tab.isAction ? (openBlock(), createBlock(PopupOption, {
                    key: 0,
                    class: "popup-option__sublink",
                    onClick: (event) => tabAction(event, tab)
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(tab.title), 1)
                    ]),
                    _: 2
                  }, 1032, ["onClick"])) : (openBlock(), createBlock(PopupOption, {
                    key: 1,
                    class: "popup-option__sublink",
                    onClick: () => changeTab(tab)
                  }, {
                    default: withCtx(() => [
                      createTextVNode(toDisplayString(tab.title) + " ", 1),
                      createVNode(IconArrow)
                    ]),
                    _: 2
                  }, 1032, ["onClick"]))
                ], 64);
              }), 256)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                createVNode(PopupOption, {
                  class: "popup-option__sublink popup-option__sublink_back",
                  onClick: () => changeTab(null)
                }, {
                  default: withCtx(() => [
                    createTextVNode(toDisplayString(unref(menu).activeTab.title) + " ", 1),
                    createVNode(IconArrow)
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                unref(menu).activeTab.tab == "order" ? (openBlock(), createBlock(unref(draggable), {
                  key: 0,
                  ref_key: "draggableRef",
                  ref: draggableRef,
                  class: "popup-option__draggable",
                  group: "table-top__item",
                  itemKey: "table-top__item",
                  modelValue: unref(fields),
                  "onUpdate:modelValue": ($event) => isRef(fields) ? fields.value = $event : null,
                  handle: ".icon__draggable",
                  onEnd: (event) => unref(fields).value = unref(scripts).setColumnAfterDragEnd(unref(fields), unref(tableRef), unref(menu), event),
                  onStart: (event) => unref(draggableRef).targetDomElement.classList.add("popup-option__draggable_dragging")
                }, {
                  item: withCtx(({ element: option }) => [
                    withDirectives(createVNode(PopupOption, {
                      class: "popup-option__sublink",
                      onDragstart: (event) => {
                        var _a3;
                        return (_a3 = unref(commonScripts$1)) == null ? void 0 : _a3.cloningDraggableComponent(event, ["popup-option__draggable"]);
                      }
                    }, {
                      default: withCtx(() => [
                        createVNode(IconDrag),
                        createTextVNode(" " + toDisplayString(option.title), 1)
                      ]),
                      _: 2
                    }, 1032, ["onDragstart"]), [
                      [vShow, option.enabled]
                    ])
                  ]),
                  _: 1
                }, 8, ["modelValue", "onUpdate:modelValue", "onEnd", "onStart"])) : (openBlock(true), createBlock(Fragment, { key: 1 }, renderList(unref(menu).activeTab.tab == "enabled" ? unref(fields) : unref(fields).filter((p) => p.enabled), (option) => {
                  return openBlock(), createBlock(PopupOption, { class: "popup__option_checkbox" }, {
                    default: withCtx(() => [
                      createVNode(AppCheckbox, {
                        item: {
                          id: option.id,
                          title: option.title,
                          type: "checkbox",
                          disabled: false,
                          value: unref(menu).activeTab.tab == "enabled" ? option.enabled : option.fixed,
                          options: null,
                          key: option.key
                        },
                        onChangeValue: (data) => fields.value = unref(scripts).changeVisibleColumn(unref(fields), unref(menu), unref(tableRef), data)
                      }, null, 8, ["item", "onChangeValue"])
                    ]),
                    _: 2
                  }, 1024);
                }), 256))
              ], 64))
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup$J = _sfc_main$J.setup;
_sfc_main$J.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Top/Top.vue");
  return _sfc_setup$J ? _sfc_setup$J(props, ctx) : void 0;
};
const TableTop = _sfc_main$J;
const view = [
  {
    id: 0,
    title: "\u041E\u0442\u043A\u0440\u044B\u0442\u044C",
    "class": "",
    action: "showModal"
  },
  {
    id: 1,
    title: "\u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "",
    action: "edit"
  },
  {
    id: 3,
    title: "\u0421\u043A\u043E\u043F\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "action_copy",
    action: "copyRow"
  },
  {
    id: 2,
    title: "\u0423\u0434\u0430\u043B\u0438\u0442\u044C",
    "class": "action_red",
    action: "initDelete"
  }
];
const portal = [
  {
    id: 0,
    title: "\u041E\u0442\u043A\u0440\u044B\u0442\u044C",
    "class": "",
    action: "showModal"
  },
  {
    id: 1,
    title: "\u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "",
    action: "edit"
  },
  {
    id: 3,
    title: "\u0421\u043A\u043E\u043F\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "action_copy",
    action: "copyRow"
  },
  {
    id: 3,
    title: "\u0410\u0432\u0442\u043E\u0440\u0438\u0437\u0438\u0440\u043E\u0432\u0430\u0442\u044C\u0441\u044F \u043A\u0430\u043A \u0430\u0434\u043C\u0438\u043D\u0438\u0441\u0442\u0440\u0430\u0442\u043E\u0440",
    "class": "action_auth",
    action: "authPortal"
  },
  {
    id: 2,
    title: "\u0423\u0434\u0430\u043B\u0438\u0442\u044C",
    "class": "action_red",
    action: "initDelete"
  }
];
const viewUsers = [
  {
    id: 0,
    title: "\u041E\u0442\u043A\u0440\u044B\u0442\u044C",
    "class": "",
    action: "showModal"
  },
  {
    id: 1,
    title: "\u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "",
    action: "edit"
  },
  {
    id: 3,
    title: "\u0421\u043A\u043E\u043F\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "action_copy",
    action: "copyRow"
  },
  {
    id: 3,
    title: "\u0417\u0430\u0439\u0442\u0438 \u043F\u043E\u0434 \u043F\u043E\u043B\u044C\u0437\u043E\u0432\u0430\u0442\u0435\u043B\u0435\u043C",
    "class": "",
    action: "authUser"
  },
  {
    id: 2,
    title: "\u0423\u0434\u0430\u043B\u0438\u0442\u044C",
    "class": "action_red",
    action: "initDelete"
  }
];
const module = [
  {
    id: 0,
    title: "\u041E\u0442\u043A\u0440\u044B\u0442\u044C",
    "class": "",
    action: "showModal"
  },
  {
    id: 1,
    title: "\u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "",
    action: "edit"
  },
  {
    id: 3,
    title: "\u0421\u043A\u043E\u043F\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "action_copy",
    action: "copyRow"
  },
  {
    id: 2,
    title: "\u041E\u0442\u0432\u044F\u0437\u0430\u0442\u044C",
    "class": "",
    action: "untieRow"
  }
];
const restore = [
  {
    id: 0,
    title: "\u041E\u0442\u043A\u0440\u044B\u0442\u044C",
    "class": "",
    action: "showModal"
  },
  {
    id: 1,
    title: "\u0412\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u0438\u0442\u044C",
    "class": "action_copy",
    action: "initRestore"
  }
];
const edit = [
  {
    id: 0,
    title: "\u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C",
    "class": "",
    action: "save"
  },
  {
    id: 1,
    title: "\u041E\u0442\u043C\u0435\u043D\u0430",
    "class": "",
    action: "cancel"
  },
  {
    id: 2,
    title: "\u0423\u0434\u0430\u043B\u0438\u0442\u044C",
    "class": "action_red",
    action: "initDelete"
  }
];
const routes = [
  {
    id: 0,
    title: "\u041E\u0442\u043A\u0440\u044B\u0442\u044C",
    "class": "",
    action: "showModal"
  },
  {
    id: 1,
    title: "\u0421\u043C\u0435\u043D\u0438\u0442\u044C \u043C\u0430\u0448\u0438\u043D\u0443",
    "class": "",
    action: "changeCar"
  },
  {
    id: 2,
    title: "\u0412\u043D\u0435\u0448\u043D\u044F\u044F \u0441\u0441\u044B\u043B\u043A\u0430",
    "class": "",
    action: "copyRow"
  },
  {
    id: 4,
    title: "\u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "",
    action: "edit"
  },
  {
    id: 5,
    title: "\u0423\u0434\u0430\u043B\u0438\u0442\u044C",
    "class": "action_red",
    action: "initDelete"
  }
];
const tasksOfRoutes = [
  {
    id: 0,
    title: "\u0421\u0438\u043D\u0445\u0440\u043E\u043D\u0438\u0437\u0438\u0440\u043E\u0432\u0430\u0442\u044C \u0437\u0430\u043A\u0430\u0437",
    "class": "",
    action: "syncOrder"
  },
  {
    id: 1,
    title: "\u0420\u0430\u0437\u0434\u0435\u043B\u0438\u0442\u044C \u0437\u0430\u043A\u0430\u0437",
    "class": "",
    action: "shareOrder"
  },
  {
    id: 2,
    title: "\u041E\u0442\u043A\u0440\u044B\u0442\u044C",
    "class": "",
    action: "showModal"
  },
  {
    id: 3,
    title: "\u0421\u043A\u043E\u043F\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "action_copy",
    action: "copyRow"
  },
  {
    id: 4,
    title: "\u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C",
    "class": "",
    action: "edit"
  },
  {
    id: 5,
    title: "\u0423\u0434\u0430\u043B\u0438\u0442\u044C",
    "class": "action_red",
    action: "initDelete"
  }
];
const actions = {
  view,
  portal,
  viewUsers,
  module,
  restore,
  edit,
  routes,
  tasksOfRoutes
};
const _sfc_main$I = {
  __name: "Actions",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        title: "\u0414\u0435\u0439\u0441\u0442\u0432\u0438\u0435",
        slug: "views"
      },
      type: Object
    },
    disabled: {
      default: false,
      type: Boolean
    },
    objId: {
      default: null,
      type: Number
    },
    permissions: {
      default: {},
      type: Object
    },
    userID: {
      default: null,
      type: Number
    },
    is_admin: {
      default: null,
      type: Boolean
    },
    relationID: {
      default: null
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    let menu = ref({
      activeTab: null
    });
    const popupRef = ref(null);
    const props = __props;
    const emit = __emit;
    const callAction = (data) => {
      if (data.action == "changeTab") {
        setTimeout(() => {
          menu.value.activeTab = data.value;
        }, 10);
      } else if (data.action == "callAction") {
        popupRef.value.popupRef.removeAttribute("open");
        menu.value.activeTab = null;
        emit("callAction", {
          action: data.action,
          value: data.value
        });
      }
    };
    const setOptions = computed(() => {
      let localOptions = actions[props.item.slug];
      if (props.objId == props.userID) {
        localOptions = localOptions.filter((p) => p.action != "authUser");
      }
      if (props.is_admin) {
        return localOptions;
      } else {
        if (props.permissions.create_p == "Y") {
          localOptions = props.relationID == props.userID ? localOptions : localOptions.filter((p) => p.action != "copyRow");
        } else if (props.permissions.create_p == "N") {
          localOptions = localOptions.filter((p) => p.action != "copyRow");
        }
        if (props.permissions.read_p == "Y") {
          localOptions = props.relationID == props.userID ? localOptions : localOptions.filter((p) => p.action != "showModal");
        } else if (props.permissions.read_p == "N") {
          localOptions = localOptions.filter((p) => p.action != "showModal");
        }
        if (props.permissions.update_p == "Y") {
          localOptions = props.relationID == props.userID ? localOptions : localOptions.filter((p) => p.action != "edit");
        } else if (props.permissions.update_p == "N") {
          localOptions = localOptions.filter((p) => p.action != "edit");
        }
        if (props.permissions.delete_p == "Y") {
          localOptions = props.relationID == props.userID ? localOptions : localOptions.filter((p) => p.action != "initDelete");
        } else if (props.permissions.delete_p == "N") {
          localOptions = localOptions.filter((p) => p.action != "initDelete");
        }
        return localOptions;
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({ class: "form-item__action" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(FormLabel, {
              style: props.item.title != null && props.item.title != "" ? null : { display: "none" },
              title: props.item.title
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppPopup, {
              class: "popup_actions",
              ref_key: "popupRef",
              ref: popupRef,
              isCanSelect: false,
              closeByClick: false,
              onClick: (e) => props.disabled ? e.preventDefault() : null
            }, {
              summary: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(IconDots, null, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(IconDots)
                  ];
                }
              }),
              content: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  if (unref(menu).activeTab == null) {
                    _push3(`<!--[-->`);
                    ssrRenderList(unref(setOptions), (tab) => {
                      _push3(ssrRenderComponent(PopupOption, {
                        class: ["popup-option__sublink", tab.class],
                        onClick: () => callAction({ action: "callAction", value: tab.action })
                      }, {
                        default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                          if (_push4) {
                            _push4(`${ssrInterpolate(tab.title)}`);
                          } else {
                            return [
                              createTextVNode(toDisplayString(tab.title), 1)
                            ];
                          }
                        }),
                        _: 2
                      }, _parent3, _scopeId2));
                    });
                    _push3(`<!--]-->`);
                  } else {
                    _push3(`<!--[-->`);
                    _push3(ssrRenderComponent(PopupOption, {
                      class: "popup-option__sublink popup-option__sublink_back",
                      onClick: () => callAction({ action: "changeTab", value: null })
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(ssrRenderComponent(IconArrow, null, null, _parent4, _scopeId3));
                          _push4(` ${ssrInterpolate(unref(menu).activeTab.title)}`);
                        } else {
                          return [
                            createVNode(IconArrow),
                            createTextVNode(" " + toDisplayString(unref(menu).activeTab.title), 1)
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                    _push3(`<!--[-->`);
                    ssrRenderList(unref(menu).activeTab.children, (option) => {
                      _push3(ssrRenderComponent(PopupOption, {
                        onClick: () => callAction({ action: "callAction", value: option.action })
                      }, {
                        default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                          if (_push4) {
                            _push4(`${ssrInterpolate(option.title)}`);
                          } else {
                            return [
                              createTextVNode(toDisplayString(option.title), 1)
                            ];
                          }
                        }),
                        _: 2
                      }, _parent3, _scopeId2));
                    });
                    _push3(`<!--]--><!--]-->`);
                  }
                } else {
                  return [
                    unref(menu).activeTab == null ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(unref(setOptions), (tab) => {
                      return openBlock(), createBlock(PopupOption, {
                        class: ["popup-option__sublink", tab.class],
                        onClick: () => callAction({ action: "callAction", value: tab.action })
                      }, {
                        default: withCtx(() => [
                          createTextVNode(toDisplayString(tab.title), 1)
                        ]),
                        _: 2
                      }, 1032, ["class", "onClick"]);
                    }), 256)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                      createVNode(PopupOption, {
                        class: "popup-option__sublink popup-option__sublink_back",
                        onClick: () => callAction({ action: "changeTab", value: null })
                      }, {
                        default: withCtx(() => [
                          createVNode(IconArrow),
                          createTextVNode(" " + toDisplayString(unref(menu).activeTab.title), 1)
                        ]),
                        _: 1
                      }, 8, ["onClick"]),
                      (openBlock(true), createBlock(Fragment, null, renderList(unref(menu).activeTab.children, (option) => {
                        return openBlock(), createBlock(PopupOption, {
                          onClick: () => callAction({ action: "callAction", value: option.action })
                        }, {
                          default: withCtx(() => [
                            createTextVNode(toDisplayString(option.title), 1)
                          ]),
                          _: 2
                        }, 1032, ["onClick"]);
                      }), 256))
                    ], 64))
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              withDirectives(createVNode(FormLabel, {
                title: props.item.title
              }, null, 8, ["title"]), [
                [vShow, props.item.title != null && props.item.title != ""]
              ]),
              createVNode(AppPopup, {
                class: "popup_actions",
                ref_key: "popupRef",
                ref: popupRef,
                isCanSelect: false,
                closeByClick: false,
                onClick: (e) => props.disabled ? e.preventDefault() : null
              }, {
                summary: withCtx(() => [
                  createVNode(IconDots)
                ]),
                content: withCtx(() => [
                  unref(menu).activeTab == null ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(unref(setOptions), (tab) => {
                    return openBlock(), createBlock(PopupOption, {
                      class: ["popup-option__sublink", tab.class],
                      onClick: () => callAction({ action: "callAction", value: tab.action })
                    }, {
                      default: withCtx(() => [
                        createTextVNode(toDisplayString(tab.title), 1)
                      ]),
                      _: 2
                    }, 1032, ["class", "onClick"]);
                  }), 256)) : (openBlock(), createBlock(Fragment, { key: 1 }, [
                    createVNode(PopupOption, {
                      class: "popup-option__sublink popup-option__sublink_back",
                      onClick: () => callAction({ action: "changeTab", value: null })
                    }, {
                      default: withCtx(() => [
                        createVNode(IconArrow),
                        createTextVNode(" " + toDisplayString(unref(menu).activeTab.title), 1)
                      ]),
                      _: 1
                    }, 8, ["onClick"]),
                    (openBlock(true), createBlock(Fragment, null, renderList(unref(menu).activeTab.children, (option) => {
                      return openBlock(), createBlock(PopupOption, {
                        onClick: () => callAction({ action: "callAction", value: option.action })
                      }, {
                        default: withCtx(() => [
                          createTextVNode(toDisplayString(option.title), 1)
                        ]),
                        _: 2
                      }, 1032, ["onClick"]);
                    }), 256))
                  ], 64))
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
const _sfc_setup$I = _sfc_main$I.setup;
_sfc_main$I.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Body/Actions/Actions.vue");
  return _sfc_setup$I ? _sfc_setup$I(props, ctx) : void 0;
};
const AppActions = _sfc_main$I;
const _sfc_main$H = {
  __name: "Map",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        title: "\u0410\u0434\u0440\u0435\u0441",
        key: "address",
        required: false,
        value: {
          text: null,
          coords: []
        }
      },
      type: Object
    },
    isReadOnly: {
      default: false,
      type: Boolean
    },
    isShowMap: {
      default: false,
      type: Boolean
    },
    isCanSelect: {
      default: false,
      type: Boolean
    },
    isShowLabel: {
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
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: "address form-item__address",
        required: props.item.required
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(FormLabel, {
              style: props.isShowLabel && props.item.title != null && props.item.title != "" ? null : { display: "none" },
              title: props.item.title
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppMap$1, {
              item: props.item,
              isShowMap: props.isShowMap,
              isReadOnly: props.isReadOnly,
              isCanSelect: props.isCanSelect,
              onChangeValue: (data) => emit("changeValue", data)
            }, null, _parent2, _scopeId));
          } else {
            return [
              withDirectives(createVNode(FormLabel, {
                title: props.item.title
              }, null, 8, ["title"]), [
                [vShow, props.isShowLabel && props.item.title != null && props.item.title != ""]
              ]),
              createVNode(AppMap$1, {
                item: props.item,
                isShowMap: props.isShowMap,
                isReadOnly: props.isReadOnly,
                isCanSelect: props.isCanSelect,
                onChangeValue: (data) => emit("changeValue", data)
              }, null, 8, ["item", "isShowMap", "isReadOnly", "isCanSelect", "onChangeValue"])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$H = _sfc_main$H.setup;
_sfc_main$H.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppInputs/Map/Map.vue");
  return _sfc_setup$H ? _sfc_setup$H(props, ctx) : void 0;
};
const AppMap = _sfc_main$H;
const _sfc_main$G = {
  __name: "ButtonPayment",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        key: "",
        title: null,
        value: 0,
        state: false
      },
      type: Object
    }
  },
  emits: [
    "initPayment"
  ],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const props = __props;
    const setTitle = computed(() => {
      if (props.item.state == null) {
        return "";
      } else if (props.item && !props.item.state) {
        return `\u041E\u043F\u043B\u0430\u0442\u0438\u0442\u044C ${props.item.value} \u0440.`;
      } else {
        return `\u041E\u043F\u043B\u0430\u0447\u0435\u043D\u043E`;
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(FormItem, mergeProps({
        class: "form-item__payment",
        required: false
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (props.item) {
              _push2(ssrRenderComponent(FormLabel, {
                style: props.item.title != null && props.item.title != "" ? null : { display: "none" },
                title: props.item.title
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(`<div class="${ssrRenderClass([
              [null, void 0].includes(props.item) || [null, void 0].includes(props.item.value) || props.item.state ? "button-payment_disabled" : "",
              "button-payment"
            ])}"${_scopeId}>${ssrInterpolate(unref(setTitle))}</div>`);
          } else {
            return [
              props.item ? withDirectives((openBlock(), createBlock(FormLabel, {
                key: 0,
                title: props.item.title
              }, null, 8, ["title"])), [
                [vShow, props.item.title != null && props.item.title != ""]
              ]) : createCommentVNode("", true),
              createVNode("div", {
                class: [
                  "button-payment",
                  [null, void 0].includes(props.item) || [null, void 0].includes(props.item.value) || props.item.state ? "button-payment_disabled" : ""
                ],
                onClick: ($event) => emit("initPayment", {
                  value: props.item.value,
                  key: props.item.key
                })
              }, toDisplayString(unref(setTitle)), 11, ["onClick"])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$G = _sfc_main$G.setup;
_sfc_main$G.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppButton/ButtonPayment/ButtonPayment.vue");
  return _sfc_setup$G ? _sfc_setup$G(props, ctx) : void 0;
};
const ButtonPayment = _sfc_main$G;
const storeFilter = {
  // Получение полей в фильтре
  async getFilterFields(localStore, gettingData) {
    let data = [];
    for (let key in gettingData) {
      gettingData[key].key = key;
      data.push(gettingData[key]);
    }
    localStore.filter.fields = localStore.filter.backupFields = data;
  },
  // Поиск по фильтру
  async searchFilter(params, slug, store, isTab = false, type = "filter") {
    let requestParams = commonScripts$1.transformParams("paramsToAddress", params);
    if (isTab) {
      requestParams.tab = slug;
    }
    commonScripts$1.setURLParams({ ...requestParams });
    tableScripts.getData(null, slug, store, type);
  },
  // Создание фильтра
  async createFilter(data, slug, trashed = false, store) {
    let localStore = store.objects[slug];
    let response = await api.callMethod("POST", `filters/${slug}${trashed ? "?trashed=1" : ""}`, {
      title: data.title,
      fields: data.fields
    });
    data.id = response.id;
    localStore.filter.savedFields.push(data);
  },
  // Обновление фильтра
  async updateFilter(data, slug, trashed = false) {
    await api.callMethod("PUT", `filters/${slug}/${data.id}${trashed ? "?trashed=1" : ""}`, {
      title: data.title,
      fields: data.fields
    });
  },
  // Удалить фильтр
  async deleteFilter(data, slug, trashed = false) {
    await api.callMethod("DELETE", `filters/${slug}/${data}${trashed ? "?trashed=1" : ""}`);
  },
  // Перемещение фильтров
  async changeOrderFilters(data, slug, trashed = false) {
    await api.callMethod("POST", `filters/${slug}/change-sort${trashed ? "?trashed=1" : ""}`, {
      items: data
    });
  }
};
const useTableStore = defineStore("tableStore", {
  // states
  state: () => {
    return {
      objects: {}
    };
  },
  persist: {
    storage: persistedState.localStorage
  },
  // actions
  actions: {
    /*  Хранилища  */
    // Инициализация нового хранилища
    async initObjectStore(slug, isDetailsTab = false) {
      await this.addStore(slug, isDetailsTab);
    },
    // Добавление нового хранилища
    async addStore(slug, isDetailsTab = false) {
      this.objects[slug] = {
        // Таблица
        table: {
          // Таблица
          tableKeys: [],
          tableData: [],
          socketRows: {
            header: [],
            body: []
          },
          // Сортировка по ключу
          sortItem: {
            key: null,
            order: null
          },
          tableFooter: {
            pages: 1,
            activePage: 1,
            count: 25
          },
          permissions: {
            read_p: "Y",
            create_p: "Y",
            update_p: "Y",
            delete_p: "Y",
            export_p: "Y",
            import_p: "Y"
          },
          countRows: 0,
          restrictions: {
            count: 0
          },
          loaderState: false,
          // Свойства таблицы
          key: "table"
        },
        // Фильтр
        filter: {
          activeItem: null,
          input: "",
          fields: [],
          search: [],
          creatingItem: null,
          savedFields: [],
          backupFields: [],
          loading: false,
          buttonLoading: false
        },
        // Состояние и загрузка страницы
        state: null,
        loaderStatus: null,
        loaderButton: false,
        removingItem: null,
        showWarning: false,
        saveSettings: false,
        fromModal: {
          id: 0,
          status: null,
          isModal: false
        },
        // Категории
        categories: [],
        selectedCategory: 0,
        buttonActions: [],
        // Прочее
        tabs: [],
        isDetailsTab,
        slug,
        trash: false,
        title: null,
        headerTitle: null
      };
    },
    // Полное очищение хранилища
    clearAllStores() {
    },
    /*  Страница  */
    // Получение информации на странице
    async getData(slug, route) {
      await tableScripts.getData(route, slug, this);
    },
    /*  Фильтр  */
    // Поиск по фильтру
    searchFilter(params, slug) {
      storeFilter.searchFilter(params, slug, this, false);
    },
    // Создание нового фильтра
    createFilter(data, slug, trashed = false) {
      storeFilter.createFilter(data, slug, trashed, this);
    },
    // Обновление фильтра
    updateFilter(data, slug, trashed = false) {
      storeFilter.updateFilter(data, slug, trashed);
    },
    // Удаление фильтра
    deleteFilter(data, slug, trashed = false) {
      storeFilter.deleteFilter(data, slug, trashed);
    },
    // Сортировка сохраненных фильтров
    changeOrderFilters(data, slug, trashed = false) {
      storeFilter.changeOrderFilters(data, slug, trashed);
    }
  }
});
const tableScripts = {
  // Создание строки
  async createRow(slug) {
    const tableStore = useTableStore();
    let response = null;
    try {
      tableStore.objects[slug].loaderButton = true;
      response = await api.callMethod("POST", `objects/${slug}`, {});
    } catch (error) {
      commonScripts$1.showNotification({
        title: "\u041E\u0448\u0438\u0431\u043A\u0430 \u043F\u0440\u0438 \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u0438",
        description: `\u0412\u043E \u0432\u0440\u0435\u043C\u044F \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u044F \u043F\u0440\u043E\u0438\u0437\u043E\u0448\u043B\u0430 \u043E\u0448\u0438\u0431\u043A\u0430. \u041F\u043E\u0436\u0430\u043B\u0443\u0439\u0441\u0442\u0430, \u043F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.`
      }, "error");
      console.log(error);
    } finally {
      tableStore.objects[slug].loaderButton = false;
      return response;
    }
  },
  // Получение данных
  async getData(route, slug, store, type = "get") {
    let localStore = store.trash ? store : store.objects[slug];
    const setParams = () => {
      var _a, _b, _c, _d;
      let params = {
        filter: null,
        address: null
      };
      if (route && (isEmpty(route.query) || route.query.object)) {
        params.address = {
          q: "",
          page: (_a = localStore.table.tableFooter.activePage) != null ? _a : 1
        };
        if (route.query.sort_field) {
          params.address.sort_field = route.query.sort_field;
          params.address.sort_order = route.query.sort_order;
        }
      } else {
        params.address = commonScripts$1.transformParams("addressToAddress");
        params.filter = commonScripts$1.transformParams("addressToParams");
        delete params.address.tab;
        delete params.address.object;
        if (isEmpty(params.address)) {
          params.address = {
            q: "",
            sort_field: (_b = params.address.sort_field) != null ? _b : "id",
            sort_order: (_c = params.address.sort_order) != null ? _c : "desc",
            page: (_d = localStore.table.tableFooter.activePage) != null ? _d : 1
          };
        }
      }
      return params;
    };
    const setData = async (params) => {
      const updateFilter = async () => {
        await storeFilter.getFilterFields(localStore, gettingData.fields);
        localStore.filter.savedFields = gettingData.filters;
        localStore.filter.activeParams = params.filter;
      };
      const updateTable = () => {
        if (type == "get") {
          localStore.table.tableKeys = gettingData.table;
        }
        localStore.table.tableData = gettingData.list.data;
        localStore.table.tableFooter = {
          pages: gettingData.list.last_page,
          activePage: gettingData.list.current_page,
          count: gettingData.list.per_page
        };
      };
      const setOtherData = async () => {
        const commonStore = useCommonStore();
        commonStore.modalInfo = gettingData.entities;
        localStore.buttonActions = gettingData.list.buttons;
        localStore.tabs = gettingData.tabs;
        localStore.permissions = gettingData.permissions;
        localStore.categories = gettingData.categories.concat([
          {
            id: null,
            name: "\u0412\u0441\u0435 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u0438",
            is_permanent: true,
            children: []
          }
        ]);
        localStore.table.sortItem = {
          key: gettingData.list.sort_field,
          order: gettingData.list.sort_order
        };
        localStore.table.countRows = gettingData.list.count;
        if (localStore.trash && slug == "users") {
          let restrictUsers = await api.callMethod("GET", `objects/${slug}/compose?${new URLSearchParams(params.address).toString()}`);
          localStore.table.restrictions = {
            count: gettingData.list.restrictions == {} ? null : gettingData.list.restrictions.count - restrictUsers.list.data.length
          };
          console.log(localStore.table.restrictions);
        } else {
          localStore.table.restrictions = gettingData.list.restrictions == {} ? {
            count: null
          } : gettingData.list.restrictions;
        }
        if (localStore.trash) {
          localStore.tabs = gettingData.list.tabs;
        }
      };
      let gettingData = await api.callMethod("GET", `objects/${slug}/compose?${localStore.trash ? "trashed=1&" : ""}${new URLSearchParams(params.address).toString()}`);
      if (gettingData.status == 403) {
        return;
      } else {
        await updateFilter();
        updateTable();
        setOtherData();
      }
    };
    try {
      localStore.table.loaderState = type == "get" ? "loading" : "filtering";
      let params = setParams();
      await setData(params);
    } catch (error) {
      commonScripts$1.showNotification({
        title: "\u041E\u0448\u0438\u0431\u043A\u0430 \u043F\u0440\u0438 \u043F\u043E\u043B\u0443\u0447\u0435\u043D\u0438\u0438 \u0434\u0430\u043D\u043D\u044B\u0445",
        description: `\u0412\u043E \u0432\u0440\u0435\u043C\u044F \u043F\u043E\u043B\u0443\u0447\u0435\u043D\u0438\u044F \u0434\u0430\u043D\u043D\u044B\u0445 \u043F\u0440\u043E\u0438\u0437\u043E\u0448\u043B\u0430 \u043E\u0448\u0438\u0431\u043A\u0430. \u041F\u043E\u0436\u0430\u043B\u0443\u0439\u0441\u0442\u0430, \u043F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.`
      }, "error");
      console.log(error);
    } finally {
      localStore.table.loaderState = null;
    }
  },
  async getTabData(params, slug, localStore, type = "get") {
    const setData = async (params2) => {
      const updateTable = async () => {
        localStore.table.tableKeys = gettingData.table;
        localStore.table.tableData = gettingData.list.data;
        localStore.table.tableFooter = {
          pages: gettingData.list.last_page,
          activePage: gettingData.list.current_page,
          count: gettingData.list.per_page
        };
        localStore.table.sortItem = {
          key: gettingData.list.sort_field,
          order: gettingData.list.sort_order
        };
      };
      const filterOptions = `${transformParams(params2.fieldValue)}&${new URLSearchParams(params2.table).toString() !== "" ? new URLSearchParams(params2.table).toString() + "&" : ""}`;
      let gettingData = await api.callMethod("GET", `objects/${slug}/compose?${filterOptions}is_slug=true`);
      console.log(gettingData);
      if (gettingData.status != 403 && gettingData.status != 404 && gettingData.status != 500) {
        await updateTable();
      }
    };
    const transformParams = (params2) => {
      let request = [];
      params2 = params2.filter((p) => p != null);
      for (let param of params2) {
        request.push(`filter[id][]=${param}`);
      }
      request = request.join("&");
      return request;
    };
    try {
      localStore.table.loaderState = type == "get" ? "loading" : "filtering";
      await setData(params);
    } catch (error) {
      commonScripts$1.showNotification({
        title: "\u041E\u0448\u0438\u0431\u043A\u0430 \u043F\u0440\u0438 \u043F\u043E\u043B\u0443\u0447\u0435\u043D\u0438\u0438 \u0434\u0430\u043D\u043D\u044B\u0445",
        description: `\u0412\u043E \u0432\u0440\u0435\u043C\u044F \u043F\u043E\u043B\u0443\u0447\u0435\u043D\u0438\u044F \u0434\u0430\u043D\u043D\u044B\u0445 \u043F\u0440\u043E\u0438\u0437\u043E\u0448\u043B\u0430 \u043E\u0448\u0438\u0431\u043A\u0430. \u041F\u043E\u0436\u0430\u043B\u0443\u0439\u0441\u0442\u0430, \u043F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.`
      }, "error");
      console.log(error);
    } finally {
      localStore.table.loaderState = null;
    }
  },
  // Обновление данных в строках
  async saveRows(data, slug, isModal = false, localStore = null) {
    const tableStore = useTableStore();
    try {
      if (tableStore.objects[slug]) {
        tableStore.objects[slug].table.loaderState = "actionLoad";
      }
      if (isModal) {
        localStore.canChangeTab = false;
      }
      await api.callMethod("PUT", `objects/${slug}/batch`, {
        rows: data
      });
    } catch (error) {
      commonScripts$1.showNotification({
        title: "\u041E\u0448\u0438\u0431\u043A\u0430 \u043F\u0440\u0438 \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u0438",
        description: `\u0412\u043E \u0432\u0440\u0435\u043C\u044F \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u044F \u043F\u0440\u043E\u0438\u0437\u043E\u0448\u043B\u0430 \u043E\u0448\u0438\u0431\u043A\u0430. \u041F\u043E\u0436\u0430\u043B\u0443\u0439\u0441\u0442\u0430, \u043F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.`
      }, "error");
      console.log(error);
    } finally {
      if (tableStore.objects[slug]) {
        tableStore.objects[slug].table.loaderState = null;
      }
      if (isModal) {
        localStore.canChangeTab = true;
      }
    }
  },
  // Установка вотображаемого количества элементов в таблице
  async setVisibleElems(count, slug) {
    try {
      await api.callMethod("POST", `tables/${slug}/per_page`, {
        per_page: count
      });
    } catch (error) {
      commonScripts$1.showNotification({
        title: "\u041E\u0448\u0438\u0431\u043A\u0430 \u043F\u0440\u0438 \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u0438",
        description: `\u0412\u043E \u0432\u0440\u0435\u043C\u044F \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u044F \u043F\u0440\u043E\u0438\u0437\u043E\u0448\u043B\u0430 \u043E\u0448\u0438\u0431\u043A\u0430. \u041F\u043E\u0436\u0430\u043B\u0443\u0439\u0441\u0442\u0430, \u043F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.`
      }, "error");
      console.log(error);
    }
  },
  // Удаление строк
  async deleteRows(data, slug, route) {
    const tableStore = useTableStore();
    try {
      tableStore.objects[slug].table.loaderState = "actionLoad";
      await api.callMethod("DELETE", `objects/${slug}`, {
        ids: data
      });
      await this.getData(route, slug, tableStore);
    } catch (error) {
      commonScripts$1.showNotification({
        title: "\u041E\u0448\u0438\u0431\u043A\u0430 \u043F\u0440\u0438 \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u0438",
        description: `\u0412\u043E \u0432\u0440\u0435\u043C\u044F \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u044F \u043F\u0440\u043E\u0438\u0437\u043E\u0448\u043B\u0430 \u043E\u0448\u0438\u0431\u043A\u0430. \u041F\u043E\u0436\u0430\u043B\u0443\u0439\u0441\u0442\u0430, \u043F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.`
      }, "error");
      console.log(error);
    } finally {
      tableStore.objects[slug].table.loaderState = null;
    }
  },
  // Сохранение настроек колонок
  async saveFields(data, slug) {
    let requestSlug = "";
    switch (data.role) {
      case "all":
        requestSlug = `/${slug}/all`;
        break;
      case "myself":
        requestSlug = `/${slug}`;
        break;
      case "admin_fines":
        requestSlug = "/admin_fines";
        break;
      default:
        requestSlug = `/${slug}/${data.role}`;
        break;
    }
    await api.callMethod("POST", `tables${requestSlug}`, {
      fields: data.fields,
      sort_order: data.sortItem.order,
      sort_field: data.sortItem.key
    });
  },
  // Копирование строки
  async copyRow(id, slug) {
    let response = await api.callMethod("POST", `objects/${slug}/${id}/copy`, {});
    return response;
  },
  // Скачивание экселя
  async downloadExcel(options, slug) {
    let response = await api.callMethod("GET", `objects/${slug}/export${options}`);
    window.open(response.link, "_blank");
  },
  // Обновление таблицы продуктов
  async updateProducts(data, id, slug, localStore) {
    try {
      localStore.loaderState = "actionLoad";
      localStore.canChangeTab = false;
      await api.callMethod("PUT", `${slug}/${id}/set_products`, {
        products: data
      });
    } catch (error) {
      commonScripts$1.showNotification({
        title: "\u041E\u0448\u0438\u0431\u043A\u0430 \u043F\u0440\u0438 \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u0438",
        description: `\u0412\u043E \u0432\u0440\u0435\u043C\u044F \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u044F \u043F\u0440\u043E\u0438\u0437\u043E\u0448\u043B\u0430 \u043E\u0448\u0438\u0431\u043A\u0430. \u041F\u043E\u0436\u0430\u043B\u0443\u0439\u0441\u0442\u0430, \u043F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.`
      }, "error");
      console.log(error);
    } finally {
      localStore.loaderState = null;
      localStore.canChangeTab = true;
    }
  },
  async paymentFine(id) {
    try {
      let response = await api.callMethod("POST", `gibdd/pay/${id}`);
      if (response.error) {
        commonScripts$1.showNotification({
          title: response.error,
          description: ``
        }, "error");
        if (response.error == "\u041D\u0435\u0434\u043E\u0441\u0442\u0430\u0442\u043E\u0447\u043D\u043E \u0441\u0440\u0435\u0434\u0441\u0442\u0432 \u043D\u0430 \u0431\u0430\u043B\u0430\u043D\u0441\u0435") {
          navigateTo("/settings/?tab=tariffs");
        }
      }
    } catch (error) {
      commonScripts$1.showNotification({
        title: "\u041E\u0448\u0438\u0431\u043A\u0430 \u043F\u0440\u0438 \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u0438",
        description: `\u0412\u043E \u0432\u0440\u0435\u043C\u044F \u0441\u043E\u0445\u0440\u0430\u043D\u0435\u043D\u0438\u044F \u043F\u0440\u043E\u0438\u0437\u043E\u0448\u043B\u0430 \u043E\u0448\u0438\u0431\u043A\u0430. \u041F\u043E\u0436\u0430\u043B\u0443\u0439\u0441\u0442\u0430, \u043F\u043E\u043F\u0440\u043E\u0431\u0443\u0439\u0442\u0435 \u0435\u0449\u0451 \u0440\u0430\u0437.`
      }, "error");
    }
  },
  // Полное очищение глобального хранилища
  clearAllStores(store) {
  },
  // Авторизация под другим пользователем
  async authUser(id) {
    const userStore = useUserStore();
    let response = await api.callMethod("POST", `auth/${id}`);
    userStore.userToken = response.token;
    window.location.replace(response.url);
  },
  async authPortal(domen) {
    let response = await api.callMethod("POST", `auth_account/${domen}`);
    window.location.replace(`https://${domen}.compas.pro${response.url}/?token=${response.token}`);
  },
  // Проверка штрафов
  async checkFines() {
    let response = await api.callMethod("POST", `gibdd/check`);
    if (response.total > 0) {
      commonScripts$1.showNotification({
        title: "\u041E\u043F\u043E\u0432\u0435\u0449\u0435\u043D\u0438\u0435",
        description: `\u0412 \u0442\u0430\u0431\u043B\u0438\u0446\u0443 \u0431\u044B\u043B\u043E \u0434\u043E\u0431\u0430\u0432\u043B\u0435\u043D\u043E ${response.total} \u043D\u043E\u0432\u044B\u0445 \u0448\u0442\u0440\u0430\u0444\u043E\u0432`
      }, "notice");
    } else {
      commonScripts$1.showNotification({
        title: "\u041E\u043F\u043E\u0432\u0435\u0449\u0435\u043D\u0438\u0435",
        description: `\u041D\u043E\u0432\u044B\u0445 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u043D\u0435 \u043E\u0431\u043D\u0430\u0440\u0443\u0436\u0435\u043D\u043E`
      }, "notice");
    }
  },
  // Получение данных для монеты
  async getMonetaPayID(payment) {
    await api.callMethod("POST", `gibdd/moneta_pay`, {
      transaction_id: payment.transaction_id,
      amount: payment.value
    });
  },
  // Получение табов в корзине
  async getTrashTabs() {
    return await api.callMethod("GET", `tabs/trash`);
  }
};
const _sfc_main$F = {
  __name: "Item",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 2249,
        title: "\u0424\u0430\u0439\u043B",
        key: "fail_2249",
        width: "325px",
        enabled: true,
        type: "file",
        is_plural: 0,
        external_link: 1,
        is_external_link: 0,
        is_link: 0,
        required: 0,
        fixed: false,
        index: 7,
        fixTarget: "0px",
        read_only: 0,
        unit: "",
        mask: "",
        can_edit: 0,
        color: "#000",
        is_hidden: 0,
        visible_always: 1,
        options: []
      },
      type: Object
    },
    isCanOpenCount: {
      default: 0,
      type: Number
    },
    row: {},
    isTrash: {
      default: false,
      type: Boolean
    },
    isPermanentEdit: {
      default: false,
      type: Boolean
    },
    slug: {
      default: "",
      type: String
    },
    actionType: {
      default: "view",
      type: String
    },
    rowId: {
      default: 0,
      type: Number
    },
    permissions: {
      default: {},
      type: Object
    }
  },
  emits: [
    "callAction",
    "changeValue"
  ],
  setup(__props, { emit: __emit }) {
    const itemRef = ref(null);
    const bodyData = inject("bodyData");
    const isNumeric = inject("isNumeric");
    const isDinamyc = inject("isDinamyc");
    const actionState = inject("actionState");
    const backupValues = inject("backupValues");
    const skipChecking = inject("skipChecking");
    const backupRows = inject("backupRows");
    const is_admin = inject("is_admin");
    const userID = inject("userID");
    ref({
      id: -1,
      delay: 500,
      clicks: 0,
      timer: null
    });
    const props = __props;
    const emit = __emit;
    const changeValue = (id, data) => {
      let findedRow = bodyData.value[id - 1];
      emit("changeValue", {
        value: { ...data, id }
      });
      if (props.isPermanentEdit) {
        skipChecking.value = true;
        if (actionState.value == null) {
          backupRows.value = JSON.parse(JSON.stringify(bodyData.value));
          actionState.value = props.isTrash ? "restoring" : "saving";
          emit("callAction", {
            action: "changeStateTab",
            value: false
          });
        }
      }
      findedRow[data.key] = data.value;
      if (data.key == "isChoose") {
        if (data.value) {
          actionState.value = props.isTrash ? "restoring" : "editting";
        } else if (bodyData.value.filter((p) => p.isChoose).length == 0) {
          actionState.value = null;
        }
      }
      if (isDinamyc && data.key == "product_id") {
        for (let key in data.value.selectedOption) {
          if (["price", "weight"].includes(key)) {
            findedRow[`product_${key}`] = data.value.selectedOption[key];
          } else if (key == "text") {
            findedRow.product_name = data.value.selectedOption.text;
          } else {
            findedRow[key] = data.value.selectedOption[key];
          }
        }
        findedRow.product_count = 1, findedRow.localOptions = data.value.selectedOption ? [{
          label: data.value.selectedOption,
          value: data.value.selectedOption.id
        }] : [];
      }
    };
    const calcSum = computed(() => {
      return (props.row.product_count * props.row.product_price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    });
    const callAction = (data) => {
      const openLink = (value) => {
        emit("callAction", {
          action: "showModal",
          value: {
            id: value.id,
            slug: value.slug,
            tab: [null, void 0].includes(value.tab) ? null : value.tab
          }
        });
      };
      const editRow = (value) => {
        let findedIndex = bodyData.value.findIndex((row) => row.id == value.id);
        backupValues.value.push(JSON.parse(JSON.stringify(bodyData.value[findedIndex])));
        bodyData.value[findedIndex].isEdit = true;
        bodyData.value[findedIndex].isChoose = true;
        actionState.value = "saving";
      };
      const untieRow = (value) => {
        let findedIndex = bodyData.value.findIndex((row) => row.id == value.id);
        backupValues.value.push(JSON.parse(JSON.stringify(bodyData.value[findedIndex])));
        bodyData.value[findedIndex].isChoose = true;
        emit("callAction", {
          action: "untie",
          value: true
        });
      };
      const authPortal = () => {
        tableScripts.authPortal(typeof props.row.name == "object" ? props.row.name.value : props.row.name);
      };
      switch (data.action) {
        case "showModal":
          openLink({
            id: props.row.id,
            slug: props.slug,
            tab: null
          });
          break;
        case "edit":
          editRow(data.value);
          break;
        case "openLink":
          openLink(data.value);
          break;
        case "untieRow":
          untieRow(data.value);
          break;
        case "authPortal":
          authPortal();
          break;
        default:
          emit("callAction", { action: data.action, value: data.value });
          break;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      var _a;
      _push(`<td${ssrRenderAttrs(mergeProps({
        ref_key: "itemRef",
        ref: itemRef,
        class: ["table__item", [
          `table__item_${props.item.type}`,
          props.item.fixed ? "table__item_fixed" : "",
          __props.item.fixed ? "table__item_pseudo-fixed" : "",
          !props.item.enabled ? "table__item_hidden" : "",
          props.item.isUpdated ? "table__item_updated" : ""
        ]],
        style: `--colorItem: ${Boolean(props.item.set_color) ? props.item.color : "#000"}; --defaultWidth: ${props.item.width};`,
        "data-key": props.item.key
      }, _attrs))}><div class="${ssrRenderClass([`table-item__content_${props.item.type}`, "table-item__content"])}">`);
      if (props.item.type == "checkbox") {
        _push(ssrRenderComponent(AppCheckbox, {
          item: {
            isHTML: false,
            id: props.row.id,
            key: props.item.key,
            title: props.item.title,
            value: props.row[props.item.key],
            required: Boolean(props.item.required)
          },
          disabled: unref(actionState) == "saving",
          onChangeValue: (data) => changeValue(props.rowId, data)
        }, null, _parent));
      } else if (props.item.type == "payment") {
        _push(ssrRenderComponent(ButtonPayment, {
          item: {
            id: props.row.id,
            value: props.row[props.item.key] ? props.row[props.item.key].value : null,
            state: props.row[props.item.key] ? props.row[props.item.key].state : null,
            key: props.item.key,
            title: props.item.title,
            isCanClick: props.item.can_edit
          },
          onInitPayment: (data) => emit("callAction", { action: "initPayment", value: {
            id: props.row.id,
            value: data.value
          } })
        }, null, _parent));
      } else if (props.item.type == "relation") {
        _push(ssrRenderComponent(AppRelation, {
          item: {
            focus: false,
            id: props.item.id,
            placeholder: null,
            key: props.item.key,
            title: props.item.title,
            value: props.row[props.item.key],
            anotherKey: unref(isDinamyc) ? "product_name" : null,
            anotherTitle: unref(isDinamyc) ? props.row.product_name : null,
            related_table: props.item.related_table,
            required: Boolean(props.item.required),
            options: ["status", "relation"].includes(props.item.type) ? props.item.options : null,
            lockedOptions: props.item.choosed
          },
          isAnotherTitle: unref(isDinamyc),
          isCanCreate: Boolean(props.item.can_create),
          isCanEdit: Boolean(props.item.can_edit),
          isMultiple: Boolean(props.item.is_plural),
          isReadOnly: Boolean(props.item.read_only || !props.row.isEdit && !props.isPermanentEdit),
          isHaveLink: props.item.key != "role_id",
          onChangeValue: (data) => changeValue(props.rowId, data),
          onOpenLink: (data) => callAction({ action: "openLink", value: { id: data.id, slug: props.item.related_table } }),
          onShowAll: () => callAction({ action: "openLink", value: { id: props.row.id, slug: props.slug, tab: props.item.related_table } }),
          onCreateOption: () => emit("callAction", { action: "createOption", value: props.item.related_table })
        }, null, _parent));
      } else if (["number", "password", "text"].includes(props.item.type) && (!unref(isDinamyc) || unref(isDinamyc) && props.item.key != "product_sum")) {
        _push(ssrRenderComponent(AppTextarea, {
          item: {
            focus: false,
            id: props.row.id,
            placeholder: null,
            key: props.item.key,
            type: props.item.type,
            title: props.item.title,
            substring: props.item.unit,
            required: Boolean(props.item.required),
            external_link: ![null, void 0].includes(props.row[props.item.key]) && props.row[props.item.key] != "" ? props.row[props.item.key].external_link : null,
            value: [null, void 0].includes(props.row[props.item.key]) ? null : typeof props.row[props.item.key] == "object" ? [null, void 0].includes(props.row[props.item.key].value) ? null : String(props.row[props.item.key].value) : String(props.row[props.item.key])
          },
          class: Boolean(props.item.read_only || !props.row.isEdit && !props.isPermanentEdit) ? "table-item__content_read-only" : "",
          disabled: false,
          isUseEnter: false,
          isTableItem: true,
          mask: props.item.mask,
          isLink: Boolean(props.item.is_external_link),
          isReadOnly: Boolean(props.item.read_only || !props.row.isEdit && !props.isPermanentEdit),
          onChangeValue: (data) => changeValue(props.rowId, data)
        }, null, _parent));
      } else if (__props.item.type == "json") {
        _push(ssrRenderComponent(FormValue, {
          isHTML: true,
          value: props.row[props.item.key],
          isLink: Boolean(props.item.is_external_link),
          link: typeof props.row[props.item.key] == "object" && props.row[props.item.key] != null ? props.row[props.item.key].external_link : null
        }, null, _parent));
      } else if (unref(isDinamyc) && props.item.key == "product_sum") {
        _push(ssrRenderComponent(FormValue, {
          isHTML: true,
          value: calcSum.value,
          isLink: Boolean(props.item.is_external_link),
          link: typeof props.row[props.item.key] == "object" && props.row[props.item.key] != null ? props.row[props.item.key].external_link : null
        }, null, _parent));
      } else if (__props.item.type == "actions") {
        _push(ssrRenderComponent(AppActions, {
          item: {
            title: "\u0414\u0435\u0439\u0441\u0442\u0432\u0438\u0435",
            slug: props.row.isEdit ? "edit" : props.actionType
          },
          objId: props.row.id,
          disabled: !props.row.isChoose && unref(actionState) == "saving",
          permissions: props.permissions,
          userID: unref(userID),
          is_admin: unref(is_admin),
          relationID: props.row.user_id ? props.row.user_id.value : null,
          onCallAction: (data) => callAction({ action: data.value, value: __props.row })
        }, null, _parent));
      } else if (props.item.type == "status") {
        _push(ssrRenderComponent(AppStatus, {
          item: {
            focus: false,
            id: props.item.id,
            key: props.item.key,
            title: props.item.title,
            options: props.item.options,
            value: props.row[props.item.key],
            required: Boolean(props.item.required)
          },
          isCanCreate: false,
          isHaveNullOption: false,
          isReadOnly: Boolean(props.item.read_only || !props.row.isEdit && !props.isPermanentEdit),
          onChangeValue: (data) => changeValue(props.rowId, data)
        }, null, _parent));
      } else if (props.item.type == "select_dropdown") {
        _push(ssrRenderComponent(AppSelect, {
          item: {
            id: props.item.id,
            key: props.item.key,
            value: props.row[props.item.key],
            focus: false,
            required: Boolean(props.item.required),
            title: props.item.title,
            options: props.item.options,
            lockedOptions: []
          },
          isReadOnly: Boolean(props.item.read_only || !props.row.isEdit || _ctx.isDymanic && props.item.read_only),
          isHaveNullOption: (_a = Boolean(props.item.have_null_option)) != null ? _a : true,
          isMultiple: Boolean(props.item.is_plural),
          isFiltered: true,
          onChangeValue: (data) => changeValue(props.rowId, data)
        }, null, _parent));
      } else if (props.item.type == "file") {
        _push(ssrRenderComponent(AppFile, {
          item: {
            id: props.row.id,
            title: props.item.title,
            key: props.item.key,
            required: Boolean(props.item.required),
            buttonName: null,
            value: props.row[props.item.key]
          },
          isReadOnly: true,
          isShowFileName: false,
          isMultiple: false,
          isOneFile: true,
          onChangeValue: (data) => changeValue(props.rowId, data)
        }, null, _parent));
      } else if (props.item.type == "date") {
        _push(ssrRenderComponent(AppDate, {
          item: {
            id: props.row.id,
            required: Boolean(props.item.required),
            title: props.item.title,
            placeholder: null,
            value: props.row[props.item.key],
            key: props.item.key,
            focus: false
          },
          isMultiple: Boolean(props.item.is_plural),
          isReadOnly: Boolean(props.item.read_only || !props.row.isEdit && !props.isPermanentEdit),
          onOpenDatepicker: () => _ctx.$emit("clickRow", true),
          onChangeValue: (data) => changeValue(props.rowId, data)
        }, null, _parent));
      } else if (props.item.type == "address") {
        _push(ssrRenderComponent(AppMap, {
          item: {
            id: props.row.id,
            title: props.item.title,
            key: props.item.key,
            required: Boolean(props.item.required),
            focus: props.item.focus,
            value: props.row[props.item.key],
            options: [],
            lockedOptions: []
          },
          isReadOnly: Boolean(props.item.read_only || !props.row.isEdit && !props.isPermanentEdit),
          isShowMap: false,
          isCanSelect: false,
          isShowLabel: false,
          onChangeValue: (data) => changeValue(props.rowId, data)
        }, null, _parent));
      } else if (props.item.type == "iconDrag") {
        _push(`<div class="table-item__icon">`);
        _push(ssrRenderComponent(IconDrag, {
          draggable: true,
          onDragover: () => {
          },
          onDragenter: () => {
          },
          onDragstart: (event) => _ctx.$emit("dragRowStart", event),
          onDragend: (event) => _ctx.$emit("dragRowEnd", event)
        }, null, _parent));
        _push(ssrRenderComponent(FormValue, {
          style: unref(isNumeric) ? null : { display: "none" },
          isHTML: false,
          value: props.rowId,
          isLink: false,
          link: null
        }, null, _parent));
        _push(`</div>`);
      } else if (props.item.type == "iconDelete") {
        _push(ssrRenderComponent(IconDelete, {
          onClick: () => callAction({ action: "removeRow", value: props.rowId })
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div></td>`);
    };
  }
};
const _sfc_setup$F = _sfc_main$F.setup;
_sfc_main$F.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Body/Item/Item.vue");
  return _sfc_setup$F ? _sfc_setup$F(props, ctx) : void 0;
};
const TableItem = _sfc_main$F;
const _sfc_main$E = {
  __name: "Row",
  __ssrInlineRender: true,
  props: {
    isTrash: {
      default: false,
      type: Boolean
    },
    row: {},
    rowId: {
      default: 0,
      type: Number
    },
    rowsIds: {
      default: [],
      type: Array
    },
    slug: {
      default: "",
      type: String
    },
    isPermanentEdit: {
      default: false,
      type: Boolean
    },
    actionType: {
      default: "view",
      type: String
    },
    permissions: {
      default: {},
      type: Object
    },
    isCanOpenCount: {
      default: 0,
      type: Number
    }
  },
  emits: [
    "callAction",
    "changeValue"
  ],
  setup(__props, { emit: __emit }) {
    const rowRef = ref(null);
    const fields = inject("fields");
    const props = __props;
    const emit = __emit;
    const clickRow = (state) => {
      if (rowRef.value != null) {
        if (state) {
          rowRef.value.classList.add("table_row_clicked");
          emit("callAction", {
            action: "chooseRow",
            value: props.row
          });
        } else {
          rowRef.value.classList.remove("table_row_clicked");
        }
      }
    };
    const setDragImage = (event) => {
      if (document.getElementById("clone-elem") == null) {
        let tbody = document.createElement("tbody");
        tbody.classList.add("table__body");
        let row = rowRef.value.cloneNode(true);
        tbody.id = "clone-elem";
        row.classList.add("table__row_clone");
        row.classList.add("table__row");
        row.style.width = `${rowRef.value.offsetWidth}px`;
        let items = rowRef.value.querySelectorAll(".table__item");
        row.querySelectorAll(".table__item").forEach((element, index) => {
          element.style.setProperty("--defaultWidth", `${items[index].offsetWidth.toFixed(2)}px`);
        });
        tbody.appendChild(row);
        document.body.appendChild(tbody);
        event.dataTransfer.setDragImage(tbody, event.offsetX, event.offsetY);
      }
    };
    const dragRowEnd = (event) => {
      let removingItem = document.getElementById("clone-elem");
      if (removingItem != null) {
        removingItem.remove();
      }
    };
    const setClasses = computed(() => {
      let classes = [];
      if (props.isCanOpenCount != 0) {
        if (!props.rowsIds.sort((prev, next) => Number(prev) - Number(next)).slice(0, props.isCanOpenCount).includes(props.row.id)) {
          classes.push("table__row_disabled");
        }
      }
      if (props.row.isEdit) {
        classes.push("table__row_edit");
      } else if (props.row.isChoose) {
        classes.push("table__row_choosed");
      }
      if (props.row.isUpdated) {
        classes.push("table__row_updated");
      }
      return classes;
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<tr${ssrRenderAttrs(mergeProps({
        ref_key: "rowRef",
        ref: rowRef,
        class: ["table__row", unref(setClasses)]
      }, _attrs, ssrGetDirectiveProps(_ctx, unref(clickOutSide), (event) => clickRow(false))))}><!--[-->`);
      ssrRenderList(unref(fields), (item) => {
        _push(ssrRenderComponent(TableItem, {
          row: props.row,
          item,
          rowId: props.rowId,
          slug: props.slug,
          isTrash: __props.isTrash,
          actionType: props.actionType,
          permissions: props.permissions,
          isPermanentEdit: props.isPermanentEdit,
          isCanOpenCount: props.isCanOpenCount,
          onClickRow: () => clickRow(true),
          onDragRowStart: (event) => setDragImage(event),
          onDragRowEnd: (event) => dragRowEnd(),
          onCallAction: (data) => emit("callAction", data),
          onChangeValue: (data) => emit("changeValue", data)
        }, null, _parent));
      });
      _push(`<!--]--></tr>`);
    };
  }
};
const _sfc_setup$E = _sfc_main$E.setup;
_sfc_main$E.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Body/Row/Row.vue");
  return _sfc_setup$E ? _sfc_setup$E(props, ctx) : void 0;
};
const TableRow = _sfc_main$E;
const _sfc_main$D = {
  __name: "Body",
  __ssrInlineRender: true,
  props: {
    isTrash: {
      default: false,
      type: Boolean
    },
    slug: {
      default: "",
      type: String
    },
    isPermanentEdit: {
      default: false,
      type: Boolean
    },
    actionType: {
      default: "view",
      type: String
    },
    permissions: {
      default: {},
      type: Object
    },
    groupBody: {
      default: null,
      type: String
    },
    isDraggableRow: {
      default: false,
      type: Boolean
    },
    isCanOpenCount: {
      default: 0,
      type: Number
    }
  },
  emits: [
    "callAction",
    "changeValue"
  ],
  setup(__props, { emit: __emit }) {
    const bodyData = inject("bodyData");
    const backupRows = inject("backupRows");
    const actionState = inject("actionState");
    const props = __props;
    const dragStart = () => {
      if (actionState.value != "saving") {
        backupRows.value = JSON.parse(JSON.stringify(bodyData.value));
      }
    };
    const emit = __emit;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(unref(draggable), mergeProps({
        tag: "tbody",
        class: "table__body",
        itemKey: props.slug,
        modelValue: unref(bodyData),
        "onUpdate:modelValue": ($event) => isRef(bodyData) ? bodyData.value = $event : null,
        group: props.groupBody,
        handle: props.isDraggableRow ? ".table__row" : ".icon__draggable",
        onStart: () => dragStart(),
        onEnd: (event) => emit("callAction", { action: "moveRows", value: event })
      }, _attrs), {
        item: withCtx(({ element: row, index }, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(TableRow, {
              row,
              rowId: index + 1,
              rowsIds: unref(bodyData).map((p) => p.id),
              slug: props.slug,
              isTrash: props.isTrash,
              actionType: props.actionType,
              permissions: props.permissions,
              isCanOpenCount: props.isCanOpenCount,
              isPermanentEdit: props.isPermanentEdit,
              onCallAction: (data) => emit("callAction", data),
              onChangeValue: (data) => emit("changeValue", data)
            }, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(TableRow, {
                row,
                rowId: index + 1,
                rowsIds: unref(bodyData).map((p) => p.id),
                slug: props.slug,
                isTrash: props.isTrash,
                actionType: props.actionType,
                permissions: props.permissions,
                isCanOpenCount: props.isCanOpenCount,
                isPermanentEdit: props.isPermanentEdit,
                onCallAction: (data) => emit("callAction", data),
                onChangeValue: (data) => emit("changeValue", data)
              }, null, 8, ["row", "rowId", "rowsIds", "slug", "isTrash", "actionType", "permissions", "isCanOpenCount", "isPermanentEdit", "onCallAction", "onChangeValue"])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$D = _sfc_main$D.setup;
_sfc_main$D.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Body/Body.vue");
  return _sfc_setup$D ? _sfc_setup$D(props, ctx) : void 0;
};
const TableBody = _sfc_main$D;
const _sfc_main$C = {
  __name: "Total",
  __ssrInlineRender: true,
  setup(__props) {
    const bodyData = inject("bodyData");
    const setSum = computed(() => {
      return bodyData.value.length == 0 ? 0 : bodyData.value.map((row) => Number(row.product_price) * Number(row.product_count)).reduce((a, b) => a + b).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    });
    const setCount = computed(() => {
      return bodyData.value.length == 0 ? 0 : bodyData.value.map((row) => Number(row.product_count)).reduce((a, b) => a + b).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    });
    const setWeight = computed(() => {
      return bodyData.value.length == 0 ? 0 : bodyData.value.map((row) => Number(row.product_weight) * Number(row.product_count)).reduce((a, b) => a + b).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "total" }, _attrs))}><div class="total__list"><div class="total__item"><div class="total__title"> \u041A\u043E\u043B\u0438\u0447\u0435\u0441\u0442\u0432\u043E: </div><div class="total__value">${ssrInterpolate(setCount.value)} \u0448\u0442. </div></div></div><div class="total__list"><div class="total__item"><div class="total__title"> \u041E\u0431\u0449\u0438\u0439 \u0432\u0435\u0441: </div><div class="total__value">${ssrInterpolate(setWeight.value)} \u043A\u0433 </div></div></div><div class="total__list"><div class="total__item"><div class="total__title"> \u0421\u0443\u043C\u043C\u0430: </div><div class="total__value">${ssrInterpolate(setSum.value)} \u0420\u0443\u0431. </div></div></div></div>`);
    };
  }
};
const _sfc_setup$C = _sfc_main$C.setup;
_sfc_main$C.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Total/Total.vue");
  return _sfc_setup$C ? _sfc_setup$C(props, ctx) : void 0;
};
const TableTotal = _sfc_main$C;
const _sfc_main$B = {
  __name: "Sort",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "ibg icon__sort" }, _attrs))}><svg xmlns="http://www.w3.org/2000/svg" width="12" height="7" fill="none"><path fill="#0584FE" fill-rule="evenodd" d="M11.77 1.2 6.15 6.91S6.04 7 5.86 7s-.3-.09-.3-.09L.1 1.21S-.14.7.22.3s.9-.23.9-.23l4.72 4.85L10.6.08s.5-.22.94.18c.45.4.23.94.23.94Z" clip-rule="evenodd"></path></svg></figure>`);
    };
  }
};
const _sfc_setup$B = _sfc_main$B.setup;
_sfc_main$B.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Sort/Sort.vue");
  return _sfc_setup$B ? _sfc_setup$B(props, ctx) : void 0;
};
const IconSort = _sfc_main$B;
const _sfc_main$A = {
  __name: "Item",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        id: 0,
        index: 0,
        key: "key",
        title: null,
        type: "text",
        width: "0px",
        fixed: false,
        enabled: true,
        sort_order: null
      },
      type: Object
    },
    headerRef: {
      default: null
    },
    isTrash: {
      default: false,
      type: Boolean
    }
  },
  emits: ["callAction", "dragStart", "dragEnd"],
  setup(__props, { emit: __emit }) {
    const tableItemRef = ref(null);
    inject("menu");
    const sortItem = inject("sortItem");
    const bodyData = inject("bodyData");
    const selectAll = inject("selectAll");
    inject("footerData");
    const actionState = inject("actionState");
    const isCanUseHeader = inject("isCanUseHeader");
    inject("isCanSort");
    ref({
      id: -1,
      delay: 500,
      clicks: 0,
      timer: null
    });
    const props = __props;
    const selectAllRows = (data) => {
      selectAll.value = data.value;
      bodyData.value.forEach((row) => {
        row.isChoose = selectAll.value;
      });
      if (data.value) {
        actionState.value = props.isTrash ? "restoring" : "editting";
      } else {
        actionState.value = null;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<th${ssrRenderAttrs(mergeProps({
        class: ["table__item table-item", unref(isCanUseHeader) ? "" : "table__item_disabled"],
        ref_key: "tableItemRef",
        ref: tableItemRef
      }, _attrs))}>`);
      if (props.item.key == "isChoose") {
        _push(`<div class="table-item__content">`);
        _push(ssrRenderComponent(AppCheckbox, {
          item: {
            id: props.item.id,
            key: props.item.key,
            type: props.item.type,
            value: unref(selectAll),
            title: props.item.title,
            substring: props.item.unit,
            is_link: props.item.is_link,
            is_plural: props.item.is_plural,
            hiddenOptions: props.item.choosed,
            related_table: props.item.related_table,
            is_external_link: props.item.is_external_link,
            options: null,
            external_link: null
          },
          isCanCreate: false,
          isUseEnter: false,
          enabledAutocomplete: false,
          isReadOnly: false,
          disabled: unref(actionState) == "saving",
          onChangeValue: (data) => selectAllRows(data)
        }, null, _parent));
        if (unref(sortItem).key == props.item.key) {
          _push(ssrRenderComponent(IconSort, {
            class: unref(sortItem).order == "asc" ? "icon__sort_up" : ""
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="table-item__drag-area"${ssrRenderAttr("draggable", true)}></div><div class="table-item__border"></div></div>`);
      } else {
        _push(`<div class="table-item__content"><span class="table-item__title">${ssrInterpolate(props.item.title)}</span>`);
        if (unref(sortItem).key == props.item.key) {
          _push(ssrRenderComponent(IconSort, {
            class: unref(sortItem).order == "asc" ? "icon__sort_up" : ""
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<div class="table-item__drag-area"${ssrRenderAttr("draggable", props.headerRef != null && !props.headerRef.parentNode.classList.contains("table_resizing") && tableItemRef.value != null && !tableItemRef.value.classList.contains("table__item_sticky"))}></div><div class="table-item__border"></div></div>`);
      }
      _push(`</th>`);
    };
  }
};
const _sfc_setup$A = _sfc_main$A.setup;
_sfc_main$A.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Header/Item/Item.vue");
  return _sfc_setup$A ? _sfc_setup$A(props, ctx) : void 0;
};
const HeaderItem = _sfc_main$A;
const commonScripts = {
  // Установка курсора
  setCursor(cursor) {
    document.body.style.setProperty("--cursorStyle", `${cursor}`);
  },
  // Скрытие всех выпадающих списков
  hideAllDetails(className, event = null) {
    if (className == "details" || event == null) {
      document.querySelectorAll(className).forEach((element) => {
        element.removeAttribute("open");
      });
    } else {
      if (event != null) {
        if (event.target.closest(className) == null) {
          document.querySelectorAll(className).forEach((element) => {
            element.removeAttribute("open");
          });
        } else {
          if (event.target.closest(".section__setting-option__summary")) {
            targetObject = event.target;
            return;
          }
          if (!(targetObject === event.target)) {
            document.querySelectorAll(className).forEach((element) => {
              element.removeAttribute("open");
            });
          }
          targetObject = event.target;
        }
      }
    }
  },
  // Клонирование перетаскиваемого элемента c созданием родителя
  cloningDraggableComponent(data, parentClass, subclass) {
    if (document.getElementById("clone-elem") == null) {
      let parentElem = document.createElement("div");
      let elem = data.target.cloneNode(true);
      parentElem.classList.add(parentClass);
      parentElem.appendChild(elem);
      parentElem.id = "clone-elem";
      parentElem.classList.add("clone-elem");
      elem.style.width = `${data.target.offsetWidth}px`;
      if (subclass != null) {
        elem.querySelector(subclass.selector).classList.add(subclass.class);
      }
      document.body.appendChild(parentElem);
      data.dataTransfer.setDragImage(parentElem, 5, 8);
    }
  },
  // Клонирование перетаскиваемого элемента без создания родителя
  cloningDraggableSection(data, subclass) {
    if (document.getElementById("clone-elem") == null) {
      let elem = data.target.cloneNode(true);
      elem.id = "clone-elem";
      elem.classList.add("clone-elem");
      elem.style.width = `${data.target.offsetWidth}px`;
      if (subclass != null) {
        elem.querySelector(subclass.selector).classList.add(subclass.class);
      }
      document.body.appendChild(elem);
      data.dataTransfer.setDragImage(elem, 5, 8);
    }
  },
  // Удаление перетаскиваемого элемента
  removingDraggableComponent() {
    let removingItem = document.getElementById("clone-elem");
    if (removingItem != null) {
      removingItem.remove();
    }
  },
  // Очистка хранилища
  clearStorage(store) {
    for (let key in store) {
      clearFilter(store, key);
      clearTableData(store, key);
    }
    clearPage(store);
  },
  // Снять выделение со всех блоков
  clearSelection(event) {
    if (window.getSelection) {
      window.getSelection().removeAllRanges();
    } else if (document.selection) {
      document.selection.empty();
    }
  },
  async getYandexCoords(data) {
    let request = await fetch(`https://geocode-maps.yandex.ru/1.x/?${data}`);
    let response = await request.json();
    return response;
  },
  // Проверка переменной на число
  isInteger(value) {
    return /^\d+$/.test(value);
  },
  checkingLink(routePath, link) {
    let routeMatch = routePath.match(link);
    if (routeMatch != null) {
      if (routeMatch.input.substr(routeMatch.input.length - 1) == "/") {
        routeMatch.input = routeMatch.input.slice(0, -1);
      } else {
        let routeString = routeMatch.input.split("/");
        if (this.isInteger(routeString[routeString.length - 1])) {
          routeString.pop();
          routeMatch.input = routeString.join("/");
        }
      }
    }
    return routeMatch;
  },
  // Прокручивание страницы вверх вместе с модальными окнами
  scrollPageTop() {
    window.scrollTo(0, 0);
    let modals = document.querySelectorAll(".modal__content");
    modals.forEach((modal) => {
      modal.scrollTop = 0;
    });
  }
};
let targetObject = null;
const clearTableData = (store, key) => {
  if (key == "sortedColumn") {
    store[key] = null;
  } else {
    store[key] = [];
  }
};
const clearFilter = (store, key) => {
  switch (key) {
    case "filterInput":
      store[key] = "";
      break;
    case "filterFields":
      store[key] = [];
      break;
    case "searchFilters":
      store[key] = [];
      break;
    case "creatingFilter":
      store[key] = null;
      break;
    case "savedFilterFields":
      store[key] = [];
      break;
  }
};
const clearPage = (store) => {
  store.table.pageInfo = {
    activePage: 1,
    totalPages: 1,
    totalElems: 0,
    countSelect: 0,
    visibleElems: 25
  };
};
const _sfc_main$z = {
  __name: "Header",
  __ssrInlineRender: true,
  props: {
    isTrash: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const headerRef = ref(null);
    const headerCopyRef = ref(null);
    const menu = inject("menu");
    const fields = inject("fields");
    const tableRef = inject("tableRef");
    const bodyData = inject("bodyData");
    let draggingItem = ref(null);
    let tableCopy = ref(null);
    let prevMouseCoords = ref(null);
    let mouseDown = ref(null);
    const props = __props;
    const emit = __emit;
    const onMouseMove = (e) => {
      const moveColumn = (posX) => {
        let itemListParent = tableCopy.value.querySelector("thead tr");
        let itemList = itemListParent.querySelectorAll(".table__item");
        let tableBodyListParent = [...tableCopy.value.querySelectorAll("tbody tr")];
        let fromIndex = [...itemList].findIndex((p) => p.getAttribute("data-key") == draggingItem.value);
        let stopDrag = false;
        let hoverElementIndex = [...itemList].findIndex((elem, index) => {
          let itemCoords = elem.getBoundingClientRect();
          let startCoord = itemCoords.x;
          let center = (itemCoords.x + (itemCoords.x + itemCoords.width)) / 2 + 10;
          let endCoord = itemCoords.x + itemCoords.width + 10;
          let coord = (startCoord + center) / 2;
          if (posX >= coord && posX <= endCoord && (posX > center + 3 && fromIndex > index || posX < center - 3 && fromIndex < index)) {
            stopDrag = true;
          }
          return posX >= coord && posX <= endCoord;
        });
        if (stopDrag || [null, void 0, -1].includes(hoverElementIndex) || itemList[hoverElementIndex].classList.contains("table__item_sticky"))
          return;
        if (fromIndex > hoverElementIndex) {
          itemListParent.insertBefore(itemList[fromIndex], itemList[hoverElementIndex]);
          for (let row of tableBodyListParent) {
            row.insertBefore(row.children[fromIndex], row.children[hoverElementIndex]);
          }
        } else if (fromIndex < hoverElementIndex) {
          itemListParent.insertBefore(itemList[hoverElementIndex], itemList[fromIndex]);
          for (let row of tableBodyListParent) {
            row.insertBefore(row.children[hoverElementIndex], row.children[fromIndex]);
          }
        } else {
          return;
        }
      };
      e = e || window.event;
      var dragX = e.pageX;
      if (prevMouseCoords.value != dragX) {
        moveColumn(dragX);
      }
      prevMouseCoords.value = dragX;
    };
    const hideAllDetails = () => {
      let details = document.querySelectorAll("details[open]");
      details.forEach((element) => {
        element.removeAttribute("open");
      });
    };
    const dragColumn = (data) => {
      const copyTable = () => {
        tableCopy.value = tableRef.value.cloneNode(true);
        tableCopy.value.classList.add("table_copy");
        tableCopy.value.querySelector(".table__header_copy").remove();
        let tableCells = tableCopy.value.querySelectorAll(".table__item");
        let tableRows = tableCopy.value.querySelectorAll(".table__row");
        tableRows.forEach((row, index) => {
          if (index >= 30) {
            row.remove();
          }
        });
        for (let cell of tableCells) {
          if (cell.getAttribute("data-key") == draggingItem.value) {
            cell.classList.add("sortable-ghost");
          }
        }
        setTimeout(() => {
          tableRef.value.closest(".table-template__body").appendChild(tableCopy.value);
          tableRef.value.closest(".section__table").style.setProperty("overflow", "hidden");
          tableRef.value.classList.add("table_hidden");
        }, 100);
      };
      const updateFields = () => {
        let list = tableCopy.value.querySelectorAll("thead .table__item");
        let findedField = null;
        let data2 = [];
        list.forEach((element, index) => {
          findedField = fields.value.find((p) => p.key == element.getAttribute("data-key"));
          findedField.index = index;
          data2.push(findedField);
        });
        fields.value = data2.sort((next, prev) => next.index - prev.index);
      };
      const dragStart = (value) => {
        draggingItem.value = value.key;
        copyTable();
        setDragImage(value.event);
        document.addEventListener("dragover", onMouseMove);
        tableRef.value.closest(".table-template").classList.add("table-template__body_drag");
      };
      const dragEnd = () => {
        const removeDragImage = () => {
          let removingItem = document.getElementById("table_transfer");
          if (removingItem != null) {
            removingItem.remove();
          }
        };
        draggingItem.value = null;
        updateFields();
        document.removeEventListener("dragover", onMouseMove);
        menu.value.showSaves(true);
        tableRef.value.closest(".section__table").style.removeProperty("overflow");
        setTimeout(() => {
          let cells = headerRef.value.querySelector("tr").children;
          resizeTable.setDefaultWidth(cells, fields.value);
          tableCopy.value.remove();
          tableRef.value.classList.remove("table_hidden");
          removeDragImage();
          tableRef.value.closest(".table-template").classList.remove("table-template__body_drag");
        }, 20);
      };
      const setDragImage = (event) => {
        if (document.getElementById("table_transfer") == null) {
          let table = tableCopy.value.cloneNode(true);
          let backupRows = tableRef.value.querySelectorAll(".table__row");
          table.id = "table_transfer";
          table.classList.add("table_transfer");
          table.classList.add("table");
          table.style.width = `${tableCopy.value.offsetWidth}px`;
          document.body.appendChild(table);
          let rows = table.querySelectorAll(".table__row");
          rows.forEach((row, index) => {
            let items = row.querySelectorAll(".table__item");
            for (let item of items) {
              if (item.getAttribute("data-key") != draggingItem.value) {
                item.remove();
              } else {
                let findedRow = [...backupRows][index];
                if (findedRow != void 0) {
                  item.style.height = `${findedRow.offsetHeight}px`;
                  item.classList.remove("sortable-ghost");
                } else {
                  item.style.height = `${row.offsetHeight}px`;
                }
              }
            }
          });
          event.dataTransfer.setDragImage(table, event.offsetX, event.offsetY);
        }
      };
      switch (data.action) {
        case "copyTable":
          copyTable();
          break;
        case "updateFields":
          updateFields();
          break;
        case "dragStart":
          dragStart(data.value);
          break;
        case "dragEnd":
          dragEnd();
          break;
      }
    };
    const updateTableHeader = (e) => {
      if (tableRef.value && tableRef.value.classList.contains("table_resizing")) {
        menu.value.showSaves(true);
        let findedIndex = fields.value.findIndex((p) => p.key == mouseDown.value.closest(".table__item").getAttribute("data-key"));
        fields.value[findedIndex].width = `${mouseDown.value.closest(".table__item").offsetWidth}px`;
        setFixedCellsWidth(tableRef.value);
        setTimeout(() => {
          commonScripts.clearSelection();
        }, 5);
      }
    };
    const setStickyState = () => {
      let cellArray = tableRef.value.querySelectorAll(".table__header > tr > th.table__item_sticky");
      let stickyWidth = [...cellArray].reduce((a, b) => a + b.offsetWidth, 0);
      if (tableRef.value.parentNode) {
        if (stickyWidth > tableRef.value.parentNode.offsetWidth - 300) {
          for (let cell of tableRef.value.querySelectorAll(".table__item_fixed")) {
            cell.classList.remove("table__item_sticky");
            cell.classList.remove("table__item_fixed");
          }
        }
        resizeTable.setStickyClass(tableRef.value);
      }
    };
    throttle(async function() {
      setStickyState();
    }, 10);
    const setFixedCellsWidth = (table) => {
      let rows = table.querySelectorAll(".table__row");
      let fixedFields = [];
      let summaryWidth = 0;
      for (let row of rows) {
        summaryWidth = 0;
        fixedFields = row.querySelectorAll(".table__item_pseudo-fixed:not(.table__item_hidden)");
        for (let index = 0; index < fixedFields.length; index++) {
          if (index > 0) {
            summaryWidth += fixedFields[index - 1].offsetWidth;
          }
          fixedFields[index].classList.add("table__item_fixed");
          fixedFields[index].style.setProperty("--fixTarget", `${summaryWidth}px`);
        }
      }
      setTimeout(() => {
        setStickyState();
      }, 10);
    };
    watch(() => fields.value, () => {
      console.log("header");
      if (fields.value.length > 0) {
        setTimeout(async () => {
          resizeTable.setCellsWidth(tableRef.value);
        }, 10);
        setTimeout(() => {
          setFixedCellsWidth(tableRef.value);
          resizeTable.setStickyClass(tableRef.value);
        }, 100);
      }
    }, { deep: true });
    onUnmounted(() => {
      document.removeEventListener("dragover", onMouseMove);
      document.removeEventListener("mouseup", updateTableHeader);
      document.removeEventListener("mousedown", (e) => {
        mouseDown.value = e.target;
      });
    });
    watch(() => bodyData.value, () => {
      setTimeout(() => {
        resizeTable.resizableGrid(tableRef.value, fields.value);
        setFixedCellsWidth(tableRef.value);
      }, 100);
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_RenderCacheable = resolveComponent("RenderCacheable");
      _push(`<!--[--><thead class="table__header"><tr class="table__row"><!--[-->`);
      ssrRenderList(unref(fields), (item) => {
        _push(ssrRenderComponent(HeaderItem, {
          item,
          headerRef: unref(headerRef),
          "data-key": item.key,
          isTrash: props.isTrash,
          class: [
            item.fixed ? "table__item_fixed" : "",
            item.fixed ? "table__item_pseudo-fixed" : "",
            !item.enabled ? "table__item_hidden" : "",
            item.required ? "table__item_required" : "",
            item.read_only || item.key == "actions" ? "table__item_readonly" : "",
            item.isUpdated ? "table__item_updated" : ""
          ],
          onMousedown: () => hideAllDetails(),
          onCallAction: (data) => emit("callAction", data),
          onDragStart: (event) => dragColumn({ action: "dragStart", value: { event, key: item.key } }),
          onDragEnd: () => dragColumn({ action: "dragEnd", value: null })
        }, null, _parent));
      });
      _push(`<!--]--></tr></thead>`);
      _push(ssrRenderComponent(_component_RenderCacheable, null, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<thead class="table__header table__header_copy"${_scopeId}><tr class="table__row"${_scopeId}><!--[-->`);
            ssrRenderList(unref(fields), (item) => {
              _push2(ssrRenderComponent(HeaderItem, {
                item,
                headerRef: unref(headerRef),
                "data-key": item.key,
                isTrash: props.isTrash,
                class: [
                  item.fixed ? "table__item_fixed" : "",
                  item.fixed ? "table__item_pseudo-fixed" : "",
                  !item.enabled ? "table__item_hidden" : "",
                  item.required ? "table__item_required" : "",
                  item.read_only || item.key == "actions" ? "table__item_readonly" : "",
                  item.isUpdated ? "table__item_updated" : ""
                ],
                onCallAction: (data) => emit("callAction", data)
              }, null, _parent2, _scopeId));
            });
            _push2(`<!--]--></tr></thead>`);
          } else {
            return [
              createVNode("thead", {
                class: "table__header table__header_copy",
                ref_key: "headerCopyRef",
                ref: headerCopyRef
              }, [
                createVNode("tr", { class: "table__row" }, [
                  (openBlock(true), createBlock(Fragment, null, renderList(unref(fields), (item) => {
                    return openBlock(), createBlock(HeaderItem, {
                      item,
                      headerRef: unref(headerRef),
                      "data-key": item.key,
                      isTrash: props.isTrash,
                      class: [
                        item.fixed ? "table__item_fixed" : "",
                        item.fixed ? "table__item_pseudo-fixed" : "",
                        !item.enabled ? "table__item_hidden" : "",
                        item.required ? "table__item_required" : "",
                        item.read_only || item.key == "actions" ? "table__item_readonly" : "",
                        item.isUpdated ? "table__item_updated" : ""
                      ],
                      onCallAction: (data) => emit("callAction", data)
                    }, null, 8, ["item", "headerRef", "data-key", "isTrash", "class", "onCallAction"]);
                  }), 256))
                ])
              ], 512)
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$z = _sfc_main$z.setup;
_sfc_main$z.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Header/Header.vue");
  return _sfc_setup$z ? _sfc_setup$z(props, ctx) : void 0;
};
const TableHeader = _sfc_main$z;
const _sfc_main$y = {
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
const _sfc_setup$y = _sfc_main$y.setup;
_sfc_main$y.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Footer/Pagination/PaginationList/PaginationList.vue");
  return _sfc_setup$y ? _sfc_setup$y(props, ctx) : void 0;
};
const PaginationList = _sfc_main$y;
const _sfc_main$x = {
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
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    inject("footerData");
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "pagination__item" }, _attrs))}>${ssrInterpolate(__props.item)}</div>`);
    };
  }
};
const _sfc_setup$x = _sfc_main$x.setup;
_sfc_main$x.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Footer/Pagination/PaginationItem/PaginationItem.vue");
  return _sfc_setup$x ? _sfc_setup$x(props, ctx) : void 0;
};
const PaginationItem = _sfc_main$x;
const _sfc_main$w = {
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
const _sfc_setup$w = _sfc_main$w.setup;
_sfc_main$w.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Footer/Pagination/PaginationListLarge/PaginationListLarge.vue");
  return _sfc_setup$w ? _sfc_setup$w(props, ctx) : void 0;
};
const PaginationListLarge = _sfc_main$w;
const _sfc_main$v = {
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
const _sfc_setup$v = _sfc_main$v.setup;
_sfc_main$v.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Footer/Pagination/PaginationListShort/PaginationListShort.vue");
  return _sfc_setup$v ? _sfc_setup$v(props, ctx) : void 0;
};
const PaginationListShort = _sfc_main$v;
const _sfc_main$u = {
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
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "pagination" }, _attrs))}><div class="pagination__title"> \u0421\u0442\u0440\u0430\u043D\u0438\u0446\u0430: </div>`);
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
const _sfc_setup$u = _sfc_main$u.setup;
_sfc_main$u.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Footer/Pagination/Pagination.vue");
  return _sfc_setup$u ? _sfc_setup$u(props, ctx) : void 0;
};
const AppPagination = _sfc_main$u;
const _sfc_main$t = {
  __name: "Footer",
  __ssrInlineRender: true,
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const sortItem = inject("sortItem");
    const bodyData = inject("bodyData");
    const footerData = inject("footerData");
    const emit = __emit;
    const callAction = (data) => {
      const changeVisibleElems = (value) => {
        footerData.value.activePage = 1;
        footerData.value.count = value;
        emit("callAction", { action: "getTableData", value: sortItem.value });
        emit("callAction", { action: "setVisibleElems", value });
      };
      const changePage = (value) => {
        window.scrollTo(0, 0);
        footerData.value.activePage = value;
        emit("callAction", { action: "getTableData", value: sortItem.value });
      };
      switch (data.action) {
        case "changeVisibleElems":
          changeVisibleElems(data.value);
          break;
        case "changePage":
          changePage(data.value);
          break;
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "table-template__footer table-footer" }, _attrs))}><div class="table-footer__count-selected"><span class="table-footer__count-selected-title">\u0412\u044B\u0431\u0440\u0430\u043D\u043E: </span> ${ssrInterpolate(unref(bodyData).filter((p) => p.isChoose).length)}</div>`);
      _push(ssrRenderComponent(AppPagination, {
        totalPages: unref(footerData).pages,
        activePage: unref(footerData).activePage,
        class: "table-footer__pagination",
        onCallAction: (data) => callAction({ action: "changePage", value: data.value })
      }, null, _parent));
      _push(`<div class="table-footer__visible-elems">`);
      _push(ssrRenderComponent(AppSelect, {
        item: {
          id: 0,
          key: "visibleElems",
          value: unref(footerData).count,
          focus: false,
          required: false,
          title: "\u041D\u0430 \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0435:",
          lockedOptions: [],
          options: [
            {
              label: "25",
              value: 25
            },
            {
              label: "50",
              value: 50
            }
            // {
            //     label: '100',
            //     value: 100
            // }
          ]
        },
        isFiltered: false,
        isHaveNullOption: false,
        onChangeValue: (data) => callAction({ action: "changeVisibleElems", value: data.value })
      }, null, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup$t = _sfc_main$t.setup;
_sfc_main$t.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Footer/Footer.vue");
  return _sfc_setup$t ? _sfc_setup$t(props, ctx) : void 0;
};
const TableFooter = _sfc_main$t;
const _sfc_main$s = {
  __name: "Socket",
  __ssrInlineRender: true,
  props: {
    socketRows: {
      default: {
        header: [
          {
            id: 0,
            key: null
          }
        ],
        body: [
          {
            id: 0,
            key: null
          }
        ]
      },
      type: Object
    }
  },
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "table-template__socket table-socket" }, _attrs))}><span class="table-socket__text">${ssrInterpolate(__props.socketRows.header.length + __props.socketRows.body.length)} \u0438\u0437\u043C\u0435\u043D\u0435\u043D\u0438\u044F \u0432 \u0442\u0430\u0431\u043B\u0438\u0446\u0435 </span>`);
      _push(ssrRenderComponent(ButtonText, {
        onClick: ($event) => _ctx.$emit("callAction", { action: "socketUpdate", value: null })
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0417\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044C `);
          } else {
            return [
              createTextVNode(" \u0417\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044C ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$s = _sfc_main$s.setup;
_sfc_main$s.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Socket/Socket.vue");
  return _sfc_setup$s ? _sfc_setup$s(props, ctx) : void 0;
};
const TableSocket = _sfc_main$s;
const _sfc_main$r = {
  __name: "Mobile",
  __ssrInlineRender: true,
  props: {
    slug: {
      default: null,
      type: String
    },
    isTrash: {
      default: false,
      type: Boolean
    },
    loaderState: {
      default: null
    },
    isPermanentEdit: {
      default: false,
      type: Boolean
    },
    actionType: {
      default: "view",
      type: String
    },
    permissions: {
      default: {},
      type: Object
    },
    isCanOpenCount: {
      default: 0,
      type: Number
    }
  },
  emits: [
    "callAction",
    "changeValue"
  ],
  setup(__props, { emit: __emit }) {
    const fields = inject("fields");
    const bodyData = inject("bodyData");
    const backupRows = inject("backupRows");
    const sectionRef = inject("sectionRef");
    const actionState = inject("actionState");
    const backupValues = inject("backupValues");
    const scrollPosition = inject("scrollPosition");
    const skipChecking = inject("skipChecking");
    const isNumeric = inject("isNumeric");
    const isDinamyc = inject("isDinamyc");
    const is_admin = inject("is_admin");
    const userID = inject("userID");
    let clickSetting = ref({
      id: -1,
      delay: 500,
      clicks: 0,
      timer: null
    });
    const props = __props;
    const emit = __emit;
    const changeValue = (id, data) => {
      emit("changeValue", { value: { ...data, id } });
      let findedRow = bodyData.value.find((row) => row.id == id);
      findedRow[data.key] = data.value;
      if (data.key == "isChoose") {
        if (data.value) {
          actionState.value = props.isTrash ? "restoring" : "editting";
        } else if (bodyData.value.filter((p) => p.isChoose).length == 0) {
          actionState.value = null;
        }
      }
      if (props.isPermanentEdit) {
        skipChecking.value = true;
        if (actionState.value == null) {
          backupRows.value = JSON.parse(JSON.stringify(bodyData.value));
          actionState.value = props.isTrash ? "restoring" : "saving";
        }
        backupValues.value = JSON.parse(JSON.stringify(bodyData.value));
      }
      if (isDinamyc && data.key == "product_id") {
        findedRow.id = data.value.selectedOption ? data.value.selectedOption.id : null;
        findedRow.product_price = data.value.selectedOption ? data.value.selectedOption.price : null;
        findedRow.product_weight = data.value.selectedOption ? data.value.selectedOption.weight : null;
        findedRow.product_count = 1;
        if (data.value.value != null) {
          findedRow[data.key].localOptions = [{
            label: data.value.selectedOption,
            value: data.value.selectedOption.id
          }];
        }
      }
    };
    const doubleClick = (event, row, item) => {
      let regexp = /<\/?[a-z][\s\S]*>/i;
      if (!regexp.test(event.target.innerHTML))
        return;
      clickSetting.value.clicks++;
      if (clickSetting.value.clicks === 1) {
        clickSetting.value.timer = setTimeout(() => {
          clickSetting.value.clicks = 0;
        }, clickSetting.value.delay);
      } else {
        let regexp2 = /<\/?[a-z][\s\S]*>/i;
        if (!row.isEdit && actionState.value != "saving" && event.target.closest(".popup_actions") == null && regexp2.test(event.target.innerHTML)) {
          callAction({ action: "showModal", value: row.id });
        }
        window.getSelection().empty();
        clearTimeout(clickSetting.value.timer);
        clickSetting.value.clicks = 0;
      }
      clickSetting.value.id = item.id;
    };
    const callAction = (data) => {
      var _a;
      console.log(data);
      const openLink = (value) => {
        emit("callAction", {
          action: "showModal",
          value: {
            id: value.id,
            slug: value.slug,
            tab: [null, void 0].includes(value.tab) ? null : value.tab
          }
        });
      };
      const editRow = (value) => {
        let findedIndex = bodyData.value.findIndex((row) => row.id == value.id);
        backupValues.value.push(JSON.parse(JSON.stringify(bodyData.value[findedIndex])));
        bodyData.value[findedIndex].isEdit = true;
        bodyData.value[findedIndex].isChoose = true;
        actionState.value = "saving";
      };
      switch (data.action) {
        case "showModal":
          openLink({
            id: (_a = data.value.id) != null ? _a : data.value,
            slug: props.slug,
            tab: null
          });
          break;
        case "edit":
          editRow(data.value);
          break;
        case "openLink":
          openLink(data.value);
          break;
        default:
          emit("callAction", { action: data.action, value: data.value });
          break;
      }
    };
    const setPosition = () => {
      if (sectionRef.value.sectionRef.getBoundingClientRect().top > 0) {
        const rect = sectionRef.value.sectionRef.getBoundingClientRect();
        const isFullyVisible = rect.top >= 0 && rect.bottom <= window.innerHeight;
        if (isFullyVisible) {
          return (sectionRef.value.sectionRef.offsetHeight - 82) / 2 - 27;
        } else {
          return (window.innerHeight - sectionRef.value.sectionRef.getBoundingClientRect().top - 82) / 2 - 17;
        }
      } else {
        let startPosScrollBlock = sectionRef.value.sectionRef.getBoundingClientRect().top + window.pageYOffset - document.body.clientTop;
        if (sectionRef.value.sectionRef.getBoundingClientRect().height + startPosScrollBlock < window.scrollY + window.innerHeight) {
          return window.innerHeight / 2 + (window.scrollY - startPosScrollBlock - startPosScrollBlock + 5);
        } else {
          return window.innerHeight / 2 + window.scrollY - startPosScrollBlock - 41;
        }
      }
    };
    const dragStart = () => {
      if (actionState.value != "saving") {
        backupRows.value = JSON.parse(JSON.stringify(bodyData.value));
      }
    };
    const calcSum = (row) => {
      return (row.product_count * row.product_price).toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    };
    onUnmounted(() => {
      window.removeEventListener("scroll", throt_funScroll);
    });
    const throt_funScroll = () => {
      scrollPosition.value = setPosition();
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "table-template__body_mobile" }, _attrs))}>`);
      _push(ssrRenderComponent(AppLoader, {
        class: "table-template__loader",
        ref: "loaderRef"
      }, null, _parent));
      _push(ssrRenderComponent(unref(draggable), {
        tag: "div",
        class: ["table-mobile", unref(bodyData).length == 0 ? "table-mobile_empty" : "", props.isPermanentEdit ? "table-mobile_permanent-edit" : ""],
        itemKey: "table-mobile",
        modelValue: unref(bodyData),
        "onUpdate:modelValue": ($event) => isRef(bodyData) ? bodyData.value = $event : null,
        handle: ".icon__draggable",
        onStart: () => dragStart(),
        onEnd: (event) => emit("callAction", { action: "moveRows", value: event.to.__draggable_component__.modelValue })
      }, {
        item: withCtx(({ element: row, index }, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div style="${ssrRenderStyle(props.loaderState != "loading" ? null : { display: "none" })}" class="${ssrRenderClass([
              row.isEdit ? "table__row_edit" : row.isChoose ? "table__row_choosed" : "",
              row.isUpdated ? "table__row_updated" : "",
              props.isCanOpenCount != 0 && row.id > props.isCanOpenCount ? "table__row_disabled" : "",
              "table-mobile__row table__row"
            ])}"${_scopeId}><!--[-->`);
            ssrRenderList(unref(fields), (item) => {
              _push2(`<div class="${ssrRenderClass([[
                !item.enabled ? "table__item_hidden" : "",
                !["checkbox", "payment", "actions", "iconDrag", "iconDelete"].includes(item.type) && (!item.visible_always && unref(isEmpty)(item.type == "relation" ? row[item.key].value : row[item.key] != null ? String(row[item.key]) : row[item.key])) ? "table__item_unvisible" : ""
              ], "table-mobile__field table__item"])}" style="${ssrRenderStyle(`--colorItem: ${item.color}`)}"${_scopeId}>`);
              if (item.type == "checkbox") {
                _push2(ssrRenderComponent(AppCheckbox, {
                  item: {
                    isHTML: false,
                    id: row.id,
                    key: item.key,
                    title: item.title,
                    value: row[item.key],
                    required: Boolean(item.required)
                  },
                  disabled: unref(actionState) == "saving",
                  onChangeValue: (data) => changeValue(row.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "payment") {
                _push2(ssrRenderComponent(ButtonPayment, {
                  item: {
                    id: row.id,
                    key: item.key,
                    title: item.title,
                    value: row[item.key] ? row[item.key].value : null,
                    state: row[item.key] ? row[item.key].state : null,
                    isCanClick: item.can_edit
                  },
                  onInitPayment: ($event) => callAction({
                    action: "initPayment",
                    value: row[item.key]
                  })
                }, null, _parent2, _scopeId));
              } else if (item.type == "relation") {
                _push2(ssrRenderComponent(AppRelation, {
                  item: {
                    focus: false,
                    id: row.id,
                    placeholder: null,
                    key: item.key,
                    title: item.title,
                    value: row[item.key],
                    required: Boolean(item.required),
                    anotherKey: unref(isDinamyc) ? "product_name" : null,
                    anotherTitle: unref(isDinamyc) ? row.product_name : null,
                    options: ["status", "relation"].includes(item.type) ? item.options : null,
                    lockedOptions: item.choosed
                  },
                  isCanCreate: Boolean(item.can_create),
                  isAnotherTitle: unref(isDinamyc),
                  isMultiple: Boolean(item.is_plural),
                  isCanEdit: Boolean(item.can_edit),
                  isReadOnly: Boolean(item.read_only || !row.isEdit),
                  onChangeValue: (data) => changeValue(row.id, data),
                  onOpenLink: (data) => callAction({ action: "openLink", value: { id: data.id, slug: item.related_table } }),
                  onShowAll: () => callAction({ action: "openLink", value: { id: row.id, slug: props.slug, tab: item.related_table } }),
                  onCreateOption: () => emit("callAction", { action: "createOption", value: item.related_table })
                }, null, _parent2, _scopeId));
              } else if (["number", "password", "text"].includes(item.type) && (!unref(isDinamyc) || unref(isDinamyc) && item.key != "product_sum")) {
                _push2(ssrRenderComponent(AppTextarea, {
                  item: {
                    focus: false,
                    id: row.id,
                    placeholder: null,
                    key: item.key,
                    type: item.type,
                    title: item.title,
                    substring: item.unit,
                    required: Boolean(item.required),
                    external_link: ![null, void 0].includes(row[item.key]) && row[item.key] != "" ? row[item.key].external_link : null,
                    value: [null, void 0].includes(row[item.key]) ? null : typeof row[item.key] == "object" ? String(row[item.key].value) : String(row[item.key])
                  },
                  disabled: false,
                  isUseEnter: false,
                  mask: item.mask,
                  isLink: Boolean(item.is_external_link),
                  isReadOnly: Boolean(item.read_only || !row.isEdit),
                  onChangeValue: (data) => changeValue(row.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "json") {
                _push2(ssrRenderComponent(FormItem, {
                  class: "form-item__value",
                  required: Boolean(item.required)
                }, {
                  default: withCtx((_, _push3, _parent3, _scopeId2) => {
                    if (_push3) {
                      _push3(ssrRenderComponent(FormLabel, {
                        style: item.title != null && item.title != "" ? null : { display: "none" },
                        title: item.title
                      }, null, _parent3, _scopeId2));
                      _push3(ssrRenderComponent(FormValue, {
                        isHTML: true,
                        value: row[item.key],
                        isLink: Boolean(item.is_external_link),
                        link: typeof row[item.key] == "object" && row[item.key] != null ? row[item.key].external_link : null
                      }, null, _parent3, _scopeId2));
                    } else {
                      return [
                        withDirectives(createVNode(FormLabel, {
                          title: item.title
                        }, null, 8, ["title"]), [
                          [vShow, item.title != null && item.title != ""]
                        ]),
                        createVNode(FormValue, {
                          isHTML: true,
                          value: row[item.key],
                          isLink: Boolean(item.is_external_link),
                          link: typeof row[item.key] == "object" && row[item.key] != null ? row[item.key].external_link : null
                        }, null, 8, ["value", "isLink", "link"])
                      ];
                    }
                  }),
                  _: 2
                }, _parent2, _scopeId));
              } else if (unref(isDinamyc) && item.key == "product_sum") {
                _push2(ssrRenderComponent(FormValue, {
                  isHTML: true,
                  value: calcSum(row),
                  isLink: Boolean(item.is_external_link),
                  link: typeof row[item.key] == "object" && row[item.key] != null ? row[item.key].external_link : null
                }, null, _parent2, _scopeId));
              } else if (item.type == "actions") {
                _push2(ssrRenderComponent(AppActions, {
                  item: {
                    title: "\u0414\u0435\u0439\u0441\u0442\u0432\u0438\u0435",
                    slug: row.isEdit ? "edit" : props.actionType
                  },
                  disabled: !row.isChoose && unref(actionState) == "saving",
                  permissions: props.permissions,
                  userID: unref(userID),
                  is_admin: unref(is_admin),
                  relationID: row.user_id.value,
                  onCallAction: (data) => callAction({ action: data.value, value: row })
                }, null, _parent2, _scopeId));
              } else if (item.type == "status") {
                _push2(ssrRenderComponent(AppStatus, {
                  item: {
                    focus: false,
                    id: row.id,
                    key: item.key,
                    title: item.title,
                    options: item.options,
                    value: row[item.key],
                    required: Boolean(item.required)
                  },
                  isCanCreate: false,
                  isHaveNullOption: false,
                  isReadOnly: Boolean(item.read_only || !row.isEdit),
                  onChangeValue: (data) => changeValue(row.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "select_dropdown") {
                _push2(ssrRenderComponent(AppSelect, {
                  item: {
                    id: row.id,
                    key: item.key,
                    value: row[item.key],
                    focus: false,
                    required: Boolean(item.required),
                    title: item.title,
                    options: item.options,
                    lockedOptions: []
                  },
                  isReadOnly: Boolean(item.read_only || !row.isEdit),
                  isHaveNullOption: true,
                  isMultiple: Boolean(item.is_plural),
                  isFiltered: true,
                  onChangeValue: (data) => changeValue(row.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "file") {
                _push2(ssrRenderComponent(AppFile, {
                  item: {
                    id: row.id,
                    title: item.title,
                    key: item.key,
                    required: Boolean(item.required),
                    buttonName: null,
                    value: row[item.key]
                  },
                  isReadOnly: true,
                  isShowFileName: false,
                  isMultiple: false,
                  isOneFile: true,
                  onChangeValue: (data) => changeValue(row.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "date") {
                _push2(ssrRenderComponent(AppDate, {
                  item: {
                    id: row.id,
                    required: true,
                    title: item.title,
                    placeholder: null,
                    value: row[item.key],
                    key: item.key,
                    focus: false
                  },
                  isMultiple: Boolean(item.is_plural),
                  isReadOnly: Boolean(item.read_only || !row.isEdit),
                  onChangeValue: (data) => changeValue(row.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "address") {
                _push2(ssrRenderComponent(AppMap, {
                  item: {
                    id: row.id,
                    title: item.title,
                    key: item.key,
                    required: Boolean(item.required),
                    focus: item.focus,
                    value: row[item.key],
                    options: [],
                    lockedOptions: []
                  },
                  isReadOnly: Boolean(item.read_only || !row.isEdit),
                  isShowMap: false,
                  isCanSelect: false,
                  isShowLabel: false,
                  onChangeValue: (data) => changeValue(row.id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "iconDrag") {
                _push2(`<div class="table-item__icon"${_scopeId}>`);
                _push2(ssrRenderComponent(IconDrag, null, null, _parent2, _scopeId));
                _push2(ssrRenderComponent(FormValue, {
                  style: unref(isNumeric) ? null : { display: "none" },
                  isHTML: false,
                  value: index + 1,
                  isLink: false,
                  link: null
                }, null, _parent2, _scopeId));
                _push2(`</div>`);
              } else if (item.type == "iconDelete") {
                _push2(ssrRenderComponent(IconDelete, {
                  onClick: () => callAction({ action: "removeRow", value: index + 1 })
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<div${_scopeId}>${ssrInterpolate(item.type)}</div>`);
              }
              _push2(`</div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              withDirectives(createVNode("div", {
                class: [
                  "table-mobile__row table__row",
                  row.isEdit ? "table__row_edit" : row.isChoose ? "table__row_choosed" : "",
                  row.isUpdated ? "table__row_updated" : "",
                  props.isCanOpenCount != 0 && row.id > props.isCanOpenCount ? "table__row_disabled" : ""
                ]
              }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(fields), (item) => {
                  return openBlock(), createBlock("div", {
                    class: ["table-mobile__field table__item", [
                      !item.enabled ? "table__item_hidden" : "",
                      !["checkbox", "payment", "actions", "iconDrag", "iconDelete"].includes(item.type) && (!item.visible_always && unref(isEmpty)(item.type == "relation" ? row[item.key].value : row[item.key] != null ? String(row[item.key]) : row[item.key])) ? "table__item_unvisible" : ""
                    ]],
                    style: `--colorItem: ${item.color}`,
                    onClick: (event) => doubleClick(event, row, item)
                  }, [
                    item.type == "checkbox" ? (openBlock(), createBlock(AppCheckbox, {
                      key: 0,
                      item: {
                        isHTML: false,
                        id: row.id,
                        key: item.key,
                        title: item.title,
                        value: row[item.key],
                        required: Boolean(item.required)
                      },
                      disabled: unref(actionState) == "saving",
                      onChangeValue: (data) => changeValue(row.id, data)
                    }, null, 8, ["item", "disabled", "onChangeValue"])) : item.type == "payment" ? (openBlock(), createBlock(ButtonPayment, {
                      key: 1,
                      item: {
                        id: row.id,
                        key: item.key,
                        title: item.title,
                        value: row[item.key] ? row[item.key].value : null,
                        state: row[item.key] ? row[item.key].state : null,
                        isCanClick: item.can_edit
                      },
                      onInitPayment: ($event) => callAction({
                        action: "initPayment",
                        value: row[item.key]
                      })
                    }, null, 8, ["item", "onInitPayment"])) : item.type == "relation" ? (openBlock(), createBlock(AppRelation, {
                      key: 2,
                      item: {
                        focus: false,
                        id: row.id,
                        placeholder: null,
                        key: item.key,
                        title: item.title,
                        value: row[item.key],
                        required: Boolean(item.required),
                        anotherKey: unref(isDinamyc) ? "product_name" : null,
                        anotherTitle: unref(isDinamyc) ? row.product_name : null,
                        options: ["status", "relation"].includes(item.type) ? item.options : null,
                        lockedOptions: item.choosed
                      },
                      isCanCreate: Boolean(item.can_create),
                      isAnotherTitle: unref(isDinamyc),
                      isMultiple: Boolean(item.is_plural),
                      isCanEdit: Boolean(item.can_edit),
                      isReadOnly: Boolean(item.read_only || !row.isEdit),
                      onChangeValue: (data) => changeValue(row.id, data),
                      onOpenLink: (data) => callAction({ action: "openLink", value: { id: data.id, slug: item.related_table } }),
                      onShowAll: () => callAction({ action: "openLink", value: { id: row.id, slug: props.slug, tab: item.related_table } }),
                      onCreateOption: () => emit("callAction", { action: "createOption", value: item.related_table })
                    }, null, 8, ["item", "isCanCreate", "isAnotherTitle", "isMultiple", "isCanEdit", "isReadOnly", "onChangeValue", "onOpenLink", "onShowAll", "onCreateOption"])) : ["number", "password", "text"].includes(item.type) && (!unref(isDinamyc) || unref(isDinamyc) && item.key != "product_sum") ? (openBlock(), createBlock(AppTextarea, {
                      key: 3,
                      item: {
                        focus: false,
                        id: row.id,
                        placeholder: null,
                        key: item.key,
                        type: item.type,
                        title: item.title,
                        substring: item.unit,
                        required: Boolean(item.required),
                        external_link: ![null, void 0].includes(row[item.key]) && row[item.key] != "" ? row[item.key].external_link : null,
                        value: [null, void 0].includes(row[item.key]) ? null : typeof row[item.key] == "object" ? String(row[item.key].value) : String(row[item.key])
                      },
                      disabled: false,
                      isUseEnter: false,
                      mask: item.mask,
                      isLink: Boolean(item.is_external_link),
                      isReadOnly: Boolean(item.read_only || !row.isEdit),
                      onChangeValue: (data) => changeValue(row.id, data)
                    }, null, 8, ["item", "mask", "isLink", "isReadOnly", "onChangeValue"])) : item.type == "json" ? (openBlock(), createBlock(FormItem, {
                      key: 4,
                      class: "form-item__value",
                      required: Boolean(item.required)
                    }, {
                      default: withCtx(() => [
                        withDirectives(createVNode(FormLabel, {
                          title: item.title
                        }, null, 8, ["title"]), [
                          [vShow, item.title != null && item.title != ""]
                        ]),
                        createVNode(FormValue, {
                          isHTML: true,
                          value: row[item.key],
                          isLink: Boolean(item.is_external_link),
                          link: typeof row[item.key] == "object" && row[item.key] != null ? row[item.key].external_link : null
                        }, null, 8, ["value", "isLink", "link"])
                      ]),
                      _: 2
                    }, 1032, ["required"])) : unref(isDinamyc) && item.key == "product_sum" ? (openBlock(), createBlock(FormValue, {
                      key: 5,
                      isHTML: true,
                      value: calcSum(row),
                      isLink: Boolean(item.is_external_link),
                      link: typeof row[item.key] == "object" && row[item.key] != null ? row[item.key].external_link : null
                    }, null, 8, ["value", "isLink", "link"])) : item.type == "actions" ? (openBlock(), createBlock(AppActions, {
                      key: 6,
                      item: {
                        title: "\u0414\u0435\u0439\u0441\u0442\u0432\u0438\u0435",
                        slug: row.isEdit ? "edit" : props.actionType
                      },
                      disabled: !row.isChoose && unref(actionState) == "saving",
                      permissions: props.permissions,
                      userID: unref(userID),
                      is_admin: unref(is_admin),
                      relationID: row.user_id.value,
                      onCallAction: (data) => callAction({ action: data.value, value: row })
                    }, null, 8, ["item", "disabled", "permissions", "userID", "is_admin", "relationID", "onCallAction"])) : item.type == "status" ? (openBlock(), createBlock(AppStatus, {
                      key: 7,
                      item: {
                        focus: false,
                        id: row.id,
                        key: item.key,
                        title: item.title,
                        options: item.options,
                        value: row[item.key],
                        required: Boolean(item.required)
                      },
                      isCanCreate: false,
                      isHaveNullOption: false,
                      isReadOnly: Boolean(item.read_only || !row.isEdit),
                      onChangeValue: (data) => changeValue(row.id, data)
                    }, null, 8, ["item", "isReadOnly", "onChangeValue"])) : item.type == "select_dropdown" ? (openBlock(), createBlock(AppSelect, {
                      key: 8,
                      item: {
                        id: row.id,
                        key: item.key,
                        value: row[item.key],
                        focus: false,
                        required: Boolean(item.required),
                        title: item.title,
                        options: item.options,
                        lockedOptions: []
                      },
                      isReadOnly: Boolean(item.read_only || !row.isEdit),
                      isHaveNullOption: true,
                      isMultiple: Boolean(item.is_plural),
                      isFiltered: true,
                      onChangeValue: (data) => changeValue(row.id, data)
                    }, null, 8, ["item", "isReadOnly", "isMultiple", "onChangeValue"])) : item.type == "file" ? (openBlock(), createBlock(AppFile, {
                      key: 9,
                      item: {
                        id: row.id,
                        title: item.title,
                        key: item.key,
                        required: Boolean(item.required),
                        buttonName: null,
                        value: row[item.key]
                      },
                      isReadOnly: true,
                      isShowFileName: false,
                      isMultiple: false,
                      isOneFile: true,
                      onChangeValue: (data) => changeValue(row.id, data)
                    }, null, 8, ["item", "onChangeValue"])) : item.type == "date" ? (openBlock(), createBlock(AppDate, {
                      key: 10,
                      item: {
                        id: row.id,
                        required: true,
                        title: item.title,
                        placeholder: null,
                        value: row[item.key],
                        key: item.key,
                        focus: false
                      },
                      isMultiple: Boolean(item.is_plural),
                      isReadOnly: Boolean(item.read_only || !row.isEdit),
                      onChangeValue: (data) => changeValue(row.id, data)
                    }, null, 8, ["item", "isMultiple", "isReadOnly", "onChangeValue"])) : item.type == "address" ? (openBlock(), createBlock(AppMap, {
                      key: 11,
                      item: {
                        id: row.id,
                        title: item.title,
                        key: item.key,
                        required: Boolean(item.required),
                        focus: item.focus,
                        value: row[item.key],
                        options: [],
                        lockedOptions: []
                      },
                      isReadOnly: Boolean(item.read_only || !row.isEdit),
                      isShowMap: false,
                      isCanSelect: false,
                      isShowLabel: false,
                      onChangeValue: (data) => changeValue(row.id, data)
                    }, null, 8, ["item", "isReadOnly", "onChangeValue"])) : item.type == "iconDrag" ? (openBlock(), createBlock("div", {
                      key: 12,
                      class: "table-item__icon"
                    }, [
                      createVNode(IconDrag),
                      withDirectives(createVNode(FormValue, {
                        isHTML: false,
                        value: index + 1,
                        isLink: false,
                        link: null
                      }, null, 8, ["value"]), [
                        [vShow, unref(isNumeric)]
                      ])
                    ])) : item.type == "iconDelete" ? (openBlock(), createBlock(IconDelete, {
                      key: 13,
                      onClick: () => callAction({ action: "removeRow", value: index + 1 })
                    }, null, 8, ["onClick"])) : (openBlock(), createBlock("div", { key: 14 }, toDisplayString(item.type), 1))
                  ], 14, ["onClick"]);
                }), 256))
              ], 2), [
                [vShow, props.loaderState != "loading"]
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$r = _sfc_main$r.setup;
_sfc_main$r.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Mobile/Mobile.vue");
  return _sfc_setup$r ? _sfc_setup$r(props, ctx) : void 0;
};
const TableMobile = _sfc_main$r;
const _sfc_main$q = {
  __name: "Delete",
  __ssrInlineRender: true,
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const isShow = inject("isShow");
    const bodyData = inject("bodyData");
    const emit = __emit;
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0423\u0434\u0430\u043B\u0435\u043D\u0438\u0435 `);
          } else {
            return [
              createTextVNode(" \u0423\u0434\u0430\u043B\u0435\u043D\u0438\u0435 ")
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="warning__text"${_scopeId}> \u0411\u0443\u0434\u0435\u0442 \u0443\u0434\u0430\u043B\u0435\u043D\u043E ${ssrInterpolate(unref(bodyData).filter((p) => p.isChoose).length)} \u0441\u0442\u0440\u043E\u043A. \u041F\u0440\u043E\u0434\u043E\u043B\u0436\u0438\u0442\u044C? </div><div class="warning__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              class: "button_red",
              onClick: () => emit("callAction", { action: "delete", value: true })
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
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
            _push2(ssrRenderComponent(AppButton, {
              onClick: () => showWarning(false)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
                } else {
                  return [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "warning__text" }, " \u0411\u0443\u0434\u0435\u0442 \u0443\u0434\u0430\u043B\u0435\u043D\u043E " + toDisplayString(unref(bodyData).filter((p) => p.isChoose).length) + " \u0441\u0442\u0440\u043E\u043A. \u041F\u0440\u043E\u0434\u043E\u043B\u0436\u0438\u0442\u044C? ", 1),
              createVNode("div", { class: "warning__actions" }, [
                createVNode(AppButton, {
                  class: "button_red",
                  onClick: () => emit("callAction", { action: "delete", value: true })
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0423\u0434\u0430\u043B\u0438\u0442\u044C ")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(AppButton, {
                  onClick: () => showWarning(false)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ]),
                  _: 1
                }, 8, ["onClick"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$q = _sfc_main$q.setup;
_sfc_main$q.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Delete/Delete.vue");
  return _sfc_setup$q ? _sfc_setup$q(props, ctx) : void 0;
};
const WarningDelete = _sfc_main$q;
const _sfc_main$p = {
  __name: "Common",
  __ssrInlineRender: true,
  props: {
    balance: {
      default: 0,
      type: Number
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const userStore = useUserStore();
    const props = __props;
    const isShow = inject("isShow");
    const activePayment = inject("activePayment");
    const activeOption = ref({
      name: "\u0421\u0411\u041F (+1%)",
      slug: "payment",
      slugIDForm: "12299232",
      percent: 1
    });
    const options = [
      {
        name: "\u0421\u0411\u041F (+1%)",
        slug: "payment",
        slugIDForm: "12299232",
        percent: 1
      },
      {
        name: "Visa, MasterCard, \u041C\u0418\u0420 (+2.7%)",
        slug: "payment",
        slugIDForm: "9990214",
        percent: 2.7
      }
    ];
    const emit = __emit;
    const setOption = (option) => {
      userStore.activePaymentOption = option;
      activePayment.value.slugIDForm = option.slugIDForm;
      activePayment.value.name = option.name;
      activeOption.value = option;
      activePayment.value.percentValue = setValue.value;
    };
    const setValue = computed(() => {
      if (activeOption.value.percent > 0) {
        return (activePayment.value.value + Number(activePayment.value.value * (activeOption.value.percent > 0 ? Number(activeOption.value.percent / 100) : 1))).toFixed(2);
      } else {
        return activePayment.value.value;
      }
    });
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<!--[--><div class="warning__text"> \u0411\u0443\u0434\u0435\u0442 \u043E\u043F\u043B\u0430\u0447\u0435\u043D \u0448\u0442\u0440\u0430\u0444 \u0432 \u0440\u0430\u0437\u043C\u0435\u0440\u0435 <b>${ssrInterpolate(unref(setValue))}</b> \u0440\u0443\u0431\u043B\u0435\u0439. \u041F\u0440\u043E\u0434\u043E\u043B\u0436\u0438\u0442\u044C? </div>`);
      if (unref(activePayment).value > props.balance && (unref(userStore).activePaymentOption && unref(userStore).activePaymentOption.slug == "compas_pay")) {
        _push(`<div class="warning__text_error"> \u0414\u043B\u044F \u0442\u043E\u0433\u043E, \u0447\u0442\u043E\u0431\u044B \u043E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444, \u043D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u043E \u043F\u043E\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u0441\u0447\u0435\u0442 \u043D\u0430 \u0441\u0443\u043C\u043C\u0443 \u0432 \u0440\u0430\u0437\u043C\u0435\u0440\u0435 <b>${ssrInterpolate(unref(activePayment).value)}</b> \u0440\u0443\u0431\u043B\u0435\u0439. </div>`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="warning__actions"><div class="button-details">`);
      _push(ssrRenderComponent(AppButton, {
        class: ["button_blue", props.loaderButton ? "button_loading" : ""],
        disabledOption: unref(userStore).activePaymentOption && unref(userStore).activePaymentOption.slug == "compas_pay" && unref(activePayment).value > props.balance,
        onClick: () => emit("callAction", { action: "payment", value: unref(activeOption) })
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<span class="warning__text"${_scopeId}>${ssrInterpolate(unref(activeOption).name)} `);
            if (unref(activeOption).slug == "compas_pay") {
              _push2(`<!--[--><span class="warning__text_green"${_scopeId}>${ssrInterpolate(props.balance)}</span> \u0440\u0443\u0431. <!--]-->`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</span>`);
          } else {
            return [
              createVNode("span", { class: "warning__text" }, [
                createTextVNode(toDisplayString(unref(activeOption).name) + " ", 1),
                unref(activeOption).slug == "compas_pay" ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                  createVNode("span", { class: "warning__text_green" }, toDisplayString(props.balance), 1),
                  createTextVNode(" \u0440\u0443\u0431. ")
                ], 64)) : createCommentVNode("", true)
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(AppPopup, {
        class: "button-details__popup",
        isCanSelect: false,
        closeByClick: true
      }, {
        summary: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(IconTriangle, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(IconTriangle)
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<!--[-->`);
            ssrRenderList(options, (option) => {
              _push2(ssrRenderComponent(PopupOption, {
                onClick: () => setOption(option)
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(`${ssrInterpolate(option.name)} `);
                    if (option.slug == "compas_pay") {
                      _push3(`<!--[--><span class="warning__text_green"${_scopeId2}>${ssrInterpolate(props.balance)}</span> \u0440\u0443\u0431. <!--]-->`);
                    } else {
                      _push3(`<!---->`);
                    }
                  } else {
                    return [
                      createTextVNode(toDisplayString(option.name) + " ", 1),
                      option.slug == "compas_pay" ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                        createVNode("span", { class: "warning__text_green" }, toDisplayString(props.balance), 1),
                        createTextVNode(" \u0440\u0443\u0431. ")
                      ], 64)) : createCommentVNode("", true)
                    ];
                  }
                }),
                _: 2
              }, _parent2, _scopeId));
            });
            _push2(`<!--]-->`);
          } else {
            return [
              (openBlock(), createBlock(Fragment, null, renderList(options, (option) => {
                return createVNode(PopupOption, {
                  onClick: () => setOption(option)
                }, {
                  default: withCtx(() => [
                    createTextVNode(toDisplayString(option.name) + " ", 1),
                    option.slug == "compas_pay" ? (openBlock(), createBlock(Fragment, { key: 0 }, [
                      createVNode("span", { class: "warning__text_green" }, toDisplayString(props.balance), 1),
                      createTextVNode(" \u0440\u0443\u0431. ")
                    ], 64)) : createCommentVNode("", true)
                  ]),
                  _: 2
                }, 1032, ["onClick"]);
              }), 64))
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
      if (unref(activePayment).value > props.balance && (unref(userStore).activePaymentOption && unref(userStore).activePaymentOption.slug == "compas_pay")) {
        _push(ssrRenderComponent(_component_NuxtLink, { to: "/settings/?tab=tariffs" }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(AppButton, { class: "button_blue" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` \u041F\u043E\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u0441\u0447\u0435\u0442 `);
                  } else {
                    return [
                      createTextVNode(" \u041F\u043E\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u0441\u0447\u0435\u0442 ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(AppButton, { class: "button_blue" }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041F\u043E\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u0441\u0447\u0435\u0442 ")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(AppButton, {
        onClick: () => showWarning(false)
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
          } else {
            return [
              createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div><!--]-->`);
    };
  }
};
const _sfc_setup$p = _sfc_main$p.setup;
_sfc_main$p.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Payment/Common/Common.vue");
  return _sfc_setup$p ? _sfc_setup$p(props, ctx) : void 0;
};
const CommonForm = _sfc_main$p;
const _sfc_main$o = {
  __name: "Form",
  __ssrInlineRender: true,
  setup(__props) {
    const activePayment = inject("activePayment");
    const host = useRequestURL();
    const state = inject("state");
    inject("isShow");
    const showWarning = () => {
      state.value = "common";
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "warning__form" }, _attrs))}><div class="warning__text"> \u041E\u043F\u043B\u0430\u0442\u0438\u0442\u044C \u0448\u0442\u0440\u0430\u0444 \u0441 \u043F\u043E\u043C\u043E\u0449\u044C\u044E ${ssrInterpolate(unref(activePayment).name)}</div><div class="warning__actions"><form target="_blank" class="button" method="get" action="https://www.payanyway.ru/assistant.htm?version=v3"><input type="hidden" name="MNT_ID" value="70116321"><input type="hidden" name="MNT_SUCCESS_URL" value="https://compas.pro/payment"><input type="hidden" name="MNT_CURRENCY_CODE" value="RUB"><input type="hidden" name="MNT_AMOUNT"${ssrRenderAttr("value", unref(activePayment).percentValue)}><input type="hidden" name="MNT_TRANSACTION_ID"${ssrRenderAttr("value", unref(activePayment).transaction_id)}><input type="hidden" name="MNT_DESCRIPTION"${ssrRenderAttr("value", `\u041E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u0430 ${unref(activePayment).id} \u043E\u0442 \u0430\u043A\u043A\u0430\u0443\u043D\u0442\u0430 ${unref(host).hostname == "localhost" ? "opt6" : unref(host).hostname.split(".")[0]}`)}><input type="hidden" name="javascriptEnabled" value="true"><input type="hidden" name="followup" value="true"><input type="hidden" name="paymentSystem.unitId"${ssrRenderAttr("value", unref(activePayment).slugIDForm)}><input type="submit" class="pseudo-button" value="\u041E\u043F\u043B\u0430\u0442\u0438\u0442\u044C"></form>`);
      _push(ssrRenderComponent(AppButton, {
        class: "warning__button",
        onClick: () => showWarning()
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
          } else {
            return [
              createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div></div>`);
    };
  }
};
const _sfc_setup$o = _sfc_main$o.setup;
_sfc_main$o.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Payment/Form/Form.vue");
  return _sfc_setup$o ? _sfc_setup$o(props, ctx) : void 0;
};
const PaymentForm = _sfc_main$o;
const _sfc_main$n = {
  __name: "Payment",
  __ssrInlineRender: true,
  props: {
    balance: {
      default: 0,
      type: Number
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const isShow = inject("isShow");
    const state = ref("common");
    const props = __props;
    const emit = __emit;
    const callAction = (action) => {
      if (action.action == "payment") {
        if (action.value.slug == "payment") {
          state.value = "payment";
        } else {
          emit("callAction", action);
        }
      } else {
        emit("callAction", action);
      }
    };
    const showWarning = (state2) => {
      isShow.value.state = state2;
    };
    provide("state", state);
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u041E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u0430 `);
          } else {
            return [
              createTextVNode(" \u041E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u0430 ")
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (unref(state) == "common") {
              _push2(ssrRenderComponent(CommonForm, {
                balance: props.balance,
                onCallAction: (data) => callAction(data)
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            if (unref(state) == "payment") {
              _push2(ssrRenderComponent(PaymentForm, {
                balance: props.balance,
                onCallAction: (data) => callAction(data)
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
          } else {
            return [
              unref(state) == "common" ? (openBlock(), createBlock(CommonForm, {
                key: 0,
                balance: props.balance,
                onCallAction: (data) => callAction(data)
              }, null, 8, ["balance", "onCallAction"])) : createCommentVNode("", true),
              unref(state) == "payment" ? (openBlock(), createBlock(PaymentForm, {
                key: 1,
                balance: props.balance,
                onCallAction: (data) => callAction(data)
              }, null, 8, ["balance", "onCallAction"])) : createCommentVNode("", true)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$n = _sfc_main$n.setup;
_sfc_main$n.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Payment/Payment.vue");
  return _sfc_setup$n ? _sfc_setup$n(props, ctx) : void 0;
};
const WarningPayment = _sfc_main$n;
const _sfc_main$m = {
  __name: "Restore",
  __ssrInlineRender: true,
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const isShow = inject("isShow");
    const bodyData = inject("bodyData");
    const emit = __emit;
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0412\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u0435 `);
          } else {
            return [
              createTextVNode(" \u0412\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u0438\u0435 ")
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="warning__text"${_scopeId}> \u0411\u0443\u0434\u0435\u0442 \u0432\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u043E ${ssrInterpolate(unref(bodyData).filter((p) => p.isChoose).length)} \u0441\u0442\u0440\u043E\u043A. \u041F\u0440\u043E\u0434\u043E\u043B\u0436\u0438\u0442\u044C? </div><div class="warning__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              class: "button_blue",
              onClick: () => emit("callAction", { action: "restore", value: true })
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0412\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u0438\u0442\u044C `);
                } else {
                  return [
                    createTextVNode(" \u0412\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u0438\u0442\u044C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppButton, {
              onClick: () => showWarning(false)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
                } else {
                  return [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "warning__text" }, " \u0411\u0443\u0434\u0435\u0442 \u0432\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u043B\u0435\u043D\u043E " + toDisplayString(unref(bodyData).filter((p) => p.isChoose).length) + " \u0441\u0442\u0440\u043E\u043A. \u041F\u0440\u043E\u0434\u043E\u043B\u0436\u0438\u0442\u044C? ", 1),
              createVNode("div", { class: "warning__actions" }, [
                createVNode(AppButton, {
                  class: "button_blue",
                  onClick: () => emit("callAction", { action: "restore", value: true })
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0412\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u0438\u0442\u044C ")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(AppButton, {
                  onClick: () => showWarning(false)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ]),
                  _: 1
                }, 8, ["onClick"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$m = _sfc_main$m.setup;
_sfc_main$m.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Restore/Restore.vue");
  return _sfc_setup$m ? _sfc_setup$m(props, ctx) : void 0;
};
const WarningRestore = _sfc_main$m;
const _sfc_main$l = {
  __name: "Delete",
  __ssrInlineRender: true,
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const isShow = inject("isShow");
    const updatedCategory = inject("updatedCategory");
    const emit = __emit;
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0423\u0434\u0430\u043B\u0435\u043D\u0438\u0435 `);
          } else {
            return [
              createTextVNode(" \u0423\u0434\u0430\u043B\u0435\u043D\u0438\u0435 ")
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="warning__text"${_scopeId}> \u0411\u0443\u0434\u0435\u0442 \u0443\u0434\u0430\u043B\u0435\u043D\u0430 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u044F <b${_scopeId}>${ssrInterpolate(unref(updatedCategory).name)}</b>. \u041F\u0440\u043E\u0434\u043E\u043B\u0436\u0438\u0442\u044C? </div><div class="warning__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              class: "button_red",
              onClick: () => emit("callAction", { action: "deleteRole", value: unref(updatedCategory).id })
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
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
            _push2(ssrRenderComponent(AppButton, {
              onClick: () => showWarning(false)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
                } else {
                  return [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "warning__text" }, [
                createTextVNode(" \u0411\u0443\u0434\u0435\u0442 \u0443\u0434\u0430\u043B\u0435\u043D\u0430 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u044F "),
                createVNode("b", null, toDisplayString(unref(updatedCategory).name), 1),
                createTextVNode(". \u041F\u0440\u043E\u0434\u043E\u043B\u0436\u0438\u0442\u044C? ")
              ]),
              createVNode("div", { class: "warning__actions" }, [
                createVNode(AppButton, {
                  class: "button_red",
                  onClick: () => emit("callAction", { action: "deleteRole", value: unref(updatedCategory).id })
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0423\u0434\u0430\u043B\u0438\u0442\u044C ")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(AppButton, {
                  onClick: () => showWarning(false)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ]),
                  _: 1
                }, 8, ["onClick"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$l = _sfc_main$l.setup;
_sfc_main$l.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Role/Delete/Delete.vue");
  return _sfc_setup$l ? _sfc_setup$l(props, ctx) : void 0;
};
const WarningDeleteRole = _sfc_main$l;
const _sfc_main$k = {
  __name: "Validation",
  __ssrInlineRender: true,
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const isShow = inject("isShow");
    const fields = inject("fields");
    const bodyData = inject("bodyData");
    const invalidRows = inject("invalidRows");
    let activePage = ref(0);
    let activeRow = ref(invalidRows.value[0]);
    const emit = __emit;
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    const changeValue = (id, data) => {
      let findedIndexBody = bodyData.value.findIndex((p) => p.id == id);
      let findedIndexInvalid = invalidRows.value.findIndex((p) => p.id == id);
      bodyData.value[findedIndexBody][data.key] = data.value;
      invalidRows.value[findedIndexInvalid][data.key].value = data.value;
    };
    const changeActiveRow = (page) => {
      activePage.value = page;
      activeRow.value = invalidRows.value[page];
    };
    watch(() => invalidRows.value, () => {
      activePage.value = 0;
      activeRow.value = invalidRows.value[0];
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        class: "warning__validation",
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u041E\u0448\u0438\u0431\u043A\u0430 \u0432\u0430\u043B\u0438\u0434\u0430\u0446\u0438\u0438 `);
          } else {
            return [
              createTextVNode(" \u041E\u0448\u0438\u0431\u043A\u0430 \u0432\u0430\u043B\u0438\u0434\u0430\u0446\u0438\u0438 ")
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="warning__text warning__subtitle"${_scopeId}> ID: <b${_scopeId}>${ssrInterpolate(unref(activeRow) == null ? null : unref(activeRow).id)}</b></div><div class="warning__text"${_scopeId}> \u041D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u043E \u0437\u0430\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u044B\u0435 \u043F\u043E\u043B\u044F </div><div class="warning__list warning-list"${_scopeId}><!--[-->`);
            ssrRenderList(unref(fields).filter((p) => p.key != "id" && unref(activeRow)[p.key] != void 0), (item) => {
              _push2(`<div class="warning-list__field"${_scopeId}>`);
              if (item.type == "relation") {
                _push2(ssrRenderComponent(AppRelation, {
                  item: {
                    key: item.key,
                    type: item.type,
                    id: unref(activeRow).id,
                    title: item.title,
                    substring: item.unit,
                    value: unref(activeRow)[item.key].value,
                    is_link: item.is_link,
                    is_plural: item.is_plural,
                    hiddenOptions: item.choosed,
                    required: Boolean(item.required),
                    related_table: item.related_table,
                    is_external_link: item.is_external_link,
                    options: ["status", "relation"].includes(item.type) ? item.options : null,
                    external_link: unref(activeRow)[item.key].value != void 0 ? unref(activeRow)[item.key].value.external_link : null
                  },
                  isCanCreate: Boolean(item.can_create),
                  isUseEnter: false,
                  enabledAutocomplete: false,
                  isReadOnly: false,
                  isCanEdit: true,
                  isMultiple: Boolean(item.is_plural),
                  onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "text" && item.is_plural) {
                _push2(ssrRenderComponent(AppTextarea, {
                  item: {
                    key: item.key,
                    type: item.type,
                    id: unref(activeRow).id,
                    title: item.title,
                    substring: item.unit,
                    value: [null, void 0].includes(unref(activeRow)[item.key].value) ? null : String(unref(activeRow)[item.key].value),
                    is_link: item.is_link,
                    is_plural: item.is_plural,
                    hiddenOptions: item.choosed,
                    related_table: item.related_table,
                    is_external_link: item.is_external_link,
                    required: Boolean(item.required),
                    options: ["status", "relation"].includes(item.type) ? item.options : null,
                    external_link: unref(activeRow)[item.key].value != void 0 ? unref(activeRow)[item.key].value.external_link : null
                  },
                  isCanCreate: true,
                  isUseEnter: false,
                  enabledAutocomplete: false,
                  isReadOnly: false,
                  isMultiple: Boolean(item.is_plural),
                  onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "status") {
                _push2(ssrRenderComponent(AppStatus, {
                  item: {
                    key: item.key,
                    type: item.type,
                    id: unref(activeRow).id,
                    title: item.title,
                    substring: item.unit,
                    value: unref(activeRow)[item.key].value,
                    is_link: item.is_link,
                    is_plural: item.is_plural,
                    hiddenOptions: item.choosed,
                    related_table: item.related_table,
                    is_external_link: false,
                    options: item.options,
                    required: Boolean(item.required),
                    external_link: null
                  },
                  isCanCreate: false,
                  isHaveNullOption: false,
                  isUseEnter: false,
                  enabledAutocomplete: false,
                  isReadOnly: false,
                  isMultiple: Boolean(item.is_plural),
                  onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "select_dropdown") {
                _push2(ssrRenderComponent(AppSelect, {
                  item: {
                    id: unref(activeRow).id,
                    key: item.key,
                    value: unref(activeRow)[item.key].value,
                    focus: false,
                    required: Boolean(item.required),
                    title: item.title,
                    options: item.options,
                    lockedOptions: []
                  },
                  isReadOnly: false,
                  isHaveNullOption: true,
                  isMultiple: Boolean(item.is_plural),
                  isFiltered: true,
                  onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "date") {
                _push2(ssrRenderComponent(AppDate, {
                  item: {
                    id: unref(activeRow).id,
                    required: Boolean(item.required),
                    title: item.title,
                    placeholder: null,
                    value: [null, void 0].includes(unref(activeRow)[item.key].value) ? null : String(unref(activeRow)[item.key].value),
                    key: item.key,
                    focus: false
                  },
                  isMultiple: Boolean(item.is_plural),
                  isReadOnly: false,
                  onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                }, null, _parent2, _scopeId));
              } else if (["number", "password"].includes(item.type) || item.type == "text" && !item.is_plural) {
                _push2(ssrRenderComponent(AppInput, {
                  item: {
                    key: item.key,
                    type: item.type,
                    id: item.id,
                    title: item.title,
                    substring: item.unit,
                    value: [null, void 0].includes(unref(activeRow)[item.key].value) ? null : typeof unref(activeRow)[item.key].value == "object" ? unref(activeRow)[item.key].value.value : unref(activeRow)[item.key].value,
                    is_link: item.is_link,
                    is_plural: item.is_plural,
                    hiddenOptions: item.choosed,
                    related_table: item.related_table,
                    is_external_link: item.is_external_link,
                    required: Boolean(item.required),
                    options: null,
                    external_link: unref(activeRow)[item.key].value != void 0 ? unref(activeRow)[item.key].value.external_link : null
                  },
                  isCanCreate: true,
                  isUseEnter: false,
                  enabledAutocomplete: false,
                  isReadOnly: false,
                  isMultiple: Boolean(item.is_plural),
                  onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                }, null, _parent2, _scopeId));
              } else if (item.type == "file") {
                _push2(ssrRenderComponent(AppFile, {
                  item: {
                    id: unref(activeRow).id,
                    title: item.title,
                    key: item.key,
                    required: Boolean(item.required),
                    buttonName: null,
                    value: [null, void 0].includes(unref(activeRow)[item.key].value) ? null : unref(activeRow)[item.key].value
                  },
                  isReadOnly: false,
                  isShowFileName: false,
                  isMultiple: true,
                  isOneFile: false,
                  onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                }, null, _parent2, _scopeId));
              } else {
                _push2(`<!---->`);
              }
              _push2(`<div class="warning-list__field-error"${_scopeId}>${ssrInterpolate(unref(activeRow)[item.key].error)}</div></div>`);
            });
            _push2(`<!--]--></div><div class="warning__text warning__progress"${_scopeId}>${ssrInterpolate(unref(activePage) + 1)} \u0438\u0437 ${ssrInterpolate(unref(invalidRows).length)}</div><div class="warning__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              style: unref(activePage) < unref(invalidRows).length - 1 ? null : { display: "none" },
              class: "button_blue",
              onClick: () => changeActiveRow(unref(activePage) + 1)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0414\u0430\u043B\u0435\u0435 `);
                } else {
                  return [
                    createTextVNode(" \u0414\u0430\u043B\u0435\u0435 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppButton, {
              style: unref(activePage) == unref(invalidRows).length - 1 ? null : { display: "none" },
              class: "button_blue",
              onClick: () => emit("callAction", { action: "save", value: true })
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C `);
                } else {
                  return [
                    createTextVNode(" \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppButton, {
              style: unref(activePage) > 0 ? null : { display: "none" },
              onClick: () => changeActiveRow(unref(activePage) - 1)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041D\u0430\u0437\u0430\u0434 `);
                } else {
                  return [
                    createTextVNode(" \u041D\u0430\u0437\u0430\u0434 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppButton, {
              onClick: () => showWarning(false)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
                } else {
                  return [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "warning__text warning__subtitle" }, [
                createTextVNode(" ID: "),
                createVNode("b", null, toDisplayString(unref(activeRow) == null ? null : unref(activeRow).id), 1)
              ]),
              createVNode("div", { class: "warning__text" }, " \u041D\u0435\u043E\u0431\u0445\u043E\u0434\u0438\u043C\u043E \u0437\u0430\u043F\u043E\u043B\u043D\u0438\u0442\u044C \u043E\u0431\u044F\u0437\u0430\u0442\u0435\u043B\u044C\u043D\u044B\u0435 \u043F\u043E\u043B\u044F "),
              createVNode("div", { class: "warning__list warning-list" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(fields).filter((p) => p.key != "id" && unref(activeRow)[p.key] != void 0), (item) => {
                  return openBlock(), createBlock("div", { class: "warning-list__field" }, [
                    item.type == "relation" ? (openBlock(), createBlock(AppRelation, {
                      key: 0,
                      item: {
                        key: item.key,
                        type: item.type,
                        id: unref(activeRow).id,
                        title: item.title,
                        substring: item.unit,
                        value: unref(activeRow)[item.key].value,
                        is_link: item.is_link,
                        is_plural: item.is_plural,
                        hiddenOptions: item.choosed,
                        required: Boolean(item.required),
                        related_table: item.related_table,
                        is_external_link: item.is_external_link,
                        options: ["status", "relation"].includes(item.type) ? item.options : null,
                        external_link: unref(activeRow)[item.key].value != void 0 ? unref(activeRow)[item.key].value.external_link : null
                      },
                      isCanCreate: Boolean(item.can_create),
                      isUseEnter: false,
                      enabledAutocomplete: false,
                      isReadOnly: false,
                      isCanEdit: true,
                      isMultiple: Boolean(item.is_plural),
                      onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                    }, null, 8, ["item", "isCanCreate", "isMultiple", "onChangeValue"])) : item.type == "text" && item.is_plural ? (openBlock(), createBlock(AppTextarea, {
                      key: 1,
                      item: {
                        key: item.key,
                        type: item.type,
                        id: unref(activeRow).id,
                        title: item.title,
                        substring: item.unit,
                        value: [null, void 0].includes(unref(activeRow)[item.key].value) ? null : String(unref(activeRow)[item.key].value),
                        is_link: item.is_link,
                        is_plural: item.is_plural,
                        hiddenOptions: item.choosed,
                        related_table: item.related_table,
                        is_external_link: item.is_external_link,
                        required: Boolean(item.required),
                        options: ["status", "relation"].includes(item.type) ? item.options : null,
                        external_link: unref(activeRow)[item.key].value != void 0 ? unref(activeRow)[item.key].value.external_link : null
                      },
                      isCanCreate: true,
                      isUseEnter: false,
                      enabledAutocomplete: false,
                      isReadOnly: false,
                      isMultiple: Boolean(item.is_plural),
                      onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : item.type == "status" ? (openBlock(), createBlock(AppStatus, {
                      key: 2,
                      item: {
                        key: item.key,
                        type: item.type,
                        id: unref(activeRow).id,
                        title: item.title,
                        substring: item.unit,
                        value: unref(activeRow)[item.key].value,
                        is_link: item.is_link,
                        is_plural: item.is_plural,
                        hiddenOptions: item.choosed,
                        related_table: item.related_table,
                        is_external_link: false,
                        options: item.options,
                        required: Boolean(item.required),
                        external_link: null
                      },
                      isCanCreate: false,
                      isHaveNullOption: false,
                      isUseEnter: false,
                      enabledAutocomplete: false,
                      isReadOnly: false,
                      isMultiple: Boolean(item.is_plural),
                      onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : item.type == "select_dropdown" ? (openBlock(), createBlock(AppSelect, {
                      key: 3,
                      item: {
                        id: unref(activeRow).id,
                        key: item.key,
                        value: unref(activeRow)[item.key].value,
                        focus: false,
                        required: Boolean(item.required),
                        title: item.title,
                        options: item.options,
                        lockedOptions: []
                      },
                      isReadOnly: false,
                      isHaveNullOption: true,
                      isMultiple: Boolean(item.is_plural),
                      isFiltered: true,
                      onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : item.type == "date" ? (openBlock(), createBlock(AppDate, {
                      key: 4,
                      item: {
                        id: unref(activeRow).id,
                        required: Boolean(item.required),
                        title: item.title,
                        placeholder: null,
                        value: [null, void 0].includes(unref(activeRow)[item.key].value) ? null : String(unref(activeRow)[item.key].value),
                        key: item.key,
                        focus: false
                      },
                      isMultiple: Boolean(item.is_plural),
                      isReadOnly: false,
                      onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : ["number", "password"].includes(item.type) || item.type == "text" && !item.is_plural ? (openBlock(), createBlock(AppInput, {
                      key: 5,
                      item: {
                        key: item.key,
                        type: item.type,
                        id: item.id,
                        title: item.title,
                        substring: item.unit,
                        value: [null, void 0].includes(unref(activeRow)[item.key].value) ? null : typeof unref(activeRow)[item.key].value == "object" ? unref(activeRow)[item.key].value.value : unref(activeRow)[item.key].value,
                        is_link: item.is_link,
                        is_plural: item.is_plural,
                        hiddenOptions: item.choosed,
                        related_table: item.related_table,
                        is_external_link: item.is_external_link,
                        required: Boolean(item.required),
                        options: null,
                        external_link: unref(activeRow)[item.key].value != void 0 ? unref(activeRow)[item.key].value.external_link : null
                      },
                      isCanCreate: true,
                      isUseEnter: false,
                      enabledAutocomplete: false,
                      isReadOnly: false,
                      isMultiple: Boolean(item.is_plural),
                      onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                    }, null, 8, ["item", "isMultiple", "onChangeValue"])) : item.type == "file" ? (openBlock(), createBlock(AppFile, {
                      key: 6,
                      item: {
                        id: unref(activeRow).id,
                        title: item.title,
                        key: item.key,
                        required: Boolean(item.required),
                        buttonName: null,
                        value: [null, void 0].includes(unref(activeRow)[item.key].value) ? null : unref(activeRow)[item.key].value
                      },
                      isReadOnly: false,
                      isShowFileName: false,
                      isMultiple: true,
                      isOneFile: false,
                      onChangeValue: (data) => changeValue(unref(activeRow).id, data)
                    }, null, 8, ["item", "onChangeValue"])) : createCommentVNode("", true),
                    createVNode("div", { class: "warning-list__field-error" }, toDisplayString(unref(activeRow)[item.key].error), 1)
                  ]);
                }), 256))
              ]),
              createVNode("div", { class: "warning__text warning__progress" }, toDisplayString(unref(activePage) + 1) + " \u0438\u0437 " + toDisplayString(unref(invalidRows).length), 1),
              createVNode("div", { class: "warning__actions" }, [
                withDirectives(createVNode(AppButton, {
                  class: "button_blue",
                  onClick: () => changeActiveRow(unref(activePage) + 1)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0414\u0430\u043B\u0435\u0435 ")
                  ]),
                  _: 1
                }, 8, ["onClick"]), [
                  [vShow, unref(activePage) < unref(invalidRows).length - 1]
                ]),
                withDirectives(createVNode(AppButton, {
                  class: "button_blue",
                  onClick: () => emit("callAction", { action: "save", value: true })
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C ")
                  ]),
                  _: 1
                }, 8, ["onClick"]), [
                  [vShow, unref(activePage) == unref(invalidRows).length - 1]
                ]),
                withDirectives(createVNode(AppButton, {
                  onClick: () => changeActiveRow(unref(activePage) - 1)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041D\u0430\u0437\u0430\u0434 ")
                  ]),
                  _: 1
                }, 8, ["onClick"]), [
                  [vShow, unref(activePage) > 0]
                ]),
                createVNode(AppButton, {
                  onClick: () => showWarning(false)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ]),
                  _: 1
                }, 8, ["onClick"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$k = _sfc_main$k.setup;
_sfc_main$k.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Validation/Validation.vue");
  return _sfc_setup$k ? _sfc_setup$k(props, ctx) : void 0;
};
const WarningValidation = _sfc_main$k;
const _sfc_main$j = {
  __name: "Settings",
  __ssrInlineRender: true,
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const isShow = inject("isShow");
    inject("updatedCategory");
    inject("categories");
    let role = ref({
      label: null
    });
    let error = ref({
      state: false,
      text: ""
    });
    const emit = __emit;
    const changeValue = (data) => {
      role.value[data.key] = data.value;
    };
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    const saveSettings = () => {
      error.value.state = false;
      if (role.value.label == null || role.value.label.trim() == "") {
        error.value = {
          state: true,
          text: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u0440\u043E\u043B\u0438 \u043D\u0435 \u043C\u043E\u0436\u0435\u0442 \u0431\u044B\u0442\u044C \u043F\u0443\u0441\u0442\u044B\u043C"
        };
        return;
      }
      if (isShow.value.type == "createRole") {
        emit("callAction", {
          action: "createRole",
          value: role.value
        });
      } else {
        emit("callAction", {
          action: "updateRole",
          value: role.value
        });
      }
      isShow.value = {
        state: false,
        type: null
      };
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(unref(isShow).type == "createRole" ? "\u0421\u043E\u0437\u0434\u0430\u043D\u0438\u0435 \u0440\u043E\u043B\u0438" : "\u0418\u0437\u043C\u0435\u043D\u0435\u043D\u0438\u0435 \u0440\u043E\u043B\u0438")}`);
          } else {
            return [
              createTextVNode(toDisplayString(unref(isShow).type == "createRole" ? "\u0421\u043E\u0437\u0434\u0430\u043D\u0438\u0435 \u0440\u043E\u043B\u0438" : "\u0418\u0437\u043C\u0435\u043D\u0435\u043D\u0438\u0435 \u0440\u043E\u043B\u0438"), 1)
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="warning__list"${_scopeId}><div class="warning-list__field"${_scopeId}>`);
            _push2(ssrRenderComponent(AppInput, {
              item: {
                id: 2,
                required: false,
                substring: null,
                type: "text",
                title: "\u0418\u043C\u044F \u0440\u043E\u043B\u0438",
                placeholder: null,
                value: unref(role).label,
                key: "label",
                focus: false
              },
              inputLength: 50,
              isReadOnly: false,
              mask: null,
              disabled: false,
              enabledAutocomplete: false,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
            if (unref(error).state) {
              _push2(`<div class="warning-list__field-error"${_scopeId}>${ssrInterpolate(unref(error).text)}</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div></div><div class="warning__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              class: "button_blue",
              onClick: () => saveSettings()
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C `);
                } else {
                  return [
                    createTextVNode(" \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppButton, {
              onClick: () => showWarning(false)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
                } else {
                  return [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "warning__list" }, [
                createVNode("div", { class: "warning-list__field" }, [
                  createVNode(AppInput, {
                    item: {
                      id: 2,
                      required: false,
                      substring: null,
                      type: "text",
                      title: "\u0418\u043C\u044F \u0440\u043E\u043B\u0438",
                      placeholder: null,
                      value: unref(role).label,
                      key: "label",
                      focus: false
                    },
                    inputLength: 50,
                    isReadOnly: false,
                    mask: null,
                    disabled: false,
                    enabledAutocomplete: false,
                    onChangeValue: (data) => changeValue(data)
                  }, null, 8, ["item", "onChangeValue"]),
                  unref(error).state ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "warning-list__field-error"
                  }, toDisplayString(unref(error).text), 1)) : createCommentVNode("", true)
                ])
              ]),
              createVNode("div", { class: "warning__actions" }, [
                createVNode(AppButton, {
                  class: "button_blue",
                  onClick: () => saveSettings()
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C ")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(AppButton, {
                  onClick: () => showWarning(false)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ]),
                  _: 1
                }, 8, ["onClick"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$j = _sfc_main$j.setup;
_sfc_main$j.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Role/Settings/Settings.vue");
  return _sfc_setup$j ? _sfc_setup$j(props, ctx) : void 0;
};
const WarningSettingsRole = _sfc_main$j;
const _sfc_main$i = {
  __name: "Delete",
  __ssrInlineRender: true,
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const isShow = inject("isShow");
    const updatedCategory = inject("updatedCategory");
    const emit = __emit;
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0423\u0434\u0430\u043B\u0435\u043D\u0438\u0435 `);
          } else {
            return [
              createTextVNode(" \u0423\u0434\u0430\u043B\u0435\u043D\u0438\u0435 ")
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="warning__text"${_scopeId}> \u0411\u0443\u0434\u0435\u0442 \u0443\u0434\u0430\u043B\u0435\u043D\u0430 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u044F <b${_scopeId}>${ssrInterpolate(unref(updatedCategory).name)}</b>. \u041F\u0440\u043E\u0434\u043E\u043B\u0436\u0438\u0442\u044C? </div><div class="warning__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              class: "button_red",
              onClick: () => emit("callAction", { action: "deleteCategory", value: unref(updatedCategory).id })
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
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
            _push2(ssrRenderComponent(AppButton, {
              onClick: () => showWarning(false)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
                } else {
                  return [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "warning__text" }, [
                createTextVNode(" \u0411\u0443\u0434\u0435\u0442 \u0443\u0434\u0430\u043B\u0435\u043D\u0430 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u044F "),
                createVNode("b", null, toDisplayString(unref(updatedCategory).name), 1),
                createTextVNode(". \u041F\u0440\u043E\u0434\u043E\u043B\u0436\u0438\u0442\u044C? ")
              ]),
              createVNode("div", { class: "warning__actions" }, [
                createVNode(AppButton, {
                  class: "button_red",
                  onClick: () => emit("callAction", { action: "deleteCategory", value: unref(updatedCategory).id })
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0423\u0434\u0430\u043B\u0438\u0442\u044C ")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(AppButton, {
                  onClick: () => showWarning(false)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ]),
                  _: 1
                }, 8, ["onClick"])
              ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Category/Delete/Delete.vue");
  return _sfc_setup$i ? _sfc_setup$i(props, ctx) : void 0;
};
const WarningDeleteCategory = _sfc_main$i;
const _sfc_main$h = {
  __name: "Settings",
  __ssrInlineRender: true,
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const isShow = inject("isShow");
    inject("categories");
    inject("updatedCategory");
    const localCategories = ref([]);
    let error = ref({
      state: false,
      text: ""
    });
    let category = ref({
      name: "\u041D\u043E\u0432\u0430\u044F \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u044F",
      children: [],
      parent_id: null
    });
    const emit = __emit;
    const changeValue = (data) => {
      category.value[data.key] = data.value;
    };
    const showWarning = (state) => {
      isShow.value.state = state;
    };
    const saveSettings = () => {
      if (category.value.name == null || category.value.name.trim() == "") {
        error.value = {
          state: true,
          text: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u0440\u043E\u043B\u0438 \u043D\u0435 \u043C\u043E\u0436\u0435\u0442 \u0431\u044B\u0442\u044C \u043F\u0443\u0441\u0442\u044B\u043C"
        };
        return;
      }
      if (isShow.value.type == "createCategory" || isShow.value.type == "createSubCategory") {
        emit("callAction", {
          action: "createCategory",
          value: category.value
        });
      } else {
        emit("callAction", {
          action: "updateCategory",
          value: category.value
        });
      }
      isShow.value = {
        state: false,
        type: null
      };
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppWarning$1, mergeProps({
        onCloseModal: () => showWarning(false),
        isShow: unref(isShow).state
      }, _attrs), {
        title: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(unref(isShow).type == "createCategory" ? "\u0421\u043E\u0437\u0434\u0430\u043D\u0438\u0435 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u0438" : "\u0418\u0437\u043C\u0435\u043D\u0435\u043D\u0438\u0435 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u0438")}`);
          } else {
            return [
              createTextVNode(toDisplayString(unref(isShow).type == "createCategory" ? "\u0421\u043E\u0437\u0434\u0430\u043D\u0438\u0435 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u0438" : "\u0418\u0437\u043C\u0435\u043D\u0435\u043D\u0438\u0435 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u0438"), 1)
            ];
          }
        }),
        body: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="warning__list"${_scopeId}><div class="warning-list__field"${_scopeId}>`);
            _push2(ssrRenderComponent(AppInput, {
              item: {
                id: 2,
                required: false,
                substring: null,
                type: "text",
                title: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u0438",
                placeholder: null,
                value: unref(category).name,
                key: "name",
                focus: false
              },
              inputLength: 50,
              isReadOnly: false,
              mask: null,
              disabled: false,
              enabledAutocomplete: false,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
            if (unref(error).state) {
              _push2(`<div class="warning-list__field-error"${_scopeId}>${ssrInterpolate(unref(error).text)}</div>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            _push2(ssrRenderComponent(AppSelect, {
              class: "warning-list__field",
              item: {
                id: 1,
                key: "parent_id",
                value: unref(category).parent_id,
                focus: false,
                required: false,
                title: "\u0420\u0430\u0441\u043F\u043E\u043B\u043E\u0436\u0435\u043D\u0438\u0435 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u0438",
                lockedOptions: [],
                options: localCategories.value
              },
              isReadOnly: false,
              isHaveNullOption: false,
              isMultiple: false,
              isFiltered: true,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
            _push2(`</div><div class="warning__actions"${_scopeId}>`);
            _push2(ssrRenderComponent(AppButton, {
              class: "button_blue",
              onClick: () => saveSettings()
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C `);
                } else {
                  return [
                    createTextVNode(" \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppButton, {
              onClick: () => showWarning(false)
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
                } else {
                  return [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "warning__list" }, [
                createVNode("div", { class: "warning-list__field" }, [
                  createVNode(AppInput, {
                    item: {
                      id: 2,
                      required: false,
                      substring: null,
                      type: "text",
                      title: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u0438",
                      placeholder: null,
                      value: unref(category).name,
                      key: "name",
                      focus: false
                    },
                    inputLength: 50,
                    isReadOnly: false,
                    mask: null,
                    disabled: false,
                    enabledAutocomplete: false,
                    onChangeValue: (data) => changeValue(data)
                  }, null, 8, ["item", "onChangeValue"]),
                  unref(error).state ? (openBlock(), createBlock("div", {
                    key: 0,
                    class: "warning-list__field-error"
                  }, toDisplayString(unref(error).text), 1)) : createCommentVNode("", true)
                ]),
                createVNode(AppSelect, {
                  class: "warning-list__field",
                  item: {
                    id: 1,
                    key: "parent_id",
                    value: unref(category).parent_id,
                    focus: false,
                    required: false,
                    title: "\u0420\u0430\u0441\u043F\u043E\u043B\u043E\u0436\u0435\u043D\u0438\u0435 \u043A\u0430\u0442\u0435\u0433\u043E\u0440\u0438\u0438",
                    lockedOptions: [],
                    options: localCategories.value
                  },
                  isReadOnly: false,
                  isHaveNullOption: false,
                  isMultiple: false,
                  isFiltered: true,
                  onChangeValue: (data) => changeValue(data)
                }, null, 8, ["item", "onChangeValue"])
              ]),
              createVNode("div", { class: "warning__actions" }, [
                createVNode(AppButton, {
                  class: "button_blue",
                  onClick: () => saveSettings()
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C ")
                  ]),
                  _: 1
                }, 8, ["onClick"]),
                createVNode(AppButton, {
                  onClick: () => showWarning(false)
                }, {
                  default: withCtx(() => [
                    createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
                  ]),
                  _: 1
                }, 8, ["onClick"])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$h = _sfc_main$h.setup;
_sfc_main$h.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Category/Settings/Settings.vue");
  return _sfc_setup$h ? _sfc_setup$h(props, ctx) : void 0;
};
const WarningSettingsCategory = _sfc_main$h;
const _sfc_main$g = {
  __name: "Warning",
  __ssrInlineRender: true,
  props: {
    balance: {
      default: 0,
      type: Number
    }
  },
  setup(__props) {
    const isShow = inject("isShow");
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      if (unref(isShow).state && unref(isShow).type == "delete") {
        _push(ssrRenderComponent(WarningDelete, _attrs, null, _parent));
      } else if (unref(isShow).state && unref(isShow).type == "restore") {
        _push(ssrRenderComponent(WarningRestore, _attrs, null, _parent));
      } else if (unref(isShow).state && unref(isShow).type == "payment") {
        _push(ssrRenderComponent(WarningPayment, mergeProps({
          balance: props.balance
        }, _attrs), null, _parent));
      } else if (unref(isShow).state && unref(isShow).type == "validation") {
        _push(ssrRenderComponent(WarningValidation, _attrs, null, _parent));
      } else if (unref(isShow).state && unref(isShow).type == "deleteCategory") {
        _push(ssrRenderComponent(WarningDeleteCategory, _attrs, null, _parent));
      } else if (unref(isShow).state && (unref(isShow).type == "createCategory" || unref(isShow).type == "updateCategory" || unref(isShow).type == "createSubCategory")) {
        _push(ssrRenderComponent(WarningSettingsCategory, _attrs, null, _parent));
      } else if (unref(isShow).state && unref(isShow).type == "deleteRole") {
        _push(ssrRenderComponent(WarningDeleteRole, _attrs, null, _parent));
      } else if (unref(isShow).state && (unref(isShow).type == "createRole" || unref(isShow).type == "updateRole")) {
        _push(ssrRenderComponent(WarningSettingsRole, _attrs, null, _parent));
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$g = _sfc_main$g.setup;
_sfc_main$g.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Warning/Warning.vue");
  return _sfc_setup$g ? _sfc_setup$g(props, ctx) : void 0;
};
const TableWarning = _sfc_main$g;
const _sfc_main$f = {
  __name: "Header",
  __ssrInlineRender: true,
  props: {
    title: {
      default: "Undefined",
      type: String
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "section__title-wrapper" }, _attrs))}><h3 class="section__title">${ssrInterpolate(props.title)}</h3>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div>`);
    };
  }
};
const _sfc_setup$f = _sfc_main$f.setup;
_sfc_main$f.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSection/Header/Header.vue");
  return _sfc_setup$f ? _sfc_setup$f(props, ctx) : void 0;
};
const SectionHeader = _sfc_main$f;
const _sfc_main$e = {
  __name: "Details",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        title: "",
        children: []
      },
      type: Object
    },
    parent_id: {
      default: null,
      type: Number
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const popupRef = ref(null);
    const emit = __emit;
    const props = __props;
    const openPopup = () => {
      if (popupRef.value.popupRef.closest("summary details")) {
        let parentDelails = popupRef.value.popupRef.closest(".table-categories__details");
        if (parentDelails.hasAttribute("open")) {
          setTimeout(() => {
            if (popupRef.value.popupRef.hasAttribute("open")) {
              popupRef.value.popupRef.removeAttribute("open");
            } else {
              popupRef.value.popupRef.setAttribute("open", true);
            }
          }, 10);
        }
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppPopup, mergeProps({
        ref_key: "popupRef",
        ref: popupRef,
        closeByClick: true,
        isCanSelect: false,
        class: "table-categories__popup",
        onOpenPopup: () => openPopup()
      }, _attrs), {
        summary: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(IconDots, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(IconDots)
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(PopupOption, {
              onClick: (event) => {
                event.preventDefault();
                emit("callAction", {
                  action: "initCreateSubCategory",
                  value: {
                    parent_id: props.parent_id,
                    id: props.item.id
                  }
                });
              }
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043F\u043E\u0434\u0433\u0440\u0443\u043F\u043F\u0443 `);
                } else {
                  return [
                    createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043F\u043E\u0434\u0433\u0440\u0443\u043F\u043F\u0443 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(PopupOption, {
              onClick: (event) => {
                event.preventDefault();
                emit("callAction", {
                  action: "initUpdateCategory",
                  value: {
                    parent_id: props.parent_id,
                    id: props.item.id
                  }
                });
              }
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C `);
                } else {
                  return [
                    createTextVNode(" \u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(PopupOption, {
              class: "popup__option_red",
              onClick: (event) => {
                event.preventDefault();
                emit("callAction", {
                  action: "initDeleteCategory",
                  value: {
                    parent_id: props.parent_id,
                    id: props.item.id,
                    name: props.item.name
                  }
                });
              }
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
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
                onClick: (event) => {
                  event.preventDefault();
                  emit("callAction", {
                    action: "initCreateSubCategory",
                    value: {
                      parent_id: props.parent_id,
                      id: props.item.id
                    }
                  });
                }
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043F\u043E\u0434\u0433\u0440\u0443\u043F\u043F\u0443 ")
                ]),
                _: 1
              }, 8, ["onClick"]),
              createVNode(PopupOption, {
                onClick: (event) => {
                  event.preventDefault();
                  emit("callAction", {
                    action: "initUpdateCategory",
                    value: {
                      parent_id: props.parent_id,
                      id: props.item.id
                    }
                  });
                }
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C ")
                ]),
                _: 1
              }, 8, ["onClick"]),
              createVNode(PopupOption, {
                class: "popup__option_red",
                onClick: (event) => {
                  event.preventDefault();
                  emit("callAction", {
                    action: "initDeleteCategory",
                    value: {
                      parent_id: props.parent_id,
                      id: props.item.id,
                      name: props.item.name
                    }
                  });
                }
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
const _sfc_setup$e = _sfc_main$e.setup;
_sfc_main$e.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Categories/Default/Details/Details.vue");
  return _sfc_setup$e ? _sfc_setup$e(props, ctx) : void 0;
};
const ItemDetails$1 = _sfc_main$e;
const _sfc_main$d = {
  __name: "ItemCopy",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        title: "",
        children: []
      },
      type: Object
    },
    index: {
      default: 0,
      type: Number
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(CategoryItem$1, mergeProps({
        item: props.item,
        index: props.index,
        parent_id: props.item.id,
        onCallAction: (data) => emit("callAction", data)
      }, _attrs), null, _parent));
    };
  }
};
const _sfc_setup$d = _sfc_main$d.setup;
_sfc_main$d.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Categories/Default/Item/ItemCopy/ItemCopy.vue");
  return _sfc_setup$d ? _sfc_setup$d(props, ctx) : void 0;
};
const CategoryItem$2 = _sfc_main$d;
const _sfc_main$c = {
  __name: "Item",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        title: "",
        children: []
      },
      type: Object
    },
    index: {
      default: 0,
      type: Number
    },
    parent_id: {
      default: null,
      type: Number
    },
    activeCategory: {
      default: null,
      type: String
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const detailsRef = ref(null);
    const props = __props;
    const emit = __emit;
    return (_ctx, _push, _parent, _attrs) => {
      if (props.item.children.length == 0) {
        _push(`<div${ssrRenderAttrs(mergeProps({
          class: ["table-categories__item", props.activeCategory == props.item.id ? "table-categories__item_active" : ""],
          style: `--itemIndex: ${props.index}`
        }, _attrs))}><div class="table-categories__title">${ssrInterpolate(props.item.name)}</div>`);
        _push(ssrRenderComponent(ItemDetails$1, {
          item: props.item,
          parent_id: props.parent_id,
          onCallAction: (data) => emit("callAction", data)
        }, null, _parent));
        _push(`</div>`);
      } else {
        _push(`<details${ssrRenderAttrs(mergeProps({
          class: "table-categories__details",
          ref_key: "detailsRef",
          ref: detailsRef
        }, _attrs))}><summary style="${ssrRenderStyle(`--itemIndex: ${props.index}`)}" class="${ssrRenderClass([props.item.id == props.activeCategory ? "table-categories__header_active" : "", "table-categories__header"])}"><div class="table-categories__title table-categories__title_summary">`);
        _push(ssrRenderComponent(IconTriangle, null, null, _parent));
        _push(`<span class="table-categories__title-text">${ssrInterpolate(props.item.name)}</span></div>`);
        _push(ssrRenderComponent(ItemDetails$1, {
          item: props.item,
          parent_id: props.parent_id,
          onCallAction: (data) => emit("callAction", data)
        }, null, _parent));
        _push(`</summary><div class="table-categories__list"><!--[-->`);
        ssrRenderList(props.item.children, (item) => {
          _push(ssrRenderComponent(CategoryItem$2, {
            item,
            parent_id: props.item.id,
            index: props.index + 2,
            activeCategory: props.activeCategory,
            onCallAction: (data) => emit("callAction", data)
          }, null, _parent));
        });
        _push(`<!--]--></div></details>`);
      }
    };
  }
};
const _sfc_setup$c = _sfc_main$c.setup;
_sfc_main$c.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Categories/Default/Item/Item.vue");
  return _sfc_setup$c ? _sfc_setup$c(props, ctx) : void 0;
};
const CategoryItem$1 = _sfc_main$c;
const _sfc_main$b = {
  __name: "Default",
  __ssrInlineRender: true,
  props: {
    activeCategory: {
      default: null,
      type: String
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const categories = inject("categories");
    const emit = __emit;
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({
        isCanResize: false,
        class: "table__categories table-categories"
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(SectionHeader, { title: "\u041A\u0430\u0442\u0430\u043B\u043E\u0433" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(ButtonText, {
                    onClick: () => emit("callAction", { action: "initCreateCategory", item: null })
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
                  return [
                    createVNode(ButtonText, {
                      onClick: () => emit("callAction", { action: "initCreateCategory", item: null })
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C ")
                      ]),
                      _: 1
                    }, 8, ["onClick"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="table-categories__list"${_scopeId}><!--[-->`);
            ssrRenderList(unref(categories), (item) => {
              _push2(ssrRenderComponent(CategoryItem$1, {
                item,
                index: 1,
                activeCategory: props.activeCategory,
                onCallAction: (data) => emit("callAction", data)
              }, null, _parent2, _scopeId));
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode(SectionHeader, { title: "\u041A\u0430\u0442\u0430\u043B\u043E\u0433" }, {
                default: withCtx(() => [
                  createVNode(ButtonText, {
                    onClick: () => emit("callAction", { action: "initCreateCategory", item: null })
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C ")
                    ]),
                    _: 1
                  }, 8, ["onClick"])
                ]),
                _: 1
              }),
              createVNode("div", { class: "table-categories__list" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(categories), (item) => {
                  return openBlock(), createBlock(CategoryItem$1, {
                    item,
                    index: 1,
                    activeCategory: props.activeCategory,
                    onCallAction: (data) => emit("callAction", data)
                  }, null, 8, ["item", "activeCategory", "onCallAction"]);
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
const _sfc_setup$b = _sfc_main$b.setup;
_sfc_main$b.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Categories/Default/Default.vue");
  return _sfc_setup$b ? _sfc_setup$b(props, ctx) : void 0;
};
const CategoriesDefault = _sfc_main$b;
const _sfc_main$a = {
  __name: "Details",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        title: "",
        children: []
      },
      type: Object
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppPopup, mergeProps({
        closeByClick: true,
        isCanSelect: false,
        class: "table-categories__popup"
      }, _attrs), {
        summary: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(IconDots, null, null, _parent2, _scopeId));
          } else {
            return [
              createVNode(IconDots)
            ];
          }
        }),
        content: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(PopupOption, {
              onClick: (event) => {
                event.preventDefault();
                emit("callAction", {
                  action: "initUpdateRole",
                  value: {
                    id: props.item.id
                  }
                });
              }
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0418\u0437\u043C\u0435\u043D\u0438\u0442\u044C \u0438\u043C\u044F `);
                } else {
                  return [
                    createTextVNode(" \u0418\u0437\u043C\u0435\u043D\u0438\u0442\u044C \u0438\u043C\u044F ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(ssrRenderComponent(PopupOption, {
              class: "popup__option_red",
              onClick: (event) => {
                event.preventDefault();
                emit("callAction", {
                  action: "initDeleteRole",
                  value: {
                    id: props.item.id,
                    name: props.item.name
                  }
                });
              }
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
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
                onClick: (event) => {
                  event.preventDefault();
                  emit("callAction", {
                    action: "initUpdateRole",
                    value: {
                      id: props.item.id
                    }
                  });
                }
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u0418\u0437\u043C\u0435\u043D\u0438\u0442\u044C \u0438\u043C\u044F ")
                ]),
                _: 1
              }, 8, ["onClick"]),
              createVNode(PopupOption, {
                class: "popup__option_red",
                onClick: (event) => {
                  event.preventDefault();
                  emit("callAction", {
                    action: "initDeleteRole",
                    value: {
                      id: props.item.id,
                      name: props.item.name
                    }
                  });
                }
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
const _sfc_setup$a = _sfc_main$a.setup;
_sfc_main$a.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Categories/Roles/Details/Details.vue");
  return _sfc_setup$a ? _sfc_setup$a(props, ctx) : void 0;
};
const ItemDetails = _sfc_main$a;
const _sfc_main$9 = {
  __name: "Item",
  __ssrInlineRender: true,
  props: {
    item: {
      default: {
        title: "",
        children: []
      },
      type: Object
    },
    index: {
      default: 0,
      type: Number
    },
    activeCategory: {
      default: null,
      type: String
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const props = __props;
    const emit = __emit;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: ["table-categories__item", props.item.id == props.activeCategory ? "table-categories__item_active" : ""],
        style: `--itemIndex: ${props.index}`
      }, _attrs))}><div class="table-categories__title">${ssrInterpolate(props.item.label)}</div>`);
      if (!props.item.is_permanent) {
        _push(ssrRenderComponent(ItemDetails, {
          item: props.item,
          onCallAction: (data) => emit("callAction", data)
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div>`);
    };
  }
};
const _sfc_setup$9 = _sfc_main$9.setup;
_sfc_main$9.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Categories/Roles/Item/Item.vue");
  return _sfc_setup$9 ? _sfc_setup$9(props, ctx) : void 0;
};
const CategoryItem = _sfc_main$9;
const _sfc_main$8 = {
  __name: "Roles",
  __ssrInlineRender: true,
  props: {
    activeCategory: {
      default: null,
      type: String
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const categories = inject("categories");
    const emit = __emit;
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({
        isCanResize: false,
        class: "table__categories table-categories"
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(ssrRenderComponent(SectionHeader, { title: "\u0420\u043E\u043B\u0438" }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(ButtonText, {
                    onClick: () => emit("callAction", { action: "initCreateRole", item: null })
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
                  return [
                    createVNode(ButtonText, {
                      onClick: () => emit("callAction", { action: "initCreateRole", item: null })
                    }, {
                      default: withCtx(() => [
                        createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C ")
                      ]),
                      _: 1
                    }, 8, ["onClick"])
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
            _push2(`<div class="table-categories__list table-categories__list_roles"${_scopeId}><!--[-->`);
            ssrRenderList(unref(categories), (item) => {
              _push2(ssrRenderComponent(CategoryItem, {
                item,
                index: 1,
                activeCategory: props.activeCategory,
                onCallAction: (data) => emit("callAction", data)
              }, null, _parent2, _scopeId));
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode(SectionHeader, { title: "\u0420\u043E\u043B\u0438" }, {
                default: withCtx(() => [
                  createVNode(ButtonText, {
                    onClick: () => emit("callAction", { action: "initCreateRole", item: null })
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C ")
                    ]),
                    _: 1
                  }, 8, ["onClick"])
                ]),
                _: 1
              }),
              createVNode("div", { class: "table-categories__list table-categories__list_roles" }, [
                (openBlock(true), createBlock(Fragment, null, renderList(unref(categories), (item) => {
                  return openBlock(), createBlock(CategoryItem, {
                    item,
                    index: 1,
                    activeCategory: props.activeCategory,
                    onCallAction: (data) => emit("callAction", data)
                  }, null, 8, ["item", "activeCategory", "onCallAction"]);
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
const _sfc_setup$8 = _sfc_main$8.setup;
_sfc_main$8.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Categories/Roles/Roles.vue");
  return _sfc_setup$8 ? _sfc_setup$8(props, ctx) : void 0;
};
const CategoriesRoles = _sfc_main$8;
const _sfc_main$7 = {
  __name: "Categories",
  __ssrInlineRender: true,
  props: {
    categoryType: {
      default: "default",
      type: String
    },
    activeCategory: {
      default: null,
      type: String
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      if (__props.categoryType == "default") {
        _push(ssrRenderComponent(CategoriesDefault, mergeProps({
          activeCategory: props.activeCategory
        }, _attrs), null, _parent));
      } else if (__props.categoryType == "roles") {
        _push(ssrRenderComponent(CategoriesRoles, mergeProps({
          activeCategory: props.activeCategory
        }, _attrs), null, _parent));
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$7 = _sfc_main$7.setup;
_sfc_main$7.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/Categories/Categories.vue");
  return _sfc_setup$7 ? _sfc_setup$7(props, ctx) : void 0;
};
const TableCategory = _sfc_main$7;
const _sfc_main$6 = {
  __name: "ScrollButtons",
  __ssrInlineRender: true,
  props: {
    isHaveScrollingHeader: {
      default: true,
      type: Boolean
    },
    isMobileCategory: {
      default: false,
      type: Boolean
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    let mouseHover = ref(false);
    const loaderRef = ref(null);
    const buttonScrollLeftRef = ref(null);
    const buttonScrollRightRef = ref(null);
    ref(null);
    ref(null);
    const tableRef = inject("tableRef");
    inject("sectionRef");
    const scrollPosition = inject("scrollPosition");
    const buttonStartRect = ref(0);
    const props = __props;
    const scroolingState = ref(null);
    const checkHeaderCopy = () => {
      var _a, _b, _c, _d, _e;
      const tableBody = (_a = tableRef.value) == null ? void 0 : _a.querySelector(".table__body");
      if ((tableBody == null ? void 0 : tableBody.getBoundingClientRect().top) < 0) {
        (_c = (_b = tableRef.value) == null ? void 0 : _b.querySelector(".table__header_copy")) == null ? void 0 : _c.classList.remove("table__header_hidden");
      } else {
        (_e = (_d = tableRef.value) == null ? void 0 : _d.querySelector(".table__header_copy")) == null ? void 0 : _e.classList.add("table__header_hidden");
      }
    };
    const actionScroll = (data) => {
      const scrollingBlock = (pos) => {
        const scrollX = (scrollSection, pos2) => {
          if (!mouseHover.value) {
            return;
          }
          setButtonsVisible(scrollSection);
          if (pos2 == "right") {
            if (scrollSection.scrollWidth == scrollSection.scrollLeft + scrollSection.offsetWidth) {
              return;
            }
            scrollSection.scrollLeft += 5;
            scrollSection.scrollTo({
              left: scrollSection.scrollLeft,
              top: 0
            });
          } else {
            if (scrollSection.scrollLeft == 0) {
              return;
            }
            scrollSection.scrollLeft -= 5;
            scrollSection.scrollTo({
              left: scrollSection.scrollLeft,
              top: 0
            });
          }
          setTimeout(() => {
            commonScripts$1.clearSelection();
            scrollX(scrollSection, pos2);
          }, 0.1);
        };
        mouseHover.value = true;
        scrollX(tableRef.value.parentNode, pos);
      };
      const setButtonsVisible = (scrollSection) => {
        if (scrollSection.scrollWidth == scrollSection.offsetWidth) {
          buttonScrollLeftRef.value ? buttonScrollLeftRef.value.classList.add("scroll-button_disabled") : null;
          buttonScrollRightRef.value ? buttonScrollRightRef.value.classList.add("scroll-button_disabled") : null;
          return;
        } else if (scrollSection.scrollLeft == 0) {
          buttonScrollLeftRef.value ? buttonScrollLeftRef.value.classList.add("scroll-button_disabled") : null;
          buttonScrollRightRef.value ? buttonScrollRightRef.value.classList.remove("scroll-button_disabled") : null;
        } else if (scrollSection.scrollWidth == scrollSection.scrollLeft + scrollSection.offsetWidth) {
          buttonScrollLeftRef.value ? buttonScrollLeftRef.value.classList.remove("scroll-button_disabled") : null;
          buttonScrollRightRef.value ? buttonScrollRightRef.value.classList.add("scroll-button_disabled") : null;
        } else {
          buttonScrollRightRef.value ? buttonScrollRightRef.value.classList.remove("scroll-button_disabled") : null;
          buttonScrollLeftRef.value ? buttonScrollLeftRef.value.classList.remove("scroll-button_disabled") : null;
        }
      };
      switch (data.action) {
        case "scrollingBlock":
          scrollingBlock(data.value);
          break;
        case "setButtonsVisible":
          setButtonsVisible(data.value);
          break;
      }
    };
    throttle(() => {
      if (scroolingState.value !== null) {
        tableRef.value.parentNode.classList.add("element_scrolling");
        clearTimeout(scroolingState.value);
      }
      scroolingState.value = setTimeout(function() {
        tableRef.value.parentNode.classList.remove("element_scrolling");
      }, 150);
      actionScroll({ action: "setButtonsVisible", value: tableRef.value.parentNode });
    }, 5);
    watch(() => props.isMobileCategory, () => {
      scrollPosition.value = checkHeaderCopy();
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(AppLoader, {
        class: "table-template__loader",
        ref_key: "loaderRef",
        ref: loaderRef
      }, null, _parent));
      _push(`<div class="table-template__buttons" style="${ssrRenderStyle(`--startButtons: ${unref(buttonStartRect)}px`)}"><div class="scroll-button scroll-button_left"></div><div class="scroll-button scroll-button_right"></div></div><!--]-->`);
    };
  }
};
const _sfc_setup$6 = _sfc_main$6.setup;
_sfc_main$6.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/ScrollButtons/ScrollButtons.vue");
  return _sfc_setup$6 ? _sfc_setup$6(props, ctx) : void 0;
};
const ScrollButtons = _sfc_main$6;
const _sfc_main$5 = {
  __name: "Edit",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__edit" }, _attrs))}><svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 13 13" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M11.3384 3.70456L9.29544 1.6616L10.6234 0.333683C10.6234 0.333683 11.747 -0.381352 12.5642 0.43583C13.3814 1.25301 12.6663 2.37664 12.6663 2.37664L11.3384 3.70456ZM2.1451 8.81194L4.18806 10.8549L10.6234 4.41959L8.58041 2.37664L2.1451 8.81194ZM1.53222 9.52698L0 13L3.57517 11.4678L1.53222 9.52698Z" fill="black"></path></svg><figcaption>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</figcaption></figure>`);
    };
  }
};
const _sfc_setup$5 = _sfc_main$5.setup;
_sfc_main$5.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Edit/Edit.vue");
  return _sfc_setup$5 ? _sfc_setup$5(props, ctx) : void 0;
};
const IconEdit = _sfc_main$5;
const _sfc_main$4 = {
  __name: "Edit",
  __ssrInlineRender: true,
  props: {
    loading: {
      default: false,
      type: Boolean
    },
    is_admin: {
      default: false,
      type: Boolean
    },
    permissions: {
      default: {},
      type: Object
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "actions__content" }, _attrs))}>`);
      if (__props.permissions.update_p != "N") {
        _push(ssrRenderComponent(AppButton, {
          class: "action-button",
          onClick: () => !props.loaderButton ? emit("callAction", { action: "edit", value: true }) : ""
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(IconEdit, { class: "actions__icon" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(` \u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C `);
                  } else {
                    return [
                      createTextVNode(" \u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C ")
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              return [
                createVNode(IconEdit, { class: "actions__icon" }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0420\u0435\u0434\u0430\u043A\u0442\u0438\u0440\u043E\u0432\u0430\u0442\u044C ")
                  ]),
                  _: 1
                })
              ];
            }
          }),
          _: 1
        }, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(AppButton, {
        class: "action-button",
        onClick: () => !props.loaderButton ? emit("callAction", { action: "cancel", value: true }) : ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
          } else {
            return [
              createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      if (__props.permissions.delete_p != "N") {
        _push(ssrRenderComponent(AppButton, {
          class: "action-button button_red action-button_right",
          onClick: () => !props.loaderButton ? emit("callAction", { action: "initDelete", value: true }) : ""
        }, {
          default: withCtx((_, _push2, _parent2, _scopeId) => {
            if (_push2) {
              _push2(ssrRenderComponent(IconDelete, { class: "actions__icon" }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
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
                createVNode(IconDelete, { class: "actions__icon" }, {
                  default: withCtx(() => [
                    createTextVNode(" \u0423\u0434\u0430\u043B\u0438\u0442\u044C ")
                  ]),
                  _: 1
                })
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
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSection/Actions/Edit/Edit.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const ButtonsEdit = _sfc_main$4;
const _sfc_main$3 = {
  __name: "Save",
  __ssrInlineRender: true,
  props: {
    loading: {
      default: false,
      type: Boolean
    },
    loaderState: {
      default: null,
      type: String
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "actions__content" }, _attrs))}>`);
      _push(ssrRenderComponent(AppButton, {
        class: ["button_blue action-button", props.loaderState == "actionLoad" ? "button_loading" : ""],
        disabled: props.loaderState == "actionLoad",
        onClick: () => !props.loaderButton ? emit("callAction", { action: "save", value: true }) : ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C `);
          } else {
            return [
              createTextVNode(" \u0421\u043E\u0445\u0440\u0430\u043D\u0438\u0442\u044C ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(AppButton, {
        class: "action-button",
        onClick: () => !props.loaderButton ? emit("callAction", { action: "cancel", value: true }) : ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
          } else {
            return [
              createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSection/Actions/Save/Save.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const ButtonsSave = _sfc_main$3;
const _sfc_main$2 = {
  __name: "Restore",
  __ssrInlineRender: true,
  props: {
    loading: {
      default: false,
      type: Boolean
    },
    loaderState: {
      default: null,
      type: String
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "actions__content" }, _attrs))}>`);
      _push(ssrRenderComponent(AppButton, {
        class: ["button_blue action-button", props.loaderState == "actionLoad" ? "button_loading" : ""],
        disabled: props.loaderState == "actionLoad",
        onClick: () => !props.loaderButton ? emit("callAction", { action: "initRestore", value: true }) : ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0412\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u0438\u0442\u044C `);
          } else {
            return [
              createTextVNode(" \u0412\u043E\u0441\u0441\u0442\u0430\u043D\u043E\u0432\u0438\u0442\u044C ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(AppButton, {
        class: "action-button",
        onClick: () => !props.loaderButton ? emit("callAction", { action: "cancel", value: true }) : ""
      }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u041E\u0442\u043C\u0435\u043D\u0430 `);
          } else {
            return [
              createTextVNode(" \u041E\u0442\u043C\u0435\u043D\u0430 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSection/Actions/Restore/Restore.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const ButtonsRestore = _sfc_main$2;
const _sfc_main$1 = {
  __name: "Actions",
  __ssrInlineRender: true,
  props: {
    actionState: {
      default: null,
      type: String
    },
    loaderState: {
      default: null,
      type: String
    },
    is_admin: {
      default: false,
      type: Boolean
    },
    permissions: {
      default: {},
      type: Object
    }
  },
  emits: [
    "callAction"
  ],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      if (props.actionState != null || props.loaderState == "actionLoad") {
        _push(`<div${ssrRenderAttrs(mergeProps({ class: "actions" }, _attrs))}>`);
        if (props.actionState == "editting") {
          _push(ssrRenderComponent(ButtonsEdit, {
            is_admin: props.is_admin,
            permissions: props.permissions,
            onCallAction: (data) => emit("callAction", data)
          }, null, _parent));
        } else if (props.actionState == "saving" || props.loaderState == "actionLoad") {
          _push(ssrRenderComponent(ButtonsSave, {
            loaderState: props.loaderState,
            onCallAction: (data) => emit("callAction", data)
          }, null, _parent));
        } else if (props.actionState == "restoring" || props.loaderState == "actionLoad") {
          _push(ssrRenderComponent(ButtonsRestore, {
            loaderState: props.loaderState,
            onCallAction: (data) => emit("callAction", data)
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`</div>`);
      } else {
        _push(`<!---->`);
      }
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSection/Actions/Actions.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const SectionActions = _sfc_main$1;
const _sfc_main = {
  __name: "AppTable",
  __ssrInlineRender: true,
  props: {
    table: {
      default: {
        tableKeys: [],
        tableData: [],
        tableFooter: {
          pages: 0,
          activePage: 0,
          count: 25
        },
        loaderState: null
      },
      type: Object
    },
    permissions: {
      default: {},
      type: Object
    },
    isTrash: {
      default: false,
      type: Boolean
    },
    isPermanentEdit: {
      default: false,
      type: Boolean
    },
    isDinamyc: {
      default: false,
      type: Boolean
    },
    isNumeric: {
      default: false,
      type: Boolean
    },
    slug: {
      default: "undefined",
      type: String
    },
    actionType: {
      default: "view",
      type: String
    },
    isHaveCategories: {
      default: false,
      type: Boolean
    },
    categories: {
      default: [],
      type: Array
    },
    categoryType: {
      default: "default",
      type: String
    },
    activeCategory: {
      default: null,
      type: String
    },
    userID: {
      default: -1,
      type: Number
    },
    isCanUseHeader: {
      default: true,
      type: Boolean
    },
    isCanSort: {
      default: true,
      type: Boolean
    },
    is_admin: {
      default: false,
      type: Boolean
    },
    balance: {
      default: 0,
      type: Number
    },
    isHaveScrollingHeader: {
      default: true,
      type: Boolean
    },
    groupBody: {
      default: null,
      type: String
    },
    isDraggableRow: {
      default: false,
      type: Boolean
    },
    title: {
      default: null,
      type: String
    },
    pageTableOnly: {
      default: true,
      type: Boolean
    }
  },
  emits: [
    "callAction",
    "changeValue"
  ],
  setup(__props, { emit: __emit }) {
    let fields = ref([]);
    let bodyData = ref([]);
    let footerData = ref({});
    let actionState = ref(null);
    let backupValues = ref([]);
    let backupRows = ref([]);
    let socketRows = ref({
      header: [],
      body: []
    });
    const tableRef = ref(null);
    const sectionRef = ref(null);
    const scrollAreaRef = ref(null);
    const activePayment = ref({
      value: 0,
      state: false
    });
    let isMobile = ref(false);
    let updatedRows = ref([]);
    let invalidRows = ref([]);
    let selectAll = ref(false);
    let scrollPosition = ref(300);
    let sortItem = ref({
      key: "id",
      order: "asc"
    });
    let isShow = ref({
      state: false,
      type: null
    });
    const menu = ref(null);
    let skipChecking = ref(false);
    let updatedCategory = ref(null);
    let categories = ref([]);
    let sectionWidth = ref(0);
    const roleRef = ref(0);
    const props = __props;
    const emit = __emit;
    const isMobileCategory = ref(false);
    provide("menu", menu);
    provide("fields", fields);
    provide("isShow", isShow);
    provide("sortItem", sortItem);
    provide("tableRef", tableRef);
    provide("bodyData", bodyData);
    provide("selectAll", selectAll);
    provide("sectionRef", sectionRef);
    provide("footerData", footerData);
    provide("categories", categories);
    provide("actionState", actionState);
    provide("isNumeric", props.isNumeric);
    provide("backupRows", backupRows);
    provide("isDinamyc", props.isDinamyc);
    provide("invalidRows", invalidRows);
    provide("backupValues", backupValues);
    provide("skipChecking", skipChecking);
    provide("scrollPosition", scrollPosition);
    provide("updatedCategory", updatedCategory);
    provide("isPermanentEdit", props.isPermanentEdit);
    provide("isCanUseHeader", props.isCanUseHeader);
    provide("isCanSort", props.isCanSort);
    provide("is_admin", props.is_admin);
    provide("userID", props.userID);
    provide("isMobile", isMobile);
    provide("activePayment", activePayment);
    provide("roleRef", roleRef);
    const changeWidth = () => {
      var _a;
      isMobileCategory.value = props.isHaveCategories && window.innerWidth <= 1320;
      isMobile.value = window.innerWidth <= 660;
      sectionWidth.value = (_a = scrollAreaRef.value) == null ? void 0 : _a.offsetWidth;
    };
    const callAction = (data) => {
      const setPropsValues = (data2) => {
        if ([null, void 0].includes(data2) || !Array.isArray(data2)) {
          return [];
        } else {
          return JSON.parse(JSON.stringify(data2.filter((p) => ![null, void 0].includes(p) && typeof p == "object" && !Array.isArray(p))));
        }
      };
      const editRows = () => {
        actionState.value = "saving";
        bodyData.value.forEach((row) => {
          if (props.is_admin || props.permissions.update_p == "A") {
            if (row.isChoose) {
              backupValues.value.push(JSON.parse(JSON.stringify(row)));
              row.isEdit = true;
            }
          } else if (props.permissions.update_p == "Y") {
            if (row.isChoose && row.user_id.value != null && row.user_id.value.includes(props.userID)) {
              backupValues.value.push(JSON.parse(JSON.stringify(row)));
              row.isEdit = true;
            }
          }
        });
        changeStateTab(false);
      };
      const cancelRows = () => {
        let findedIndex = null;
        for (let row of backupValues.value) {
          row.isChoose = false;
          findedIndex = bodyData.value.findIndex((p) => p.id == row.id);
          bodyData.value[findedIndex] = row;
        }
        for (let row of bodyData.value) {
          row.isChoose = false;
        }
        if (!props.isPermanentEdit) {
          backupValues.value = [];
        } else {
          backupValues.value = JSON.parse(JSON.stringify(bodyData.value));
          bodyData.value = JSON.parse(JSON.stringify(backupRows.value));
        }
        invalidRows.value = [];
        actionState.value = null;
        selectAll.value = false;
        changeStateTab(true);
      };
      const saveRows = () => {
        const getUpdatedFields = (row, backupRow) => {
          let flag = false;
          let updatedRow = {
            id: row.id
          };
          for (let key in row) {
            let findedField = fields.value.find((p) => p.key == key);
            if (findedField && !["isEdit", "isChoose"].includes(key)) {
              switch (findedField.type) {
                case "status":
                  if (!isEqual(String(row[key]), String(backupRow[key]))) {
                    flag = true;
                    updatedRow[key] = row[key];
                  }
                  break;
                default:
                  if (!isEqual(row[key], backupRow[key])) {
                    flag = true;
                    updatedRow[key] = row[key];
                  }
                  break;
              }
            }
          }
          return flag == false ? null : JSON.parse(JSON.stringify(updatedRow));
        };
        const validateFields = (row) => {
          let flag = false;
          let error = null;
          let fieldError = {};
          for (let field of fields.value) {
            if (field.type == "status") {
              row[field.key] = setStatusValue(field, row[field.key]);
            }
            if (field.type == "relation") {
              error = ValidateField(field, row[field.key].value);
            } else {
              error = ValidateField(field, row[field.key]);
            }
            if (error.state) {
              flag = true;
              fieldError.id = row.id;
              fieldError[field.key] = {
                value: row[field.key],
                error: error.text
              };
            }
          }
          if (flag) {
            return fieldError;
          } else {
            return false;
          }
        };
        const setStatusValue = (field, value) => {
          let options = field.options.filter((option) => option.label.is_hidden == 0 || option.label.field_id == field.id);
          let findedOption = options == null ? null : options.find((option) => option.value == value);
          if ([null, void 0].includes(findedOption)) {
            if (options == null || options.filter((p) => p.label.is_hidden == 0).length == 0) {
              return null;
            } else {
              return options.filter((p) => p.label.is_hidden == 0)[0].value;
            }
          } else {
            return findedOption.value;
          }
        };
        const checkingRows = () => {
          for (let backupRow of backupValues.value) {
            findedIndex = bodyData.value.findIndex((p) => p.id == backupRow.id);
            let error = validateFields(bodyData.value[findedIndex]);
            if (!error) {
              let updatedRow = JSON.parse(JSON.stringify(getUpdatedFields(bodyData.value[findedIndex], backupRow)));
              updatedRows.value.push(updatedRow);
            } else {
              invalidFlag = true;
              invalidRows.value.push(error);
            }
          }
        };
        const initSave = () => {
          const transformUpdatedRows = (row) => {
            for (let key in row) {
              let findedField = fields.value.find((p) => p.key == key);
              if (findedField != void 0) {
                if (findedField.type == "relation") {
                  let findedIndex2 = bodyData.value.findIndex((p) => p.id == row.id);
                  let transformedItem = [null, void 0].includes(row[key]) || row[key].value == null ? null : row[key].value.filter((p) => p != null);
                  if (bodyData.value[findedIndex2][key]) {
                    bodyData.value[findedIndex2][key].value = toRaw(transformedItem);
                    row[key] = key == "product_id" ? row[key] : toRaw(transformedItem);
                  }
                } else if (findedField.type == "select_dropdown") {
                  row[key] = Array.isArray(row[key]) || [null, void 0].includes(row[key]) ? row[key] : [row[key]];
                }
              }
            }
          };
          if (invalidFlag) {
            isShow.value = {
              state: true,
              type: "validation"
            };
          } else {
            actionState.value = null;
            selectAll.value = false;
            updatedRows.value = updatedRows.value.filter((p) => p != null && p.id != null);
            for (let row of updatedRows.value) {
              let bodyRow = bodyData.value.find((p) => p.id == row.id);
              if (bodyRow) {
                bodyRow.isUpdated = true;
                setTimeout(() => {
                  bodyRow.isUpdated = false;
                }, 3e3);
              }
              transformUpdatedRows(row);
              delete row.isEdit;
              delete row.iconDrag;
              delete row.iconDelete;
            }
            if (!props.isPermanentEdit) {
              for (let row of bodyData.value) {
                delete row.isEdit;
                row.isChoose = false;
              }
              backupValues.value = [];
            } else {
              backupValues.value = JSON.parse(JSON.stringify(bodyData.value));
            }
            if (updatedRows.value.length == 0 && !props.isPermanentEdit) {
              return;
            } else {
              emit("callAction", {
                action: "save",
                value: updatedRows.value
              });
            }
          }
        };
        let findedIndex = null;
        let invalidFlag = false;
        invalidRows.value = [];
        isShow.value = {
          state: false,
          type: null
        };
        if (!skipChecking.value) {
          updatedRows.value = [];
          checkingRows();
        } else {
          if (fields.value.find((p) => p.key == "product_id")) {
            bodyData.value = bodyData.value.filter((p) => p.product_id != null && p.product_id.value != null);
            for (let item of bodyData.value) {
              if (item.product_sum == null) {
                item.product_sum = 0;
              }
            }
          }
          updatedRows.value = JSON.parse(JSON.stringify(bodyData.value));
        }
        initSave();
        skipChecking.value = false;
      };
      const initDeleteRows = (value) => {
        if (typeof value == "object") {
          let findedIndex = bodyData.value.findIndex((row) => row.id == value.id);
          bodyData.value[findedIndex].isChoose = true;
        }
        isShow.value = {
          state: true,
          type: "delete"
        };
      };
      const deleteRows = (type = "delete") => {
        let data2 = [];
        for (let row of bodyData.value) {
          if (props.is_admin || props.permissions.delete_p == "A") {
            if (row.isChoose) {
              data2.push(row.id);
              bodyData.value = bodyData.value.filter((p) => p.id != row.id);
            }
          } else if (props.permissions.delete_p == "Y") {
            if (row.isChoose && row.user_id.value != null && row.user_id.value.includes(props.userID)) {
              data2.push(row.id);
              bodyData.value = bodyData.value.filter((p) => p.id != row.id);
            }
          } else {
            row.isChoose = false;
          }
        }
        selectAll.value = false;
        isShow.value = {
          state: false,
          type: null
        };
        actionState.value = null;
        emit("callAction", { action: props.actionType == "module" ? "untie" : type, value: data2 });
      };
      const initRestoreRows = (value) => {
        if (typeof value == "object") {
          let findedIndex = bodyData.value.findIndex((row) => row.id == value.id);
          bodyData.value[findedIndex].isChoose = true;
        }
        isShow.value = {
          state: true,
          type: "restore"
        };
      };
      const socketUpdate = () => {
        const updateBody = () => {
          const updateFieldValue = (row, updatedRow) => {
            for (let key in updatedRow) {
              row[key] = updatedRow[key];
            }
          };
          const setUpdatedStatus = (id) => {
            let findedIndex = bodyData.value.findIndex((row) => row.id == id);
            bodyData.value[findedIndex].isUpdated = true;
            setTimeout(() => {
              let findedIndex2 = bodyData.value.findIndex((row) => row.id == id);
              delete bodyData.value[findedIndex2].isUpdated;
            }, 3e3);
          };
          for (let socketRow of socketRows.value.body) {
            if (socketRow.isNew) {
              bodyData.value.unshift(socketRow);
              setUpdatedStatus(socketRow.id);
            } else if (socketRow.isDeleted) {
              bodyData.value = bodyData.value.filter((row) => row.id != socketRow.id);
            } else {
              let findedIndex = bodyData.value.findIndex((row) => row.id == socketRow.id);
              if (props.permissions.read_p == "Y") {
                bodyData.value = bodyData.value.filter((row) => row.user_id.value.includes(props.userID));
              }
              updateFieldValue(bodyData.value[findedIndex], socketRow);
              setUpdatedStatus(socketRow.id);
            }
            socketRows.value.body = socketRows.value.body.filter((row) => row.id != socketRow.id);
            emit("callAction", {
              action: "updateTableBody",
              value: bodyData.value
            });
          }
        };
        const updateHeader = () => {
          const updateFieldValue = (column, updatedColumn) => {
            for (let key in updatedColumn) {
              column[key] = updatedColumn[key];
              if (key == "has_roles_write" && updatedColumn[key]) {
                if (!updatedColumn.roles_write.includes(props.userID)) {
                  column.read_only = true;
                }
              }
            }
          };
          const setUpdatedStatus = (id) => {
            let findedIndex = fields.value.findIndex((column) => column.id == id);
            fields.value[findedIndex].isUpdated = true;
            setTimeout(() => {
              let findedIndex2 = fields.value.findIndex((column) => column.id == id);
              delete fields.value[findedIndex2].isUpdated;
            }, 3e3);
          };
          for (let socketColumn of socketRows.value.header) {
            let findedIndex = fields.value.findIndex((column) => column.id == socketColumn.id);
            if (socketColumn.isNew) {
              fields.value.unshift(socketColumn);
            } else if (socketColumn.isDeleted) {
              fields.value = fields.value.filter((column) => column.id != socketColumn.id);
              emit("callAction", {
                action: "updateTableHeader",
                value: fields.value
              });
            } else {
              updateFieldValue(fields.value[findedIndex], socketColumn);
              setUpdatedStatus(socketColumn.id);
            }
            socketRows.value.header = socketRows.value.header.filter((column) => column.id != socketColumn.id);
          }
        };
        updateBody();
        updateHeader();
        emit("callAction", { action: "clearSocket", value: null });
      };
      const getTableData = (value) => {
        emit("callAction", { action: "getTableData", value: {
          sortItem: value,
          footerData: footerData.value
        } });
      };
      const moveRows = (event) => {
        if (props.isDraggableRow) {
          emit("callAction", {
            action: "moveRow",
            value: JSON.parse(JSON.stringify(event.item.__draggable_context.element)),
            rows: event.to.__draggable_component__.modelValue
          });
        } else {
          actionState.value = "saving";
          skipChecking.value = true;
          updatedRows.value = JSON.parse(JSON.stringify(event.to.__draggable_component__.modelValue));
          bodyData.value = event.to.__draggable_component__.modelValue;
        }
      };
      const removeRow = (id) => {
        if (actionState.value != "saving") {
          backupRows.value = JSON.parse(JSON.stringify(bodyData.value));
        }
        actionState.value = "saving";
        skipChecking.value = true;
        bodyData.value.splice(id - 1, 1);
        updatedRows.value = JSON.parse(JSON.stringify(bodyData.value));
      };
      const initUpdateCategory = (data2) => {
        isShow.value = {
          state: true,
          type: "updateCategory"
        };
        updatedCategory.value = data2;
      };
      const initCreateCategory = () => {
        isShow.value = {
          state: true,
          type: "createCategory"
        };
      };
      const initCreateSubCategory = (data2) => {
        isShow.value = {
          state: true,
          type: "createSubCategory"
        };
        updatedCategory.value = data2;
      };
      const initDeleteCategory = (data2) => {
        isShow.value = {
          state: true,
          type: "deleteCategory"
        };
        updatedCategory.value = data2;
      };
      const deleteCategory = (data2) => {
        isShow.value = {
          state: false,
          type: null
        };
        emit("callAction", { action: "deleteCategory", value: data2 });
      };
      const initUpdateRole = (data2) => {
        isShow.value = {
          state: true,
          type: "updateRole"
        };
        updatedCategory.value = data2;
      };
      const initCreateRole = () => {
        isShow.value = {
          state: true,
          type: "createRole"
        };
      };
      const initDeleteRole = (data2) => {
        isShow.value = {
          state: true,
          type: "deleteRole"
        };
        updatedCategory.value = data2;
      };
      const deleteRole = (data2) => {
        isShow.value = {
          state: false,
          type: null
        };
        categories.value = categories.value.filter((p) => p.id != data2);
        emit("callAction", { action: "deleteRole", value: data2 });
      };
      const addRow = () => {
        if (actionState.value == null) {
          backupRows.value = JSON.parse(JSON.stringify(bodyData.value));
          actionState.value = "saving";
        }
        let newRow = {
          isEdit: true
        };
        skipChecking.value = true;
        changeStateTab(false);
        for (let column of fields.value) {
          switch (column.type) {
            case "checkbox":
              newRow[column.key] = false;
              break;
            case "relation":
              newRow[column.key] = {
                value: null,
                localOptions: []
              };
              break;
            default:
              newRow[column.key] = null;
              break;
          }
        }
        bodyData.value.push(newRow);
      };
      const changeStateTab = (data2) => {
        emit("callAction", {
          action: "changeStateTab",
          value: data2
        });
      };
      const initPayment = (data2) => {
        isShow.value = {
          state: true,
          type: "payment"
        };
        activePayment.value = {
          id: data2.id,
          value: data2.value
        };
      };
      const paymentFine = () => {
        isShow.value = {
          state: false,
          type: null
        };
        let findedIndex = bodyData.value.findIndex((p) => p.id == activePayment.value.id);
        bodyData.value[findedIndex].payment.state = true;
        bodyData.value[findedIndex].isUpdated = true;
        setTimeout(() => {
          delete bodyData.value[findedIndex].isUpdated;
        }, 3e3);
        emit("callAction", { action: "paymentFine", value: activePayment.value });
      };
      switch (data.action) {
        case "setPropsValues":
          return setPropsValues(data.value);
        case "edit":
          editRows();
          break;
        case "cancel":
          cancelRows();
          break;
        case "save":
          saveRows();
          break;
        case "initDelete":
          initDeleteRows(data.value);
          break;
        case "delete":
          deleteRows("delete");
          break;
        case "untie":
          deleteRows("untie");
          break;
        case "initRestore":
          initRestoreRows(data.value);
          break;
        case "restore":
          deleteRows("restore");
          break;
        case "socketUpdate":
          socketUpdate();
          break;
        case "getTableData":
          getTableData(data.value);
          break;
        case "moveRows":
          moveRows(data.value);
          break;
        case "removeRow":
          removeRow(data.value);
          break;
        case "initUpdateCategory":
          initUpdateCategory(data.value);
          break;
        case "initCreateCategory":
          initCreateCategory(data.value);
          break;
        case "initCreateSubCategory":
          initCreateSubCategory(data.value);
          break;
        case "initDeleteCategory":
          initDeleteCategory(data.value);
          break;
        case "deleteCategory":
          deleteCategory(data.value);
          break;
        case "initUpdateRole":
          initUpdateRole(data.value);
          break;
        case "initCreateRole":
          initCreateRole(data.value);
          break;
        case "initDeleteRole":
          initDeleteRole(data.value);
          break;
        case "deleteRole":
          deleteRole(data.value);
          break;
        case "addRow":
          addRow();
          break;
        case "changeStateTab":
          changeStateTab(data.value);
          break;
        case "initPayment":
          initPayment(data.value);
          break;
        case "payment":
          paymentFine();
          break;
        default:
          emit("callAction", data);
          break;
      }
    };
    const checkEdittingRows = computed(() => {
      if (props.is_admin || props.permissions.update_p == "A") {
        return "A";
      } else if (props.permissions.update_p == "Y") {
        return bodyData.value.filter((row) => row.isChoose && row.user_id.value != null && row.user_id.value.includes(props.userID)).length > 0 ? "A" : "N";
      } else {
        return "N";
      }
    });
    const checkDelittingRows = computed(() => {
      if (props.is_admin || props.permissions.delete_p == "A") {
        return "A";
      } else if (props.permissions.delete_p == "Y") {
        return bodyData.value.filter((row) => row.isChoose && row.user_id.value != null && row.user_id.value.includes(props.userID)).length > 0 ? "A" : "N";
      } else {
        return "N";
      }
    });
    watch(() => props.table.tableData, () => {
      footerData.value = JSON.parse(JSON.stringify(props.table.tableFooter));
      bodyData.value = callAction({ action: "setPropsValues", value: props.table.tableData });
      if (props.isPermanentEdit) {
        backupValues.value = callAction({ action: "setPropsValues", value: props.table.tableData });
        bodyData.value.forEach((row) => {
          backupValues.value.push(JSON.parse(JSON.stringify(row)));
          row.isEdit = true;
          row.isChoose = true;
        });
      }
    }, {
      deep: true
    });
    watch(() => props.table.tableKeys, () => {
      console.log("table");
      fields.value = callAction({ action: "setPropsValues", value: props.table.tableKeys });
      roleRef.value++;
    }, {
      deep: true
    });
    watch(() => props.categories, () => {
      categories.value = JSON.parse(JSON.stringify(props.categories));
    }, {
      deep: true
    });
    watch(() => props.table.socketRows, () => {
      socketRows.value = JSON.parse(JSON.stringify(props.table.socketRows));
    }, {
      deep: true
    });
    watch(() => props.table.sortItem, () => {
      sortItem.value = JSON.parse(JSON.stringify(props.table.sortItem));
    });
    watch(() => isMobile.value, () => {
      setTimeout(() => {
        changeWidth();
      }, 200);
    }, {
      deep: true
    });
    return (_ctx, _push, _parent, _attrs) => {
      const _component_RenderCacheable = resolveComponent("RenderCacheable");
      _push(ssrRenderComponent(_component_RenderCacheable, mergeProps({
        class: ["table-container", props.isHaveCategories ? "table-container_categories" : "", unref(isMobileCategory) || !props.pageTableOnly ? "table-container_construct" : ""],
        "cache-key": "table",
        "max-age": 3600
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            if (props.isHaveCategories) {
              _push2(ssrRenderComponent(TableCategory, {
                categoryType: props.categoryType,
                activeCategory: props.activeCategory,
                onCallAction: (data) => callAction(data)
              }, null, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            if (props.isDinamyc) {
              _push2(ssrRenderComponent(AppSection, {
                class: "table__product-actions",
                isCanResize: false
              }, {
                default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                  if (_push3) {
                    _push3(ssrRenderComponent(AppButton, {
                      class: "button_add button_blue",
                      onClick: () => callAction({
                        action: "addRow",
                        value: null
                      })
                    }, {
                      default: withCtx((_3, _push4, _parent4, _scopeId3) => {
                        if (_push4) {
                          _push4(` \u0414\u043E\u0431\u0430\u0432\u0438\u0442\u044C \u0441\u0442\u0440\u043E\u043A\u0443 `);
                        } else {
                          return [
                            createTextVNode(" \u0414\u043E\u0431\u0430\u0432\u0438\u0442\u044C \u0441\u0442\u0440\u043E\u043A\u0443 ")
                          ];
                        }
                      }),
                      _: 1
                    }, _parent3, _scopeId2));
                    if (props.isDinamyc) {
                      _push3(ssrRenderComponent(TableTotal, null, null, _parent3, _scopeId2));
                    } else {
                      _push3(`<!---->`);
                    }
                  } else {
                    return [
                      createVNode(AppButton, {
                        class: "button_add button_blue",
                        onClick: () => callAction({
                          action: "addRow",
                          value: null
                        })
                      }, {
                        default: withCtx(() => [
                          createTextVNode(" \u0414\u043E\u0431\u0430\u0432\u0438\u0442\u044C \u0441\u0442\u0440\u043E\u043A\u0443 ")
                        ]),
                        _: 1
                      }, 8, ["onClick"]),
                      props.isDinamyc ? (openBlock(), createBlock(TableTotal, { key: 0 })) : createCommentVNode("", true)
                    ];
                  }
                }),
                _: 1
              }, _parent2, _scopeId));
            } else {
              _push2(`<!---->`);
            }
            _push2(ssrRenderComponent(AppSection, {
              ref_key: "sectionRef",
              ref: sectionRef,
              class: [
                "section__table table-template",
                props.table.restrictions && props.table.restrictions.count != null && props.table.restrictions.count <= unref(bodyData).length ? "section__table_uncopy" : "",
                unref(socketRows).header.length > 0 || unref(socketRows).body.length > 0 ? "table-template_updating" : "",
                props.table.loaderState == "loading" ? "table-template_loading" : props.table.loaderState == "filtering" ? "table-template_filtering" : "",
                unref(fields).length == 0 || unref(bodyData).length == 0 ? "table-template_empty" : ""
              ],
              style: `--stickyTop: ${unref(scrollPosition)}px; --sectionWidth: ${unref(sectionWidth)}px`,
              isCanResize: false
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(ssrRenderComponent(TableTop, {
                    tableTitle: props.title,
                    permissions: props.permissions,
                    onCallAction: (data) => callAction(data)
                  }, {
                    top: withCtx((_3, _push4, _parent4, _scopeId3) => {
                      if (_push4) {
                        ssrRenderSlot(_ctx.$slots, "top", {}, null, _push4, _parent4, _scopeId3);
                      } else {
                        return [
                          renderSlot(_ctx.$slots, "top")
                        ];
                      }
                    }),
                    _: 3
                  }, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(TableSocket, {
                    style: unref(socketRows).header.length > 0 || unref(socketRows).body.length > 0 ? null : { display: "none" },
                    socketRows: unref(socketRows),
                    onCallAction: (data) => callAction(data)
                  }, null, _parent3, _scopeId2));
                  if (unref(isMobile)) {
                    _push3(ssrRenderComponent(TableMobile, {
                      slug: props.slug,
                      isTrash: props.isTrash,
                      actionType: props.actionType,
                      isCanOpenCount: props.table.restrictions ? props.table.restrictions.count : 0,
                      permissions: props.permissions,
                      loaderState: props.table.loaderState,
                      isPermanentEdit: props.isPermanentEdit,
                      onCallAction: (data) => callAction(data),
                      onChangeValue: (data) => emit("changeValue", data)
                    }, null, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  if (unref(tableRef) != null) {
                    _push3(ssrRenderComponent(ScrollButtons, {
                      tableRef: unref(tableRef),
                      isMobileCategory: unref(isMobileCategory),
                      isHaveScrollingHeader: props.isHaveScrollingHeader,
                      onCallAction: (data) => callAction(data)
                    }, null, _parent3, _scopeId2));
                  } else {
                    _push3(`<!---->`);
                  }
                  if (!unref(isMobile)) {
                    _push3(`<div class="table-template__body section__scroll-area"${_scopeId2}><table class="${ssrRenderClass([[props.isPermanentEdit ? "table_permanent-edit" : ""], "table"])}"${_scopeId2}>`);
                    if (props.table.loaderState != "loading") {
                      _push3(ssrRenderComponent(TableHeader, {
                        isTrash: props.isTrash,
                        role: `table_${unref(roleRef)}`,
                        key: unref(roleRef),
                        onCallAction: (data) => callAction(data)
                      }, null, _parent3, _scopeId2));
                    } else {
                      _push3(`<!---->`);
                    }
                    _push3(ssrRenderComponent(TableBody, {
                      actionType: props.actionType,
                      slug: props.slug,
                      role: `table_${unref(roleRef)}`,
                      key: unref(roleRef),
                      isCanOpenCount: props.table.restrictions ? props.table.restrictions.count : 0,
                      isTrash: props.isTrash,
                      groupBody: props.groupBody,
                      isDraggableRow: props.isDraggableRow,
                      permissions: props.permissions,
                      isPermanentEdit: props.isPermanentEdit,
                      onCallAction: (data) => callAction(data),
                      onChangeValue: (data) => emit("changeValue", data)
                    }, null, _parent3, _scopeId2));
                    _push3(`</table><div class="table__empty-block"${_scopeId2}> \u041D\u0435\u0442 \u0434\u0430\u043D\u043D\u044B\u0445 </div></div>`);
                  } else {
                    _push3(`<!---->`);
                  }
                  _push3(ssrRenderComponent(TableFooter, {
                    onCallAction: (data) => callAction(data)
                  }, null, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(SectionActions, {
                    is_admin: props.is_admin,
                    permissions: {
                      update_p: unref(checkEdittingRows),
                      delete_p: unref(checkDelittingRows)
                    },
                    actionState: unref(actionState),
                    loaderState: props.table.loaderState,
                    onCallAction: (data) => callAction(data)
                  }, null, _parent3, _scopeId2));
                  _push3(ssrRenderComponent(TableWarning, {
                    balance: props.balance,
                    onCallAction: (data) => callAction(data)
                  }, null, _parent3, _scopeId2));
                } else {
                  return [
                    createVNode(TableTop, {
                      tableTitle: props.title,
                      permissions: props.permissions,
                      onCallAction: (data) => callAction(data)
                    }, {
                      top: withCtx(() => [
                        renderSlot(_ctx.$slots, "top")
                      ]),
                      _: 3
                    }, 8, ["tableTitle", "permissions", "onCallAction"]),
                    withDirectives(createVNode(TableSocket, {
                      socketRows: unref(socketRows),
                      onCallAction: (data) => callAction(data)
                    }, null, 8, ["socketRows", "onCallAction"]), [
                      [vShow, unref(socketRows).header.length > 0 || unref(socketRows).body.length > 0]
                    ]),
                    unref(isMobile) ? (openBlock(), createBlock(TableMobile, {
                      key: 0,
                      slug: props.slug,
                      isTrash: props.isTrash,
                      actionType: props.actionType,
                      isCanOpenCount: props.table.restrictions ? props.table.restrictions.count : 0,
                      permissions: props.permissions,
                      loaderState: props.table.loaderState,
                      isPermanentEdit: props.isPermanentEdit,
                      onCallAction: (data) => callAction(data),
                      onChangeValue: (data) => emit("changeValue", data)
                    }, null, 8, ["slug", "isTrash", "actionType", "isCanOpenCount", "permissions", "loaderState", "isPermanentEdit", "onCallAction", "onChangeValue"])) : createCommentVNode("", true),
                    unref(tableRef) != null ? (openBlock(), createBlock(ScrollButtons, {
                      key: 1,
                      tableRef: unref(tableRef),
                      isMobileCategory: unref(isMobileCategory),
                      isHaveScrollingHeader: props.isHaveScrollingHeader,
                      onCallAction: (data) => callAction(data)
                    }, null, 8, ["tableRef", "isMobileCategory", "isHaveScrollingHeader", "onCallAction"])) : createCommentVNode("", true),
                    !unref(isMobile) ? (openBlock(), createBlock("div", {
                      key: 2,
                      ref_key: "scrollAreaRef",
                      ref: scrollAreaRef,
                      class: "table-template__body section__scroll-area"
                    }, [
                      createVNode("table", {
                        class: ["table", [props.isPermanentEdit ? "table_permanent-edit" : ""]],
                        ref_key: "tableRef",
                        ref: tableRef
                      }, [
                        props.table.loaderState != "loading" ? (openBlock(), createBlock(TableHeader, {
                          isTrash: props.isTrash,
                          role: `table_${unref(roleRef)}`,
                          key: unref(roleRef),
                          onCallAction: (data) => callAction(data)
                        }, null, 8, ["isTrash", "role", "onCallAction"])) : createCommentVNode("", true),
                        (openBlock(), createBlock(TableBody, {
                          actionType: props.actionType,
                          slug: props.slug,
                          role: `table_${unref(roleRef)}`,
                          key: unref(roleRef),
                          isCanOpenCount: props.table.restrictions ? props.table.restrictions.count : 0,
                          isTrash: props.isTrash,
                          groupBody: props.groupBody,
                          isDraggableRow: props.isDraggableRow,
                          permissions: props.permissions,
                          isPermanentEdit: props.isPermanentEdit,
                          onCallAction: (data) => callAction(data),
                          onChangeValue: (data) => emit("changeValue", data)
                        }, null, 8, ["actionType", "slug", "role", "isCanOpenCount", "isTrash", "groupBody", "isDraggableRow", "permissions", "isPermanentEdit", "onCallAction", "onChangeValue"]))
                      ], 2),
                      createVNode("div", { class: "table__empty-block" }, " \u041D\u0435\u0442 \u0434\u0430\u043D\u043D\u044B\u0445 ")
                    ], 512)) : createCommentVNode("", true),
                    createVNode(TableFooter, {
                      onCallAction: (data) => callAction(data)
                    }, null, 8, ["onCallAction"]),
                    createVNode(SectionActions, {
                      is_admin: props.is_admin,
                      permissions: {
                        update_p: unref(checkEdittingRows),
                        delete_p: unref(checkDelittingRows)
                      },
                      actionState: unref(actionState),
                      loaderState: props.table.loaderState,
                      onCallAction: (data) => callAction(data)
                    }, null, 8, ["is_admin", "permissions", "actionState", "loaderState", "onCallAction"]),
                    createVNode(TableWarning, {
                      balance: props.balance,
                      onCallAction: (data) => callAction(data)
                    }, null, 8, ["balance", "onCallAction"])
                  ];
                }
              }),
              _: 3
            }, _parent2, _scopeId));
          } else {
            return [
              props.isHaveCategories ? (openBlock(), createBlock(TableCategory, {
                key: 0,
                categoryType: props.categoryType,
                activeCategory: props.activeCategory,
                onCallAction: (data) => callAction(data)
              }, null, 8, ["categoryType", "activeCategory", "onCallAction"])) : createCommentVNode("", true),
              props.isDinamyc ? (openBlock(), createBlock(AppSection, {
                key: 1,
                class: "table__product-actions",
                isCanResize: false
              }, {
                default: withCtx(() => [
                  createVNode(AppButton, {
                    class: "button_add button_blue",
                    onClick: () => callAction({
                      action: "addRow",
                      value: null
                    })
                  }, {
                    default: withCtx(() => [
                      createTextVNode(" \u0414\u043E\u0431\u0430\u0432\u0438\u0442\u044C \u0441\u0442\u0440\u043E\u043A\u0443 ")
                    ]),
                    _: 1
                  }, 8, ["onClick"]),
                  props.isDinamyc ? (openBlock(), createBlock(TableTotal, { key: 0 })) : createCommentVNode("", true)
                ]),
                _: 1
              })) : createCommentVNode("", true),
              createVNode(AppSection, {
                ref_key: "sectionRef",
                ref: sectionRef,
                class: [
                  "section__table table-template",
                  props.table.restrictions && props.table.restrictions.count != null && props.table.restrictions.count <= unref(bodyData).length ? "section__table_uncopy" : "",
                  unref(socketRows).header.length > 0 || unref(socketRows).body.length > 0 ? "table-template_updating" : "",
                  props.table.loaderState == "loading" ? "table-template_loading" : props.table.loaderState == "filtering" ? "table-template_filtering" : "",
                  unref(fields).length == 0 || unref(bodyData).length == 0 ? "table-template_empty" : ""
                ],
                style: `--stickyTop: ${unref(scrollPosition)}px; --sectionWidth: ${unref(sectionWidth)}px`,
                isCanResize: false
              }, {
                default: withCtx(() => [
                  createVNode(TableTop, {
                    tableTitle: props.title,
                    permissions: props.permissions,
                    onCallAction: (data) => callAction(data)
                  }, {
                    top: withCtx(() => [
                      renderSlot(_ctx.$slots, "top")
                    ]),
                    _: 3
                  }, 8, ["tableTitle", "permissions", "onCallAction"]),
                  withDirectives(createVNode(TableSocket, {
                    socketRows: unref(socketRows),
                    onCallAction: (data) => callAction(data)
                  }, null, 8, ["socketRows", "onCallAction"]), [
                    [vShow, unref(socketRows).header.length > 0 || unref(socketRows).body.length > 0]
                  ]),
                  unref(isMobile) ? (openBlock(), createBlock(TableMobile, {
                    key: 0,
                    slug: props.slug,
                    isTrash: props.isTrash,
                    actionType: props.actionType,
                    isCanOpenCount: props.table.restrictions ? props.table.restrictions.count : 0,
                    permissions: props.permissions,
                    loaderState: props.table.loaderState,
                    isPermanentEdit: props.isPermanentEdit,
                    onCallAction: (data) => callAction(data),
                    onChangeValue: (data) => emit("changeValue", data)
                  }, null, 8, ["slug", "isTrash", "actionType", "isCanOpenCount", "permissions", "loaderState", "isPermanentEdit", "onCallAction", "onChangeValue"])) : createCommentVNode("", true),
                  unref(tableRef) != null ? (openBlock(), createBlock(ScrollButtons, {
                    key: 1,
                    tableRef: unref(tableRef),
                    isMobileCategory: unref(isMobileCategory),
                    isHaveScrollingHeader: props.isHaveScrollingHeader,
                    onCallAction: (data) => callAction(data)
                  }, null, 8, ["tableRef", "isMobileCategory", "isHaveScrollingHeader", "onCallAction"])) : createCommentVNode("", true),
                  !unref(isMobile) ? (openBlock(), createBlock("div", {
                    key: 2,
                    ref_key: "scrollAreaRef",
                    ref: scrollAreaRef,
                    class: "table-template__body section__scroll-area"
                  }, [
                    createVNode("table", {
                      class: ["table", [props.isPermanentEdit ? "table_permanent-edit" : ""]],
                      ref_key: "tableRef",
                      ref: tableRef
                    }, [
                      props.table.loaderState != "loading" ? (openBlock(), createBlock(TableHeader, {
                        isTrash: props.isTrash,
                        role: `table_${unref(roleRef)}`,
                        key: unref(roleRef),
                        onCallAction: (data) => callAction(data)
                      }, null, 8, ["isTrash", "role", "onCallAction"])) : createCommentVNode("", true),
                      (openBlock(), createBlock(TableBody, {
                        actionType: props.actionType,
                        slug: props.slug,
                        role: `table_${unref(roleRef)}`,
                        key: unref(roleRef),
                        isCanOpenCount: props.table.restrictions ? props.table.restrictions.count : 0,
                        isTrash: props.isTrash,
                        groupBody: props.groupBody,
                        isDraggableRow: props.isDraggableRow,
                        permissions: props.permissions,
                        isPermanentEdit: props.isPermanentEdit,
                        onCallAction: (data) => callAction(data),
                        onChangeValue: (data) => emit("changeValue", data)
                      }, null, 8, ["actionType", "slug", "role", "isCanOpenCount", "isTrash", "groupBody", "isDraggableRow", "permissions", "isPermanentEdit", "onCallAction", "onChangeValue"]))
                    ], 2),
                    createVNode("div", { class: "table__empty-block" }, " \u041D\u0435\u0442 \u0434\u0430\u043D\u043D\u044B\u0445 ")
                  ], 512)) : createCommentVNode("", true),
                  createVNode(TableFooter, {
                    onCallAction: (data) => callAction(data)
                  }, null, 8, ["onCallAction"]),
                  createVNode(SectionActions, {
                    is_admin: props.is_admin,
                    permissions: {
                      update_p: unref(checkEdittingRows),
                      delete_p: unref(checkDelittingRows)
                    },
                    actionState: unref(actionState),
                    loaderState: props.table.loaderState,
                    onCallAction: (data) => callAction(data)
                  }, null, 8, ["is_admin", "permissions", "actionState", "loaderState", "onCallAction"]),
                  createVNode(TableWarning, {
                    balance: props.balance,
                    onCallAction: (data) => callAction(data)
                  }, null, 8, ["balance", "onCallAction"])
                ]),
                _: 3
              }, 8, ["style", "class"])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppTable/AppTable.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AppTable = _sfc_main;

export { AppTable as A };
//# sourceMappingURL=AppTable-9ca02910.mjs.map
