<template>
    <b-modal ref="modal" size="md" centered @hidden="reset" :title="title" v-cloak>
        <form-input :error="errors.name" v-model="formData.name" label="Category Name" helper="Category Name must be distinct"
                    required="required"></form-input>
        <form-select label="Status" name="status" v-model="formData.status"
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
        id: null,
        name: '',
        status: 'ACTIVE',
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
                return this.isEditing() ? ProcessMaker.apiClient.put : ProcessMaker.apiClient.post;
            },
            savePath() {
                return this.isEditing() ? 'process_categories/' + this.formData.id : 'process_categories';
            },
            save() {
                this.request()(
                        this.savePath(), {
                            id: this.formData.id,
                            name: this.formData.name,
                            status: this.formData.status,
                        }
                    )
                    .then(response => {
                        if (this.isEditing()) {
                            ProcessMaker.alert('Category Updated Successfully', 'success');
                        } else {
                            ProcessMaker.alert('New Category Successfully Created', 'success');
                        }
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
