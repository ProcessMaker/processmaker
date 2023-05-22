import Vue from "vue";
import Vuex from "vuex";
import ScreenBuilder from "./screen";

Vue.use(Vuex);
const store = new Vuex.Store({});

// Bootstrap our Designer application
new Vue({
  store,
  el: "#screen-container",
  components: { ScreenBuilder },
});
