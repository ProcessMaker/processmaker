import { BNavbar } from "bootstrap-vue";
import Multiselect from "@processmaker/vue-multiselect/src/Multiselect";
import moment from "moment";
import Sidebaricon from "./components/Sidebaricon";
import SelectStatus from "./components/SelectStatus";
import SelectUser from "./components/SelectUser";
import SelectUserGroup from "./components/SelectUserGroup";
import CategorySelect from "./processes/categories/components/CategorySelect";
import SelectFromApi from "./components/SelectFromApi";
import TimelineItem from "./components/TimelineItem";
import Required from "./components/shared/Required";

import {
  FileUpload,
  FileDownload,
} from "./processes/screen-builder/components";
import RequiredCheckbox from "./processes/screen-builder/components/inspector/RequiredCheckbox";

/**
 * Global adjustment parameters for moment.js.
 */
import __ from "./modules/lang";

require("bootstrap");

const { Vue } = window;
if (window.ProcessMaker && window.ProcessMaker.user) {
  moment.tz.setDefault(window.ProcessMaker.user.timezone);
  moment.defaultFormat = window.ProcessMaker.user.datetime_format;
  moment.defaultFormatUtc = window.ProcessMaker.user.datetime_format;
}
if (document.documentElement.lang) {
  moment.locale(document.documentElement.lang);
  window.ProcessMaker.user.lang = document.documentElement.lang;
}

Vue.prototype.moment = moment;
window.moment = moment;

Vue.component("Multiselect", Multiselect);
Vue.component("Sidebaricon", Sidebaricon);
Vue.component("SelectStatus", SelectStatus);
Vue.component("SelectUser", SelectUser);
Vue.component("SelectUserGroup", SelectUserGroup);
Vue.component("CategorySelect", CategorySelect);
Vue.component("SelectFromApi", SelectFromApi);
Vue.component("FileUpload", FileUpload);
Vue.component("FileDownload", FileDownload);
Vue.component("RequiredCheckbox", RequiredCheckbox);
Vue.component("TimelineItem", TimelineItem);
Vue.component("Required", Required);

// Event bus ProcessMaker
window.ProcessMaker.events = new Vue();

window.ProcessMaker.nodeTypes = [];
window.ProcessMaker.nodeTypes.get = function (id) {
  return this.find((node) => node.id === id);
};

window.ProcessMaker.navbar = new Vue({
  el: "#navbar",
  data() {
    return {
      alerts: [],
    };
  },
});

window.ProcessMaker.closeSessionModal = function () {
  ProcessMaker.navbar.sessionShow = false;
};

// Set our own specific alert function at the ProcessMaker global object that could
// potentially be overwritten by some custom theme support
window.ProcessMaker.alert = function (
  msg,
  variant,
  showValue = 5,
  stayNextScreen = false,
  showLoader = false
) {
  if (showValue === 0) {
    // Just show it indefinitely, no countdown
    showValue = true;
  }
  // amount of items allowed in array
  if (ProcessMaker.navbar.alerts.length > 5) {
    ProcessMaker.navbar.alerts.shift();
  }
  ProcessMaker.navbar.alerts.push({
    alertText: msg,
    alertShow: showValue,
    alertVariant: String(variant),
    showLoader: showLoader,
    stayNextScreen,
    timestamp: Date.now(),
  });
};

// flags print forms
window.ProcessMaker.apiClient.requestCount = 0;
window.ProcessMaker.apiClient.requestCountFlag = false;

window.ProcessMaker.apiClient.interceptors.request.use((request) => {
  // flags print forms
  if (window.ProcessMaker.apiClient.requestCountFlag) {
    window.ProcessMaker.apiClient.requestCount++;
  }

  window.ProcessMaker.EventBus.$emit("api-client-loading", request);
  return request;
});

window.ProcessMaker.apiClient.interceptors.response.use(
  (response) => {
    // TODO: this could be used to show a default "created/upated/deleted resource" alert
    window.ProcessMaker.EventBus.$emit("api-client-done", response);
    // flags print forms
    if (
      window.ProcessMaker.apiClient.requestCountFlag &&
      window.ProcessMaker.apiClient.requestCount > 0
    ) {
      window.ProcessMaker.apiClient.requestCount--;
    }
    return response;
  },
  (error) => {
    window.ProcessMaker.EventBus.$emit("api-client-error", error);
    if (
      error.response &&
      error.response.status &&
      error.response.status === 401
    ) {
      // stop 401 error consuming endpoints with data-sources
      const { url } = error.config;
      if (url.includes("/data_sources/")) {
        if (url.includes("requests/") || url.includes("/test")) {
          throw error;
        }
      }
      window.location = "/login";
    } else {
      if (_.has(error, "config.url") && !error.config.url.match("/debug")) {
        window.ProcessMaker.apiClient.post("/debug", {
          name: "Javascript ProcessMaker.apiClient Error",
          message: JSON.stringify({
            message: error.message,
            code: error.code,
            config: error.config,
          }),
        });
      }
      return Promise.reject(error);
    }
  }
);

// Display any uncaught promise rejections from axios in the Process Maker alert box
window.addEventListener("unhandledrejection", (event) => {
  const error = event.reason;
  if (error.config && error.config._defaultErrorShown) {
    // Already handeled
    event.preventDefault(); // stops the unhandled rejection error
  } else if (
    error.response &&
    error.response.data &&
    error.response.data.message
  ) {
    window.ProcessMaker.alert(error.response.data.message, "danger");
  } else if (error.message) {
    window.ProcessMaker.alert(error.message, "danger");
  }
});
