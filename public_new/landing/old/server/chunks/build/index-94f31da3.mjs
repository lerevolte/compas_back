import { a as useHead } from './server.mjs';
import { ssrRenderComponent } from 'vue/server-renderer';
import { T as TemplateKnowledge } from './Knowledge-94826b04.mjs';
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
import './Breadcrambs-9c951e2d.mjs';
import './asyncData-2f1fb5f7.mjs';
import './Input-3345b1b6.mjs';
import './AppNav-b6ff05a6.mjs';
import './index-e6d877f1.mjs';
import './knowledgeStore-d6e78376.mjs';

const _sfc_main = {
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      // title: "Блог Compas.pro: Полезные статьи о штрафах, ПДД и правах водителей | Compas.pro",
      // meta: [
      // 	{
      // 		name: "description",
      // 		content: "Читайте наш блог на Compas.pro — здесь собраны полезные статьи и советы для водителей о штрафах, правилах дорожного движения и защите своих прав. Узнайте, как проверить штрафы ГИБДД, избежать и оспорить их.",
      // 	},
      // ],
      link: [
        {
          rel: "canonical",
          href: `https://compas.pro/knowledge`
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(TemplateKnowledge, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/knowledge/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-94f31da3.mjs.map
