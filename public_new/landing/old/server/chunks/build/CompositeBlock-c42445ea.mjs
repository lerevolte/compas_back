import { useSSRContext, mergeProps, withCtx, createVNode, renderSlot } from 'vue';
import { ssrRenderComponent, ssrRenderSlot } from 'vue/server-renderer';
import { A as AppSection } from './AppSection-1ea634ac.mjs';

const _sfc_main = {
  __name: "CompositeBlock",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppSection, mergeProps({ class: "composite-block section_without-background" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`<div class="section__content"${_scopeId}><div class="section__info"${_scopeId}>`);
            ssrRenderSlot(_ctx.$slots, "content", {}, null, _push2, _parent2, _scopeId);
            _push2(`</div></div><div class="section__image"${_scopeId}>`);
            ssrRenderSlot(_ctx.$slots, "image", {}, null, _push2, _parent2, _scopeId);
            _push2(`</div>`);
          } else {
            return [
              createVNode("div", { class: "section__content" }, [
                createVNode("div", { class: "section__info" }, [
                  renderSlot(_ctx.$slots, "content")
                ])
              ]),
              createVNode("div", { class: "section__image" }, [
                renderSlot(_ctx.$slots, "image")
              ])
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/AppSection/CompositeBlock/CompositeBlock.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const CompositeBlock = _sfc_main;

export { CompositeBlock as C };
//# sourceMappingURL=CompositeBlock-c42445ea.mjs.map
