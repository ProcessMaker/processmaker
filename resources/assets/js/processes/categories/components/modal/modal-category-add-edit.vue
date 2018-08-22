<template>
    <b-modal ref="modal" size="md" centered @hidden="reset" :title="title" v-cloak>
        <form-input :error="errors.name" v-model="formData.cat_name" label="Category Name" helper="Category Name must be distinct"
                    required="required"></form-input>
        <form-select label="Status" name="status" v-model="formData.cat_status"
                     :options="statusSelectOptions"></form-select>

        <div slot="modal-footer">
            <b-button @click="close" class="btn btn-outline-success btn-sm text-uppercase">
                CANCEL
            </b-button>
            <b-button @click="save" class="btn btn-success btn-sm text-uppercase">
                SAVE
            </b-button>
        </div>
    </b-modal>
</template>

<script>
    import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";

    const newData = {
        cat_uid: null,
        cat_name: '',
        cat_status: 'ACTIVE',
        edit: false,
    };

    export default {
        components: {FormInput, FormSelect},
        props: ['inputData'],
        data() {
            return {
                'title': '',
                'statusSelectOptions': [
                    { value: 'ACTIVE', content: 'Active' },
                    { value: 'INACTIVE', content: 'Inactive' },
                ],
                'errors': {
                    'name': null,
                },
                'formData': { },
                'validator': null,
            }
        },
        mounted() {
            this.reset();
        },
        watch: {
            inputData(data) {
                this.formData = Object.assign(data, { edit: true })
                this.setTitle();
            }
        },
        methods: {
            close() {
                this.$refs.modal.hide();
            },
            reset() {
                this.formData = Object.assign({}, newData);
                this.errors.name = null;
                this.setTitle();
            },
            setTitle() {
                this.title = this.isEditing() ? 'Edit Category' : 'Add Category'
            },
            isEditing() {
                return this.formData.edit
            },
            request() {
                console.log("Is Editing is:", this.isEditing())
                return this.isEditing() ? ProcessMaker.apiClient.put : ProcessMaker.apiClient.post;
            },
            save() {
                this.request()(
                        'category', {
                            uid: this.formData.cat_uid,
                            name: this.formData.cat_name,
                            status: this.formData.cat_status,
                        }
                    )
                    .then(response => {
                        ProcessMaker.alert('New Category Successfully Created', 'success');
                        this.$emit('reload')
                        this.close();
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
                        console.log(this.errors.name);
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
