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

        <div class="form-group">
            <label>{{labels.groups}}</label>
            <multiselect v-model="value" :options="dataGroups" :multiple="true" track-by="name" :custom-label="customLabel"
                    label="name">

                <template slot="tag" slot-scope="props">
                      <span class="multiselect__tag  d-flex align-items-center" style="width:max-content;">
                            <img class="option__image mr-1" :src="props.option.img" alt="Check it">
                            <span class="option__desc mr-1">{{ props.option.name }}
                              <span class="option__title">{{ props.option.desc }}</span>
                            </span>
                        <i aria-hidden="true" tabindex="1" @click="props.remove(props.option)"
                           class="multiselect__tag-icon"></i>
                      </span>
                </template>

                <template slot="option" slot-scope="props">
                    <div class="option__desc d-flex align-items-center">
                        <img class="option__image mr-1" :src="props.option.img" alt="options">
                        <span class="option__title mr-1">{{ props.option.name }}</span>
                        <span class="option__small">{{ props.option.desc }}</span>
                    </div>
                </template>
            </multiselect>
        </div>
    </div>

</template>

<script>
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";
    import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
    import Multiselect from "vue-multiselect/src/Multiselect";

    const emptyUser = {
        id: null,
        username: '',
        firstname: '',
        lastname: '',
        email: '',
        password: '',
        status: 'ACTIVE',
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
        components: {Multiselect, FormSelect, FormInput},
        props: ['inputData', 'inputDataGroups'],
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
                    'groups': 'Groups',
                    'helper': 'User Name must be distinct'
                },
                'value' : '',
                'dataGroups': [],
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
            this.fillData(this.inputData);
            this.fillDataGroups(this.inputDataGroups);
        },
        methods: {
            customLabel(option) {
                return ` ${option.img} ${option.name} ${option.desc}`
            },
            reset() {
                this.formData = Object.assign({}, {
                    id: null,
                    username: '',
                    firstname: '',
                    lastname: '',
                    email: '',
                    password: '',
                    memberships: ''

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
                return !!this.formData.id
            },
            fillData(data) {
                if (data && data.id) {
                    let that = this;
                    $.each(that.formData, function (value) {
                        if (that.inputData.hasOwnProperty(value)) {
                            that.formData[value] = data[value];
                        }
                    });
                }
            },
            fillDataGroups(data) {
                let that = this;
                that.dataGroups = [];
                let values = [];
                $.each(data, function (key, value) {
                    let option = value;
                    option.img = '/img/avatar-placeholder.gif';
                    option.desc = ' ';
                    that.dataGroups.push(option);
                    //fill groups selected
                    if (that.inputData && that.inputData.hasOwnProperty('memberships')) {
                        $.each(that.inputData.memberships, function (keyMember, dataMember) {
                            if (dataMember.group_id === option.id) {
                                values.push(option);
                            }
                        });
                    }
                });
                that.value = values;
            },
            request() {
                return this.isEditing() ? ProcessMaker.apiClient.put : ProcessMaker.apiClient.post;
            },
            savePath() {
                return this.isEditing() ? 'users/' + this.formData.id : 'users';
            },
            onClose() {
                this.$emit('close');
            },
            saveGroups(id, update) {

                let that = this;
                //Remove member that has previously registered and is not in the post data.
                if (update && that.inputData && that.inputData.hasOwnProperty('memberships') && that.inputData.memberships) {
                    $.each(that.inputData.memberships, function (keyMember, dataMember) {
                        let deleteMember = true;
                        $.each(that.value, function (key, group) {
                            if (dataMember.group_id === group.id) {
                                deleteMember = false;
                                return false;
                            }
                        });
                        if(deleteMember) {
                            ProcessMaker.apiClient
                                .delete('group_members/'+ dataMember.id);
                        }
                    });
                }
                //Add member who were not previously registered.
                $.each(that.value, function (key, group) {
                    let save = true;
                    if (that.inputData && that.inputData.hasOwnProperty('memberships') && that.inputData.memberships) {
                        $.each(that.inputData.memberships, function (keyMember, dataMember) {
                            if (dataMember.group_id === group.id) {
                                save = false;
                                return false;
                            }
                        });
                    }
                    if (save) {
                        ProcessMaker.apiClient
                            .post('group_members', {
                                    'group_id': group.id,
                                    'member_type': 'ProcessMaker\\Models\\User',
                                    'member_id': id
                                }
                            );
                    }
                });

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
                        this.saveGroups(this.formData.id, true);
                        this.$emit('update');
                    } else {
                        this.saveGroups(response.data.id, false);
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

    .multiselect__element span img {
        border-radius: 50%;
        height: 20px;
    }

    .multiselect__tags-wrap {
        display: flex !important;
    }

    .multiselect__tags-wrap img {
        height: 15px;
        border-radius: 50%;
    }

    .multiselect__tag-icon:after {
        color: white !important;
    }

    .multiselect__option--highlight {
        background: #00bf9c !important;
    }

    .multiselect__option--selected.multiselect__option--highlight {
        background: #00bf9c !important;
    }

    .multiselect__tags {
        border: 1px solid #b6bfc6 !important;
        border-radius: 0.125em !important;
        height: calc(1.875rem + 2px) !important;
    }

    .multiselect__tag {
        background: #788793 !important;
    }

    .multiselect__tag-icon:after {
        color: white !important;
    }
</style>
