import Vue from 'vue';
import GroupEdit from './components/fields-group';
new Vue({
    el: '#group-edit',
    data: {},
    components: {
        GroupEdit,
    },
    methods: {
        onClose() {
            window.location.href = '/admin/groups';
        },
        onSave() {
            this.$refs.groupEdit.onSave();
        },
        afterUpdate() {
            ProcessMaker.alert('Update Group Successfully', 'success');
            this.onClose();
        }
    }
});
