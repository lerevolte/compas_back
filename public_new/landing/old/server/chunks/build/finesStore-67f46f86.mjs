import { d as defineStore, v as persistedState } from './server.mjs';

const useFinesStore = defineStore("finesStore", {
  state: () => {
    return {
      fields: null,
      fines: []
    };
  },
  persist: {
    storage: persistedState.localStorage
  },
  actions: {}
});

export { useFinesStore as u };
//# sourceMappingURL=finesStore-67f46f86.mjs.map
