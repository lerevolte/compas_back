import { e as __nuxt_component_0 } from './server.mjs';
import { useSSRContext, mergeProps, withCtx, createTextVNode, toDisplayString } from 'vue';
import { ssrRenderAttrs, ssrRenderList, ssrRenderComponent, ssrInterpolate } from 'vue/server-renderer';

const _sfc_main = {
  __name: "Breadcrambs",
  __ssrInlineRender: true,
  props: {
    breadcrumbs: {
      default: [
        {
          title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F",
          link: "/"
        }
      ],
      type: Object
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "breadcrambs" }, _attrs))}><!--[-->`);
      ssrRenderList(props.breadcrumbs, (breadcrumb) => {
        _push(`<!--[-->`);
        if (breadcrumb == null ? void 0 : breadcrumb.title) {
          _push(ssrRenderComponent(_component_NuxtLink, {
            class: "breadcrambs__item",
            to: breadcrumb == null ? void 0 : breadcrumb.link
          }, {
            default: withCtx((_, _push2, _parent2, _scopeId) => {
              if (_push2) {
                _push2(`${ssrInterpolate(breadcrumb == null ? void 0 : breadcrumb.title)}`);
              } else {
                return [
                  createTextVNode(toDisplayString(breadcrumb == null ? void 0 : breadcrumb.title), 1)
                ];
              }
            }),
            _: 2
          }, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      });
      _push(`<!--]--></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppBreadcrambs/Breadcrambs.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AppBreadcrambs = _sfc_main;

export { AppBreadcrambs as A };
//# sourceMappingURL=Breadcrambs-9c951e2d.mjs.map
