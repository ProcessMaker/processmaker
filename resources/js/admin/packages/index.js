import Vue from "vue";
import PackagesListing from "./components/PackagesListing";

new Vue({
    el: "#packages-listing",
    data: {
        filter: ""
    },
    components: {PackagesListing},
    methods: {
        reload () {
            this.$refs.listing.dataManager([{
                field: "updated_at",
                direction: "desc"
            }]);
        }
    }
});
