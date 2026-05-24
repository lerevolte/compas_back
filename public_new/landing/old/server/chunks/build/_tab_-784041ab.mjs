import { useSSRContext } from 'vue';
import { u as useRoute, a as useHead } from './server.mjs';
import { ssrRenderComponent } from 'vue/server-renderer';
import { D as Distance } from './Distance-710baf9c.mjs';
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

const mkad = {
  title: "\u041A\u0430\u043B\u044C\u043A\u0443\u043B\u044F\u0442\u043E\u0440 \u0440\u0430\u0441\u0441\u0442\u043E\u044F\u043D\u0438\u044F \u043E\u0442 \u041C\u041A\u0410\u0414 | \u0411\u044B\u0441\u0442\u0440\u044B\u0439 \u0440\u0430\u0441\u0447\u0435\u0442 \u0440\u0430\u0441\u0441\u0442\u043E\u044F\u043D\u0438\u0439",
  description: "\u0420\u0430\u0441\u0441\u0447\u0438\u0442\u0430\u0439\u0442\u0435 \u0442\u043E\u0447\u043D\u043E\u0435 \u0440\u0430\u0441\u0441\u0442\u043E\u044F\u043D\u0438\u0435 \u043E\u0442 \u041C\u041A\u0410\u0414 \u0434\u043E \u043B\u044E\u0431\u043E\u0433\u043E \u0433\u043E\u0440\u043E\u0434\u0430 \u041C\u043E\u0441\u043A\u043E\u0432\u0441\u043A\u043E\u0439 \u043E\u0431\u043B\u0430\u0441\u0442\u0438 \u0438 \u0434\u0440\u0443\u0433\u0438\u0445 \u0440\u0435\u0433\u0438\u043E\u043D\u043E\u0432. \u041D\u0430\u0448 \u043A\u0430\u043B\u044C\u043A\u0443\u043B\u044F\u0442\u043E\u0440 \u043F\u043E\u043C\u043E\u0436\u0435\u0442 \u0432\u0430\u043C \u0440\u0430\u0441\u0441\u0447\u0438\u0442\u0430\u0442\u044C \u0432\u0440\u0435\u043C\u044F \u0432 \u043F\u0443\u0442\u0438, \u0437\u0430\u0442\u0440\u0430\u0442\u044B \u043D\u0430 \u0442\u043E\u043F\u043B\u0438\u0432\u043E \u0438 \u043E\u043F\u0442\u0438\u043C\u0438\u0437\u0438\u0440\u043E\u0432\u0430\u0442\u044C \u043C\u0430\u0440\u0448\u0440\u0443\u0442\u044B."
};
const kad = {
  title: "\u041A\u0430\u043B\u044C\u043A\u0443\u043B\u044F\u0442\u043E\u0440 \u0440\u0430\u0441\u0441\u0442\u043E\u044F\u043D\u0438\u044F \u043E\u0442 \u041A\u0410\u0414 \u0421\u0430\u043D\u043A\u0442-\u041F\u0435\u0442\u0435\u0440\u0431\u0443\u0440\u0433\u0430",
  description: "\u0420\u0430\u0441\u0441\u0447\u0438\u0442\u0430\u0439\u0442\u0435 \u0442\u043E\u0447\u043D\u043E\u0435 \u0440\u0430\u0441\u0441\u0442\u043E\u044F\u043D\u0438\u0435 \u043E\u0442 \u041A\u0410\u0414 \u0421\u0430\u043D\u043A\u0442-\u041F\u0435\u0442\u0435\u0440\u0431\u0443\u0440\u0433\u0430 \u0434\u043E \u043B\u044E\u0431\u043E\u0433\u043E \u0433\u043E\u0440\u043E\u0434\u0430 \u041B\u0435\u043D\u0438\u043D\u0433\u0440\u0430\u0434\u0441\u043A\u043E\u0439 \u043E\u0431\u043B\u0430\u0441\u0442\u0438 \u0438 \u0434\u0440\u0443\u0433\u0438\u0445 \u0440\u0435\u0433\u0438\u043E\u043D\u043E\u0432. \u041D\u0430\u0448 \u043A\u0430\u043B\u044C\u043A\u0443\u043B\u044F\u0442\u043E\u0440 \u043F\u043E\u043C\u043E\u0436\u0435\u0442 \u0432\u0430\u043C \u0440\u0430\u0441\u0441\u0447\u0438\u0442\u0430\u0442\u044C \u0432\u0440\u0435\u043C\u044F \u0432 \u043F\u0443\u0442\u0438, \u0437\u0430\u0442\u0440\u0430\u0442\u044B \u043D\u0430 \u0442\u043E\u043F\u043B\u0438\u0432\u043E \u0438 \u043E\u043F\u0442\u0438\u043C\u0438\u0437\u0438\u0440\u043E\u0432\u0430\u0442\u044C \u043C\u0430\u0440\u0448\u0440\u0443\u0442\u044B."
};
const meta = {
  mkad,
  kad
};
const _sfc_main = {
  __name: "[tab]",
  __ssrInlineRender: true,
  setup(__props) {
    const route = useRoute();
    useHead({
      title: `${meta[route.params.tab].title} | Compas.pro`,
      meta: [
        {
          name: "description",
          content: `${meta[route.params.tab].description}`
        }
      ],
      link: [
        {
          rel: "canonical",
          href: `https://compas.pro/products/distance/${route.params.tab}`
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/products/distance/[tab].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_tab_-784041ab.mjs.map
