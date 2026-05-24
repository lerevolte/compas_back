import { a as useHead } from './server.mjs';
import { ssrRenderComponent } from 'vue/server-renderer';
import { T as TemplateMain } from './Main-eea9d5da.mjs';
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
import './Programm-1c3794ff.mjs';
import './program-f89190ac.mjs';
import './youtube_blue-a00a4300.mjs';
import './Validate-398d291a.mjs';
import 'lodash';
import '@vuepic/vue-datepicker';
import 'vue-accessible-color-picker';
import './Input-3345b1b6.mjs';
import './ButtonText-edbdf3ac.mjs';
import './finesStore-67f46f86.mjs';
import './preview-inn-d36097f7.mjs';
import 'swiper/vue';
import './Slider-a943f5b9.mjs';
import 'swiper';
import './ArticleItem-812dd48a.mjs';
import './dayjs-ce9ed7b6.mjs';
import './index-e6d877f1.mjs';
import './WrapText-f5da5fca.mjs';
import './Companies-75bbb9ed.mjs';
import './index-c8ee539a.mjs';
import './Social-983a064f.mjs';

const _sfc_main = {
  __name: "index",
  __ssrInlineRender: true,
  setup(__props) {
    useHead({
      title: "\u041F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043E\u043D\u043B\u0430\u0439\u043D | Compas.pro",
      meta: [
        {
          name: "description",
          content: "\u0411\u044B\u0441\u0442\u0440\u0430\u044F \u043F\u0440\u043E\u0432\u0435\u0440\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u0430 \u0448\u0442\u0440\u0430\u0444\u043E\u0432 \u0413\u0418\u0411\u0414\u0414 \u043E\u043D\u043B\u0430\u0439\u043D. \u0423\u0434\u043E\u0431\u043D\u044B\u0439 \u0441\u0435\u0440\u0432\u0438\u0441 \u0434\u043B\u044F \u043F\u043E\u0438\u0441\u043A\u0430 \u0438 \u043E\u043F\u043B\u0430\u0442\u044B \u0448\u0442\u0440\u0430\u0444\u043E\u0432. \u042D\u043A\u043E\u043D\u043E\u043C\u044C\u0442\u0435 \u0432\u0440\u0435\u043C\u044F \u0438 \u0438\u0437\u0431\u0435\u0433\u0430\u0439\u0442\u0435 \u043F\u0440\u043E\u0431\u043B\u0435\u043C \u0441 \u043D\u0430\u0448\u0438\u043C\u0438 \u0431\u0435\u0437\u043E\u043F\u0430\u0441\u043D\u044B\u043C\u0438 \u0438 \u043D\u0430\u0434\u0435\u0436\u043D\u044B\u043C\u0438 \u043E\u043D\u043B\u0430\u0439\u043D-\u043F\u043B\u0430\u0442\u0435\u0436\u0430\u043C\u0438."
        }
      ],
      link: [
        {
          rel: "canonical",
          href: "https://compas.pro/products/fines"
        }
      ]
    });
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(TemplateMain, _attrs, null, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("pages/products/fines/index.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};

export { _sfc_main as default };
//# sourceMappingURL=index-26ec33e9.mjs.map
