import Vue from "vue";
import CounterCard from "./components/CounterCard";
import CounterCardGroup from "./components/CounterCardGroup";
import RequestsListing from "./components/RequestsListing";
import AvatarImage from "../components/AvatarImage";

Vue.component("AvatarImage", AvatarImage);

new Vue({
  el: "#requests-listing",
  components: {
    CounterCard, CounterCardGroup, RequestsListing,
  },
  data: {
    filter: "",
    pmql: "",
    urlPmql: "",
    filtersPmql: "",
    fullPmql: "",
    status: [],
    requester: [],
    additions: [],
  },
  created() {
    const params = {};

    switch (Processmaker.status) {
      case "":
        status = "In Progress";
        this.requester.push(Processmaker.user);
        break;
      case "in_progress":
        status = "In Progress";
        break;
      case "completed":
        status = "Completed";
        break;
    }

    if (status) {
      this.status.push({
        name: status,
        value: status,
      });
    }

    // translate status labels when available
    window.ProcessMaker.i18nPromise.then(() => {
      this.status.forEach((item) => {
        item.name = this.$t(item.name);
      });
    });

    const urlParams = new URLSearchParams(window.location.search);
    this.urlPmql = urlParams.get("pmql");
  },
  mounted() {
    ProcessMaker.EventBus.$on('advanced-search-addition', (component) => {
      this.additions.push(component);
    });
  },
  methods: {
    onFiltersPmqlChange(value) {
      this.filtersPmql = value[0];
      this.fullPmql = this.getFullPmql();
      this.onSearch();
    },
    onNLQConversion(query) {
      this.onChange(query);
      this.onSearch();
    },
    onChange(query) {
      this.pmql = query;
      this.fullPmql = this.getFullPmql();
    },
    onSearch() {
      this.$refs.requestList.fetch(null, true);
    },
    getFullPmql() {
      let fullPmqlString = "";

      if (this.filtersPmql && this.filtersPmql !== "") {
        fullPmqlString = this.filtersPmql;
      }

      if (fullPmqlString !== "" && this.pmql && this.pmql !== "") {
        fullPmqlString = `${fullPmqlString} AND ${this.pmql}`;
      }

      if (fullPmqlString === "" && this.pmql && this.pmql !== "") {
        fullPmqlString = this.pmql;
      }

      return fullPmqlString;
    },
  },
});
