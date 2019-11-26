import Vue from "vue";
import CategoriesListing from "./components/CategoriesListing";

ProcessMaker.CategoriesIndex = new Vue({
  el: "#categories-listing",
  data: {
    filter: "",
    formData: null
  },
  components: {
    CategoriesListing
  },
  methods: {
    reload () {
      this.$refs.list.fetch();
    }
  }
});
