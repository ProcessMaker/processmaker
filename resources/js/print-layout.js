import { BNavbar } from "bootstrap-vue";
import { Multiselect } from "@processmaker/vue-multiselect";
import moment from "moment-timezone";
import { sanitizeUrl } from "@braintree/sanitize-url";
import VueHtml2Canvas from "vue-html2canvas";
import Sidebaricon from "./components/Sidebaricon";
import SelectStatus from "./components/SelectStatus";
import SelectUser from "./components/SelectUser";
import SelectUserGroup from "./components/SelectUserGroup";
import CategorySelect from "./processes/categories/components/CategorySelect";
import ProjectSelect from "./components/shared/ProjectSelect";
import SelectFromApi from "./components/SelectFromApi";
import Breadcrumbs from "./components/Breadcrumbs";
import TimelineItem from "./components/TimelineItem";
import Required from "./components/shared/Required";
import { FileUpload, FileDownload } from "./processes/screen-builder/components";
import RequiredCheckbox from "./processes/screen-builder/components/inspector/RequiredCheckbox";
import WelcomeModal from "./Mobile/WelcomeModal";
/** ****
 * Global adjustment parameters for moment.js.
 */
import __ from "./modules/lang";

require("bootstrap");

const { Vue } = window;

Vue.use(VueHtml2Canvas);

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
// initializing global instance of a moment object
window.moment = moment;
/** ***** */

Vue.prototype.$sanitize = sanitizeUrl;

Vue.component("Multiselect", Multiselect);
Vue.component("Sidebaricon", Sidebaricon);
Vue.component("SelectStatus", SelectStatus);
Vue.component("SelectUser", SelectUser);
Vue.component("SelectUserGroup", SelectUserGroup);
Vue.component("CategorySelect", CategorySelect);
Vue.component("ProjectSelect", ProjectSelect);
Vue.component("SelectFromApi", SelectFromApi);
Vue.component("FileUpload", FileUpload);
Vue.component("FileDownload", FileDownload);
Vue.component("RequiredCheckbox", RequiredCheckbox);
Vue.component("Breadcrumbs", Breadcrumbs);
Vue.component("TimelineItem", TimelineItem);
Vue.component("Required", Required);
Vue.component("Welcome", WelcomeModal);

// Event bus ProcessMaker
window.ProcessMaker.events = new Vue();

window.ProcessMaker.nodeTypes = [];
window.ProcessMaker.nodeTypes.get = function (id) {
  return this.find((node) => node.id === id);
};

// Assign our navbar component to our global ProcessMaker object
window.ProcessMaker.navbar = {};

// Set our own specific alert function at the ProcessMaker global object that could
// potentially be overwritten by some custom theme support
window.ProcessMaker.navbar.alerts = [];
window.ProcessMaker.alert = function (msg, variant, showValue = 5, stayNextScreen = false, showLoader = false, msgLink = "", msgTitle = "") {
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
    alertLink: msgLink,
    alertTitle: msgTitle,
    alertShow: showValue,
    alertVariant: String(variant),
    showLoader,
    stayNextScreen,
    timestamp: Date.now(),
  });
};

// Set out own specific confirm modal.
window.ProcessMaker.confirmModal = function (title, message, variant, callback, size = "md", dataTestClose = "confirm-btn-close", dataTestOk = "confirm-btn-ok") {
  ProcessMaker.navbar.confirmTitle = title || __("Confirm");
  ProcessMaker.navbar.confirmMessage = message || __("Are you sure you want to delete?");
  ProcessMaker.navbar.confirmVariant = variant;
  ProcessMaker.navbar.confirmCallback = callback;
  ProcessMaker.navbar.confirmShow = true;
  ProcessMaker.navbar.confirmSize = size;
  ProcessMaker.navbar.confirmDataTestClose = dataTestClose;
  ProcessMaker.navbar.confirmDataTestOk = dataTestOk;
};

// Set out own specific message modal.
window.ProcessMaker.messageModal = function (title, message, variant, callback) {
  ProcessMaker.navbar.messageTitle = title || __("Message");
  ProcessMaker.navbar.messageMessage = message || __("");
  ProcessMaker.navbar.messageVariant = variant;
  ProcessMaker.navbar.messageCallback = callback;
  ProcessMaker.navbar.messageShow = true;
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

window.ProcessMaker.apiClient.interceptors.response.use((response) => {
  // TODO: this could be used to show a default "created/upated/deleted resource" alert
  // response.config.method (PUT, POST, DELETE)
  // response.config.url (extract resource name)
  window.ProcessMaker.EventBus.$emit("api-client-done", response);
  // flags print forms
  if (window.ProcessMaker.apiClient.requestCountFlag && window.ProcessMaker.apiClient.requestCount > 0) {
    window.ProcessMaker.apiClient.requestCount--;
  }
  return response;
}, (error) => {
  // Set in your .catch to false to not show the alert inside window.ProcessMaker.apiClient
  if (!error?.response?.showAlert) {
    return Promise.reject(error);
  }

  if (error.code && error.code === "ERR_CANCELED") {
    return Promise.reject(error);
  }
  window.ProcessMaker.EventBus.$emit("api-client-error", error);
  if (error.response && error.response.status && error.response.status === 401) {
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
});

// Display any uncaught promise rejections from axios in the Process Maker alert box
window.addEventListener("unhandledrejection", (event) => {
  const error = event.reason;
  if (error.config && error.config._defaultErrorShown) {
    // Already handeled
    event.preventDefault(); // stops the unhandled rejection error
  } else if (error.response && error.response.data && error.response.data.message) {
    if (!(error.code && error.code === "ECONNABORTED")) {
      window.ProcessMaker.alert(error.response.data.message, "danger");
    }
  } else if (error.message) {
    if (!(error.code && error.code === "ECONNABORTED")) {
      window.ProcessMaker.alert(error.message, "danger");
    }
  }
});
