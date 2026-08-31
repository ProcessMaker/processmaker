import Vue from "vue";
import AssetLinkFields from "./components/AssetLinkFields.vue";
import DoNotUpdateSwitch from "./components/DoNotUpdateSwitch.vue";

const initial = window.ProcessMaker.EnvironmentVariableEdit || {};

new Vue({
  el: "#editEnvironmentVariable",
  components: {
    AssetLinkFields,
    DoNotUpdateSwitch,
  },
  data() {
    return {
      formData: {
        id: initial.id,
        name: initial.name,
        description: initial.description,
        value: initial.value || null,
        asset_type: initial.asset_type || null,
        do_not_update: !!initial.do_not_update,
      },
      errors: {
        name: null,
        description: null,
        value: null,
        asset_type: null,
      },
    };
  },
  methods: {
    resetErrors() {
      this.errors = {
        name: null,
        description: null,
        value: null,
        asset_type: null,
      };
    },
    onClose() {
      window.location.href = "/designer/environment-variables";
    },
    onUpdate() {
      this.resetErrors();
      const payload = {
        name: this.formData.name,
        description: this.formData.description,
        asset_type: this.formData.asset_type,
        value: this.formData.value,
        do_not_update: this.formData.do_not_update,
      };
      ProcessMaker.apiClient.put(`environment_variables/${this.formData.id}`, payload)
        .then(() => {
          ProcessMaker.alert(this.$t("The environment variable was saved."), "success");
          this.onClose();
        })
        .catch((error) => {
          if (error.response && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
  },
});
