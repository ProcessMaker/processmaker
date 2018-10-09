import Vue from 'vue';
import formUser from './components/fields-users';

new Vue({
    el: '#users-edit',
    data: {
        filter: '',
        userUuid: null,
        userModal: false
    },
    components: {
        formUser,
    },
    methods: {
        show() {
            this.userUuid = null;
            this.userModal = true;
        },
        reload() {
            this.$refs.listing.dataManager([
                {
                    field: 'updated_at',
                    direction: 'desc'
                }
            ]);
        }
    }
});