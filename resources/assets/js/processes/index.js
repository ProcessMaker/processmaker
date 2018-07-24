import Vue from 'vue'
import ProcessesListing from './components/ProcessesListing'
import ModalCreateProcess from "./components/modal/modal-process-add.vue";

new Vue({
    el: '#processes-listing',
    data: {
        filter: ''
    },
    components: {
        ProcessesListing,
        ModalCreateProcess
    },
    methods: {
        openModalProcess() {
            this.$refs.modalProcess.onShow();
        }
    }
});
