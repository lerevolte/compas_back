import { useSSRContext, ref, mergeProps } from 'vue';
import { ssrRenderAttrs, ssrRenderSlot } from 'vue/server-renderer';

const _sfc_main = {
  __name: "ButtonText",
  __ssrInlineRender: true,
  setup(__props, { expose: __expose }) {
    const buttonTextRef = ref(null);
    __expose({
      buttonTextRef
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({
        class: "button-text",
        ref_key: "buttonTextRef",
        ref: buttonTextRef
      }, _attrs))}>`);
      ssrRenderSlot(_ctx.$slots, "default", {}, null, _push, _parent);
      _push(`</div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppButton/ButtonText/ButtonText.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const ButtonText = _sfc_main;

export { ButtonText as B };
//# sourceMappingURL=ButtonText-edbdf3ac.mjs.map
