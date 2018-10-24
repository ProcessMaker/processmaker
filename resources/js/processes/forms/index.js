import Vue from 'vue'
import FormListing from './components/FormListing'
import ModalCreateForm from "./components/modal/modal-form-add";

new Vue({
    el: '#formIndex',
    data: {
        filter: '',
        formModal: false,
        formId: null
    },
    components: {
        FormListing,
        ModalCreateForm
    },
    methods: {
        show() {
            this.formId = null;
            this.formModal = true;
        },
        reload() {
            this.$refs.formListing.dataManager([
                {
                    field: 'updated_at',
                    direction: 'desc'
                }
            ]);
        }
    }
});
