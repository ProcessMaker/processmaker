import Vue from "vue";
import FormListing from "./components/FormListing";

new Vue({
    el: "#formIndex",
    data: {
        filter: ""
    },
    components: {
        FormListing
    },
    methods: {
        reload () {
            this.$refs.formListing.dataManager([
                {
                    field: "updated_at",
                    direction: "desc"
                }
            ]);
        }
    }
});
