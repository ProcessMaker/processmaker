import Vuex from "vuex";

export default {
  install(Vue) {
    const globalStore = new Vuex.Store({
      modules: { },
    });

    Vue.globalStore = globalStore;

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
