import { sanitizeUrl } from "@braintree/sanitize-url";
// import VueHtml2Canvas from "vue-html2canvas";
import Sidebaricon from "../../components/Sidebaricon.vue";
import { getGlobalVariable } from "../globalVariables";

const Vue = getGlobalVariable("Vue");

// Vue.use(VueHtml2Canvas);
Vue.prototype.$sanitize = sanitizeUrl;

const sidebar = new Vue({
  el: "#sidebar",
  components: {
    Sidebaricon,
  },
  data() {
    return {
      expanded: false,
    };
  },
});
