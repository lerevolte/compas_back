import { useSSRContext, unref, mergeProps, withCtx, renderSlot } from 'vue';
import { ssrRenderComponent, ssrRenderSlot } from 'vue/server-renderer';
import { Swiper } from 'swiper/vue';
import { Navigation, Pagination, Virtual } from 'swiper';

const _sfc_main = {
  __name: "Slider",
  __ssrInlineRender: true,
  props: {
    options: {
      default: {
        VisibleSlides: 1,
        spaceBetween: 50,
        pagination: { clickable: true, dynamicBullets: true }
      },
      type: Object
    }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(unref(Swiper), mergeProps({ class: "slider" }, _ctx.$attrs, {
        "slides-per-view": props.options.VisibleSlides,
        "space-between": props.options.spaceBetween,
        modules: [unref(Navigation), unref(Pagination), unref(Virtual)],
        navigation: "",
        pagination: props.options.pagination,
        virtual: ""
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            ssrRenderSlot(_ctx.$slots, "slide", {}, null, _push2, _parent2, _scopeId);
          } else {
            return [
              renderSlot(_ctx.$slots, "slide")
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSlider/Slider.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const AppSlider = _sfc_main;

export { AppSlider as A };
//# sourceMappingURL=Slider-a943f5b9.mjs.map
