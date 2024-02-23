import Vue from "vue";
import TemplateAssetsView from "../components/templates/TemplateAssetsView.vue";

const app = new Vue({
  el: "#template-asset-manager",
  components: { TemplateAssetsView },
  props: [],
  data() {
    return {
      assets: [],
      name: "",
      responseId: "",
      request: {},
      redirectTo: "",
      wizardTemplateUuid: null,
    };
  },
  mounted() {
    if (localStorage.getItem("templateAssetsState")) {
      const stateData = JSON.parse(localStorage.getItem("templateAssetsState"));
      this.name = stateData.name;
      this.assets = JSON.parse(stateData.assets);
      this.responseId = stateData.responseId;
      this.request = JSON.parse(stateData.request);
      this.redirectTo = stateData.redirectTo;
      this.wizardTemplateUuid = stateData.wizardTemplateUuid;
    }

    window.addEventListener("popstate", () => {
      window.location.href = "/processes";
    });
  },
});

export default app;
