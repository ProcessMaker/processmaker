import Vue from 'vue'
import CategoriesListing from './components/CategoriesListing'

new Vue({
    el: '#process-categories-listing',
    data: {
        filter: '',
    },
    components: {
        CategoriesListing,
    }
});
