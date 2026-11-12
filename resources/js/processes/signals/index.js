import Vue from "vue";
import SignalsListing from "./components/SignalListing.vue";

new Vue({
  el: "#listSignals",
  components: {
    SignalsListing,
  },
  data() {
    return {
      filter: "",
      formData: {},
      errors: {
        name: null,
        id: null,
      },
      disabled: false,
    };
  },
  mounted() {
    this.resetFormData();
    this.resetErrors();
  },
  methods: {
    onClose() {
      this.resetFormData();
      this.resetErrors();
    },
    resetFormData() {
      this.formData = Object.assign({}, {
        name: null,
        id: null,
      });
    },
    resetErrors() {
      this.errors = Object.assign({}, {
        name: null,
        id: null,
      });
    },
    reload() {
      this.$refs.signalList.dataManager([
        {
          field: "name",
          direction: "desc",
        },
      ]);
    },
    onSubmit() {
      this.resetErrors();
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      ProcessMaker.apiClient.post("signals", this.formData)
        .then(() => {
          ProcessMaker.alert(this.$t("The signal was created."), "success");
          window.location = "/designer/signals";
        })
        .catch((error) => {
          this.disabled = false;
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
  },
});
