import { configureCompat } from "vue";

configureCompat({
  MODE: 2,
  COMPILER_V_FOR_TEMPLATE_KEY_PLACEMENT: true,
  COMPILER_V_IF_V_FOR_PRECEDENCE: true,
  COMPILER_NATIVE_TEMPLATE: true,
  INSTANCE_EVENT_EMITTER: true,
});

import * as bootstrap from "bootstrap";
import "bootstrap-vue-next/dist/bootstrap-vue-next.css";
import BootstrapVueNext from "./lib/installBootstrapVueNext";
import Vue from "vue";

import i18next from "i18next";
import Backend from "i18next-chained-backend";
import LocalStorageBackend from "i18next-localstorage-backend";
import HttpBackend from "i18next-http-backend";

import * as vue from "vue";
import VueCookies from "vue-cookies";
import translator from "./modules/lang.js";
import AccessibilityMixin from "./components/common/mixins/accessibility";
import { createPmEventBus } from "./lib/pmEventBus";

window.__ = translator;
window._ = require("lodash");
window.Popper = require("popper.js").default;

window.$ = window.jQuery = require("jquery");

window.Vue = Vue;
window.vue = vue;
window.bootstrap = bootstrap;
window.Vue.use(BootstrapVueNext);
window.Vue.use(VueCookies);

let translationsLoaded = false;
const mdates = JSON.parse(
  document.head.querySelector("meta[name=\"i18n-mdate\"]")?.content,
);

Vue.mixin({
  methods: {
    $t(...args) {
      return i18next.t(...args);
    },
  },
});
Vue.mixin(AccessibilityMixin);

window.ProcessMaker = {
  i18n: i18next,
  EventBus: createPmEventBus(),
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

Vue.use({
  install(vueApp) {
    vueApp.mixin({
      mounted() {
        if (this.$parent) {
          // only run on root
        }
      },
    });
  },
});

window.dispatchEvent(new Event("app-bootstrapped"));
