import Vue from "vue";
import SignalsListing from "./components/SignalListing";

new Vue({
    el: "#listSignals",
    data: {
        filter: ""
    },
    components: {SignalsListing},
    methods: {
        reload () {
            this.$refs.signalList.dataManager([
                {
                    field: "name",
                    direction: "desc"
                }
            ]);
        }
    }
});
