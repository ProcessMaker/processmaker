import Vue from 'vue'
import ProcessesListing from './components/ProcessesListing'

new Vue({
    el: '#processIndex',
    data: {
        filter: '',
        processModal: false,
        processId: null
    },
    components: {
        ProcessesListing,
    },
    methods: {
        show() {
            this.processId = null;
            this.processModal = true;
        },
        edit(id) {
            this.processId = id;
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