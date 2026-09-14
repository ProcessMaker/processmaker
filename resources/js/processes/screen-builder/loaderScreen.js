import * as ScreenBuilder from "@processmaker/screen-builder";
import { setupMain } from "../../next/setupMain";
import monaco from "../../next/monaco";
import vueFormElements from "../../next/libraries/vueFormElements";

import("@processmaker/screen-builder/dist/vue-form-builder.css");

// Load screen-builder for PMBLOCKs

window.ScreenBuilder = ScreenBuilder;

setupMain();
monaco();
window.Vue.use(ScreenBuilder.default);

window.ProcessMaker.packages = window.temporal?.packages || [];
window.packages = window.ProcessMaker.packages;
window.ProcessMaker.setValidatorLanguage = (validator, lang) => {
  const availableLanguages = ["ar", "az", "be", "bg", "bs", "ca", "cs", "cy", "da", "de", "el", "en", "es", "et", "eu", "fa", "fi",
    "fr", "hr", "hu", "id", "it", "ja", "ka", "km", "ko", "lt", "lv", "mk", "mn", "ms", "nb_NO", "nl", "pl", "pt", "pt_BR", "ro", "ru",
    "se", "sl", "sq", "sr", "sv", "tr", "ua", "uk", "uz", "vi", "zh", "zh_TW"];
  const selectedLang = availableLanguages.includes(lang) ? lang : "en";
  if (validator) {
    validator.useLang(selectedLang);
  }
};
