import i18next from "i18next";
import Backend from "i18next-chained-backend";
import LocalStorageBackend from "i18next-localstorage-backend";
import XHR from "i18next-xhr-backend";
import VueI18Next from "@panter/vue-i18next";
import { setGlobalPMVariables, setUses, getGlobalVariable } from "../globalVariables";

export default () => {
  const Vue = getGlobalVariable("Vue");

  const isProd = document.head.querySelector("meta[name=\"is-prod\"]")?.content === "true";
  let translationsLoaded = false;

  const mdates = JSON.parse(
    document.head.querySelector("meta[name=\"i18n-mdate\"]")?.content,
  );

  const missingTranslations = new Set();
  const missingTranslation = function (value) {
    if (missingTranslations.has(value)) { return; }
    missingTranslations.add(value);
    if (!isProd) {
      console.warn("Missing Translation:", value);
    }
  };

  const i18nPromise = i18next.use(Backend).init({
    lng: document.documentElement.lang,
    fallbackLng: "en", // default language when no translations
    returnEmptyString: false, // When a translation is an empty string, return the default language, not empty
    nsSeparator: false,
    keySeparator: false,
    parseMissingKeyHandler(value) {
      if (!translationsLoaded) { return value; }
      // Report that a translation is missing
      missingTranslation(value);
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

  i18nPromise.then(() => { translationsLoaded = true; });

  setUses(Vue, { VueI18Next });
  Vue.mixin({ i18n: new VueI18Next(i18next) });
  setGlobalPMVariables({
    i18n: i18next,
    i18nPromise,
    missingTranslations,
    missingTranslation,
  });
};
