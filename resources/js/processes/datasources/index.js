import Vue from "vue";
import DatasourceList from "./components/ListDatasource.vue";
import CategorySelect from "../categories/components/CategorySelect.vue";
import CategoriesListing from "../categories/components/CategoriesListing.vue";

Vue.component("datasource-list", DatasourceList);
Vue.component("category-select", CategorySelect);
Vue.component("categories-listing", CategoriesListing);

new Vue({
  el: "#categorizedList",
  data: {
    filter: "",
    processModal: false,
    dataSourceId: null
  },
  methods: {
    reload () {
      this.$refs.datasourceList.dataManager([{
        field: "updated_at",
        direction: "desc"
      }]);
    }
  }
});
