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
            console.log('deleting', data.cat_uid);
        },
        reload() {
            this.$refs.list.fetch();
        }
    }
});
