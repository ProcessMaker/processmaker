import Vue from 'vue'
import CategoriesListing from './components/CategoriesListing'
import ModalCategoryAddEdit from "./components/modal/modal-category-add-edit";

new Vue({
    el: '#process-categories-listing',
    data: {
        filter: '',
        categoryModal: false,
    },
    components: {
        CategoriesListing,
        ModalCategoryAddEdit,
    }
});
