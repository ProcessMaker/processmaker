<template>
    <b-modal v-model="opened" size="md" centered @hidden="onClose" @show="onReset" @close="onClose" :title="title" v-cloak>
        <form-input :error="errors.name" v-model="name" label="Category Name" helper="Category Name must be distinct"
                    required="required"></form-input>
        <form-select label="Status" name="status" v-model="statusSelect"
                     :options="statusSelectOptions"></form-select>

        <div slot="modal-footer">
            <b-button @click="onClose" class="btn btn-outline-success btn-sm text-uppercase">
                CANCEL
            </b-button>
            <b-button @click="onSave" class="btn btn-success btn-sm text-uppercase">
                SAVE
            </b-button>
        </div>

    </b-modal>
</template>

<script>
    import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";

    export default {
        components: {FormInput, FormSelect},
        props: ['show'],
        data() {
            return {
                'title': 'Add New Category',
                'name': '',
                'statusSelect': null,
                'statusSelectOptions': [
                    { value: 'active', content: 'Active' },
                    { value: 'inactive', content: 'Inactive' },
                ],
                'errors': {
                    'name': null,
                },
                'opened': this.show
            }
        },
        watch: {
            show(value) {
                this.opened = value;
            }
        },
        methods: {
            onHidden() {
                this.$emit('hidden')
            },
            onClose() {
                this.$emit('close');
            },
            onReset() {
                this.name = '';
                this.categorySelect = null;
                this.errors.name = null;
            },
            onSave() {
                ProcessMaker.apiClient
                    .post(
                        'categories',
                        {
                            name: this.name,
                            status: this.status,
                        }
                    )
                    .then(response => {
                        ProcessMaker.alert('New Category Successfully Created', 'success');
                        this.onClose();
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
