import Vue from 'vue'
import ProcessesListing from './components/ProcessesListing'
import ModalCreateProcess from "./components/modal/modal-process-add.vue";

new Vue({
    el: '#processIndex',
    data: {
        filter: '',
        processModal: false
    },
    components: {
        ProcessesListing,
        ModalCreateProcess
    }
});
