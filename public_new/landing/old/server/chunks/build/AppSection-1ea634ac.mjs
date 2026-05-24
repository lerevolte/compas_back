import { useSSRContext, ref, mergeProps } from 'vue';
import { ssrRenderAttrs, ssrRenderSlot } from 'vue/server-renderer';

const _sfc_main = {
  __name: "AppSection",
  __ssrInlineRender: true,
  setup(__props, { expose: __expose }) {
    const sectionRef = ref();
    __expose({
      sectionRef
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<section${ssrRenderAttrs(mergeProps({
        ref_key: "sectionRef",
        ref: sectionRef
      }, _attrs))}>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</section>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSection/AppSection.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AppSection = _sfc_main;

export { AppSection as A };
//# sourceMappingURL=AppSection-1ea634ac.mjs.map
