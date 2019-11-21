<template>
    <b-modal id="addUserModal" hide-footer>
        <slot name="modal-title"></slot>
        <slot name="default"></slot>
        <slot name="modal-footer"></slot>
    </b-modal>
</template>

<script>
export default {
    data() {
        return {
            username: '',
            email: '',
            disabled: false,
        }
    },
    methods: {
        showAddUserModal() {
            this.$bvModal.show('addUserModal');
        },
        hideAddUserModal() {
            this.$bvModal.hide('addUserModal');
        },
        showMsgBox(message, restoreData) {
            this.$bvModal.msgBoxConfirm([message], {
                title: this.$t('Deleted User Found'),
                centered: true,
                okVariant: 'secondary',
                okTitle: this.$t('Yes'),
                cancelVariant: 'outline-secondary'
            }).then(value => {
                if (value) {
                    ProcessMaker.apiClient.put('/users/restore', restoreData).then(response => {
                        ProcessMaker.alert(this.$t("The user was restored."), "success");
                        location.reload();
                    }).catch(error => {
                        ProcessMaker.alert(error, "danger");
                    });
                } else  {
                    this.$bvModal.show('addUserModal');
                }
            }).catch(err => {
                ProcessMaker.alert(err, "danger");
            });
        },
        onSubmit(value) {
            this.username = value.username;
            this.email = value.email;
            //single click
            if (this.disabled) {
                return
            }
            this.disabled = true;
            ProcessMaker.apiClient.post("/users", {
                username: value.username,
                firstname: value.firstname,
                lastname: value.lastname,
                title: value.title,
                status: value.status,
                email: value.email,
                password: value.password
            }).then(function (response) {
                window.location = "/admin/users/" + response.data.id + '/edit?created=true'
            }).catch(error => {
                this.addError = error.response.data.errors;
                this.$root.$emit('updateErrors', error.response.data.errors);
                this.disabled = false;

                let deletedUserExists = false;
                for (let key of ['username', 'email']) {
                    if (key in this.addError ) {
                        let message = this.addError[key].find(m => m.startsWith('A user with the'));
                        if (message) {
                            deletedUserExists = message;
                        }
                    }
                }

                if (deletedUserExists) {
                    let h = this.$createElement;
                    let messageVNode = h('p', {}, [
                        deletedUserExists + ' ' + this.$t('Would you like to save and reactivate their account?')
                    ]);
                    let restoreData = {
                        email: this.email
                    };
                    this.showMsgBox(messageVNode, restoreData);
                }
            });
    
        }
    },
}
</script>