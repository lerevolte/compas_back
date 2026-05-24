import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import { useSSRContext, withAsyncContext, watch, ref, computed, unref, toRefs, mergeProps, withCtx, createTextVNode, toDisplayString, isRef } from 'vue';
import { _ as _export_sfc, u as useRoute, s as storeToRefs, a as useHead, n as navigateTo, A as AppH2 } from './server.mjs';
import { u as useAsyncData } from './asyncData-2f1fb5f7.mjs';
import { ssrRenderComponent, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
import { A as AppAutocomplete } from './Input-3345b1b6.mjs';
import { A as ArticleItem } from './ArticleItem-812dd48a.mjs';
import { A as AppNav, a as AppPagination } from './AppNav-b6ff05a6.mjs';
import { u as useArticlesStore } from './index-e6d877f1.mjs';

const _sfc_main$3 = {
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
    const searchText = ref("");
    const emit = __emit;
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
          placeholder: "\u041F\u043E\u0438\u0441\u043A \u043F\u043E \u0441\u0442\u0430\u0442\u044C\u044F\u043C",
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
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Articles/components/Search/Search.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const Search = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["__scopeId", "data-v-f743a0c0"]]);
const _sfc_main$2 = {
  __name: "Title",
  __ssrInlineRender: true,
  props: {
    title: { type: String, required: true }
  },
  setup(__props) {
    const props = __props;
    return (_ctx, _push, _parent, _attrs) => {
      _push(ssrRenderComponent(AppH2, mergeProps({ class: "title" }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          if (_push2) {
            _push2(`${ssrInterpolate(props.title)}`);
          } else {
            return [
              createTextVNode(toDisplayString(props.title), 1)
            ];
          }
        }),
        _: 1
      }, _parent));
    };
  }
};
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Articles/components/Title/Title.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const Title = _sfc_main$2;
const _sfc_main$1 = {
  __name: "ArticlesList",
  __ssrInlineRender: true,
  props: {
    articlesList: {
      type: Array,
      required: true,
      default: []
    }
  },
  setup(__props) {
    const articlesStore = useArticlesStore();
    const { page, perPage, countPages } = storeToRefs(articlesStore);
    const showMore = () => {
      articlesStore.showMore();
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
    const props = __props;
    const { articlesList } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      _push(`<!--[--><div class="articles__list"><!--[-->`);
      ssrRenderList(unref(articlesList), ({ name, views, slug, preview_picture, created_at }) => {
        var _a;
        _push(`<!--[-->`);
        if (name) {
          _push(ssrRenderComponent(ArticleItem, {
            date: created_at,
            id: slug.value ? slug.value : slug,
            image: (_a = preview_picture[0]) == null ? void 0 : _a.file,
            title: name,
            views
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
        class: "articles__list-pagination"
      }, null, _parent));
      _push(`<!--]-->`);
    };
  }
};
const _sfc_setup$1 = _sfc_main$1.setup;
_sfc_main$1.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Articles/components/ArticlesList/ArticlesList.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const ArticlesList = _sfc_main$1;
const _sfc_main = {
  __name: "Articles",
  __ssrInlineRender: true,
  async setup(__props) {
    var _a, _b;
    let __temp, __restore;
    const route = useRoute();
    const articlesStore = useArticlesStore();
    const { categories, page, countPages, currentCategory, perPage, articlesCategories, currentTitle, articlesList, articles, currentCategoryId, options } = storeToRefs(articlesStore);
    page.value = 1;
    perPage.value = 12;
    [__temp, __restore] = withAsyncContext(async () => useAsyncData("articles", async () => await articlesStore.loadArticles())), await __temp, __restore();
    watch(
      () => [page.value, perPage.value],
      async () => {
        await articlesStore.loadArticles();
      }
    );
    watch(
      () => currentCategoryId.value,
      async () => {
        await articlesStore.loadArticles();
      }
    );
    const searchOptions = ref([]);
    const changeValueSearch = async (search) => {
      if (search.value) {
        await navigateTo(`/articles/${search.value}`);
        return;
      }
      searchOptions.value = await articlesStore.searchOptions(search);
      searchOptions.value = searchOptions.value.map((i) => {
        return { ...i, value: i.label.slug };
      });
    };
    const category = computed(() => articlesCategories.value.find((category2) => category2.slug == route.params.category));
    watch(
      () => category.value,
      () => {
        var _a2, _b2;
        if (category.value) {
          useHead({
            title: ((_a2 = category.value) == null ? void 0 : _a2.seo_title) + " | \u0421\u0442\u0430\u0442\u044C\u0438 | Compas.pro",
            meta: [
              {
                name: "description",
                content: (_b2 = category.value) == null ? void 0 : _b2.seo_description
              }
            ]
          });
          return;
        }
        useHead({
          title: "\u041F\u043E\u043B\u0435\u0437\u043D\u044B\u0435 \u0441\u0442\u0430\u0442\u044C\u0438 \u043E\u0431 \u044D\u0444\u0444\u0435\u043A\u0442\u0438\u0432\u043D\u043E\u043C \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u0438 \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u043E\u043C | Compas.pro",
          meta: [
            {
              name: "description",
              content: "\u0427\u0438\u0442\u0430\u0439\u0442\u0435 \u043D\u0430\u0448 \u0431\u043B\u043E\u0433 \u043D\u0430 Compas.pro \u2014 \u0437\u0434\u0435\u0441\u044C \u0441\u043E\u0431\u0440\u0430\u043D\u044B \u043F\u043E\u043B\u0435\u0437\u043D\u044B\u0435 \u0441\u0442\u0430\u0442\u044C\u0438 \u0438 \u0441\u043E\u0432\u0435\u0442\u044B \u0434\u043B\u044F \u044D\u0444\u0444\u0435\u043A\u0442\u0438\u0432\u043D\u043E\u0433\u043E \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u044F \u0430\u0432\u0442\u043E\u043F\u0430\u0440\u043A\u043E\u043C. \u041A\u0430\u043A \u043A\u043E\u043D\u0442\u0440\u043E\u043B\u0438\u0440\u043E\u0432\u0430\u0442\u044C \u0432\u043E\u0434\u0438\u0442\u0435\u043B\u0435\u0439 \u0438 \u0430\u0432\u0442\u043E\u043C\u043E\u0431\u0438\u043B\u0438 \u0438 \u044D\u043A\u043E\u043D\u043E\u043C\u0438\u0442\u044C \u043D\u0430 \u0443\u043F\u0440\u0430\u0432\u043B\u0435\u043D\u0438\u0438."
            }
          ]
        });
      },
      { immediate: true, deep: true }
    );
    let breadcrumbs = [
      {
        title: "\u0413\u043B\u0430\u0432\u043D\u0430\u044F \u0441\u0442\u0440\u0430\u043D\u0438\u0446\u0430",
        link: "/"
      },
      {
        title: "\u0421\u0442\u0430\u0442\u044C\u0438",
        link: "/articles"
      },
      category.value ? {
        title: (_a = category.value) == null ? void 0 : _a.title,
        link: `/questions-category/${(_b = category.value) == null ? void 0 : _b.slug}`
      } : null
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_AppBreadcrambs = AppBreadcrambs;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(_component_AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      _push(`<div class="articles" data-v-9017ba62><div class="articles__left" data-v-9017ba62>`);
      _push(ssrRenderComponent(Search, {
        class: "articles__search",
        onChangeValue: changeValueSearch,
        placeholder: "\u041F\u043E\u0438\u0441\u043A \u043F\u043E \u0441\u0442\u0430\u0442\u044C\u044F\u043C",
        options: unref(searchOptions)
      }, null, _parent));
      if (unref(articlesCategories)) {
        _push(ssrRenderComponent(AppNav, {
          title: "\u0421\u0442\u0430\u0442\u044C\u0438",
          categories: unref(articlesCategories),
          path: "articles-category"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="articles__right" data-v-9017ba62>`);
      _push(ssrRenderComponent(Title, { title: unref(currentTitle) }, null, _parent));
      _push(ssrRenderComponent(ArticlesList, { articlesList: unref(articlesList) }, null, _parent));
      _push(`</div></div><!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Articles/Articles.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const TemplateArticles = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-9017ba62"]]);

export { TemplateArticles as T };
//# sourceMappingURL=Articles-f94f3d15.mjs.map
