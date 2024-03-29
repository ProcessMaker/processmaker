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
      console.log("shouldShowLoader", this.apiDataLoading, this.apiNoResults);
      return this.apiDataLoading || this.apiNoResults;
    },
  },
  mounted() {
    ProcessMaker.EventBus.$on("api-data-loading", (val) => {
      this.apiDataLoading = val;
    });
    ProcessMaker.EventBus.$on("api-data-no-results", (val) => {
      this.apiNoResults = val;
    });
  },
};
