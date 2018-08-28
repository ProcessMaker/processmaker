import Vue from 'vue'
import ProcessesListing from './components/ProcessesListing'
import ModalCreateProcess from "./components/modal/modal-process-add.vue";

new Vue({
    el: '#processes-listing',
    data: {
        filter: '',
        processUid: null,
        processModal: false,
        labels: {
            panel: 'Create New Process',
            title: 'Title',
            description: 'Description',
            category: 'Category'
        }
    },
    components: {
        ProcessesListing,
        ModalCreateProcess
    },
    methods: {
        showModal() {
            this.labels.panel = 'Create New Process';
            this.processUid = null;
            this.processModal = true;
        },
        edit(uid) {
            this.labels.panel = 'Edit Process';
            this.processUid = uid;
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
