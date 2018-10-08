<template>
    <b-modal v-model="opened" size="md" centered @hidden="onClose" @show="onReset" @close="onClose"
             :title="labels.title" v-cloak>
        <form-input :error="errors.name" v-model="name" :label="labels.name" :helper="labels.helper"
                    required="required">
        </form-input>
        <form-text-area :error="errors.description" :rows="3" v-model="description"
                        :label="labels.description">
        </form-text-area>
        <form-select :error="errors.status" :label="labels.status" v-model="status"
                     :options="statusOptions">
        </form-select>
        <form-select :error="errors.process_category_uuid" :label="labels.category" name="category"
                     v-model="categorySelect"
                     :options="categorySelectOptions">
        </form-select>

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
        props: ['show', 'processUuid'],
        data() {
            return {
                'name': '',
                'description': '',
                'status': '',
                'categorySelect': null,
                'categorySelectOptions': [{value: '', content: ''}],
                'errors': {
                    'name': null,
                    'description': null,
                    'status': null,
                    'process_category_uuid': null,
                },
                'statusOptions': [
                    {value: 'ACTIVE', content: 'Active'},
                    {value: 'INACTIVE', content: 'Inactive'}
                ],
                'labels': {
                    'title': 'Create New Process',
                    'name': 'Title',
                    'description': 'Description',
                    'status': 'Status',
                    'category': 'Category',
                    'helper': 'Process Name must be distinct'
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
                this.status = 'ACTIVE';
                this.categorySelect = null;
                this.errors.name = null;
                this.errors.description = null;
                this.errors.status = null;
                this.errors.process_category_id = null;
                this.loadCategories();
                this.labels.title = 'Create New Process';
                if (this.processUuid) {
                    this.labels.title = 'Update Process';
                    this.fetch();
                }
            },
            loadCategories() {
                window.ProcessMaker.apiClient.get('process_categories?per_page=1000&status=ACTIVE')
                    .then((response) => {
                        let options = [
                            {
                                value: null, content: 'None'
                            }
                        ];
                        response.data.data.map(function (category) {
                            options.push({
                                value: category.uuid,
                                content: category.name
                            })
                        });
                        this.categorySelectOptions = options;
                    })
            },
            fetch() {
                ProcessMaker.apiClient.get("processes/" + this.processUuid)
                    .then(response => {
                        this.name = response.data.name;
                        this.description = response.data.description;
                        this.categorySelect = response.data.process_category_uuid;
                        this.status = response.data.status;
                    })
            },
            request() {
                return this.processUuid ? ProcessMaker.apiClient.put : ProcessMaker.apiClient.post;
            },
            savePath() {
                return this.processUuid ? 'processes/' + this.processUuid : 'processes';
            },
            onSave() {
                this.request()(
                    this.savePath(), {
                        uuid: this.processUuid,
                        name: this.name,
                        description: this.description,
                        status: this.status,
                        process_category_uuid: this.categorySelect
                    }
                ).then(response => {
                        this.onClose();
                        if (this.processUuid) {
                            ProcessMaker.alert('Update Process Successfully', 'success');
                            this.$emit('reload');
                        } else {
                            ProcessMaker.alert('New Process Successfully Created', 'success');
                            if (response.data && response.data.uuid) {
                                //Change way to open the designer
                                window.location.href = '/designer/' + response.data.uuid;
                            }
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
