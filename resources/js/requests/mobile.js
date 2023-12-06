import Vue from "vue";
import MobileRequests from "./components/MobileRequests.vue";
import FilterMobile from "../Mobile/FilterMobile.vue";
import FilterMixin from "../Mobile/FilterMixin";

new Vue({
  el: "#requests-mobile",
  components: { MobileRequests, FilterMobile },
  mixins: [FilterMixin],
  data: {
    filter: "",
    pmql: "",
    status: [],
    fullPmql: "",
  },
  methods: {

  },
});
