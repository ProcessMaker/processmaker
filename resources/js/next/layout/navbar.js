import newRequestModal from "../../components/requests/requestModal.vue";
import requestModal from "../../components/requests/modal.vue";
import requestModalMobile from "../../components/requests/modalMobile.vue";
import WelcomeModal from "../../Mobile/WelcomeModal.vue";
import notifications from "../../notifications/components/notifications.vue";
import sessionModalComponent from "../../components/Session.vue";
import ConfirmationModal from "../../components/Confirm.vue";
import MessageModal from "../../components/Message.vue";
import NavbarProfile from "../../components/NavbarProfile.vue";
import Menu from "../../components/Menu.vue";
import { getGlobalVariable, getGlobalPMVariable, setGlobalPMVariable } from "../globalVariables";

const Vue = getGlobalVariable("Vue");
const $ = getGlobalVariable("$");
const events = getGlobalPMVariable("events");

Vue.component("LanguageSelectorButton", (resolve) => {
  if (window.ProcessMaker.languageSelectorButtonComponent) {
    resolve(window.ProcessMaker.languageSelectorButtonComponent);
  } else {
    window.ProcessMaker.languageSelectorButtonComponentResolve = resolve;
  }
});

// Variables
const browser = navigator.userAgent;
const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(browser);

const mobileApp = !!isMobileDevice;
const isMobileNavbar = events.$cookies.get("isMobile"); // Verify is in mobile mode

// Set our own specific alert function at the ProcessMaker global object that could
// potentially be overwritten by some custom theme support
const alert = function (msg, variant, showValue = 5, stayNextScreen = false, showLoader = false, msgLink = "", msgTitle = "") {
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
const sessionModal = function (title, message, time, warnSeconds) {
  ProcessMaker.navbar.sessionTitle = title || __("Session Warning");
  ProcessMaker.navbar.sessionMessage = message || __("Your session is about to expire.");
  ProcessMaker.navbar.sessionTime = time;
  ProcessMaker.navbar.sessionWarnSeconds = warnSeconds;
  ProcessMaker.navbar.sessionShow = true;
};

const closeSessionModal = function () {
  ProcessMaker.navbar.sessionShow = false;
};

// Set out own specific confirm modal.
const confirmModal = function (
  title,
  message,
  variant,
  callback,
  size = "md",
  dataTestClose = "confirm-btn-close",
  dataTestOk = "confirm-btn-ok",
) {
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
const messageModal = function (title, message, variant, callback) {
  ProcessMaker.navbar.messageTitle = title || __("Message");
  ProcessMaker.navbar.messageMessage = message || __("");
  ProcessMaker.navbar.messageVariant = variant;
  ProcessMaker.navbar.messageCallback = callback;
  ProcessMaker.navbar.messageShow = true;
};

// Assign our navbar component to our global ProcessMaker object
const navbar = new Vue({
  el: "#navbar",
  components: {
    TopMenu: Menu,
    requestModal,
    notifications,
    sessionModal: sessionModalComponent,
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
      isMobileDevice: mobileApp,
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
          this.screenBuilder = window.ProcessMaker.ScreenBuilder; // window.ProcessMaker.ScreenBuilder is not defined in the global scope
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

setGlobalPMVariable("mobileApp", mobileApp);
setGlobalPMVariable("alert", alert);
setGlobalPMVariable("sessionModal", sessionModal);
setGlobalPMVariable("closeSessionModal", closeSessionModal);
setGlobalPMVariable("confirmModal", confirmModal);
setGlobalPMVariable("messageModal", messageModal);
setGlobalPMVariable("navbar", navbar);
// Breadcrumbs are now part of the navbar component. Alias it here.
setGlobalPMVariable("breadcrumbs", navbar);

// Assign our navbar component to our global ProcessMaker object
if (isMobileNavbar === "true") {
  const navbarMobile = new Vue({
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

  setGlobalPMVariable("navbarMobile", navbarMobile);
}
