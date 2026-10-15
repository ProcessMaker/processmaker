import Vue from "vue";
import Vuex from "vuex";
import { Multiselect } from "@processmaker/vue-multiselect";
import ScreenBuilder from "./screen.vue";

Vue.component("Multiselect", Multiselect);

Vue.use(Vuex);
const store = new Vuex.Store({});

// Mount after 'load' so package addon scripts (from $manager->getScripts())
// are guaranteed to have executed before Vue renders the screen builder.
window.addEventListener("load", () => {
  new Vue({
    store,
    el: "#screen-container",
    components: { ScreenBuilder },
    mounted() {
      window.ProcessMaker.ScreenBuilder = this.$refs.screenBuilder;
    },
  });
});
