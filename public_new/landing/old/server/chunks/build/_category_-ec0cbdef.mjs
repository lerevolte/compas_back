import { watch, onUnmounted, useSSRContext } from 'vue';
import { u as useRoute, a as useHead } from './server.mjs';
import { ssrRenderComponent } from 'vue/server-renderer';
import { T as TemplateArticles } from './Articles-f94f3d15.mjs';
import { u as useArticlesStore } from './index-e6d877f1.mjs';
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
import './ArticleItem-812dd48a.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'devalue';
import '@unhead/ssr';
import './dayjs-ce9ed7b6.mjs';
import './AppNav-b6ff05a6.mjs';

const _sfc_main = {
  __name: "[category]",
  __ssrInlineRender: true,
  setup(__props) {
    const articlesStore = useArticlesStore();
    const route = useRoute();
    watch(
      () => route.params.category,
      async () => {
        await articlesStore.loadArticles();
      },
      { deep: true, immediate: true }
    );
    onUnmounted(async () => {
      await articlesStore.loadArticles();
    });
    useHead({
      // title: "Блог Compas.pro: Полезные статьи о штрафах, ПДД и правах водителей | Compas.pro",
      // meta: [
      // 	{
      // 		name: "description",
      // 		content:
      // 			"Читайте наш блог на Compas.pro — здесь собраны полезные статьи и советы для водителей о штрафах, правилах дорожного движения и защите своих прав. Узнайте, как проверить штрафы ГИБДД, избежать и оспорить их.",
      // 	},
      // ],
      link: [
        {
          rel: "canonical",
          href: `https://compas.pro/articles-category/${route.params.category}`
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(TemplateArticles, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/articles-category/[category].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_category_-ec0cbdef.mjs.map
