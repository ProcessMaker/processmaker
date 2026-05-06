import CasesRetentionLogs from "./components/CasesRetentionLogs.vue";

// Use window.Vue from bootstrap (has vuetable, i18n, etc.)
// eslint-disable-next-line no-unused-vars -- Vue app mounted for side effect
const casesRetentionApp = new window.Vue({
  el: "#casesRetentionIndex",
  components: { CasesRetentionLogs },
  data() {
    return {
      filter: "",
    };
  },
  methods: {
    downloadRetentionLogs() {
      const params = new URLSearchParams();
      if (this.filter) {
        params.set("filter", this.filter);
      }
      const qs = params.toString();
      const path = qs ? `cases-retention/logs/export?${qs}` : "cases-retention/logs/export";

      ProcessMaker.apiClient
        .get(path)
        .then((response) => {
          if (response.data.success) {
            ProcessMaker.alert(response.data.message, "success");
          } else {
            ProcessMaker.alert(
              response.data.message || "Unable to start export.",
              "danger",
            );
          }
        })
        .catch(() => {
          ProcessMaker.alert("Unable to download logs.", "danger");
        });
    },
    reload() {
      this.$refs.casesRetentionLogs.reload();
    },
  },
});
