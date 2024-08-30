
import * as bootstrap from "bootstrap";
import Vue from "vue";
import * as vue from "vue";
import VueCookies from "vue-cookies";
import GlobalStore from "./globalStore";

window.$ = window.jQuery = require("jquery");

window.Vue = Vue;
window.vue = vue;
window.bootstrap = bootstrap;
window.Vue.use(GlobalStore);
window.Vue.use(VueCookies);
window.ProcessMaker = {
  packages: [],
};

// click an active tab after all components have mounted
Vue.use({
  install(vue) {
    vue.mixin({
      mounted() {
        if (this.$parent) {
          // only run on root
          return;
        }
      },
    });
  },
});

// Send an event when the global Vue and ProcessMaker instance is available
window.dispatchEvent(new Event("app-bootstrapped"));
