import "bootstrap-vue/dist/bootstrap-vue.css";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import Echo from "laravel-echo";
import Router from "vue-router";
import ScreenBuilder, { initializeScreenCache } from "@processmaker/screen-builder";
import * as VueDeepSet from "vue-deepset";

/**
 * Setup Translations
 */
import i18next from "i18next";
import Backend from "i18next-chained-backend";
import LocalStorageBackend from "i18next-localstorage-backend";
import XHR from "i18next-xhr-backend";
import VueI18Next from "@panter/vue-i18next";
import { install as VuetableInstall } from "vuetable-2";
import MonacoEditor from "vue-monaco";
import Vue from "vue";
import VueCookies from "vue-cookies";
import Pagination from "./components/common/Pagination";
import ScreenSelect from "./processes/modeler/components/inspector/ScreenSelect.vue";
import translator from "./modules/lang.js";
import datetime_format from "./data/datetime_formats.json";
import RequestChannel from "./tasks/components/ProcessRequestChannel";
import Modal from "./components/shared/Modal";
import AccessibilityMixin from "./components/common/mixins/accessibility";
import PmqlInput from "./components/shared/PmqlInput.vue";
import DataTreeToggle from "./components/common/data-tree-toggle.vue";
import TreeView from "./components/TreeView.vue";
import FilterTable from "./components/shared/FilterTable.vue";
import PaginationTable from "./components/shared/PaginationTable.vue";
import "@processmaker/screen-builder/dist/vue-form-builder.css";

window.__ = translator;
window._ = require("lodash");
window.Popper = require("popper.js").default;

/**
 * Give node plugins access to our custom screen builder components
 */
window.ProcessmakerComponents = require("./processes/screen-builder/components");

/**
 * Give node plugins access to additional components
 */
window.SharedComponents = require("./components/shared");

window.ProcessesComponents = require("./processes/components");
window.ScreensComponents = require("./processes/screens/components");
window.ScriptsComponents = require("./processes/scripts/components");

/**
 * Exporting Modeler inspector components
 */
window.ModelerInspector = require("./processes/modeler/components/inspector");
/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

window.$ = window.jQuery = require("jquery");

/**
 * Vue is a modern JavaScript library for building interactive web interfaces
 * using reactive data binding and reusable components. Vue's API is clean
 * and simple, leaving you to focus on building your next great project.
 */

window.Vue = Vue;

window.Vue.use(BootstrapVue);
window.Vue.use(BootstrapVueIcons);
window.Vue.use(ScreenBuilder);
window.Vue.use(VueDeepSet);
window.Vue.use(VueCookies);
if (!document.head.querySelector("meta[name=\"is-horizon\"]")) {
  window.Vue.use(Router);
}
window.VueMonaco = require("vue-monaco");

window.ScreenBuilder = require("@processmaker/screen-builder");
window.VueFormElements = require("@processmaker/vue-form-elements");

window.VueRouter = Router;

window.Vue.use(VueI18Next);
VuetableInstall(window.Vue);
window.Vue.component("pagination", Pagination);
window.Vue.component("monaco-editor", MonacoEditor);
window.Vue.component("screen-select", ScreenSelect);
window.Vue.component("pm-modal", Modal);
window.Vue.component("pmql-input", PmqlInput);
window.Vue.component("data-tree-toggle", DataTreeToggle);
window.Vue.component("tree-view", TreeView);
window.Vue.component("filter-table", FilterTable);
window.Vue.component("pagination-table", PaginationTable);

let translationsLoaded = false;
const mdates = JSON.parse(
  document.head.querySelector("meta[name=\"i18n-mdate\"]")?.content,
);

// Make $t available to all vue instances
Vue.mixin({ i18n: new VueI18Next(i18next) });
Vue.mixin(AccessibilityMixin);

