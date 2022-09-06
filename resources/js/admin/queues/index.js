import Vue from "vue";
import _ from "lodash";
import axios from "axios";
import moment from "moment";
import App from "Horizon/components/App";
import router from "./router";

window.$ = window.jQuery = require("jquery");
window.Popper = require("popper.js").default;

require("bootstrap");

$("body").tooltip({
  selector: "[data-toggle=tooltip]",
});

Vue.prototype.$http = axios.create({
  baseUrl: "",
});

// Add a request interceptor
Vue.prototype.$http.interceptors.request.use((config) => {
  config.baseURL = "";
  config.url = config.url.replace("/horizon/api", "/admin/queues/api");
  // Do something before request is sent
  return config;
}, (error) =>
// Do something with request error
  Promise.reject(error));

// HACK until horizon is fixed. Makes full page request.
Vue.prototype.$http.interceptors.response.use((response, req) => {
  if (response.config.url.match("/admin/queues/api/jobs/recent")) {
    response.data.jobs.forEach((job) => {
      job.id = `../../admin/queues/failed/${job.id}`;
    });
  }
  return response;
});

window.Bus = new Vue({ name: "Bus" });

Vue.component("Loader", require("Horizon/components/Status/Loader.vue"));

Vue.config.errorHandler = function (err, vm, info) {
  console.error(err);
};

Vue.mixin({
  methods: {
    /**
         * Format the given date with respect to timezone.
         */
    formatDate(unixTime) {
      return moment(unixTime * 1000).add(new Date().getTimezoneOffset() / 60);
    },

    /**
         * Extract the job base name.
         */
    jobBaseName(name) {
      if (!name.includes("\\")) return name;

      const parts = name.split("\\");

      return parts[parts.length - 1];
    },

    /**
         * Convert to human readable timestamp.
         */
    readableTimestamp(timestamp) {
      return this.formatDate(timestamp).format("YY-MM-DD HH:mm:ss");
    },

    /**
         * Convert to human readable timestamp.
         */
    displayableTagsList(tags) {
      if (!tags || !tags.length) return "";

      return _.reduce(tags, (s, n) => (s ? `${s}, ` : "") + _.truncate(n), "");
    },
  },
});

new Vue({
  el: "#root",

  router,

  /**
     * The component's data.
     */
  data() {
    return {};
  },

  render: (h) => h(App),
});
