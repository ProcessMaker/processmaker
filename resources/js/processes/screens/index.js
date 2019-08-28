import Vue from 'vue'
import ScreenListing from './components/ScreenListing'
import CategorySelect from "../categories/components/CategorySelect";

Vue.component('category-select', CategorySelect);

new Vue({
    el: '#screenIndex',
    data: {
        filter: '',
        screenModal: false,
        screenId: null
    },
    components: {
        ScreenListing,
    },
    methods: {
        goToImport() {
          window.location = 'screens/import';
        },
        show() {
            this.screenId = null;
            this.screenModal = true;
        },
        reload() {
            this.$refs.screenListing.dataManager([
                {
                    field: 'updated_at',
                    direction: 'desc'
                }
            ]);
        }
    }
});
