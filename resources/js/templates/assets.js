import Vue from "vue";
import TemplateAssetsView from "../components/templates/TemplateAssetsView.vue";

new Vue({
  el: "#template-asset-manager",
  components: { TemplateAssetsView },
  props: [],
  data() {
    return {
      assets: [],
      name: "",
      responseId: null,
      request: {},
      redirectTo: null,
      wizardTemplateUuid: null,
    };
  },
  mounted() {
    if (localStorage.getItem("templateAssetsState")) {
      const stateData = JSON.parse(localStorage.getItem("templateAssetsState"));
      this.name = stateData.name;
      this.assets = JSON.parse(stateData.assets);
      this.responseId = stateData.responseId;
      this.request = stateData.request;
    }

    window.addEventListener("popstate", (event) => {
      window.location.href = "/processes";
    });
  },
});
