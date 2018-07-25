<template>
    <b-modal id="createProcess" ref="modal" size="md" centered @hidden="onHidden" title="Create New Process" v-cloak>
        <b-alert dismissable :show="validationError != null" variant="danger">@{{validationError}}</b-alert>
        <form-input :error="errors.name" v-model="name" label="Title" helper="Process Name must be distinct"
                    required="required"></form-input>
        <form-text-area :error="errors.description" :rows="3" v-model="description" label="Description"></form-text-area>
        <form-select :error="errors.process_category_id" label="Category" name="category" v-model="categorySelect"
                     :options="categorySelectOptions"></form-select>

        <template slot="modal-footer">
            <b-button @click="onCancel" class="btn-outline-secondary btn-md">
                CANCEL
            </b-button>
            <b-button @click="onSave" class="btn-secondary text-light btn-md">
                SAVE
            </b-button>
        </template>

    </b-modal>
</template>

<script>
    import FormTextArea from "@processmaker/vue-form-elements/src/components/FormTextArea";
    import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";

    export default {
        components: {FormInput, FormSelect, FormTextArea},
        data() {
            return {
                'name': '',
                'description': '',
                'categorySelect': null,
                'categorySelectOptions': [],
                'validationError': null,
                'errors': {
                    'name': null,
                    'description': null,
                    'process_category_id': null,
                }
            }
        },
        methods: {
            onHidden() {
                this.$emit('hidden')
            },
            onCancel() {
                this.$refs.modal.hide()
            },
            reset() {
                this.name = '';
                this.description = '';
                this.categorySelect = null;
                this.validationError = null;
                this.errors.name = null;
                this.errors.description = null;
                this.errors.process_category_id = null;
            },
            onShow() {
                //load Process Categories
                window.ProcessMaker.apiClient.get('categories')
                    .then((response) => {

                        let options = [
                            {
                                value: null, content: 'None'
                            }
                        ];
                        response.data.map(function (category) {
                            options.push({
                                value: category.cat_uid,
                                content: category.cat_name
                            })
                        });
                        this.categorySelectOptions = options;

                        this.reset();
                        this.$refs.modal.show()
                    })
                    .catch((error) => {
                        this.validationError = error;
                        if (error.response.status === 422) {
                            this.validationError = error.response.data.message;
                        }
                    })
            },
            onSave() {
                ProcessMaker.apiClient
                    .post(
                        'processes/create',
                        {
                            name: this.name,
                            description: this.description,
                            category_uid: this.categorySelect
                        }
                    )
                    .then(response => {
                        ProcessMaker.alert('New Process Successfully Created', 'success');
                        this.$refs.modal.hide();
                        if (response.data && response.data.uid) {
                            //Change way to open the designer
                            window.location.href = '/designer/' + response.data.uid;
                        }
                    })
                    .catch(error => {
                        //define how display errors
                        if (error.response.status === 422) {
                            // Validation error
                            this.validationError = error.response.data.message;
                            let fields = Object.keys(error.response.data.errors);
                            for (var field of fields) {
                                this.errors[field] = error.response.data.errors[field][0];

                            }
                        }
                    });
            }
        },
        mounted() {
            this.$refs.modal.hide();
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
