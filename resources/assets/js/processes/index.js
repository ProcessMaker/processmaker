import Vue from 'vue'
import ProcessesListing from './components/ProcessesListing'
import ModalCreateProcess from "./components/modal/modal-process-add.vue";

new Vue({
    el: '#processes-listing',
    data: {
        filter: '',
        processModal: false
    },
    components: {
        ProcessesListing,
        ModalCreateProcess
    }
});
