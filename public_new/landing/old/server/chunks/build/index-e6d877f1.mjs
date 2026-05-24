import { u as useRoute, d as defineStore, b as api } from './server.mjs';

const route = useRoute();
const useArticlesStore = defineStore("articlesStore", {
  state: () => ({
    articles: null,
    articleDetail: null,
    categories: null,
    page: 1,
    perPage: 12,
    canUpdate: true
  }),
  getters: {
    currentTitle() {
      if (!(this == null ? void 0 : this.categories))
        return null;
      const activeChild = this.activeChild;
      if (activeChild) {
        return activeChild.mainTitle;
      }
      const category = this.categories.find((category2) => route.fullPath.includes(category2.slug) ? category2 : null);
      return category ? category.name : this.categories[0].name;
    },
    activeChild: (state) => {
      var _a;
      if (!(state == null ? void 0 : state.categories))
        return null;
      for (const category of state.categories) {
        const child = (_a = category.children) == null ? void 0 : _a.find((child2) => route.fullPath.includes(child2.value));
        if (child) {
          return child;
        }
      }
      return null;
    },
    articlesList() {
      var _a, _b;
      return ((_b = (_a = this.articles) == null ? void 0 : _a.list) == null ? void 0 : _b.data) || [];
    },
    articlesCategories() {
      var _a;
      return (_a = this.categories) == null ? void 0 : _a.map((category) => {
        var _a2;
        return {
          ...category,
          id: category.id,
          value: category.slug,
          title: category.name,
          isOpen: false,
          children: (_a2 = category.children) == null ? void 0 : _a2.map((child) => ({
            id: child.id,
            value: child.slug,
            title: child.name
          }))
        };
      });
    },
    currentCategory() {
      var _a;
      return (_a = this.categories) == null ? void 0 : _a.find((category) => category.value === route.params.category);
    },
    currentCategoryId() {
      var _a, _b;
      return (_b = (_a = this.categories) == null ? void 0 : _a.find((category) => category.slug == route.params.id)) == null ? void 0 : _b.id;
    },
    countPages() {
      var _a, _b;
      return (_b = (_a = this.articles) == null ? void 0 : _a.list) == null ? void 0 : _b.last_page;
    }
  },
  actions: {
    async loadArticles() {
      var _a, _b;
      if (this.canUpdate) {
        const { categories } = await api.callMethod("GET", `blog`, {});
        this.categories = categories;
        const categoryId = (_b = (_a = this.categories) == null ? void 0 : _a.find((category) => category.slug == route.params.id)) == null ? void 0 : _b.id;
        this.articles = await api.callMethod("GET", `blog?page=${this.page}&per_page=${this.perPage}&q=${categoryId ? `&filter[category_id]=${categoryId}` : ""}`, {});
        if (this.page > this.countPages) {
          this.page = 1;
        }
      }
    },
    async loadArticle(slug) {
      this.articleDetail = await api.callMethod("GET", `blog/${slug}`, {});
    },
    async searchOptions(search) {
      const res = await api.callMethod("GET", `blog/search?q=${search}&entity=articles`, {});
      if ((res == null ? void 0 : res.length) > 0) {
        return res;
      }
      return [{ label: { text: "\u041D\u0435 \u043D\u0430\u0439\u0434\u0435\u043D\u043E" } }];
    },
    async showMore() {
      var _a, _b, _c, _d;
      this.canUpdate = false;
      console.log(this.page + 1 > this.countPages);
      if (this.page + 1 > this.countPages) {
        this.canUpdate = true;
        return;
      }
      this.page++;
      const categoryId = (_b = (_a = this.categories) == null ? void 0 : _a.find((category) => category.slug == route.params.id)) == null ? void 0 : _b.id;
      const newArticles = await api.callMethod("GET", `blog?page=${this.page}&per_page=${this.perPage}&q=${categoryId ? `&filter[category_id]=${categoryId}` : ""}`);
      if (((_d = (_c = newArticles == null ? void 0 : newArticles.list) == null ? void 0 : _c.data) == null ? void 0 : _d.length) > 0) {
        this.articles.list.data = [...this.articles.list.data, ...newArticles.list.data];
      }
      console.log("showMore end");
      this.canUpdate = true;
    }
  }
});

export { useArticlesStore as u };
//# sourceMappingURL=index-e6d877f1.mjs.map
