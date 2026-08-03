import Vue from "vue";

import Required from "../components/shared/Required.vue";
import CreateProcessModal from "./components/CreateProcessModal.vue";
import SelectTemplateModal from "../components/templates/SelectTemplateModal.vue";
import ProcessesListing from "./components/ProcessesListing.vue";

// Shared by categories tab modal (`<required>`) loaded later via @append
Vue.component("Required", Required);

if (document.getElementById("processIndex")) {
  // eslint-disable-next-line no-new
  new Vue({
    el: "#processIndex",
    components: {
      CreateProcessModal,
      SelectTemplateModal,
      ProcessesListing,
    },
    data: {
      filter: "",
      pmql: "",
      urlPmql: "",
      processModal: false,
      processId: null,
      showModal: false,
    },
    created() {
      const urlParams = new URLSearchParams(window.location.search);
      this.urlPmql = urlParams.get("pmql");
    },
    methods: {
      show() {
        this.processId = null;
        this.processModal = true;
      },
      edit(id) {
        this.processId = id;
        this.processModal = true;
      },
      goToImport() {
        window.location = "/processes/import";
      },
      onNLQConversion(query) {
        this.onChange(query);
        this.reload();
      },
      onChange(query) {
        this.pmql = query;
      },
      reload() {
        this.$refs.processListing.fetch();
      },
    },
  });
}

if (window.temporal?.loadCategoryOnStart) {
  ProcessMaker.EventBus.$emit("api-data-category", true);
}
