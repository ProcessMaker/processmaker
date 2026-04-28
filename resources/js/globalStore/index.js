import { createStore } from "vuex";

export default {
  install(Vue) {
    const globalStore = createStore({
      modules: {},
    });

    Vue.globalStore = globalStore;

    if (Vue.config?.globalProperties) {
      Vue.config.globalProperties.$globalStore = globalStore;
    }

    Vue.mixin({
      beforeCreate() {
        const options = this.$options;

        if (options.globalStore) {
          this.$globalStore = typeof options.globalStore === "function"
            ? options.globalStore()
            : options.globalStore;

          return;
        }

        if (options?.parent?.$globalStore) {
          this.$globalStore = options.parent.$globalStore;

          return;
        }

        this.$globalStore = globalStore;
      },
    });
  },
};
