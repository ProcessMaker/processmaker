import Vue from "vue";
import Vuex from "vuex";
import ScreenBuilder from "./screen";
import globalErrorsModule from "@processmaker/screen-builder/src/store/modules/globalErrorsModule";
import undoRedoModule from "@processmaker/screen-builder/src/store/modules/undoRedoModule";

Vue.use(Vuex);
const store = new Vuex.Store({
  modules: {
    globalErrorsModule,
    undoRedoModule,
  },
});

// Bootstrap our Designer application
new Vue({
  store,
  el: "#screen-container",
  components: { ScreenBuilder },
});
