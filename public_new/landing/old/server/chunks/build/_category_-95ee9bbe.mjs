import { watch, onUnmounted, watchEffect, useSSRContext } from 'vue';
import { s as storeToRefs, u as useRoute, a as useHead } from './server.mjs';
import { ssrRenderComponent } from 'vue/server-renderer';
import { T as TemplateQuestions } from './Questions-1ed262c7.mjs';
import { u as useQuestionsStore } from './index-c8ee539a.mjs';
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
import './QuestionFull-b10f870c.mjs';
import '../routes/renderer.mjs';
import 'vue-bundle-renderer/runtime';
import 'devalue';
import '@unhead/ssr';

const _sfc_main = {
  __name: "[category]",
  __ssrInlineRender: true,
  setup(__props) {
    const questionsStore = useQuestionsStore();
    const { questionsCategories } = storeToRefs(questionsStore);
    const route = useRoute();
    watch(
      () => route.params.category,
      async () => {
        await questionsStore.loadQuestions();
      },
      { deep: true, immediate: true }
    );
    onUnmounted(async () => {
      await questionsStore.loadQuestions();
    });
    watchEffect(() => {
      var _a;
      const category = (_a = questionsCategories.value) == null ? void 0 : _a.find((category2) => category2.slug == route.params.category);
      if (category) {
        useHead({
          title: (category == null ? void 0 : category.seo_title) + " | \u0412\u043E\u043F\u0440\u043E\u0441-\u043E\u0442\u0432\u0435\u0442 | Compas.pro",
          meta: [
            {
              name: "description",
              content: category == null ? void 0 : category.seo_description
            }
          ]
        });
      }
    });
    useHead({
      link: [
        {
          rel: "canonical",
          href: `https://compas.pro/questions-category/${route.params.category}`
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(TemplateQuestions, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/questions-category/[category].vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=_category_-95ee9bbe.mjs.map
