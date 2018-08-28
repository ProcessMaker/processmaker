<template>
    <b-modal v-model="opened" size="md" centered @hidden="onClose" @show="onReset" @close="onClose" title="Create New Process" v-cloak>
        <form-input :error="errors.name" v-model="name" label="Title" helper="Process Name must be distinct"
                    required="required"></form-input>
        <form-text-area :error="errors.description" :rows="3" v-model="description"
                        label="Description"></form-text-area>
        <form-select :error="errors.process_category_id" label="Category" name="category" v-model="categorySelect"
                     :options="categorySelectOptions"></form-select>

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
    import FormTextArea from "@processmaker/vue-form-elements/src/components/FormTextArea";
    import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";

    export default {
        components: {FormInput, FormSelect, FormTextArea},
        props: ['show'],
        data() {
            return {
                'name': '',
                'description': '',
                'categorySelect': null,
                'categorySelectOptions': [{value:'', content:''}],
                'errors': {
                    'name': null,
                    'description': null,
                    'process_category_id': null,
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
                this.description = '';
                this.categorySelect = null;
                this.errors.name = null;
                this.errors.description = null;
                this.errors.process_category_id = null;
                this.loadCategories();
            },
            loadCategories() {
                window.ProcessMaker.apiClient.get('categories?per_page=1000&status=ACTIVE')
                    .then((response) => {
                        let options = [
                            {
                                value: null, content: 'None'
                            }
                        ];
                        response.data.data.map(function (category) {
                            options.push({
                                value: category.uid,
                                content: category.name
                            })
                        });
                        this.categorySelectOptions = options;
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
                        this.onClose();
                        if (response.data && response.data.uid) {
                            //Change way to open the designer
                            window.location.href = '/designer/' + response.data.uid;
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
