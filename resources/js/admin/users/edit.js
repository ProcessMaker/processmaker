import Vue from "vue";
import formEditUser from "./components/fields-users";

new Vue({
    el: "#users-edit",
    data: {},
    components: {
        formEditUser
    },
    methods: {
        onClose () {
            window.location.href = "/admin/users";
        },
        onSave () {
            this.$refs.formEditUser.onSave();
        },
        afterUpdate () {
            ProcessMaker.alert("Update User Successfully", "success");
            this.onClose();
        }
    }
});
