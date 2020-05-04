import requestModal from "./components/requests/modal";
import notifications from "./components/requests/notifications";
import {BNavbar} from "bootstrap-vue";
import sessionModal from "./components/Session";
import Sidebaricon from "./components/Sidebaricon";
import ConfirmationModal from "./components/Confirm";
import NavbarProfile from "./components/NavbarProfile";
import Multiselect from "vue-multiselect/src/Multiselect";
import SelectUser from "./components/SelectUser";
import CategorySelect from "./processes/categories/components/CategorySelect";
import SelectFromApi from "./components/SelectFromApi";
import Breadcrumbs from "./components/Breadcrumbs";
import TimelineItem from './components/TimelineItem';

import { FileUpload, FileDownload } from './processes/screen-builder/components'

/******
 * Global adjustment parameters for moment.js.
 */
import moment from "moment";
import moment_timezone from "moment-timezone";
import __ from "./modules/lang";

require("./bootstrap");

let Vue = window.Vue;
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
/********/

Vue.component("multiselect", Multiselect);
Vue.component("Sidebaricon", Sidebaricon);
Vue.component("select-user", SelectUser);
Vue.component("category-select", CategorySelect);
Vue.component("select-from-api", SelectFromApi);
Vue.component("FileUpload", FileUpload);
Vue.component("FileDownload", FileDownload);
Vue.component("Breadcrumbs", Breadcrumbs);
Vue.component("timeline-item", TimelineItem);

// Event bus ProcessMaker
window.ProcessMaker.events = new Vue();

window.ProcessMaker.nodeTypes = [];
window.ProcessMaker.nodeTypes.get = function (id) {
  return this.find(node => node.id === id);
};

// Setup breadcrumbs as a Vue component so that we can cloak it until Vue
// has finished loading.

if (document.getElementById("breadcrumbs")) {
  window.ProcessMaker.breadcrumbs = new Vue({
    el: '#breadcrumbs',
    methods: {
      getRoutes() {
        if (this.$refs.breadcrumbs) {
          return this.$refs.breadcrumbs.list;
        } else {
          return [];
        }
      },
      setRoutes(routes) {
        if (this.$refs.breadcrumbs) {
          return this.$refs.breadcrumbs.updateRoutes(routes);
        } else {
          return false;
        }
      }
    }
  });
}

// Assign our navbar component to our global ProcessMaker object
window.ProcessMaker.navbar = new Vue({
  el: "#navbar",
  components: {
    'b-navbar': BNavbar,
    requestModal,
    notifications,
    sessionModal,
    ConfirmationModal,
    NavbarProfile
  },
  watch: {
    alerts (array) {
      this.saveLocalAlerts(array);
    }
  },
  data () {
    return {
      messages: ProcessMaker.notifications,
      alerts: this.loadLocalAlerts(),
      confirmTitle: "",
      confirmMessage: "",
      confirmVariant: "",
      confirmCallback: "",
      confirmShow: false,
      sessionShow: false,
      sessionTitle: "",
      sessionMessage: "",
      sessionTime: "",
      sessionWarnSeconds: ""
    };
  },
  methods: {
    alertDownChanged (dismissCountDown, item) {
      item.alertShow = dismissCountDown;
      this.saveLocalAlerts(this.alerts);
    },
    alertDismissed (alert) {
      alert.alertShow = 0;
      const index = this.alerts.indexOf(alert);
      let copy = _.cloneDeep(this.alerts);
      index > -1 ? copy.splice(index, 1) : null;
      // remove old alerts
      copy = copy.filter(item => ((Date.now() - item.timestamp) / 1000) < item.alertShow);
      this.saveLocalAlerts(copy);
    },
    loadLocalAlerts () {
      try {
        return window.localStorage.processmakerAlerts &&
                    window.localStorage.processmakerAlerts.substr(0, 1) === "["
          ? JSON.parse(window.localStorage.processmakerAlerts) : [];
      } catch (e) {
        return [];
      }
    },
    saveLocalAlerts (array) {
      const nextScreenAlerts = array.filter(alert => alert.stayNextScreen);
      window.localStorage.processmakerAlerts = JSON.stringify(nextScreenAlerts);
    }
  },
  mounted () {
    Vue.nextTick() // This is needed to override the default alert method.
      .then(() => {
        if (document.querySelector("meta[name='alert']")) {
          ProcessMaker.alert(
            document.querySelector("meta[name='alertMessage']").getAttribute("content"),
            document.querySelector("meta[name='alertVariant']").getAttribute("content")
          );
        }
      });
  }
});

// Set our own specific alert function at the ProcessMaker global object that could
// potentially be overwritten by some custom theme support
window.ProcessMaker.alert = function (msg, variant, showValue = 5, stayNextScreen = false) {
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
    stayNextScreen,
    timestamp: Date.now()
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
window.ProcessMaker.confirmModal = function (title, message, variant, callback) {
  ProcessMaker.navbar.confirmTitle = title || __("Confirm");
  ProcessMaker.navbar.confirmMessage = message || __("Are you sure you want to delete?");
  ProcessMaker.navbar.confirmVariant = variant;
  ProcessMaker.navbar.confirmCallback = callback;
  ProcessMaker.navbar.confirmShow = true;
};

window.ProcessMaker.apiClient.interceptors.request.use((request) => {
  window.ProcessMaker.EventBus.$emit("api-client-loading", request);
  return request;
});

window.ProcessMaker.apiClient.interceptors.response.use((response) => {
  // TODO: this could be used to show a default "created/upated/deleted resource" alert
  // response.config.method (PUT, POST, DELETE)
  // response.config.url (extract resource name)
  window.ProcessMaker.EventBus.$emit("api-client-done", response);
  return response;
}, (error) => {
    window.ProcessMaker.EventBus.$emit("api-client-error", error);
    if (error.response && error.response.status && error.response.status === 401) {
        window.location = "/login";
    } else {
      if (_.has(error, 'config.url') && !error.config.url.match('/debug')) {
        window.ProcessMaker.apiClient.post('/debug', {
            name: 'Javascript ProcessMaker.apiClient Error',
            message: JSON.stringify({
                message: error.message,
                code: error.code,
                config: error.config
            })
        });
      }
      return Promise.reject(error);
    }
});

// Display any uncaught promise rejections from axios in the Process Maker alert box
window.addEventListener("unhandledrejection", (event) => {
  let error = event.reason;
  if (error.config && error.config._defaultErrorShown) {
    // Already handeled
    event.preventDefault(); // stops the unhandled rejection error
  } else if (error.response && error.response.data && error.response.data.message) {
    window.ProcessMaker.alert(error.response.data.message, "danger");
  } else if (error.message) {
    window.ProcessMaker.alert(error.message, "danger");
  }
});

new Vue({
  el: "#sidebar",
  components: {
    Sidebaricon
  },
  data () {
    return {
      expanded: false
    };
  },
  created () {
    this.expanded === false;
  }
});
