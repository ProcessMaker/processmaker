import Vue from "vue";
import MobileTasks from "./components/MobileTasks.vue";
import FilterMobile from "../Mobile/FilterMobile.vue";
import FilterMixin from "../Mobile/FilterMixin";

new Vue({
  el: "#tasks-mobile",
  components: { MobileTasks, FilterMobile },
  mixins: [FilterMixin],
  data: {
    filter: "",
    pmql: "",
    status: [],
    fullPmql: "",
  },
});
