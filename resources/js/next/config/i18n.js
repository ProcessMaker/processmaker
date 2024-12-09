import i18next from "i18next";
import Backend from "i18next-chained-backend";
import LocalStorageBackend from "i18next-localstorage-backend";
import XHR from "i18next-xhr-backend";
import VueI18Next from "@panter/vue-i18next";

window.Vue.use(VueI18Next);
window.Vue.mixin({ i18n: new VueI18Next(i18next) });

let translationsLoaded = false;
const mdates = JSON.parse(
  document.head.querySelector("meta[name=\"i18n-mdate\"]")?.content,
);

window.ProcessMaker.i18n = i18next;
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
