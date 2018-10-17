import Vue from 'vue';
import GroupsListing from './components/GroupsListing';

new Vue({
    el: '#listGroups',
    data: {
        filter: '',
        groupUid: null,
        groupModal: false,
        labels: {
            panel: 'Create New Group',
            name: 'Name',
            status: 'Status'
        }
    },
    components: {
        GroupsListing,
        ModalGroup
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
