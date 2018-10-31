import Vue from 'vue'
import ScreenListing from './components/ScreenListing'

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
