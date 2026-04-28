import { configureCompat } from "vue";

configureCompat({
  MODE: 2,
  COMPILER_V_FOR_TEMPLATE_KEY_PLACEMENT: true,
  COMPILER_V_IF_V_FOR_PRECEDENCE: true,
  COMPILER_NATIVE_TEMPLATE: true,
  INSTANCE_EVENT_EMITTER: true,
});

import "bootstrap-vue-next/dist/bootstrap-vue-next.css";
import BootstrapVueNext from "./lib/installBootstrapVueNext";
import * as bootstrap from "bootstrap";
import { createRouter, createWebHistory } from "vue-router";
import ScreenBuilder, { initializeScreenCache } from "@processmaker/screen-builder";
import * as VueDeepSet from "vue-deepset";

import i18next from "i18next";
import Backend from "i18next-chained-backend";
import LocalStorageBackend from "i18next-localstorage-backend";
import HttpBackend from "i18next-http-backend";
import MonacoEditor from "vue-monaco";
import Vue from "vue";
import * as vue from "vue";
import VueCookies from "vue-cookies";
import VuetablePkg from "vue3-vuetable";

import { initSessionSync } from "./common/sessionSync";
import TenantAwareEcho from "./common/TenantAwareEcho";
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
import { createPmEventBus } from "./lib/pmEventBus";
import "@processmaker/screen-builder/dist/vue-form-builder.css";

window.__ = translator;
window._ = require("lodash");
window.Popper = require("popper.js").default;

window.ProcessmakerComponents = require("./processes/screen-builder/components");

window.SharedComponents = require("./components/shared");

window.ProcessesComponents = require("./processes/components");
window.ScreensComponents = require("./processes/screens/components");
window.ScriptsComponents = require("./processes/scripts/components");
window.ProcessesCatalogueComponents = require("./processes-catalogue/components/utils");
window.Utils = require("./utils");

window.PMDropdownSuggest = PMDropdownSuggest;

window.ModelerInspector = require("./processes/modeler/components/inspector");

window.$ = window.jQuery = require("jquery");

window.Vue = Vue;
window.vue = vue;
window.bootstrap = bootstrap;

const VuetablePlugin = VuetablePkg.default || VuetablePkg;

window.Vue.use(BootstrapVueNext);
window.Vue.use(ScreenBuilder);
window.Vue.use(GlobalStore);
window.Vue.use(VueDeepSet);
window.Vue.use(VueCookies);
window.Vue.use(VuetablePlugin);

window.VueMonaco = require("vue-monaco");

window.ScreenBuilder = require("@processmaker/screen-builder");
window.VueFormElements = require("@processmaker/vue-form-elements");
window.Modeler = require("@processmaker/modeler");

const router = createRouter({
  history: createWebHistory(),
  routes: [],
});
window.VueRouter = { createRouter, createWebHistory };
window.ProcessMakerRouter = router;

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

const i18nMixin = {
  methods: {
    $t(...args) {
      return i18next.t(...args);
    },
  },
};
Vue.mixin(i18nMixin);
Vue.mixin(AccessibilityMixin);

const eventsBus = createPmEventBus();
Object.defineProperty(eventsBus, "$cookies", {
  configurable: true,
  get() {
    return window.Vue.$cookies;
  },
});

window.ProcessMaker = {
  i18n: i18next,

  EventBus: createPmEventBus(),

  Router: router,

  notifications: [],

  pushNotification(notification) {
    if (window.ProcessMaker.notifications.filter((x) => x.id === notification).length === 0) {
      window.ProcessMaker.notifications.push(notification);
    }
  },

  removeNotification(id) {
    const idx = ProcessMaker.notifications.findIndex((x) => x.id === id);
    if (idx >= 0) {
      ProcessMaker.notifications.splice(idx, 1);
    }
  },

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
  fallbackLng: "en",
  returnEmptyString: false,
  nsSeparator: false,
  keySeparator: false,
  parseMissingKeyHandler(value) {
    if (!translationsLoaded) { return value; }
    window.ProcessMaker.missingTranslation(value);
    return value;
  },
  backend: {
    backends: [
      LocalStorageBackend,
      HttpBackend,
    ],
    backendOptions: [
      { versions: mdates },
      { loadPath: "/i18next/fetch/{{lng}}/_default" },
    ],
  },
});

