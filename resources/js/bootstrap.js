import "bootstrap-vue/dist/bootstrap-vue.css";
import { BootstrapVue, BootstrapVueIcons } from "bootstrap-vue";
import * as bootstrap from "bootstrap";
import TenantAwareEcho from "./common/TenantAwareEcho";
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
import * as vue from "vue";
import VueCookies from "vue-cookies";
import GlobalStore from "./globalStore";
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
import PMDropdownSuggest from "./components/PMDropdownSuggest";
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
window.ProcessesCatalogueComponents = require("./processes-catalogue/components/utils");
window.Utils = require("./utils");

window.PMDropdownSuggest = PMDropdownSuggest;

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
window.vue = vue;
window.bootstrap = bootstrap;
window.Vue.use(BootstrapVue);
window.Vue.use(BootstrapVueIcons);
window.Vue.use(ScreenBuilder);
window.Vue.use(GlobalStore);
window.Vue.use(VueDeepSet);
window.Vue.use(VueCookies);
if (!document.head.querySelector("meta[name=\"is-horizon\"]")) {
  window.Vue.use(Router);
}
window.VueMonaco = require("vue-monaco");

window.ScreenBuilder = require("@processmaker/screen-builder");
window.VueFormElements = require("@processmaker/vue-form-elements");
window.Modeler = require("@processmaker/modeler");

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
    if (!isProd) {
      console.warn("Missing Translation:", value);
    }
  },

  RequestChannel,

  $notifications: {
    icons: {},
  },
};

window.ProcessMaker.setValidatorLanguage = (validator, lang) => {
  const availableLanguages = ["ar", "az", "be", "bg", "bs", "ca", "cs", "cy", "da", "de", "el", "en", "es", "et", "eu", "fa", "fi",
    "fr", "hr", "hu", "id", "it", "ja", "ka", "km", "ko", "lt", "lv", "mk", "mn", "ms", "nb_NO", "nl", "pl", "pt", "pt_BR", "ro", "ru",
    "se", "sl", "sq", "sr", "sv", "tr", "ua", "uk", "uz", "vi", "zh", "zh_TW"];
  const selectedLang = availableLanguages.includes(lang) ? lang : "en";
  if (validator) {
    validator.useLang(selectedLang);
  }
};

