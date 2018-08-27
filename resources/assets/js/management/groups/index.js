import Vue from 'vue'
import GroupsListing from './components/GroupsListing'
import ModalGroup from './components/modal-group'

// Bootstrap our Designer application
new Vue({
    el: '#groups-listing',
    data: {
        filter: '',
        groupUid: null,
        groupModal: false,
        labels: {
            panel: 'Create New Group',
            title: 'Title',
            status: 'Status'
        }
    },
    components: {GroupsListing, ModalGroup},
    methods: {
        showModal() {
            this.labels.panel = 'Create New Group';
            this.groupUid = null;
            this.groupModal = true;
        },
        edit(uid) {
            this.labels.panel = 'Edit Group';
            this.groupUid = uid;
            this.groupModal = true;
        },
        reload() {
            // Status is 200, so let's just change our sort and sort direction and then fetch
            this.$refs.groupsListing.dataManager([
                {
                    field: 'created_at',
                    direction: 'desc'
                }
            ]);
        }
    }
});


