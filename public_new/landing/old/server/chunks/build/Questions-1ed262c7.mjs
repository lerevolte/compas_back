import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import { useSSRContext, watch, computed, withAsyncContext, ref, watchEffect, unref, toRefs, mergeProps, withCtx, createTextVNode, toDisplayString, isRef } from 'vue';
import { _ as _export_sfc, u as useRoute, s as storeToRefs, a as useHead, A as AppH2, k as AppButton, n as navigateTo } from './server.mjs';
import { u as useAsyncData } from './asyncData-2f1fb5f7.mjs';
import { ssrRenderComponent, ssrRenderClass, ssrRenderAttrs, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
import { A as AppAutocomplete } from './Input-3345b1b6.mjs';
import { u as useQuestionsStore, Q as QuestionItem } from './index-c8ee539a.mjs';
import { A as AppNav, a as AppPagination } from './AppNav-b6ff05a6.mjs';
import { Q as Question } from './QuestionFull-b10f870c.mjs';

const _sfc_main$4 = {
  __name: "Search",
  __ssrInlineRender: true,
  props: {
    value: {
      type: String,
      required: false,
      default: ""
    },
    address: {
      type: String,
      required: false,
      default: ""
    },
    isReadOnly: {
      type: Boolean,
      required: false,
      default: false
    },
    placeholder: {
      type: String,
      required: false,
      default: ""
    },
    isShowSubstring: {
      type: Boolean,
      required: false,
      default: true
    },
    isShowInputButton: {
      type: Boolean,
      required: false,
      default: true
    },
    options: {
      type: Array,
      required: false,
      default: []
    }
  },
  emits: ["changeValue"],
  setup(__props, { emit: __emit }) {
    const emit = __emit;
    const searchText = ref("");
    const changeValue = (data) => {
      const value = data.value;
      if (value) {
        emit("changeValue", data);
        return;
      }
      searchText.value = data.search;
      const search = data.search;
      emit("changeValue", search);
    };
    const searchOptions = (data) => {
    };
    const props = __props;
    const { options } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppAutocomplete, mergeProps({
        class: "search",
        ref: "autocompleteComponent",
        item: {
          id: 0,
          required: false,
          title: "title",
          value: unref(searchText),
          type: "address",
          placeholder: "\u041F\u043E\u0438\u0441\u043A \u043F\u043E \u0432\u043E\u043F\u0440\u043E\u0441\u0430\u043C",
          focus: false,
          key: 0,
          options: unref(searchText).length > 0 ? unref(options) : [],
          lockedOptions: []
        },
        isReadOnly: props.isReadOnly,
        isCanCreate: false,
        isLink: false,
        isShowId: false,
        "is-show-label": false,
        placeholder: props.placeholder,
        isShowSubstring: false,
        isShowNotSelected: false,
        onChangeValue: (data) => changeValue(data),
        onSearchOptions: (data) => searchOptions(),
        onClickButton: (data) => changeValue(data)
      }, _attrs), null, _parent));
    };
  }
};
const _sfc_setup$4 = _sfc_main$4.setup;
_sfc_main$4.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Questions/components/Search/Search.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const Search = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["__scopeId", "data-v-9fd72fd5"]]);
const _sfc_main$3 = {
  __name: "Title",
  __ssrInlineRender: true,
  setup(__props) {
    const questionsStore = useQuestionsStore();
    const { currentTitle } = storeToRefs(questionsStore);
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppH2, mergeProps({ class: "title" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(unref(currentTitle))}`);
          } else {
            return [
              createTextVNode(toDisplayString(unref(currentTitle)), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Questions/components/Title/Title.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const Title = _sfc_main$3;
const _sfc_main$2 = {
  __name: "AskQuestion",
  __ssrInlineRender: true,
  setup(__props) {
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<div${ssrRenderAttrs(mergeProps({ class: "ask" }, _attrs))} data-v-6c28398f>`);
      _push(ssrRenderComponent(AppH2, { class: "ask__title" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`\u041D\u0435 \u043D\u0430\u0448\u043B\u0438 \u0447\u0442\u043E \u0438\u0441\u043A\u0430\u043B\u0438?`);
          } else {
            return [
              createTextVNode("\u041D\u0435 \u043D\u0430\u0448\u043B\u0438 \u0447\u0442\u043E \u0438\u0441\u043A\u0430\u043B\u0438?")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(ssrRenderComponent(AppButton, { class: "ask__button button_blue" }, {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`\u0417\u0430\u0434\u0430\u0442\u044C \u0432\u043E\u043F\u0440\u043E\u0441`);
          } else {
            return [
              createTextVNode("\u0417\u0430\u0434\u0430\u0442\u044C \u0432\u043E\u043F\u0440\u043E\u0441")
            ];
          }
        }),
        _: 1
      }, _parent));
      _push(`</div>`);
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Questions/components/AskQuestion/AskQuestion.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const AskQuestion = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-6c28398f"]]);
const _sfc_main$1 = {
  __name: "QuestionsList",
  __ssrInlineRender: true,
  setup(__props) {
    const questionsStore = useQuestionsStore();
    const { page, perPage, countPages, questionsList } = storeToRefs(questionsStore);
    page.value = 1;
    perPage.value = 12;
    const showMore = () => {
      questionsStore.showMore();
    };
    const perPageOptions = [
      {
        label: "12",
        value: 12
      },
      {
        label: "24",
        value: 24
      },
      {
        label: "36",
        value: 36
      }
    ];
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[--><div class="questions__list"><!--[-->`);
      ssrRenderList(unref(questionsList), ({ created_at, slug, detail_picture, detail_text, name, views }) => {
        var _a, _b;
        _push(`<!--[-->`);
        if (detail_text) {
          _push(ssrRenderComponent(QuestionItem, {
            date: created_at,
            title: name,
            answer: (_a = detail_text == null ? void 0 : detail_text[0]) == null ? void 0 : _a.body,
            image: (_b = detail_picture == null ? void 0 : detail_picture[0]) == null ? void 0 : _b.file,
            views,
            id: slug == null ? void 0 : slug.value
          }, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      });
      _push(`<!--]--></div>`);
      _push(ssrRenderComponent(AppPagination, {
        totalPages: unref(countPages),
        perPageOptions,
        perPage: unref(perPage),
        modelValue: unref(page),
        "onUpdate:modelValue": ($event) => isRef(page) ? page.value = $event : null,
        "onUpdate:perPage": ($event) => isRef(perPage) ? perPage.value = $event : null,
        onShowMore: showMore,
        class: "questions__list-pagination"
      }, null, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Questions/components/QuestionsList/QuestionsList.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const QuestionsList = _sfc_main$1;
const _sfc_main = {
  __name: "Questions",
  __ssrInlineRender: true,
  async setup(__props) {
    let __temp, __restore;
    const route = useRoute();
    const questionsStore = useQuestionsStore();
    const { questionsCategories, questionsList, questionDetail, page, perPage, currentCategory } = storeToRefs(questionsStore);
    watch(
      () => [page.value, perPage.value],
      async () => {
        await questionsStore.loadQuestions();
      }
    );
    const questionId = computed(() => route.params.id);
    questionDetail.value = null;
    [__temp, __restore] = withAsyncContext(async () => useAsyncData("question", async () => questionId.value ? await questionsStore.loadQuestion(route.params.id) : 0)), await __temp, __restore();
    [__temp, __restore] = withAsyncContext(async () => useAsyncData("questions", async () => !questionsList.value.length ? await questionsStore.loadQuestions() : 0)), await __temp, __restore();
    const searchOptions = ref([]);
    const changeValueSearch = async (search) => {
      if (search.value) {
        await navigateTo(`/questions/${search.value}`);
        return;
      }
      searchOptions.value = await questionsStore.searchOptions(search);
      searchOptions.value = searchOptions.value.map((i) => {
        return { ...i, value: i.label.slug };
      });
    };
    watchEffect(() => {
      var _a, _b, _c, _d, _e, _f;
      if (questionId.value) {
        useHead({
          title: ((_c = (_b = (_a = questionDetail.value) == null ? void 0 : _a.seo_title) == null ? void 0 : _b.value) == null ? void 0 : _c.value) + " | \u0412\u043E\u043F\u0440\u043E\u0441-\u043E\u0442\u0432\u0435\u0442 | Compas.pro",
          meta: [
            {
              name: "description",
              content: (_f = (_e = (_d = questionDetail.value) == null ? void 0 : _d.seo_description) == null ? void 0 : _e.value) == null ? void 0 : _f.value
            }
          ]
        });
        return;
      }
      useHead({
        title: "\u0412\u043E\u043F\u0440\u043E\u0441\u044B \u0438 \u043E\u0442\u0432\u0435\u0442\u044B \u2014 \u0427\u0430\u0441\u0442\u043E \u0437\u0430\u0434\u0430\u0432\u0430\u0435\u043C\u044B\u0435 \u0432\u043E\u043F\u0440\u043E\u0441\u044B \u043E \u0448\u0442\u0440\u0430\u0444\u0430\u0445 \u0438 \u041F\u0414\u0414 | Compas.pro",
        meta: [
          {
            name: "description",
            content: "\u041D\u0430\u0439\u0434\u0438\u0442\u0435 \u043E\u0442\u0432\u0435\u0442\u044B \u043D\u0430 \u0441\u0430\u043C\u044B\u0435 \u0447\u0430\u0441\u0442\u044B\u0435 \u0432\u043E\u043F\u0440\u043E\u0441\u044B \u043E \u0448\u0442\u0440\u0430\u0444\u0430\u0445, \u043F\u0440\u0430\u0432\u0438\u043B\u0430\u0445 \u0434\u043E\u0440\u043E\u0436\u043D\u043E\u0433\u043E \u0434\u0432\u0438\u0436\u0435\u043D\u0438\u044F \u0438 \u043F\u0440\u0430\u0432\u0430\u0445 \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u0435\u0439 \u043D\u0430 \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0435 '\u0412\u043E\u043F\u0440\u043E\u0441\u044B \u0438 \u043E\u0442\u0432\u0435\u0442\u044B' Compas.pro. \u041C\u044B \u043F\u043E\u043C\u043E\u0436\u0435\u043C \u0440\u0430\u0437\u043E\u0431\u0440\u0430\u0442\u044C\u0441\u044F \u0432 \u0441\u043B\u043E\u0436\u043D\u044B\u0445 \u0441\u0438\u0442\u0443\u0430\u0446\u0438\u044F\u0445 \u0438 \u0438\u0437\u0431\u0435\u0436\u0430\u0442\u044C \u043E\u0448\u0438\u0431\u043E\u043A."
          }
        ]
      });
    });
    let breadcrumbs = computed(() => {
      var _a, _b;
      const category = (_a = questionsCategories.value) == null ? void 0 : _a.find((category2) => category2.slug == route.params.category);
      return [
        {
          title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0430",
          link: "/"
        },
        {
          title: "\u0412\u043E\u043F\u0440\u043E\u0441-\u043E\u0442\u0432\u0435\u0442",
          link: "/questions"
        },
        category ? {
          title: category.title,
          link: `/questions-category/${category.slug}`
        } : null,
        questionId.value ? {
          title: (_b = questionDetail.value) == null ? void 0 : _b.name.value,
          link: `/questions/${questionId.value}`
        } : null
      ];
    });
    return (_ctx, _push, _parent, _attrs) => {
      var _a, _b, _c, _d, _e, _f, _g, _h, _i, _j, _k, _l, _m, _n, _o;
      const _component_AppBreadcrambs = AppBreadcrambs;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(_component_AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      _push(`<div class="${ssrRenderClass([{ question_open: unref(questionId) }, "questions"])}" data-v-95b8a67e><div class="questions__left" data-v-95b8a67e>`);
      _push(ssrRenderComponent(Search, {
        class: "questions__search",
        onChangeValue: changeValueSearch,
        placeholder: "\u041F\u043E\u0438\u0441\u043A \u043F\u043E \u0432\u043E\u043F\u0440\u043E\u0441\u0430\u043C",
        options: unref(searchOptions)
      }, null, _parent));
      if (unref(questionsCategories)) {
        _push(ssrRenderComponent(AppNav, {
          class: "questions__nav",
          title: "\u0412\u043E\u043F\u0440\u043E\u0441-\u043E\u0442\u0432\u0435\u0442",
          categories: unref(questionsCategories),
          path: "questions-category"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(ssrRenderComponent(AskQuestion, null, null, _parent));
      _push(`</div><div class="questions__right" data-v-95b8a67e>`);
      if (!unref(questionId)) {
        _push(`<!--[-->`);
        _push(ssrRenderComponent(Title, null, null, _parent));
        if (unref(questionsList)) {
          _push(ssrRenderComponent(QuestionsList, null, null, _parent));
        } else {
          _push(`<!---->`);
        }
        _push(`<!--]-->`);
      } else if (unref(questionDetail)) {
        _push(ssrRenderComponent(Question, {
          answer: (_b = (_a = unref(questionDetail)) == null ? void 0 : _a.detail_text.value) == null ? void 0 : _b[0].body,
          image: (_e = (_d = (_c = unref(questionDetail)) == null ? void 0 : _c.detail_picture.value) == null ? void 0 : _d[0]) == null ? void 0 : _e.file,
          title: (_g = (_f = unref(questionDetail)) == null ? void 0 : _f.name) == null ? void 0 : _g.value,
          views: (_i = (_h = unref(questionDetail)) == null ? void 0 : _h.views) == null ? void 0 : _i.value,
          date: (_k = (_j = unref(questionDetail)) == null ? void 0 : _j.created_at) == null ? void 0 : _k.value,
          id: (_m = (_l = unref(questionDetail)) == null ? void 0 : _l.slug) == null ? void 0 : _m.value.value,
          readingTime: (_o = (_n = unref(questionDetail)) == null ? void 0 : _n.reading_time) == null ? void 0 : _o.value
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div></div><!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Questions/Questions.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const TemplateQuestions = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-95b8a67e"]]);

export { TemplateQuestions as T };
//# sourceMappingURL=Questions-1ed262c7.mjs.map
