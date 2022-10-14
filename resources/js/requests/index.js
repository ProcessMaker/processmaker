import Vue from "vue";
import CounterCard from "./components/CounterCard";
import CounterCardGroup from "./components/CounterCardGroup";
import RequestsListing from "./components/RequestsListing";
import AdvancedSearch from "../components/AdvancedSearch";
import AvatarImage from "../components/AvatarImage";

Vue.component("AvatarImage", AvatarImage);

new Vue({
  el: "#requests-listing",
  components: {
    CounterCard, CounterCardGroup, RequestsListing, AdvancedSearch,
  },
  data: {
    filter: "",
    pmql: "",
    status: [],
    requester: [],
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
  },
  methods: {
    onChange(query) {
      this.pmql = query;
    },
    onSearch() {
      this.$refs.requestList.fetch(null, true);
    },
  },
});
