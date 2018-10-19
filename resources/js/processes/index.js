import Vue from 'vue'
import ProcessesListing from './components/ProcessesListing'

new Vue({
    el: '#processIndex',
    data: {
        filter: '',
        processModal: false,
        processUuid: null
    },
    components: {
        ProcessesListing,
    },
    methods: {
        show() {
            this.processUuid = null;
            this.processModal = true;
        },
        edit(uuid) {
            this.processUuid = uuid;
            this.processModal = true;
        },
        reload() {
            this.$refs.processListing.dataManager([{
                field: 'updated_at',
                direction: 'desc'
            }]);
        }
    }
});