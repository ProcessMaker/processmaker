import Vue from 'vue'
import CategoriesListing from './components/CategoriesListing'
import ModalCategoryAddEdit from "./components/modal/modal-category-add-edit";

new Vue({
    el: '#process-categories-listing',
    data: {
        filter: '',
        formData: null,
    },
    components: {
        CategoriesListing,
        ModalCategoryAddEdit,
    },
    methods: {
        editCategory(data) {
            this.formData = Object.assign({}, data);
            this.showModal()
        },
        showModal() {
            this.$refs.addEdit.$refs.modal.show()
        },
        deleteCategory(data) {
            //@todo implement
            ProcessMaker.apiClient.delete('category/' + data.uid)
                .then(response => {
                    ProcessMaker.alert('Category Successfully Deleted', 'success');
                    this.reload();
                })
                .catch(error => {
                    if (error.response.status === 422) {
                        let errors = error.response.data.errors;
                        ProcessMaker.alert(errors.processCategory.join(', '), 'danger');
                    }
                });
        },
        reload() {
            this.$refs.list.fetch();
        }
    }
});
