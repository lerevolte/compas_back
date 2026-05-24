import { A as AppBreadcrambs } from './Breadcrambs-9c951e2d.mjs';
import { useSSRContext, withAsyncContext, watchEffect, watch, ref, computed, unref, toRefs, mergeProps, withCtx, createVNode, toDisplayString, createTextVNode, isRef } from 'vue';
import { _ as _export_sfc, u as useRoute, s as storeToRefs, a as useHead, n as navigateTo, m as IconPasswordEye, A as AppH2, e as __nuxt_component_0 } from './server.mjs';
import { u as useAsyncData } from './asyncData-2f1fb5f7.mjs';
import { ssrRenderComponent, ssrInterpolate, ssrRenderList } from 'vue/server-renderer';
import { A as AppAutocomplete } from './Input-3345b1b6.mjs';
import { A as AppNav, a as AppPagination } from './AppNav-b6ff05a6.mjs';
import { u as useArticlesStore } from './index-e6d877f1.mjs';
import { u as useKnowledgeStore } from './knowledgeStore-d6e78376.mjs';

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
          placeholder: "\u041F\u043E\u0438\u0441\u043A \u043F\u043E \u0431\u0430\u0437\u0435 \u0437\u043D\u0430\u043D\u0438\u0439",
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Knowledge/components/Search/Search.vue");
  return _sfc_setup$4 ? _sfc_setup$4(props, ctx) : void 0;
};
const Search = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["__scopeId", "data-v-1a4e5be2"]]);
const _sfc_main$3 = {
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
const _sfc_setup$3 = _sfc_main$3.setup;
_sfc_main$3.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Knowledge/components/Title/Title.vue");
  return _sfc_setup$3 ? _sfc_setup$3(props, ctx) : void 0;
};
const Title = _sfc_main$3;
const _sfc_main$2 = {
  __name: "KnowledgeItem",
  __ssrInlineRender: true,
  props: {
    title: {
      type: String,
      required: true
    },
    text: {
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
    const { title, views, image, text, id } = toRefs(props);
    return (_ctx, _push, _parent, _attrs) => {
      const _component_NuxtLink = __nuxt_component_0;
      _push(ssrRenderComponent(_component_NuxtLink, mergeProps({
        onClick: ($event) => ("navigateTo" in _ctx ? _ctx.navigateTo : unref(navigateTo))(`/knowledge/${unref(id)}`),
        class: "knowledge__item"
      }, _attrs), {
        default: withCtx((_, _push2, _parent2, _scopeId) => {
          var _a;
          if (_push2) {
            _push2(`<div class="knowledge__body" data-v-99d57237${_scopeId}><div class="knowledge__title" data-v-99d57237${_scopeId}>${ssrInterpolate(unref(title))}</div><div class="knowledge__subtitle" data-v-99d57237${_scopeId}>${(_a = unref(text)) != null ? _a : ""}</div><div class="knowledge__views views" data-v-99d57237${_scopeId}>`);
            _push2(ssrRenderComponent(IconPasswordEye, null, null, _parent2, _scopeId));
            _push2(`<span data-v-99d57237${_scopeId}>${ssrInterpolate(unref(views))}</span></div></div>`);
          } else {
            return [
              createVNode("div", { class: "knowledge__body" }, [
                createVNode("div", { class: "knowledge__title" }, toDisplayString(unref(title)), 1),
                createVNode("div", {
                  innerHTML: unref(text),
                  class: "knowledge__subtitle"
                }, null, 8, ["innerHTML"]),
                createVNode("div", { class: "knowledge__views views" }, [
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
const _sfc_setup$2 = _sfc_main$2.setup;
_sfc_main$2.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Common/KnowledgeItem/KnowledgeItem.vue");
  return _sfc_setup$2 ? _sfc_setup$2(props, ctx) : void 0;
};
const KnowledgeItem = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["__scopeId", "data-v-99d57237"]]);
const _sfc_main$1 = {
  __name: "List",
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
      ssrRenderList(unref(articlesList), ({ name, views, slug, preview_picture, created_at, detail_text }) => {
        _push(`<!--[-->`);
        if (name) {
          _push(ssrRenderComponent(KnowledgeItem, {
            date: created_at,
            id: slug.value ? slug.value : slug,
            title: name,
            views,
            text: detail_text[0].body
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
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Knowledge/components/List/List.vue");
  return _sfc_setup$1 ? _sfc_setup$1(props, ctx) : void 0;
};
const List = _sfc_main$1;
const _sfc_main = {
  __name: "Knowledge",
  __ssrInlineRender: true,
  async setup(__props) {
    var _a, _b;
    let __temp, __restore;
    const route = useRoute();
    const articlesStore = useKnowledgeStore();
    const { categories, page, countPages, currentCategory, perPage, articlesCategories, currentTitle, articlesList, articles, currentCategoryId, options } = storeToRefs(articlesStore);
    page.value = 1;
    perPage.value = 12;
    [__temp, __restore] = withAsyncContext(async () => useAsyncData("knowledges", async () => await articlesStore.loadArticles())), await __temp, __restore();
    watchEffect(async () => {
      route.params.category;
      await articlesStore.loadArticles();
    });
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
            title: ((_a2 = category.value) == null ? void 0 : _a2.seo_title) + " | \u0411\u0430\u0437\u0430 \u0437\u043D\u0430\u043D\u0438\u0439 | Compas.pro",
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
        title: "\u0411\u0430\u0437\u0430 \u0437\u043D\u0430\u043D\u0438\u0439",
        link: "/knowledge"
      },
      category.value ? {
        title: (_a = category.value) == null ? void 0 : _a.title,
        link: `/knowledge-category/${(_b = category.value) == null ? void 0 : _b.slug}`
      } : null
    ];
    return (_ctx, _push, _parent, _attrs) => {
      const _component_AppBreadcrambs = AppBreadcrambs;
      _push(`<!--[-->`);
      _push(ssrRenderComponent(_component_AppBreadcrambs, { breadcrumbs: unref(breadcrumbs) }, null, _parent));
      _push(`<div class="articles" data-v-c43187a9><div class="articles__left" data-v-c43187a9>`);
      _push(ssrRenderComponent(Search, {
        class: "articles__search",
        onChangeValue: changeValueSearch,
        placeholder: "\u041F\u043E\u0438\u0441\u043A \u043F\u043E \u0431\u0430\u0437\u0435 \u0437\u043D\u0430\u043D\u0438\u0439",
        options: unref(searchOptions)
      }, null, _parent));
      if (unref(articlesCategories)) {
        _push(ssrRenderComponent(AppNav, {
          title: "\u0411\u0430\u0437\u0430 \u0437\u043D\u0430\u043D\u0438\u0439",
          categories: unref(articlesCategories),
          path: "knowledge-category"
        }, null, _parent));
      } else {
        _push(`<!---->`);
      }
      _push(`</div><div class="articles__right" data-v-c43187a9>`);
      _push(ssrRenderComponent(Title, { title: unref(currentTitle) }, null, _parent));
      _push(ssrRenderComponent(List, { articlesList: unref(articlesList) }, null, _parent));
      _push(`</div></div><!--]-->`);
    };
  }
};
const _sfc_setup = _sfc_main.setup;
_sfc_main.setup = (props, ctx) => {
  const ssrContext = useSSRContext();
  (ssrContext.modules || (ssrContext.modules = /* @__PURE__ */ new Set())).add("components/Templates/Knowledge/Knowledge.vue");
  return _sfc_setup ? _sfc_setup(props, ctx) : void 0;
};
const TemplateKnowledge = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-c43187a9"]]);

export { TemplateKnowledge as T };
//# sourceMappingURL=Knowledge-94826b04.mjs.map
