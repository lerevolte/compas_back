import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { useSSRContext, ref, watch, mergeProps, unref, withCtx, createTextVNode, createVNode, withKeys, openBlock, createBlock, toDisplayString, createCommentVNode, watchEffect, Fragment, renderList, computed, withDirectives, vShow } from 'vue';
import { w as useUserStore, y as useCommonStore, u as useRoute, n as navigateTo, x as AppH1, j as AppInput, k as AppButton, s as storeToRefs, z as AppCheckbox } from './server.mjs';
import { ssrRenderAttrs, ssrRenderComponent, ssrRenderAttr, ssrInterpolate, ssrRenderList, ssrRenderStyle } from 'vue/server-renderer';
import { A as AppSection } from './AppSection-1ea634ac.mjs';

const _imports_0 = "" + publicAssetsURL("pages/auth/background.svg");
const _sfc_main$4 = {
  __name: "Entry",
  __ssrInlineRender: true,
  props: {
    authRef: {
      default: null
    }
  },
  setup(__props) {
    const userStore = useUserStore();
    const props = __props;
    const changeValue = (data) => {
      userStore.authData[data.key] = data.value;
    };
    const logIn = () => {
      if (!userStore.authButtonLoad) {
        userStore.logIn(userStore.authData, props.authRef);
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(AppH1, { class: "auth__title" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0412\u0445\u043E\u0434 \u043D\u0430 \u043F\u043E\u0440\u0442\u0430\u043B <span class="auth__title-portal__name"${_scopeId}></span>`);
          } else {
            return [
              createTextVNode(" \u0412\u0445\u043E\u0434 \u043D\u0430 \u043F\u043E\u0440\u0442\u0430\u043B "),
              createVNode("span", { class: "auth__title-portal__name" })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(AppSection, { class: "auth__form" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="auth__input-wrapper"${_scopeId}>`);
            _push2(ssrRenderComponent(AppInput, {
              class: "auth__input auth__input_substr",
              item: {
                id: 0,
                title: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
                value: unref(userStore).authData.domain,
                placeholder: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
                type: "text",
                key: "domain",
                substring: ".compas.pro"
              },
              mask: null,
              disabled: unref(userStore).authButtonLoad,
              enabledAutocomplete: true,
              onChangeValue: (data) => changeValue(data),
              onKeyup: logIn
            }, null, _parent2, _scopeId));
            if (unref(userStore).authError.text) {
              _push2(`<p class="warning-list__field-error"${_scopeId}>${ssrInterpolate(unref(userStore).authError.text)}</p>`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            _push2(ssrRenderComponent(AppButton, {
              class: [unref(userStore).authButtonLoad ? "button_loading" : "", "auth__button button_blue"],
              onClick: logIn
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0412\u043E\u0439\u0442\u0438 `);
                } else {
                  return [
                    createTextVNode(" \u0412\u043E\u0439\u0442\u0438 ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              createVNode("div", { class: "auth__input-wrapper" }, [
                createVNode(AppInput, {
                  class: "auth__input auth__input_substr",
                  item: {
                    id: 0,
                    title: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
                    value: unref(userStore).authData.domain,
                    placeholder: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
                    type: "text",
                    key: "domain",
                    substring: ".compas.pro"
                  },
                  mask: null,
                  disabled: unref(userStore).authButtonLoad,
                  enabledAutocomplete: true,
                  onChangeValue: (data) => changeValue(data),
                  onKeyup: withKeys(logIn, ["enter"])
                }, null, 8, ["item", "disabled", "onChangeValue"]),
                unref(userStore).authError.text ? (openBlock(), createBlock("p", {
                  key: 0,
                  class: "warning-list__field-error"
                }, toDisplayString(unref(userStore).authError.text), 1)) : createCommentVNode("", true)
              ]),
              createVNode(AppButton, {
                class: [unref(userStore).authButtonLoad ? "button_loading" : "", "auth__button button_blue"],
                onClick: logIn
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u0412\u043E\u0439\u0442\u0438 ")
                ]),
                _: 1
              }, 8, ["class"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="auth__text auth__subtext"> \u041D\u0435\u0442 \u043F\u043E\u0440\u0442\u0430\u043B\u0430? <span class="auth__link"> \u0421\u043E\u0437\u0434\u0430\u0439\u0442\u0435 \u0431\u0435\u0441\u043F\u043B\u0430\u0442\u043D\u044B\u0439. </span></div><!--]-->`);
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/AuthPage/Entry/Entry.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const AuthEntry = _sfc_main$4;
const _sfc_main$3 = {
  __name: "Exit",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<figure${ssrRenderAttrs(mergeProps({ class: "icon__exit" }, _attrs))}><svg width="14px" height="15px" viewBox="0 0 14 15" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><title>Shape</title><g class="to-fill" id="\u041B\u043E\u0433\u0438\u0441\u0442\u0438\u043A\u0430-2" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><g class="to-fill" id="Desktop-HD-Copy-14" transform="translate(-839, -359)" fill="#A6B7D4" fill-rule="nonzero"><g id="Group-21" transform="translate(536, 185)"><g id="Group-23" transform="translate(0, 56)"><g id="Group-11-Copy-3" transform="translate(35, 102)"><path d="M278.877389,25.4387699 L278.877389,23.5521565 L274.122597,23.5521565 L274.122597,21.6655148 L278.877389,21.6655148 L278.877389,19.778873 L281.730259,22.6088215 L278.877389,25.4387699 Z M278.469876,24.4954632 L278.469876,27.9420902 C278.470631,28.0322239 278.437741,28.1096955 278.371205,28.1745051 C278.304669,28.2393147 278.22577,28.2707191 278.134508,28.2687183 L273.715044,28.2687183 L273.715044,30.8090311 C273.718587,30.8890796 273.697142,30.9448585 273.650712,30.9763679 C273.604281,31.0078774 273.543532,31.0078774 273.468466,30.9763679 L268.273614,28.3998107 C268.168089,28.3616992 268.095144,28.3066064 268.054778,28.2345323 C268.014412,28.1624583 267.999254,28.0602416 268.009305,27.9278824 L268.009305,16.6433625 C267.978265,16.4511992 268.024883,16.2925181 268.14916,16.1673192 C268.273437,16.0421204 268.437835,15.9882199 268.642354,16.0056179 L277.830147,16.0056179 C278.087216,15.9876352 278.255539,16.012824 278.335115,16.0811844 C278.414691,16.1495448 278.459611,16.3075331 278.469876,16.5551491 L278.469876,20.722208 L277.518861,20.722208 L277.518861,16.9489246 L269.911199,16.9489246 L273.410216,18.6843766 C273.526441,18.7316882 273.606606,18.7919264 273.650712,18.8650913 C273.694817,18.9382561 273.716262,19.0465725 273.715044,19.1900404 L273.715044,27.3254116 L277.518861,27.3254116 L277.518861,24.4954632 L278.469876,24.4954632 Z" id="Shape"></path></g></g></g></g></g></svg></figure>`);
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppIcons/Exit/Exit.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const AppExit = _sfc_main$3;
const _sfc_main$2 = {
  __name: "Accounts",
  __ssrInlineRender: true,
  props: {
    authRef: {
      default: null
    }
  },
  setup(__props) {
    const commonStore = useCommonStore();
    const { accounts } = storeToRefs(commonStore);
    const deleteAccount = (accToDelete) => {
      accounts.value = accounts.value.filter((acc) => acc != accToDelete);
    };
    watchEffect(() => {
      if (accounts.value.length <= 0) {
        navigateTo("/auth/entry");
      }
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(AppH1, { class: "auth__title" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0412\u0445\u043E\u0434 \u043D\u0430 \u043F\u043E\u0440\u0442\u0430\u043B <span class="auth__title-portal__name"${_scopeId}></span>`);
          } else {
            return [
              createTextVNode(" \u0412\u0445\u043E\u0434 \u043D\u0430 \u043F\u043E\u0440\u0442\u0430\u043B "),
              createVNode("span", { class: "auth__title-portal__name" })
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(AppSection, { class: "auth__form" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="auth__account-wrapper"${_scopeId}><p class="auth__account-title"${_scopeId}>\u0411\u044B\u0441\u0442\u0440\u044B\u0439 \u0432\u0445\u043E\u0434</p><!--[-->`);
            ssrRenderList(unref(accounts), (account) => {
              _push2(`<div class="auth__account"${_scopeId}><span class="auth__link"${_scopeId}>${ssrInterpolate(`${account}.compas.pro`)}</span>`);
              _push2(ssrRenderComponent(AppExit, {
                onClick: ($event) => deleteAccount(account),
                class: "auth__account-exit"
              }, null, _parent2, _scopeId));
              _push2(`</div>`);
            });
            _push2(`<!--]--></div>`);
          } else {
            return [
              createVNode("div", { class: "auth__account-wrapper" }, [
                createVNode("p", { class: "auth__account-title" }, "\u0411\u044B\u0441\u0442\u0440\u044B\u0439 \u0432\u0445\u043E\u0434"),
                (openBlock(true), createBlock(Fragment, null, renderList(unref(accounts), (account) => {
                  return openBlock(), createBlock("div", { class: "auth__account" }, [
                    createVNode("span", {
                      class: "auth__link",
                      onClick: ($event) => ("navigateTo" in _ctx ? _ctx.navigateTo : unref(navigateTo))(`http://${account}.compas.pro/`, { external: true })
                    }, toDisplayString(`${account}.compas.pro`), 9, ["onClick"]),
                    createVNode(AppExit, {
                      onClick: ($event) => deleteAccount(account),
                      class: "auth__account-exit"
                    }, null, 8, ["onClick"])
                  ]);
                }), 256))
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="auth__text auth__subtext"><span class="auth__link"> \u0412\u043E\u0439\u0442\u0438 \u0432 \u043D\u043E\u0432\u044B\u0439 \u043F\u043E\u0440\u0442\u0430\u043B </span></div><!--]-->`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/AuthPage/Accounts/Accounts.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const AuthAccounts = _sfc_main$2;
const checkboxLink = `<div class="auth__text">
	       \u042F \u043F\u043E\u043D\u0438\u043C\u0430\u044E \u0438 \u043F\u0440\u0438\u043D\u0438\u043C\u0430\u044E <a href="/docs/politics" class="auth__link" target="_blank"> \u0443\u0441\u043B\u043E\u0432\u0438\u044F \u0438 \u043F\u043E\u043B\u0438\u0442\u0438\u043A\u0443 \u043A\u043E\u043D\u0444\u0438\u0434\u0435\u043D\u0446\u0438\u0430\u043B\u044C\u043D\u043E\u0441\u0442\u0438 </a> Compas
	   </div>`;
const _sfc_main$1 = {
  __name: "Registration",
  __ssrInlineRender: true,
  setup(__props) {
    const userStore = useUserStore();
    const route = useRoute();
    const { regData } = storeToRefs(userStore);
    const changeValue = (data) => {
      regData.value[data.key] = data.value;
    };
    const disabledButton = computed(() => {
      return !regData.value.confidence || regData.value.password == "" || regData.value.passwordConfirmation == "" || regData.value.email == "";
    });
    const registration = () => {
      if (route.query.tariff) {
        regData.value.tariff = route.query.tariff;
      }
      if (!userStore.regButtonLoad) {
        userStore.registration(regData.value);
      }
    };
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[-->`);
      _push(ssrRenderComponent(AppH1, { class: "auth__title" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(` \u0420\u0435\u0433\u0438\u0441\u0442\u0440\u0430\u0446\u0438\u044F \u043F\u043E\u0440\u0442\u0430\u043B\u0430 `);
          } else {
            return [
              createTextVNode(" \u0420\u0435\u0433\u0438\u0441\u0442\u0440\u0430\u0446\u0438\u044F \u043F\u043E\u0440\u0442\u0430\u043B\u0430 ")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(AppSection, { class: "auth__form" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="auth__error" style="${ssrRenderStyle(unref(userStore).authError.status ? null : { display: "none" })}"${_scopeId}>${ssrInterpolate(unref(userStore).authError.text)}</div><div class="auth__input-wrapper"${_scopeId}>`);
            _push2(ssrRenderComponent(AppInput, {
              disabled: unref(userStore).regButtonLoad,
              class: "auth__input auth__input_substr",
              item: {
                id: 0,
                title: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
                value: unref(regData).domain,
                placeholder: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
                type: "text",
                key: "domain",
                substring: ".compas.pro"
              },
              mask: null,
              enabledAutocomplete: true,
              onKeyup: ($event) => !unref(disabledButton) ? registration() : null,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
            if (unref(regData).domainError) {
              _push2(`<!--[-->`);
              ssrRenderList(unref(regData).domainError, (error) => {
                _push2(`<p class="warning-list__field-error"${_scopeId}>${ssrInterpolate(error)}</p>`);
              });
              _push2(`<!--]-->`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="auth__input-wrapper"${_scopeId}>`);
            _push2(ssrRenderComponent(AppInput, {
              class: "auth__input",
              item: {
                id: 0,
                title: "E-mail",
                value: unref(regData).email,
                placeholder: "E-mail",
                type: "text",
                key: "email"
              },
              required: true,
              mask: null,
              disabled: unref(userStore).regButtonLoad,
              enabledAutocomplete: true,
              onKeyup: ($event) => !unref(disabledButton) ? registration() : null,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
            if (unref(regData).emailError) {
              _push2(`<!--[-->`);
              ssrRenderList(unref(regData).emailError, (error) => {
                _push2(`<p class="warning-list__field-error"${_scopeId}>${ssrInterpolate(error)}</p>`);
              });
              _push2(`<!--]-->`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="auth__input-wrapper"${_scopeId}>`);
            _push2(ssrRenderComponent(AppInput, {
              class: "auth__input",
              item: {
                id: 1,
                title: "\u041F\u0430\u0440\u043E\u043B\u044C",
                value: unref(regData).password,
                placeholder: "\u041F\u0430\u0440\u043E\u043B\u044C",
                type: "password",
                key: "password"
              },
              mask: null,
              required: true,
              disabled: unref(userStore).regButtonLoad,
              enabledAutocomplete: false,
              onKeyup: ($event) => !unref(disabledButton) ? registration() : null,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
            if (unref(regData).passwordError) {
              _push2(`<!--[-->`);
              ssrRenderList(unref(regData).passwordError, (error) => {
                _push2(`<p class="warning-list__field-error"${_scopeId}>${ssrInterpolate(error)}</p>`);
              });
              _push2(`<!--]-->`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div><div class="auth__input-wrapper"${_scopeId}>`);
            _push2(ssrRenderComponent(AppInput, {
              class: "auth__input",
              item: {
                id: 1,
                title: "\u041F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u0438\u0435 \u043F\u0430\u0440\u043E\u043B\u044F",
                value: unref(regData).passwordConfirmation,
                placeholder: "\u041F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u0438\u0435 \u043F\u0430\u0440\u043E\u043B\u044F",
                type: "password",
                key: "passwordConfirmation"
              },
              mask: null,
              required: true,
              disabled: unref(userStore).regButtonLoad,
              enabledAutocomplete: false,
              onKeyup: ($event) => !unref(disabledButton) ? registration() : null,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
            if (unref(regData).passwordConfirmationError) {
              _push2(`<!--[-->`);
              ssrRenderList(unref(regData).passwordConfirmationError, (error) => {
                _push2(`<p class="warning-list__field-error"${_scopeId}>${ssrInterpolate(error)}</p>`);
              });
              _push2(`<!--]-->`);
            } else {
              _push2(`<!---->`);
            }
            _push2(`</div>`);
            _push2(ssrRenderComponent(AppCheckbox, {
              class: "auth__checkbox auth__checkbox_long",
              item: {
                id: 2,
                title: checkboxLink,
                value: unref(regData).confidence,
                placeholder: "",
                type: "checkbox",
                key: "confidence",
                isHTML: true
              },
              disabled: unref(userStore).regButtonLoad,
              isTextClickable: false,
              onChangeValue: (data) => changeValue(data)
            }, null, _parent2, _scopeId));
            _push2(ssrRenderComponent(AppButton, {
              disabledOption: unref(disabledButton),
              class: [{ button_loading: unref(userStore).regButtonLoad }, "auth__button button_blue"],
              onClick: registration
            }, {
              default: withCtx((_2, _push3, _parent3, _scopeId2) => {
                if (_push3) {
                  _push3(` \u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043F\u043E\u0440\u0442\u0430\u043B `);
                } else {
                  return [
                    createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043F\u043E\u0440\u0442\u0430\u043B ")
                  ];
                }
              }),
              _: 1
            }, _parent2, _scopeId));
          } else {
            return [
              withDirectives(createVNode("div", { class: "auth__error" }, toDisplayString(unref(userStore).authError.text), 513), [
                [vShow, unref(userStore).authError.status]
              ]),
              createVNode("div", { class: "auth__input-wrapper" }, [
                createVNode(AppInput, {
                  disabled: unref(userStore).regButtonLoad,
                  class: "auth__input auth__input_substr",
                  item: {
                    id: 0,
                    title: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
                    value: unref(regData).domain,
                    placeholder: "\u041D\u0430\u0437\u0432\u0430\u043D\u0438\u0435 \u043F\u043E\u0440\u0442\u0430\u043B\u0430",
                    type: "text",
                    key: "domain",
                    substring: ".compas.pro"
                  },
                  mask: null,
                  enabledAutocomplete: true,
                  onKeyup: withKeys(($event) => !unref(disabledButton) ? registration() : null, ["enter"]),
                  onChangeValue: (data) => changeValue(data)
                }, null, 8, ["disabled", "item", "onKeyup", "onChangeValue"]),
                unref(regData).domainError ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(unref(regData).domainError, (error) => {
                  return openBlock(), createBlock("p", { class: "warning-list__field-error" }, toDisplayString(error), 1);
                }), 256)) : createCommentVNode("", true)
              ]),
              createVNode("div", { class: "auth__input-wrapper" }, [
                createVNode(AppInput, {
                  class: "auth__input",
                  item: {
                    id: 0,
                    title: "E-mail",
                    value: unref(regData).email,
                    placeholder: "E-mail",
                    type: "text",
                    key: "email"
                  },
                  required: true,
                  mask: null,
                  disabled: unref(userStore).regButtonLoad,
                  enabledAutocomplete: true,
                  onKeyup: withKeys(($event) => !unref(disabledButton) ? registration() : null, ["enter"]),
                  onChangeValue: (data) => changeValue(data)
                }, null, 8, ["item", "disabled", "onKeyup", "onChangeValue"]),
                unref(regData).emailError ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(unref(regData).emailError, (error) => {
                  return openBlock(), createBlock("p", { class: "warning-list__field-error" }, toDisplayString(error), 1);
                }), 256)) : createCommentVNode("", true)
              ]),
              createVNode("div", { class: "auth__input-wrapper" }, [
                createVNode(AppInput, {
                  class: "auth__input",
                  item: {
                    id: 1,
                    title: "\u041F\u0430\u0440\u043E\u043B\u044C",
                    value: unref(regData).password,
                    placeholder: "\u041F\u0430\u0440\u043E\u043B\u044C",
                    type: "password",
                    key: "password"
                  },
                  mask: null,
                  required: true,
                  disabled: unref(userStore).regButtonLoad,
                  enabledAutocomplete: false,
                  onKeyup: withKeys(($event) => !unref(disabledButton) ? registration() : null, ["enter"]),
                  onChangeValue: (data) => changeValue(data)
                }, null, 8, ["item", "disabled", "onKeyup", "onChangeValue"]),
                unref(regData).passwordError ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(unref(regData).passwordError, (error) => {
                  return openBlock(), createBlock("p", { class: "warning-list__field-error" }, toDisplayString(error), 1);
                }), 256)) : createCommentVNode("", true)
              ]),
              createVNode("div", { class: "auth__input-wrapper" }, [
                createVNode(AppInput, {
                  class: "auth__input",
                  item: {
                    id: 1,
                    title: "\u041F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u0438\u0435 \u043F\u0430\u0440\u043E\u043B\u044F",
                    value: unref(regData).passwordConfirmation,
                    placeholder: "\u041F\u043E\u0434\u0442\u0432\u0435\u0440\u0436\u0434\u0435\u043D\u0438\u0435 \u043F\u0430\u0440\u043E\u043B\u044F",
                    type: "password",
                    key: "passwordConfirmation"
                  },
                  mask: null,
                  required: true,
                  disabled: unref(userStore).regButtonLoad,
                  enabledAutocomplete: false,
                  onKeyup: withKeys(($event) => !unref(disabledButton) ? registration() : null, ["enter"]),
                  onChangeValue: (data) => changeValue(data)
                }, null, 8, ["item", "disabled", "onKeyup", "onChangeValue"]),
                unref(regData).passwordConfirmationError ? (openBlock(true), createBlock(Fragment, { key: 0 }, renderList(unref(regData).passwordConfirmationError, (error) => {
                  return openBlock(), createBlock("p", { class: "warning-list__field-error" }, toDisplayString(error), 1);
                }), 256)) : createCommentVNode("", true)
              ]),
              createVNode(AppCheckbox, {
                class: "auth__checkbox auth__checkbox_long",
                item: {
                  id: 2,
                  title: checkboxLink,
                  value: unref(regData).confidence,
                  placeholder: "",
                  type: "checkbox",
                  key: "confidence",
                  isHTML: true
                },
                disabled: unref(userStore).regButtonLoad,
                isTextClickable: false,
                onChangeValue: (data) => changeValue(data)
              }, null, 8, ["item", "disabled", "onChangeValue"]),
              createVNode(AppButton, {
                disabledOption: unref(disabledButton),
                class: [{ button_loading: unref(userStore).regButtonLoad }, "auth__button button_blue"],
                onClick: registration
              }, {
                default: withCtx(() => [
                  createTextVNode(" \u0421\u043E\u0437\u0434\u0430\u0442\u044C \u043F\u043E\u0440\u0442\u0430\u043B ")
                ]),
                _: 1
              }, 8, ["disabledOption", "class"])
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`<div class="auth__text auth__subtext"> \u0423\u0436\u0435 \u0437\u0430\u0440\u0435\u0433\u0438\u0441\u0442\u0440\u0438\u0440\u043E\u0432\u0430\u043D\u044B? <span class="auth__link"> \u0412\u043E\u0439\u0442\u0438 \u0432 \u0441\u0438\u0441\u0442\u0435\u043C\u0443 </span></div><!--]-->`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/AuthPage/Registration/Registration.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const AuthRegistration = _sfc_main$1;
const _sfc_main = {
  __name: "AuthPage",
  __ssrInlineRender: true,
  setup(__props) {
    const userStore = useUserStore();
    const commonStore = useCommonStore();
    const route = useRoute();
    let activeTab = ref(route.params.tab);
    const authRef = ref(null);
    const router = useRoute();
    const changeActiveTab = (tab) => {
      userStore.authError.text = "";
      activeTab.value = tab;
      navigateTo(`/auth/${tab}`);
    };
    watch(
      () => router.params,
      () => {
        if (router.params.tab) {
          activeTab.value = router.params.tab;
        } else {
          commonStore.accounts.length > 0 ? activeTab.value = "accounts" : activeTab.value = "entry";
          navigateTo(`/auth/${activeTab.value}`);
        }
      },
      {
        deep: true
      }
    );
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: "auth",
        ref_key: "authRef",
        ref: authRef
      }, _attrs))}><div class="auth__main"><div class="auth__wrapper">`);
      if (unref(activeTab) == "accounts") {
        _push(ssrRenderComponent(AuthAccounts, {
          authRef: unref(authRef),
          onChangeActiveTab: (tab) => changeActiveTab(tab)
        }, null, _parent));
      } else if (unref(activeTab) == "entry") {
        _push(ssrRenderComponent(AuthEntry, {
          authRef: unref(authRef),
          onChangeActiveTab: (tab) => changeActiveTab(tab)
        }, null, _parent));
      } else if (unref(activeTab) == "registration") {
        _push(ssrRenderComponent(AuthRegistration, {
          onChangeActiveTab: (tab) => changeActiveTab(tab)
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div><figure class="ibg auth__background"><img${ssrRenderAttr("src", _imports_0)} alt=""></figure></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/AuthPage/AuthPage.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AuthPage = _sfc_main;

export { AuthPage as A };
//# sourceMappingURL=AuthPage-98d4911a.mjs.map
