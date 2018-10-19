import Vue from 'vue';
import GroupsListing from './components/GroupsListing';

new Vue({
    el: '#listGroups',
    data: {
        filter: ''
    },
    components: {GroupsListing},
    methods: {
        reload() {
            this.$refs.groupList.dataManager([
                {
                    field: 'updated_at',
                    direction: 'desc'
                }
            ]);
        }
    }
});
