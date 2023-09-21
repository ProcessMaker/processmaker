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
      responseId: null,
      request: {},
    };
  },
  mounted() {
    this.name = window.history.state.name;
    this.assets = JSON.parse(window.history.state.assets);
    this.responseId = window.history.state.responseId;
    this.request = window.history.state.request;

    window.addEventListener('popstate', function(event) {
      window.location.href = '/processes';
    });
  },
});