window.ProcessMaker.i18nPromise = i18next.use(Backend).init({
  lng: document.documentElement.lang,
  fallbackLng: "en", // default language when no translations
  returnEmptyString: false, // When a translation is an empty string, return the default language, not empty
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
const isProd = document.head.querySelector("meta[name=\"is-prod\"]")?.content === "true";

if (token) {
  window.ProcessMaker.apiClient.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
} else {
  console.error("CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token");
}

// Setup api versions
const apiVersionConfig = [
  { version: "1.0", baseURL: "/api/1.0/" },
  { version: "1.1", baseURL: "/api/1.1/" },
];

window.ProcessMaker.apiClient.defaults.baseURL = apiVersionConfig[0].baseURL;
window.ProcessMaker.apiClient.interceptors.request.use((config) => {
  if (typeof config.url !== "string" || !config.url) {
    throw new Error("Invalid URL in the request configuration");
  }

  apiVersionConfig.forEach(({ version, baseURL }) => {
    const versionPrefix = `/api/${version}/`;
    if (config.url.startsWith(versionPrefix)) {
      // eslint-disable-next-line no-param-reassign
      config.baseURL = baseURL;
      // eslint-disable-next-line no-param-reassign
      config.url = config.url.replace(versionPrefix, "");
    }
  });

  return config;
});

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

  window.Echo = new TenantAwareEcho(config);
}

if (userID) {
  // Session timeout
  const sessionChannelName = "pm-session-sync";
  const sessionLeaderKey = "pm:session:leader";
  const sessionStateKey = "pm:session:state";
  const sessionWarningKey = "pm:session:warning";
  const sessionMessageKey = "pm:session:message";
  const sessionTabId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const leaderHeartbeatMs = 4000;
  const leaderTtlMs = 8000;
  const sessionDebugEnabled = localStorage.getItem("pm:session:debug") === "1";
  const sessionDebugLog = (...args) => {
    if (sessionDebugEnabled && !isProd) {
      console.info("[SessionSync]", `[tab:${sessionTabId}]`, ...args);
    }
  };

  const timeoutScript = document.head.querySelector("meta[name=\"timeout-worker\"]")?.content;
  window.ProcessMaker.AccountTimeoutLength = parseInt(eval(document.head.querySelector("meta[name=\"timeout-length\"]")?.content));
  // Server config provides warn seconds; convert to minutes for session math.
  const warnSeconds = parseInt(document.head.querySelector("meta[name=\"timeout-warn-seconds\"]")?.content);
  window.ProcessMaker.AccountTimeoutWarnSeconds = Number.isNaN(warnSeconds) ? 0 : warnSeconds;
  window.ProcessMaker.AccountTimeoutWarnMinutes = window.ProcessMaker.AccountTimeoutWarnSeconds / 60;
  window.ProcessMaker.AccountTimeoutEnabled = document.head.querySelector("meta[name=\"timeout-enabled\"]") ? parseInt(document.head.querySelector("meta[name=\"timeout-enabled\"]")?.content) : 1;
  sessionDebugLog("worker:init", { timeoutScript });
  window.ProcessMaker.AccountTimeoutWorker = new Worker(timeoutScript);
  sessionDebugLog("worker:created");

  const readStorageJson = (key) => {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      return null;
    }
  };

  const writeStorageJson = (key, value) => {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
      // ignore storage failures (private mode or disabled)
    }
  };

  const removeStorageKey = (key) => {
    try {
      localStorage.removeItem(key);
    } catch (error) {
      // ignore storage failures (private mode or disabled)
    }
  };

  let sessionState = {
    timeout: window.ProcessMaker.AccountTimeoutLength,
    startedAt: Date.now(),
  };

  const refreshSessionStateFromStorage = () => {
    const storedSessionState = readStorageJson(sessionStateKey);
    if (storedSessionState?.timeout && storedSessionState?.startedAt) {
      const storedTimeout = Number(storedSessionState.timeout);
      const storedStartedAt = Number(storedSessionState.startedAt);
      const elapsedMinutes = (Date.now() - storedStartedAt) / 60000;
      if (storedTimeout > 0 && elapsedMinutes < storedTimeout) {
        sessionState = storedSessionState;
      } else {
        sessionDebugLog("session-state:stale", { storedSessionState, elapsedMinutes });
        writeStorageJson(sessionStateKey, sessionState);
      }
    } else {
      writeStorageJson(sessionStateKey, sessionState);
    }
    sessionDebugLog("session-state:refresh", sessionState);
    return sessionState;
  };

  refreshSessionStateFromStorage();

  const setSessionState = (timeoutSeconds) => {
    sessionState = {
      timeout: timeoutSeconds,
      startedAt: Date.now(),
    };
    writeStorageJson(sessionStateKey, sessionState);
    sessionDebugLog("session-state", sessionState);
  };

  let warningState = readStorageJson(sessionWarningKey);

  const refreshWarningStateFromStorage = () => {
    const storedWarningState = readStorageJson(sessionWarningKey);
    if (storedWarningState?.time && storedWarningState?.ts) {
      warningState = storedWarningState;
    } else {
      warningState = null;
    }
    sessionDebugLog("warning-state:refresh", warningState);
    return warningState;
  };

  const setWarningState = (timeSeconds) => {
    warningState = {
      time: timeSeconds,
      ts: Date.now(),
    };
    writeStorageJson(sessionWarningKey, warningState);
    sessionDebugLog("warning-state:set", warningState);
  };

  const clearWarningState = () => {
    warningState = null;
    removeStorageKey(sessionWarningKey);
    sessionDebugLog("warning-state:clear");
  };

  const sessionChannel = "BroadcastChannel" in window ? new BroadcastChannel(sessionChannelName) : null;

  const broadcastSessionEvent = (type, data = {}) => {
    const message = {
      id: `${sessionTabId}-${Date.now()}`,
      type,
      data,
      from: sessionTabId,
      ts: Date.now(),
    };

    sessionDebugLog("broadcast", message);
    writeStorageJson(sessionMessageKey, message);
    if (sessionChannel) {
      sessionChannel.postMessage(message);
    } else {
      // storage already written above
    }
  };

  const getLeader = () => readStorageJson(sessionLeaderKey);

  const writeLeader = () => {
    writeStorageJson(sessionLeaderKey, {
      tabId: sessionTabId,
      ts: Date.now(),
    });
    sessionDebugLog("leader:claim", { tabId: sessionTabId });
  };

  const isLeader = () => {
    const leader = getLeader();
    return document.visibilityState === "visible"
      && !!leader
      && leader.tabId === sessionTabId
      && Date.now() - leader.ts < leaderTtlMs;
  };

  let workerStarted = false;
  const ensureWorkerRunning = (reason) => {
    if (workerStarted) {
      return;
    }
    workerStarted = true;
    refreshSessionStateFromStorage();
    refreshWarningStateFromStorage();
    sessionDebugLog("worker:ensure", { reason, sessionState });
    startTimeoutWorker(sessionState.timeout);
    showWarningIfActive();
  };

  const markActivity = (source) => {
    const timeout = window.ProcessMaker.AccountTimeoutLength;
    setSessionState(timeout);
    clearWarningState();
    broadcastSessionEvent("activity", { timeout, source });
    sessionDebugLog("activity", { source, timeout });
    if (isLeader()) {
      ensureWorkerRunning(`activity:${source}`);
    }
  };

  const getRemainingTimeout = (timeoutMinutes) => {
    const elapsedMinutes = (Date.now() - sessionState.startedAt) / 60000;
    const remaining = timeoutMinutes - elapsedMinutes;
    return Math.max(0, remaining);
  };

  const getRemainingWarningTime = () => {
    if (!warningState?.time || !warningState?.ts) {
      return 0;
    }
    const elapsedSeconds = Math.floor((Date.now() - warningState.ts) / 1000);
    return Math.max(0, warningState.time - elapsedSeconds);
  };

  const startTimeoutWorker = (timeoutSeconds) => {
    const remaining = getRemainingTimeout(timeoutSeconds);
    sessionDebugLog("worker:start", { timeoutSeconds, remaining });
    if (remaining <= 0) {
      broadcastSessionEvent("expired");
      window.location = "/logout?timeout=true";
      return;
    }

    window.ProcessMaker.AccountTimeoutWorker.postMessage({
      method: "start",
      data: {
        timeout: remaining,
        warnSeconds: window.ProcessMaker.AccountTimeoutWarnSeconds,
        enabled: window.ProcessMaker.AccountTimeoutEnabled,
      },
    });
  };

  const handleSessionMessage = (message) => {
    if (!message || message.from === sessionTabId) {
      return;
    }

    sessionDebugLog("receive", message);
    if (message.type === "warning") {
      const time = Number(message.data?.time);
      if (time) {
        setWarningState(time);
      }
      if (!isLeader() && window.ProcessMaker.closeSessionModal) {
        window.ProcessMaker.closeSessionModal();
      }
      return;
    }

    if (message.type === "renewed" || message.type === "started" || message.type === "activity") {
      const timeout = Number(message.data?.timeout) || window.ProcessMaker.AccountTimeoutLength;
      clearWarningState();
      setSessionState(timeout);
      if (window.ProcessMaker.closeSessionModal) {
        window.ProcessMaker.closeSessionModal();
      }
      if (isLeader()) {
        startTimeoutWorker(timeout);
      }
      return;
    }

    if (message.type === "expired") {
      clearWarningState();
      window.location = "/logout?timeout=true";
    }
  };

  if (sessionChannel) {
    sessionChannel.onmessage = (event) => handleSessionMessage(event.data);
  }

  window.addEventListener("storage", (event) => {
    if (event.key !== sessionMessageKey || !event.newValue) {
      return;
    }

    handleSessionMessage(readStorageJson(sessionMessageKey));
  });

  window.ProcessMaker.sessionSync = {
    broadcast: broadcastSessionEvent,
    isLeader,
    setSessionState,
    clearWarningState,
  };

  window.ProcessMaker.AccountTimeoutWorker.onmessage = (e) => {
    if (!isLeader()) {
      return;
    }

    if (e.data.method === "countdown") {
      sessionDebugLog("worker:countdown", e.data.data);
      setWarningState(e.data.data.time);
      // Guard for layouts that don't include the session modal.
      if (typeof window.ProcessMaker.sessionModal === "function") {
        window.ProcessMaker.sessionModal(
          "Session Warning",
          "<p>Your user session is expiring. If your session expires, all of your unsaved data will be lost.</p><p>Would you like to stay connected?</p>",
          e.data.data.time,
          window.ProcessMaker.AccountTimeoutWarnSeconds,
        );
      }
      broadcastSessionEvent("warning", { time: e.data.data.time });
    }
    if (e.data.method === "timedOut") {
      sessionDebugLog("worker:timedOut");
      refreshSessionStateFromStorage();
      const remaining = getRemainingTimeout(sessionState.timeout);
      sessionDebugLog("worker:timedOut:check", { remaining, sessionState });
      if (remaining > 0) {
        startTimeoutWorker(sessionState.timeout);
        return;
      }
      clearWarningState();
      broadcastSessionEvent("expired");
      window.location = "/logout?timeout=true";
    }
  };

  const showWarningIfActive = () => {
    const remainingTime = getRemainingWarningTime();
    if (remainingTime <= 0) {
      sessionDebugLog("warning:skip", { remainingTime });
      clearWarningState();
      return;
    }
    sessionDebugLog("warning:show", { remainingTime });
    // Guard for layouts that don't include the session modal.
    if (typeof window.ProcessMaker.sessionModal === "function") {
      window.ProcessMaker.sessionModal(
        "Session Warning",
        "<p>Your user session is expiring. If your session expires, all of your unsaved data will be lost.</p><p>Would you like to stay connected?</p>",
        remainingTime,
        window.ProcessMaker.AccountTimeoutWarnSeconds,
      );
    }
  };

  // in some cases it's necessary to start manually
  let wasLeader = false;
  const updateLeadership = () => {
    const leader = getLeader();
    const now = Date.now();
    const isVisible = document.visibilityState === "visible";
    sessionDebugLog("leader:check", {
      isVisible,
      leader,
      now,
    });
    if (isVisible) {
      writeLeader();
    }

    const leaderNow = isLeader();
    if (leaderNow) {
      ensureWorkerRunning("leadership");
    }
    if (leaderNow !== wasLeader) {
      wasLeader = leaderNow;
      sessionDebugLog("leader:changed", { isLeader: leaderNow });
      if (leaderNow) {
        ensureWorkerRunning("leadership-change");
      } else if (window.ProcessMaker.closeSessionModal) {
        workerStarted = false;
        window.ProcessMaker.closeSessionModal();
      }
    }
  };

  updateLeadership();
  if (isLeader()) {
    markActivity("load");
    ensureWorkerRunning("load");
  }
  setInterval(updateLeadership, leaderHeartbeatMs);
  window.addEventListener("visibilitychange", () => {
    updateLeadership();
    if (isLeader()) {
      // Keep warning/timer state in sync when switching tabs.
      refreshSessionStateFromStorage();
      refreshWarningStateFromStorage();
      startTimeoutWorker(sessionState.timeout);
      showWarningIfActive();
    }
  });

  // Broadcast logout so all tabs close warning and redirect.
  document.addEventListener("click", (event) => {
    const logoutLink = event.target.closest('a[href="/logout"], a[href^="/logout?"]');
    if (!logoutLink) {
      return;
    }
    if (window.ProcessMaker.sessionSync?.clearWarningState) {
      window.ProcessMaker.sessionSync.clearWarningState();
    }
    if (window.ProcessMaker.sessionSync?.broadcast) {
      window.ProcessMaker.sessionSync.broadcast("expired");
    }
  });

  // Restart the timeout worker (when the user interacts with the page)
  const eventsTimeoutWorker = ["click", "keypress"];

  eventsTimeoutWorker.forEach((event) => {
    document.addEventListener(event, () => {
      if (!isLeader()) {
        sessionDebugLog("worker:restart:skip", { event });
        return;
      }
      markActivity(event);
      sessionDebugLog("worker:restart", { event });
      window.ProcessMaker.AccountTimeoutWorker.postMessage({ method: "restart" });
    });
  });

  // End -> Restart the timeout worker (when the user interacts with the page)

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
      if (!isSameDevice(e)) {
        return;
      }

      sessionDebugLog("event:session-started", { lifetime });
      setSessionState(lifetime);
      // Clear any stale warning on new login/session.
      clearWarningState();
      broadcastSessionEvent("started", { timeout: lifetime });
      if (window.ProcessMaker.closeSessionModal) {
        window.ProcessMaker.closeSessionModal();
      }
      if (isLeader()) {
        startTimeoutWorker(lifetime);
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
const screenSecureHandlerToggleVisible = document.head.querySelector("meta[name='screen-secure-handler-toggle-visible']");
window.ProcessMaker.screen = {
  cacheEnabled: screenCacheEnabled === "true",
  cacheTimeout: Number(screenCacheTimeout),
  secureHandlerToggleVisible: !!Number(screenSecureHandlerToggleVisible?.content),
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
