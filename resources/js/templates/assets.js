import Vue from "vue";
import TemplateAssetsView from "../components/templates/TemplateAssetsView.vue";
import router from "./routes";

new Vue({
  el: '#template-asset-manager',
  components: { TemplateAssetsView },
  mixins: [],
  router,
  data() {
    return {
    };
  },
});

