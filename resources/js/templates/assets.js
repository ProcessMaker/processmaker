import Vue from "vue";
import TemplateAssetsView from "../components/templates/TemplateAssetsView.vue";

new Vue({
  el: '#template-asset-manager',
  components: { TemplateAssetsView },
  props: [],
  data() {
    return {
      assets: [],
      name: "",
    };
  },
  mounted() {
    this.name = window.history.state.name;
    this.assets = JSON.parse(window.history.state.assets);
  }
});
