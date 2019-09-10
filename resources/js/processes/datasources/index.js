import Vue from "vue";
import DatasourceList from "./components/ListDatasource";
import CategorySelect from "../categories/components/CategorySelect";
import CategoriesListing from "../categories/components/CategoriesListing";

Vue.component("datasource-list", DatasourceList);
Vue.component("category-select", CategorySelect);
Vue.component("categories-listing", CategoriesListing);

new Vue({
    el: "#datasourceIndex",
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