window.ProcessMaker = {
  i18n: i18next,

  /**
     * A general use global event bus that can be used
     */
  EventBus: new Vue(),
  /**
     * A general use global router that can be used
     */
  Router: new Router({
    mode: "history",
  }),
  /**
     * ProcessMaker Notifications
     */
  notifications: [],
  /**
     * Push a notification.
     *
     * @param {object} notification
     *
     * @returns {void}
     */
  pushNotification(notification) {
    if (this.notifications.filter((x) => x.id === notification).length === 0) {
      this.notifications.push(notification);
    }
  },

  /**
     * Removes notifications by message ids or urls
     *
     * @returns {void}
     * @param messageIds
     *
     * @param urls
     */
  removeNotifications(messageIds = [], urls = []) {
    return window.ProcessMaker.apiClient.put("/read_notifications", { message_ids: messageIds, routes: urls }).then(() => {
      messageIds.forEach((messageId) => {
        ProcessMaker.notifications.splice(ProcessMaker.notifications.findIndex((x) => x.id === messageId), 1);
      });

      urls.forEach((url) => {
        const messageIndex = ProcessMaker.notifications.findIndex((x) => x.url === url);
        if (messageIndex >= 0) {
          ProcessMaker.removeNotification(ProcessMaker.notifications[messageIndex].id);
        }
      });
    });
  },
  /**
     * Mark as unread a list of notifications
     *
     * @returns {void}
     * @param messageIds
     *
     * @param urls
     */
  unreadNotifications(messageIds = [], urls = []) {
    return window.ProcessMaker.apiClient.put("/unread_notifications", { message_ids: messageIds, routes: urls });
  },

  missingTranslations: new Set(),
  missingTranslation(value) {
    if (this.missingTranslations.has(value)) { return; }
    this.missingTranslations.add(value);
    console.warn("Missing Translation:", value);
  },

  RequestChannel,

  $notifications: {
    icons: {},
  },
};

window.ProcessMaker.i18nPromise = i18next.use(Backend).init({
  lng: document.documentElement.lang,
  fallbackLng: false,
  nsSeparator: false,
  keySeparator: false,
  parseMissingKeyHandler(value) {
    if (!translationsLoaded) { return value; }
    // Report that a translation is missing
    window.ProcessMaker.missingTranslation(value);
    // Fallback to showing the english version
    return value;
  },
  backend: {
    backends: [
      LocalStorageBackend, // Try cache first
      XHR,
    ],
    backendOptions: [
      { versions: mdates },
      { loadPath: "/i18next/fetch/{{lng}}/_default" },
    ],
  },
});

window.ProcessMaker.i18nPromise.then(() => { translationsLoaded = true; });

/**
 * Create a axios instance which any vue component can bring in to call
 * REST api endpoints through oauth authentication
 *
 */
window.ProcessMaker.apiClient = require("axios");

window.ProcessMaker.apiClient.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

const token = document.head.querySelector("meta[name=\"csrf-token\"]");

if (token) {
  window.ProcessMaker.apiClient.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
} else {
  console.error("CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token");
}

window.ProcessMaker.apiClient.defaults.baseURL = "/api/1.0/";

// Set the default API timeout
let apiTimeout = 5000;
if (window.Processmaker && window.Processmaker.apiTimeout !== undefined) {
  apiTimeout = window.Processmaker.apiTimeout;
}
window.ProcessMaker.apiClient.defaults.timeout = apiTimeout;

// Default alert functionality
window.ProcessMaker.alert = function (text, variant) {
  if (typeof text === "string") {
    window.alert(text);
  }
};

const openAiEnabled = document.head.querySelector("meta[name=\"open-ai-nlq-to-pmql\"]");

if (openAiEnabled) {
  window.ProcessMaker.openAi = {
    enabled: openAiEnabled.content,
  };
} else {
  window.ProcessMaker.openAi = {
    enabled: false,
  };
}

const userID = document.head.querySelector("meta[name=\"user-id\"]");
const userFullName = document.head.querySelector("meta[name=\"user-full-name\"]");
const userAvatar = document.head.querySelector("meta[name=\"user-avatar\"]");
const formatDate = document.head.querySelector("meta[name=\"datetime-format\"]");
const timezone = document.head.querySelector("meta[name=\"timezone\"]");
const appUrl = document.head.querySelector("meta[name=\"app-url\"]");

if (appUrl) {
  window.ProcessMaker.app = {
    url: appUrl.content,
  };
}

if (userID) {
  window.ProcessMaker.user = {
    id: userID.content,
    datetime_format: formatDate?.content,
    calendar_format: formatDate?.content,
    timezone: timezone?.content,
    fullName: userFullName?.content,
    avatar: userAvatar?.content,
  };
  datetime_format.forEach((value) => {
    if (formatDate.content === value.format) {
      window.ProcessMaker.user.datetime_format = value.momentFormat;
      window.ProcessMaker.user.calendar_format = value.calendarFormat;
    }
  });
}

if (window.Processmaker && window.Processmaker.broadcasting) {
  const config = window.Processmaker.broadcasting;

  if (config.broadcaster == "pusher") {
    window.Pusher = require("pusher-js");
    window.Pusher.logToConsole = config.debug;
  }

  window.Echo = new Echo(config);
}

