import newRequestModal from "../../components/requests/requestModal.vue";
import requestModal from "../../components/requests/modal.vue";
import notifications from "../../notifications/components/notifications.vue";
import sessionModal from "../../components/Session.vue";
import ConfirmationModal from "../../components/Confirm.vue";
import MessageModal from "../../components/Message.vue";
import NavbarProfile from "../../components/NavbarProfile.vue";
import Menu from "../../components/Menu.vue";

window.Vue.component("LanguageSelectorButton", (resolve) => {
  if (window.ProcessMaker.languageSelectorButtonComponent) {
    resolve(window.ProcessMaker.languageSelectorButtonComponent);
  } else {
    window.ProcessMaker.languageSelectorButtonComponentResolve = resolve;
  }
});

// Verify if is mobile
const browser = navigator.userAgent;
const isMobileDevice = /Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i.test(browser);
window.ProcessMaker.mobileApp = false;
if (isMobileDevice) {
  window.ProcessMaker.mobileApp = true;
}

// Verify is in mobile mode
const isMobileNavbar = window.ProcessMaker.events.$cookies.get("isMobile");

// Default alert functionality
window.ProcessMaker.alert = function (text, variant) {
  if (typeof text === "string") {
    window.alert(text);
  }
};

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

// Assign our navbar component to our global ProcessMaker object
window.ProcessMaker.navbar = new Vue({
  el: "#navbar",
  components: {
    TopMenu: Menu,
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
