import { a as useHead } from './server.mjs';
import { ssrRenderComponent } from 'vue/server-renderer';
import { D as Distance } from './Distance-710baf9c.mjs';
import { useSSRContext } from 'vue';
import '../runtime.mjs';
import 'node:http';
import 'node:https';
import 'node:fs';
import 'node:path';
import 'node:async_hooks';
import 'node:url';
import 'unhead';
import '@unhead/shared';
import 'vue-router';
import 'dayjs';
import 'dayjs/plugin/updateLocale.js';
import 'dayjs/plugin/relativeTime.js';
import 'dayjs/plugin/utc.js';
import 'chalk';
import 'pinia-plugin-persistedstate';
import 'maska';
import 'vuedraggable';
import '@mahdikhashan/vue3-click-outside';
import 'axios';
import 'vue3-toastify';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'devalue';
import '@unhead/ssr';
import './Breadcrambs-9c951e2d.mjs';
import './AppSection-1ea634ac.mjs';
import './Social-983a064f.mjs';
import './Field-d36cf7e6.mjs';
import './dayjs-ce9ed7b6.mjs';
import 'lodash-es';
import './Input-3345b1b6.mjs';
import './ButtonText-edbdf3ac.mjs';

const _sfc_main = {
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u0420\u0430\u0441\u0447\u0451\u0442 \u0440\u0430\u0441\u0441\u0442\u043E\u044F\u043D\u0438\u044F | Compas.pro",
      meta: [
        {
          name: "description",
          content: "\u041E\u043F\u0438\u0441\u0430\u043D\u0438\u0435."
        }
      ],
      link: [
        {
          rel: "canonical",
          href: `https://compas.pro/products/distance`
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(Distance, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/products/distance/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-c3499185.mjs.map