if (userID) {
  // Session timeout
  const timeoutScript = document.head.querySelector("meta[name=\"timeout-worker\"]")?.content;
  window.ProcessMaker.AccountTimeoutLength = parseInt(eval(document.head.querySelector("meta[name=\"timeout-length\"]")?.content));
  window.ProcessMaker.AccountTimeoutWarnSeconds = parseInt(document.head.querySelector("meta[name=\"timeout-warn-seconds\"]")?.content);
  window.ProcessMaker.AccountTimeoutEnabled = document.head.querySelector("meta[name=\"timeout-enabled\"]") ? parseInt(document.head.querySelector("meta[name=\"timeout-enabled\"]")?.content) : 1;
  window.ProcessMaker.AccountTimeoutWorker = new Worker(timeoutScript);
  window.ProcessMaker.AccountTimeoutWorker.addEventListener("message", (e) => {
    if (e.data.method === "countdown") {
      window.ProcessMaker.sessionModal(
        "Session Warning",
        "<p>Your user session is expiring. If your session expires, all of your unsaved data will be lost.</p><p>Would you like to stay connected?</p>",
        e.data.data.time,
        window.ProcessMaker.AccountTimeoutWarnSeconds,
      );
    }
    if (e.data.method === "timedOut") {
      window.location = "/logout?timeout=true";
    }
  });

  // in some cases it's necessary to start manually
  window.ProcessMaker.AccountTimeoutWorker.postMessage({
    method: "start",
    data: {
      timeout: window.ProcessMaker.AccountTimeoutLength,
      warnSeconds: window.ProcessMaker.AccountTimeoutWarnSeconds,
      enabled: window.ProcessMaker.AccountTimeoutEnabled,
    },
  });

  const isSameDevice = (e) => {
    const localDeviceId = Vue.$cookies.get(e.device_variable);
    const remoteDeviceId = e.device_id;
    return localDeviceId && localDeviceId === remoteDeviceId;
  };

  window.Echo.private(`ProcessMaker.Models.User.${userID.content}`)
    .notification((token) => {
      ProcessMaker.pushNotification(token);
    })
    .listen(".SessionStarted", (e) => {
      const lifetime = parseInt(eval(e.lifetime));
      if (isSameDevice(e)) {
        window.ProcessMaker.AccountTimeoutWorker.postMessage({
          method: "start",
          data: {
            timeout: lifetime,
            warnSeconds: window.ProcessMaker.AccountTimeoutWarnSeconds,
            enabled: window.ProcessMaker.AccountTimeoutEnabled,
          },
        });
        if (window.ProcessMaker.closeSessionModal) {
          window.ProcessMaker.closeSessionModal();
        }
      }
    })
    .listen(".Logout", (e) => {
      if (isSameDevice(e) && window.location.pathname.indexOf("/logout") === -1) {
        const localDeviceId = Vue.$cookies.get(e.device_variable);
        const redirectLogoutinterval = setInterval(() => {
          const newDeviceId = Vue.$cookies.get(e.device_variable);
          if (localDeviceId !== newDeviceId) {
            clearInterval(redirectLogoutinterval);
            window.location.href = "/logout";
          }
        }, 100);
      }
    })
    .listen(".SecurityLogDownloadJobCompleted", (e) => {
      if (e.success) {
        const { link } = e;
        const { message } = e;
        window.ProcessMaker.alert(message, "success", 0, false, false, link);
      } else {
        window.ProcessMaker.alert(e.message, "warning");
      }
    });
}

// Configuration Global object used by ScreenBuilder
// @link https://processmaker.atlassian.net/browse/FOUR-6833 Cache configuration
const screenCacheEnabled = document.head.querySelector("meta[name=\"screen-cache-enabled\"]")?.content ?? "false";
const screenCacheTimeout = document.head.querySelector("meta[name=\"screen-cache-timeout\"]")?.content ?? "5000";
window.ProcessMaker.screen = {
  cacheEnabled: screenCacheEnabled === "true",
  cacheTimeout: Number(screenCacheTimeout),
};
// Initialize screen-builder cache
initializeScreenCache(window.ProcessMaker.apiClient, window.ProcessMaker.screen);

const clickTab = () => {
  const { hash } = window.location;
  if (!hash) {
    return;
  }
  const tab = $(`[role="tab"][href="${hash}"]`);
  if (tab.length) {
    tab.tab("show");
  }
};
window.addEventListener("hashchange", clickTab);

// click an active tab after all components have mounted
Vue.use({
  install(vue) {
    vue.mixin({
      mounted() {
        if (this.$parent) {
          // only run on root
          return;
        }

        // Run after component mounted
        this.$nextTick(() => {
          clickTab();
        });
      },
    });
  },
});

// Send an event when the global Vue and ProcessMaker instance is available
window.dispatchEvent(new Event("app-bootstrapped"));
