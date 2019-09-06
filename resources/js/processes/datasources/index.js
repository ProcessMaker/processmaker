import Vue from "vue";
import DatasourceList from "./components/ListDatasource";
import CategorySelect from "../categories/components/CategorySelect";

Vue.component("datasource-list", DatasourceList);
Vue.component("category-select", CategorySelect);

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
