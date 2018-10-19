import Vue from 'vue'
import RequestsListing from './components/RequestsListing'

new Vue({
    data: {
        filter: '',
        title: 'All Request'
    },
    el: '#requests-listing',
    components: {RequestsListing},
    methods: {
        reload(value) {
            this.title = 'All Request';
            switch (value) {
                case 'started_me':
                    this.title = 'Started by Me';
                    break;
                case 'in_progress':
                    this.title = 'In Progress';
                    break;
                case 'completed':
                    this.title = 'Completed';
                    break;
            }

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
