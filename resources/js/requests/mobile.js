import Vue from "vue";
import MobileRequests from "./components/MobileRequests.vue";
import FilterMobile from "../Mobile/FilterMobile.vue";
import FilterMixin from "../Mobile/FilterMixin";

new Vue({
  el: "#requests-mobile",
  mixins: [FilterMixin],
  components: { MobileRequests, FilterMobile },
  data: {
    filter: "",
    pmql: "",
    status: [],
    fullPmql: "",
  },
  methods: {
    
  },
});
