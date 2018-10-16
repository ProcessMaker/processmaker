import Vue from 'vue';
import formEdit from './components/fields-form';

new Vue({
    el: '#form-edit',
    data: {},
    components: {
        formEdit,
    },
    methods: {
        onClose() {
            window.location.href = '/processes/forms';
        },
        onSave() {
            this.$refs.formEdit.onSave();
        },
        afterUpdate() {
            ProcessMaker.alert('Update Form Successfully', 'success');
            this.onClose();
        }
    }
});