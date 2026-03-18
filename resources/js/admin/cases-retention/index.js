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
      console.log("downloadRetentionLogs");
    },
    reload() {
      this.$refs.casesRetentionLogs.reload();
    },
  },
});
