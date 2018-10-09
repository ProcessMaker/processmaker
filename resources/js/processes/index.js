import Vue from 'vue'
import ProcessesListing from './components/ProcessesListing'
import ModalCreateProcess from "./components/modal/modal-process-add-edit.vue";

new Vue({
    el: '#processIndex',
    data: {
        filter: '',
        processModal: false,
        processUuid: null
    },
    components: {
        ProcessesListing,
        ModalCreateProcess
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
            this.$refs.processListing.dataManager([
                {
                    field: 'updated_at',
                    direction: 'desc'
                }
            ]);
        }
    }
});
