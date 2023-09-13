import Vue from "vue";
import NotificationsList from "./components/NotificationsList";

new Vue({
  el: "#notifications",
  components: { NotificationsList },
  data() {
    return {
      filter: "",
      filterComments: null,
    }
  },
  methods: {
    setFilterComments(filter) {
      this.filterComments = filter;
    }
  }
});
