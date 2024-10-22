import * as bootstrap from "bootstrap";
import Vue from "vue";

/**
 * Setup Translations
 */
import i18next from "i18next";
import Backend from "i18next-chained-backend";
import LocalStorageBackend from "i18next-localstorage-backend";
import XHR from "i18next-xhr-backend";
import VueI18Next from "@panter/vue-i18next";

import * as vue from "vue";
import VueCookies from "vue-cookies";
import translator from "./modules/lang.js";
import AccessibilityMixin from "./components/common/mixins/accessibility";

window.__ = translator;
window._ = require("lodash");
window.Popper = require("popper.js").default;

window.$ = window.jQuery = require("jquery");

window.Vue = Vue;
window.vue = vue;
window.bootstrap = bootstrap;
window.Vue.use(VueCookies);
window.Vue.use(VueI18Next);

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
  packages: [],
  missingTranslations: new Set(),
  missingTranslation(value) {
    if (this.missingTranslations.has(value)) { return; }
    this.missingTranslations.add(value);
    console.warn("Missing Translation:", value);
  },

  $notifications: {
    icons: {},
  },
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


// click an active tab after all components have mounted
Vue.use({
  install(vue) {
    vue.mixin({
      mounted() {
        if (this.$parent) {
          // only run on root
          return;
        }
      },
    });
  },
});

// Send an event when the global Vue and ProcessMaker instance is available
window.dispatchEvent(new Event("app-bootstrapped"));
