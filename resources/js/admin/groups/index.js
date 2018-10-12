import Vue from 'vue';
import GroupsListing from './components/GroupsListing';
import ModalCreateGroup from "./components/modal-group-add";

new Vue({
    el: '#groupIndex',
    data: {
        filter: '',
        groupModal: false
    },
    components: {
        GroupsListing,
        ModalCreateGroup,
    },
    methods: {
        show() {
            this.groupModal = true;
        },
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
