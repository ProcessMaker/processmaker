import { sanitizeUrl } from "@braintree/sanitize-url";
// import VueHtml2Canvas from "vue-html2canvas";
import Sidebaricon from "../../components/Sidebaricon.vue";

const { Vue } = window;

// Vue.use(VueHtml2Canvas);
Vue.prototype.$sanitize = sanitizeUrl;

Vue.component("LanguageSelectorButton", (resolve) => {
  if (window.ProcessMaker.languageSelectorButtonComponent) {
    resolve(window.ProcessMaker.languageSelectorButtonComponent);
  } else {
    window.ProcessMaker.languageSelectorButtonComponentResolve = resolve;
  }
});

new Vue({
  el: "#sidebar",
  components: {
    Sidebaricon,
  },
  data() {
    return {
      expanded: false,
    };
  },
  created() {
    this.expanded === false;
  },
});
