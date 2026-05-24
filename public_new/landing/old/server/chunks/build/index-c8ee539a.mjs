import { p as publicAssetsURL } from '../routes/renderer.mjs';
import { u as useRoute, d as defineStore, b as api, m as IconPasswordEye, e as __nuxt_component_0, _ as _export_sfc } from './server.mjs';
import { useSSRContext, toRefs, mergeProps, unref, withCtx, createVNode, toDisplayString } from 'vue';
import { ssrRenderComponent, ssrRenderAttr, ssrInterpolate } from 'vue/server-renderer';

const defaultImgQuestion = "" + publicAssetsURL("main/questions/question.jpg");
const _sfc_main = {
  __name: "QuestionItem",
  __ssrInlineRender: true,
  props: {
    title: {
      type: String,
      required: true
    },
    answer: {
      type: String
    },
    image: {
      type: String
    },
    views: {
      type: Number,
      default: 0
    },
    date: {
      type: String
    },
    id: {
      type: String
    }
  },
  setup(__props) {
    const props = __props;
    const { title, views, image, answer, id } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(ssrRenderComponent(_component_NuxtLink, mergeProps({
        to: `/questions/${unref(id)}`,
        class: "question__item"
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a;
          if (_push2) {
            _push2(`<div class="question__top" data-v-97bf2102${_scopeId}><figure class="ibg question__icon" data-v-97bf2102${_scopeId}><img${ssrRenderAttr("src", unref(image) ? unref(image) : unref(defaultImgQuestion))}${ssrRenderAttr("alt", unref(title))} data-v-97bf2102${_scopeId}></figure></div><div class="question__body" data-v-97bf2102${_scopeId}><div class="question__title" data-v-97bf2102${_scopeId}>${ssrInterpolate(unref(title))}</div><div class="question__subtitle" data-v-97bf2102${_scopeId}>${(_a = unref(answer)) != null ? _a : ""}</div><div class="question__views views" data-v-97bf2102${_scopeId}>`);
            _push2(ssrRenderComponent(IconPasswordEye, null, null, _parent2, _scopeId));
            _push2(`<span data-v-97bf2102${_scopeId}>${ssrInterpolate(unref(views))}</span></div></div>`);
          } else {
            return [
              createVNode("div", { class: "question__top" }, [
                createVNode("figure", { class: "ibg question__icon" }, [
                  createVNode("img", {
                    src: unref(image) ? unref(image) : unref(defaultImgQuestion),
                    alt: unref(title)
                  }, null, 8, ["src", "alt"])
                ])
              ]),
              createVNode("div", { class: "question__body" }, [
                createVNode("div", { class: "question__title" }, toDisplayString(unref(title)), 1),
                createVNode("div", {
                  innerHTML: unref(answer),
                  class: "question__subtitle"
                }, null, 8, ["innerHTML"]),
                createVNode("div", { class: "question__views views" }, [
                  createVNode(IconPasswordEye),
                  createVNode("span", null, toDisplayString(unref(views)), 1)
                ])
              ])
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/QuestionItem/QuestionItem.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const QuestionItem = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-97bf2102"]]);
const route = useRoute();
const useQuestionsStore = defineStore("questionsStore", {
  state: () => ({
    questions: null,
    questionDetail: null,
    categories: null,
    page: 1,
    perPage: 12,
    canUpdate: true
  }),
  getters: {
    currentTitle() {
      if (!this.categories)
        return null;
      const activeChild = this.activeChild;
      if (activeChild) {
        return activeChild.mainTitle;
      }
      const category = this.categories.find((category2) => route.fullPath.includes(category2.value) ? category2 : null);
      return category ? category.mainTitle : this.categories[0].mainTitle;
    },
    activeChild: (state) => {
      var _a;
      if (!state.categories)
        return null;
      for (const category of state.categories) {
        const child = (_a = category.children) == null ? void 0 : _a.find((child2) => route.fullPath.includes(child2.value));
        if (child) {
          return child;
        }
      }
      return null;
    },
    questionsList() {
      var _a, _b;
      return ((_b = (_a = this.questions) == null ? void 0 : _a.list) == null ? void 0 : _b.data) || [];
    },
    questionsCategories() {
      var _a;
      return (_a = this.categories) == null ? void 0 : _a.map((category) => {
        var _a2;
        return {
          ...category,
          value: category.slug,
          title: category.name,
          isOpen: false,
          children: (_a2 = category.children) == null ? void 0 : _a2.map((child) => ({
            value: child.slug,
            title: child.name
          }))
        };
      });
    },
    currentCategory() {
      return (slug) => {
        var _a;
        return (_a = this.categories) == null ? void 0 : _a.find((category) => category.value === slug);
      };
    },
    currentCategoryId() {
      var _a, _b;
      return (_b = (_a = this.categories) == null ? void 0 : _a.find((category) => category.slug == route.params.id)) == null ? void 0 : _b.id;
    },
    countPages() {
      var _a;
      return (_a = this.questions) == null ? void 0 : _a.list.last_page;
    }
  },
  actions: {
    async loadQuestions() {
      var _a, _b;
      if (this.canUpdate) {
        const { categories } = await api.callMethod("GET", `faq`, {});
        this.categories = categories;
        const categoryId = (_b = (_a = this.categories) == null ? void 0 : _a.find((category) => category.slug == route.params.category)) == null ? void 0 : _b.id;
        this.questions = await api.callMethod("GET", `faq?page=${this.page}&per_page=${this.perPage}&q=${categoryId ? `&filter[category_id]=${categoryId}` : ""}`, {});
        if (this.page > this.countPages) {
          this.page = 1;
        }
      }
    },
    async loadQuestion(slug) {
      this.questionDetail = await api.callMethod("GET", `faq/${slug}`, {});
    },
    async searchOptions(search) {
      const res = await api.callMethod("GET", `faq/search?q=${search}&entity=faq`, {});
      if ((res == null ? void 0 : res.length) > 0) {
        return res;
      }
      return [{ label: { text: "\u041D\u0435 \u043D\u0430\u0439\u0434\u0435\u043D\u043E" } }];
    },
    async showMore() {
      var _a, _b, _c, _d;
      this.canUpdate = false;
      if (this.page + 1 > this.countPages) {
        this.canUpdate = true;
        return;
      }
      this.page++;
      const categoryId = (_b = (_a = this.categories) == null ? void 0 : _a.find((category) => category.slug == route.params.id)) == null ? void 0 : _b.id;
      const newQuestions = await api.callMethod("GET", `faq?page=${this.page}&per_page=${this.perPage}&q=${categoryId ? `&filter[category_id]=${categoryId}` : ""}`);
      if (((_d = (_c = newQuestions == null ? void 0 : newQuestions.list) == null ? void 0 : _c.data) == null ? void 0 : _d.length) > 0) {
        this.questions.list.data = [...this.questions.list.data, ...newQuestions.list.data];
      }
      this.canUpdate = true;
    }
  }
});

export { QuestionItem as Q, useQuestionsStore as u };
//# sourceMappingURL=index-c8ee539a.mjs.map
