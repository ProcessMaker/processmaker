import Vue from "vue";
import BootstrapVue from "bootstrap-vue";
import "bootstrap/dist/css/bootstrap.css";
import "bootstrap-vue/dist/bootstrap-vue.css";
import router from "./router";

// Install BootstrapVue
Vue.use(BootstrapVue);

// Initialize the Vue app when the DOM is ready
document.addEventListener("DOMContentLoaded", () => {
  if (document.getElementById("tenant-queues-dashboard")) {
    // eslint-disable-next-line no-new
    new Vue({
      el: "#tenant-queues-dashboard",
      router,
    });
  }
});
