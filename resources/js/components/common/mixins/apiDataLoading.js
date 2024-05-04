import DataLoading from "../../../components/common/DataLoading";

export default {
  components: {
    DataLoading,
  },
  data() {
    return {
      apiDataLoading: true,
      apiNoResults: false,
      dataLoadingId: Math.random(),
    };
  },
  computed: {
    shouldShowLoader() {
      return this.apiDataLoading || this.apiNoResults;
    },
  },
  mounted() {
    ProcessMaker.EventBus.$on("api-data-loading", (val, id) => {
      // Restrict the flag to the specified id, but only if an ID
      // was sent. This is used when there are multiple DataLoading
      // components on the page.
      if (typeof id === "undefined" || this.dataLoadingId === id) {
        this.apiDataLoading = val;
      }
    });
    ProcessMaker.EventBus.$on("api-data-no-results", (val, id) => {
      if (typeof id === "undefined" || this.dataLoadingId === id) {
        this.apiNoResults = val;
      }
    });
  },
};
