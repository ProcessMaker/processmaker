import Vue from 'vue'
import RequestsListing from './components/RequestsListing'

new Vue({
    data: {
        filter: ''
    },
    el: '#requests-listing',
    components: {RequestsListing},
    methods: {
        reload(value) {
            console.log(value);
            this.$refs.requestList.additionalParams = value ? '&include=assigned,' + value : '&include=assigned';
            this.$refs.requestList.dataManager([
                {
                    field: 'updated_at',
                    direction: 'desc'
                }
            ]);
        }
    }
});
