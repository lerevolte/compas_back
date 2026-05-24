import { useSSRContext, toRefs, ref, mergeProps, unref } from 'vue';
import { ssrRenderAttrs, ssrRenderAttr } from 'vue/server-renderer';

const _sfc_main = {
  __name: "WrapText",
  __ssrInlineRender: true,
  props: {
    text: { type: String },
    image: { type: String }
  },
  setup(__props) {
    const props = __props;
    const { image, text } = toRefs(props);
    ref(null);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "wrap" }, _attrs))}>`);
      if (unref(image)) {
        _push(`<img${ssrRenderAttr("src", unref(image))} alt="image" class="wrap__image">`);
      } else {
        _push(`<!---->`);
      }
      _push(`<div class="wrap__text"></div></div>`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/WrapText/WrapText.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const MainAbout = _sfc_main;

export { MainAbout as M };
//# sourceMappingURL=WrapText-f5da5fca.mjs.map