window.ProcessMaker.i18nPromise.then(() => { translationsLoaded = true; });

window.ProcessMaker.apiClient = require("axios");

window.ProcessMaker.apiClient.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

const token = document.head.querySelector("meta[name=\"csrf-token\"]");
const isProd = document.head.querySelector("meta[name=\"is-prod\"]")?.content === "true";

if (token) {
  window.ProcessMaker.apiClient.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
} else {
  console.error("CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token");
}

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

let apiTimeout = 5000;
if (window.Processmaker && window.Processmaker.apiTimeout !== undefined) {
  apiTimeout = window.Processmaker.apiTimeout;
}
window.ProcessMaker.apiClient.defaults.timeout = apiTimeout;

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
  const timeoutScript = document.head.querySelector("meta[name=\"timeout-worker\"]")?.content;
  const accountTimeoutLength = parseInt(eval(document.head.querySelector("meta[name=\"timeout-length\"]")?.content));
  const warnSeconds = parseInt(document.head.querySelector("meta[name=\"timeout-warn-seconds\"]")?.content);
  const accountTimeoutWarnSeconds = Number.isNaN(warnSeconds) ? 0 : warnSeconds;
  const accountTimeoutEnabled = document.head.querySelector("meta[name=\"timeout-enabled\"]") ? parseInt(document.head.querySelector("meta[name=\"timeout-enabled\"]")?.content) : 1;

  const sessionSyncState = initSessionSync({
    userId: userID.content,
    isProd,
    timeoutScript,
    accountTimeoutLength,
    accountTimeoutWarnSeconds,
    accountTimeoutEnabled,
    Vue,
    Echo: window.Echo,
    pushNotification: window.ProcessMaker.pushNotification,
    alert: window.ProcessMaker.alert,
    getSessionModal: () => window.ProcessMaker.sessionModal,
    getCloseSessionModal: () => window.ProcessMaker.closeSessionModal,
    getNavbar: () => window.ProcessMaker.navbar,
  });

  if (sessionSyncState) {
    window.ProcessMaker.AccountTimeoutLength = sessionSyncState.AccountTimeoutLength;
    window.ProcessMaker.AccountTimeoutWarnSeconds = sessionSyncState.AccountTimeoutWarnSeconds;
    window.ProcessMaker.AccountTimeoutWarnMinutes = sessionSyncState.AccountTimeoutWarnMinutes;
    window.ProcessMaker.AccountTimeoutEnabled = sessionSyncState.AccountTimeoutEnabled;
    window.ProcessMaker.AccountTimeoutWorker = sessionSyncState.AccountTimeoutWorker;
    window.ProcessMaker.sessionSync = sessionSyncState.sessionSync;
  }
}

const screenCacheEnabled = document.head.querySelector("meta[name=\"screen-cache-enabled\"]")?.content ?? "false";
const screenCacheTimeout = document.head.querySelector("meta[name=\"screen-cache-timeout\"]")?.content ?? "5000";
const screenSecureHandlerToggleVisible = document.head.querySelector("meta[name='screen-secure-handler-toggle-visible']");
window.ProcessMaker.screen = {
  cacheEnabled: screenCacheEnabled === "true",
  cacheTimeout: Number(screenCacheTimeout),
  secureHandlerToggleVisible: !!Number(screenSecureHandlerToggleVisible?.content),
};
initializeScreenCache(window.ProcessMaker.apiClient, window.ProcessMaker.screen);

const clickTab = () => {
  const { hash } = window.location;
  if (!hash) {
    return;
  }
  const escaped = typeof CSS !== "undefined" && CSS.escape ? CSS.escape(hash) : hash.replace(/"/g, "\\\"");
  const trigger = document.querySelector(`[role="tab"][href="${escaped}"]`);
  if (trigger && bootstrap.Tab) {
    try {
      bootstrap.Tab.getOrCreateInstance(trigger).show();
    } catch (e) {
      // ignore
    }
  }
};
window.addEventListener("hashchange", clickTab);

Vue.use({
  install(vueApp) {
    vueApp.mixin({
      mounted() {
        if (this.$parent) {
          return;
        }
        this.$nextTick(() => {
          clickTab();
        });
      },
    });
  },
});

window.dispatchEvent(new Event("app-bootstrapped"));
