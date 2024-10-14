import Vue from "vue";

window.ProcessMaker.languageSelector = new Vue({
  el: "#language-selector",
  components: {
    LanguageSelectorButton: (resolve) => {
      if (window.ProcessMaker.languageSelectorButtonComponent) {
        resolve(window.ProcessMaker.languageSelectorButtonComponent);
      } else {
        window.ProcessMaker.languageSelectorButtonComponentResolve = resolve;
      }
    },
  },
});
