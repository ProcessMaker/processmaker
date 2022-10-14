import Vue from "vue";
import NotificationsList from "./components/NotificationsList";

new Vue({
  el: "#notifications",
  components: { NotificationsList },
  data: {
    filter: "",
  },
});
