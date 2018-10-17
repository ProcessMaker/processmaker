import Vue from 'vue';
import GroupsListing from './components/GroupsListing';

new Vue({
    el: '#listGroups',
    data: {
        filter: '',
        groupUid: null,
        labels: {
            panel: 'Create New Group',
            name: 'Name',
            status: 'Status'
        }
    },
    components: {
        GroupsListing,
    },
    methods: {
        reload() {
            // Status is 200, so let's just change our sort and sort direction and then fetch
            this.$refs.groupsListing.dataManager([{
                field: 'created_at',
                direction: 'desc'
            }]);
        }
    }
});