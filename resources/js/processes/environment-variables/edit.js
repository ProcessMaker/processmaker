import Vue from "vue";
import AssetLinkFields from "./components/AssetLinkFields.vue";

const initial = window.ProcessMaker.EnvironmentVariableEdit || {};

new Vue({
  el: "#editEnvironmentVariable",
  components: {
    AssetLinkFields,
  },
  data() {
    return {
      formData: {
        id: initial.id,
        name: initial.name,
        description: initial.description,
        value: initial.value || null,
        asset_type: initial.asset_type || null,
        asset_uuid: initial.asset_uuid || null,
      },
      errors: {
        name: null,
        description: null,
        value: null,
        asset_type: null,
        asset_uuid: null,
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
        asset_uuid: null,
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
        asset_uuid: this.formData.asset_uuid,
      };
      if (!this.formData.asset_type && this.formData.value) {
        payload.value = this.formData.value;
      }
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
