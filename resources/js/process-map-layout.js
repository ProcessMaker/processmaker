import moment from "moment";

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

// Event bus ProcessMaker.
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

window.ProcessMaker.closeSessionModal = () => {
  ProcessMaker.navbar.sessionShow = false;
};

// Set our own specific alert function at the ProcessMaker global object that could
// potentially be overwritten by some custom theme support
window.ProcessMaker.alert = (
  msg,
  variant,
  showValue = 5,
  stayNextScreen = false,
  showLoader = false,
) => {
  let updatedShowValue = showValue;
  if (showValue === 0) {
    // Just show it indefinitely, no countdown.
    updatedShowValue = true;
  }
  // amount of items allowed in array
  if (ProcessMaker.navbar.alerts.length > 5) {
    ProcessMaker.navbar.alerts.shift();
  }
  ProcessMaker.navbar.alerts.push({
    alertText: msg,
    alertShow: updatedShowValue,
    alertVariant: String(variant),
    showLoader,
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
    window.ProcessMaker.apiClient.requestCount += 1;
  }

  window.ProcessMaker.EventBus.$emit("api-client-loading", request);
  return request;
});

window.ProcessMaker.apiClient.interceptors.response.use(
  (response) => {
    window.ProcessMaker.EventBus.$emit("api-client-done", response);
    // flags print forms
    if (
      window.ProcessMaker.apiClient.requestCountFlag
      && window.ProcessMaker.apiClient.requestCount > 0
    ) {
      window.ProcessMaker.apiClient.requestCount -= 1;
    }
    return response;
  },
  (error) => {
    window.ProcessMaker.EventBus.$emit("api-client-error", error);
    if (
      error.response
      && error.response.status
      && error.response.status === 401
    ) {
      // stop 401 error consuming endpoints with data-sources
      const { url } = error.config;
      if (url.includes("/data_sources/")) {
        if (url.includes("requests/") || url.includes("/test")) {
          throw error;
        }
      }
      window.location = "/login";
    } else if (_.has(error, "config.url") && !error.config.url.match("/debug")) {
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
  },
);

// Display any uncaught promise rejections from axios in the Process Maker alert box
window.addEventListener("unhandledrejection", (event) => {
  const error = event.reason;
  // eslint-disable-next-line no-underscore-dangle
  if (error.config && error.config._defaultErrorShown) {
    // Already handeled
    event.preventDefault(); // stops the unhandled rejection error
  } else if (
    error.response
    && error.response.data
    && error.response.data.message
  ) {
    window.ProcessMaker.alert(error.response.data.message, "danger");
  } else if (error.message) {
    window.ProcessMaker.alert(error.message, "danger");
  }
});
