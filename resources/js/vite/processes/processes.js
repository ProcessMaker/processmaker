import Vue from "vue";

import Required from "../../components/shared/Required.vue";
import CreateProcessModal from "../../processes/components/CreateProcessModal.vue";
import SelectTemplateModal from "../../components/templates/SelectTemplateModal.vue";
import ProcessesListing from "../../processes/components/ProcessesListing.vue";
import CategorySelect from "../../processes/categories/components/CategorySelect.vue";
import CategoriesListing from "../../processes/categories/components/CategoriesListing.vue";
import ProcessTemplatesListing from "../../templates/components/ProcessTemplatesListing.vue";
import ArchivedProcessList from "../../processes/components/ArchivedProcessList.vue";


Vue.component("CategorySelect", CategorySelect);
Vue.component("CategoriesListing", CategoriesListing);
Vue.component("Required", Required);
Vue.component("ArchivedProcessesList", ArchivedProcessList);

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

if (document.getElementById("templatesIndex")) {
  // eslint-disable-next-line no-new
  new Vue({
    el: "#templatesIndex",
    components: {
      ProcessTemplatesListing,
    },
    data: {
      filter: "",
    },
    methods: {
      show() {},
      edit() {},
      goToImport() {
        window.location = "/template/process/import";
      },
      reload() {
        this.$refs.templateListing.dataManager([{
          field: "updated_at",
          direction: "desc",
        }]);
      },
    },
  });
}

if (document.getElementById("categories-listing")) {
  window.ProcessMaker.CategoriesIndex = new Vue({
    el: "#categories-listing",
    data: {
      filter: "",
      formData: null,
      errors: {},
      id: "",
      name: "",
      status: "ACTIVE",
      disabled: false,
      route: window.temporal?.categoryApiRoute || "",
    },
    methods: {
      emptyData() {
        this.id = "";
        this.name = "";
        this.status = "ACTIVE";
        this.disabled = false;
        this.errors = {};
      },
      getTitle() {
        return this.id ? this.$t("Edit Category") : this.$t("Create Category");
      },
      reload() {
        this.$refs.list.fetch();
      },
      edit(value) {
        this.emptyData();
        this.id = value.id;
        this.name = value.name;
        this.status = value.status;
        this.$refs.createCategoryModal.show();
      },
      showModal() {
        this.emptyData();
        this.$refs.createCategoryModal.show();
      },
      onClose() {
        this.emptyData();
      },
      onSubmit() {
        this.errors = {};
        if (this.disabled) {
          return;
        }
        this.disabled = true;
        let method = "POST";
        let url = this.route;
        if (this.id) {
          method = "PUT";
          url = `${url}/${this.id}`;
        }
        ProcessMaker.apiClient({
          method,
          url,
          baseURL: "/",
          data: {
            name: this.name,
            status: this.status,
          },
        })
          .then(() => {
            this.$refs.createCategoryModal.hide();
            let message = "The category was created.";
            if (this.id) {
              message = "The category was saved.";
            }
            ProcessMaker.alert(this.$t(message), "success");
            this.emptyData();
            this.reload();
          }).catch((error) => {
            this.disabled = false;
            if (error.response.status === 422) {
              this.errors = error.response.data.errors;
            }
          });
      },
    },
  });
}

if (document.getElementById("archivedProcess")) {
  // eslint-disable-next-line no-new
  new Vue({
    el: "#archivedProcess",
    data: {
      filter: "",
      pmql: "",
      urlPmql: "",
    },
    created() {
      const urlParams = new URLSearchParams(window.location.search);
      this.urlPmql = urlParams.get("pmql");
    },
    methods: {
      onNLQConversion(query) {
        this.onChange(query);
        this.reload();
      },
      onChange(query) {
        this.pmql = query;
      },
      reload() {
        this.$refs.processListing.dataManager([{
          field: "updated_at",
          direction: "desc",
        }]);
      },
    },
  });
}

if (window.temporal?.loadCategoryOnStart) {
  ProcessMaker.EventBus.$emit("api-data-category", true);
}
