import { BNavbar } from "bootstrap-vue";
import { Multiselect } from "@processmaker/vue-multiselect";
import moment from "moment-timezone";
import { sanitizeUrl } from "@braintree/sanitize-url";
import VueHtml2Canvas from "vue-html2canvas";
import newRequestModal from "./components/requests/requestModal";
import requestModal from "./components/requests/modal";
import requestModalMobile from "./components/requests/modalMobile";
import notifications from "./notifications/components/notifications";
import sessionModal from "./components/Session";
import Sidebaricon from "./components/Sidebaricon";
import ConfirmationModal from "./components/Confirm";
import MessageModal from "./components/Message";
import NavbarProfile from "./components/NavbarProfile";
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
import Menu from "./components/Menu.vue";
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
Vue.component("LanguageSelectorButton", (resolve) => {
  if (window.ProcessMaker.languageSelectorButtonComponent) {
    resolve(window.ProcessMaker.languageSelectorButtonComponent);
  } else {
    window.ProcessMaker.languageSelectorButtonComponentResolve = resolve;
  }
});

// Event bus ProcessMaker
window.ProcessMaker.events = new Vue();

// Verify if is mobile
const browser = navigator.userAgent;
const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(browser);
window.ProcessMaker.mobileApp = false;
if (isMobileDevice) {
  window.ProcessMaker.mobileApp = true;
}

// Verify is in mobile mode
const isMobileNavbar = window.ProcessMaker.events.$cookies.get("isMobile");

window.ProcessMaker.nodeTypes = [];
window.ProcessMaker.nodeTypes.get = function (id) {
  return this.find((node) => node.id === id);
};

// Assign our navbar component to our global ProcessMaker object
window.ProcessMaker.navbar = new Vue({
  el: "#navbar",
  components: {
    TopMenu: Menu,
    "b-navbar": BNavbar,
    requestModal,
    notifications,
    sessionModal,
    ConfirmationModal,
    MessageModal,
    NavbarProfile,
    newRequestModal,
    GlobalSearch: (resolve) => {
      if (window.ProcessMaker.globalSearchComponent) {
        resolve(window.ProcessMaker.globalSearchComponent);
      } else {
        window.ProcessMaker.globalSearchComponentResolve = resolve;
      }
    },
  },
  data() {
    return {
      screenBuilder: null,
      messages: ProcessMaker.notifications,
      alerts: this.loadLocalAlerts(),
      confirmTitle: "",
      confirmMessage: "",
      confirmVariant: "",
      confirmCallback: "",
      confirmSize: "md",
      confirmDataTestClose: "confirm-btn-close",
      confirmDataTestOk: "confirm-btn-ok",
      messageTitle: "",
      messageMessage: "",
      messageVariant: "",
      messageCallback: "",
      confirmShow: false,
      sessionShow: false,
      messageShow: false,
      sessionTitle: "",
      sessionMessage: "",
      sessionTime: "",
      sessionWarnSeconds: "",
      taskTitle: "",
      isMobile: false,
      isMobileDevice: window.ProcessMaker.mobileApp,
    };
  },
  watch: {
    alerts(array) {
      this.saveLocalAlerts(array);
    },
  },
  mounted() {
    Vue.nextTick() // This is needed to override the default alert method.
      .then(() => {
        this.onResize();
        window.addEventListener("resize", this.onResize, { passive: true });

        if (document.querySelector("meta[name='alert']")) {
          ProcessMaker.alert(
            document.querySelector("meta[name='alertMessage']").getAttribute("content"),
            document.querySelector("meta[name='alertVariant']").getAttribute("content"),
          );
        }
        const findSB = setInterval(() => {
          this.screenBuilder = window.ProcessMaker.ScreenBuilder;
          if (this.screenBuilder) {
            clearInterval(findSB);
          }
        }, 80);
      });
  },
  methods: {
    alertDownChanged(dismissCountDown, item) {
      item.alertShow = dismissCountDown;
      this.saveLocalAlerts(this.alerts);
    },
    alertDismissed(alert) {
      alert.alertShow = 0;
      const index = this.alerts.indexOf(alert);
      let copy = _.cloneDeep(this.alerts);
      index > -1 ? copy.splice(index, 1) : null;
      // remove old alerts
      copy = copy.filter((item) => ((Date.now() - item.timestamp) / 1000) < item.alertShow);
      this.saveLocalAlerts(copy);
    },
    loadLocalAlerts() {
      try {
        return window.localStorage.processmakerAlerts
                    && window.localStorage.processmakerAlerts.substr(0, 1) === "["
          ? JSON.parse(window.localStorage.processmakerAlerts) : [];
      } catch (e) {
        return [];
      }
    },
    saveLocalAlerts(array) {
      const nextScreenAlerts = array.filter((alert) => alert.stayNextScreen);
      window.localStorage.processmakerAlerts = JSON.stringify(nextScreenAlerts);
    },
    switchToMobile() {
      this.$cookies.set("isMobile", true);
      window.open("/requests", "_self");
    },
    getRoutes() {
      if (this.$refs.breadcrumbs) {
        return this.$refs.breadcrumbs.list;
      }
      return [];
    },
    setRoutes(routes) {
      if (this.$refs.breadcrumbs) {
        return this.$refs.breadcrumbs.updateRoutes(routes);
      }
      return false;
    },
    onResize() {
      this.isMobile = window.innerWidth < 992;
    },
  },
});

// Assign our navbar component to our global ProcessMaker object
if (isMobileNavbar === "true") {
  window.ProcessMaker.navbarMobile = new Vue({
    el: "#navbarMobile",
    components: {
      requestModalMobile,
      WelcomeModal,
    },
    data() {
      return {
        display: true,
      };
    },
    mounted() {
      if (this.$cookies.get("firstMounted") === "true") {
        $("#welcomeModal").modal("show");
      }
    },
    methods: {
      switchToDesktop() {
        this.$cookies.set("isMobile", false);
        window.location.reload();
      },
      onResize() {
        this.isMobile = window.innerWidth < 992;
      },
    },
  });
}

// Breadcrumbs are now part of the navbar component. Alias it here.
window.ProcessMaker.breadcrumbs = window.ProcessMaker.navbar;

// Set our own specific alert function at the ProcessMaker global object that could
// potentially be overwritten by some custom theme support
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

// Setup our login modal
window.ProcessMaker.sessionModal = function (title, message, time, warnSeconds) {
  ProcessMaker.navbar.sessionTitle = title || __("Session Warning");
  ProcessMaker.navbar.sessionMessage = message || __("Your session is about to expire.");
  ProcessMaker.navbar.sessionTime = time;
  ProcessMaker.navbar.sessionWarnSeconds = warnSeconds;
  ProcessMaker.navbar.sessionShow = true;
};

window.ProcessMaker.closeSessionModal = function () {
  ProcessMaker.navbar.sessionShow = false;
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

new Vue({
  el: "#sidebar",
  components: {
    Sidebaricon,
  },
  data() {
    return {
      expanded: false,
    };
  },
  created() {
    this.expanded === false;
  },
});
