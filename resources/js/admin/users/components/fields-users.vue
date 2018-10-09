<template>
    <div>
        <form-input :error="errors.username" v-model="formData.username" :label="labels.username"
                    required="required" :helper="labels.helper"></form-input>
        <form-input :error="errors.firstname" v-model="formData.firstname" :label="labels.firstname"
                    required="required"></form-input>
        <form-input :error="errors.lastname" v-model="formData.lastname" :label="labels.lastname"
                    required="required"></form-input>
        <form-input :error="errors.email" type="email" v-model="formData.email" :label="labels.email"
                    required="required"></form-input>
        <form-select v-if="isEditing()" :error="errors.status" :label="labels.status" v-model="formData.status"
                     :options="statusOptions">
        </form-select>
        <form-input :error="errors.password" type="password" v-model="formData.password"
                    :label="labels.password" required="required"></form-input>
        <form-input :error="passwordMismatch" type="password" v-model="confirmation"
                    :label="labels.confirm" required="required"></form-input>
    </div>

</template>

<script>
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";
    import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";

    const emptyUser = {
        uuid: null,
        username: '',
        firstname: '',
        lastname: '',
        email: '',
        password: '',
        status: 'ACTIVE'
    };

    const emptyErrors = {
        username: null,
        firstname: null,
        lastname: null,
        email: null,
        password: null,
        status: null
    };

    export default {
        components: {FormSelect, FormInput},
        props: ['inputData'],
        data() {
            return {
                'confirmation': '',
                'statusOptions': [
                    {value: 'ACTIVE', content: 'Active'},
                    {value: 'INACTIVE', content: 'Inactive'}
                ],
                'labels': {
                    'username': 'Username',
                    'firstname': 'First Name',
                    'lastname': 'Last Name',
                    'email': 'Email Address',
                    'status': 'Status',
                    'password': 'Password',
                    'confirm': 'Confirm Password',
                    'helper': 'User Name must be distinct'
                },
                'formData': emptyUser,
                'errors': emptyErrors
            }
        },
        watch: {
            inputData(user) {
                this.reset();
                this.fillData(user);
            }
        },
        computed: {
            passwordMismatch() {
                if (this.formData.password !== this.confirmation) {
                    return 'Confirmation password must match'
                } else {
                    return null
                }
            },
        },
        mounted() {
            this.fillData(this.inputData)
        },
        methods: {
            reset() {
                this.formData = Object.assign({}, {
                    uuid: null,
                    username: '',
                    firstname: '',
                    lastname: '',
                    email: '',
                    password: ''
                });
                this.errors = Object.assign({}, {
                    username: null,
                    firstname: null,
                    lastname: null,
                    email: null,
                    password: null
                });
            },
            isEditing() {
                return !!this.formData.uuid
            },
            fillData(data) {
                if (data && data.uuid) {
                    let that = this;
                    $.each(that.formData, function (value) {
                        if (that.inputData.hasOwnProperty(value)) {
                            that.formData[value] = data[value];
                        }
                    });
                }
            },
            request() {
                return this.isEditing() ? ProcessMaker.apiClient.put : ProcessMaker.apiClient.post;
            },
            savePath() {
                return this.isEditing() ? 'users/' + this.formData.uuid : 'users';
            },
            onClose() {
                this.$emit('close');
            },
            onSave() {
                this.errors = Object.assign({}, emptyErrors);
                if (this.formData.password && (this.formData.password.trim() !== '' || this.confirmation.trim() !== '')) {
                    if (this.formData.password.trim() === '') {
                        this.errors.password = "Field required";
                        return
                    }
                    if (this.passwordMismatch) {
                        this.errors.password = "You must correct the data before continuing";
                        return
                    }
                    this.errors.password = null;
                } else {
                    delete this.formData.password;
                }

                this.request()(
                    this.savePath(), this.formData
                ).then(response => {
                    if (this.isEditing()) {
                        this.$emit('update');
                    } else {
                        this.$emit('save');
                    }
                })
                    .catch(error => {
                        //define how display errors
                        if (error.response.status === 422) {
                            // Validation error
                            let fields = Object.keys(error.response.data.errors);
                            for (let field of fields) {
                                this.errors[field] = error.response.data.errors[field][0];
                            }
                        }
                    });
            }
        }
    };
</script>
<style lang="scss" scoped>
    .inline-input {
        margin-right: 6px;
    }

    .inline-button {
        background-color: rgb(109, 124, 136);
        font-weight: 100;
    }

    .input-and-select {
        width: 212px;
    }
</style>
