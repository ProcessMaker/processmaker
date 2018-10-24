<template>
    <div>
        <form-input :error="errors.title" v-model="formData.title" :label="labels.title" :helper="labels.helper">
        </form-input>
        <form-text-area :error="errors.description" :rows="3" v-model="formData.description" :value="formData.description"
                        :label="labels.description" ref="desc">
        </form-text-area>
        <form-select :error="errors.type" :label="labels.type" v-model="formData.type" :options="typeOptions">
        </form-select>
    </div>
</template>

<script>
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";
    import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
    import FormTextArea from "@processmaker/vue-form-elements/src/components/FormTextArea";

    export default {
        components: {FormInput, FormSelect, FormTextArea},
        props: ['inputData'],
        data() {
            return {
                'typeOptions': [
                    {value: 'FORM', content: 'FORM'},
                ],
                'labels': {
                    'title': 'Title',
                    'description': 'Description',
                    'type': 'Type',
                    'helper': 'Form Name must be distinct'
                },
                'formData': {
                    'id': null,
                    'title': '',
                    'description': '',
                    'type': 'FORM'
                },
                'errors': {
                    'title': null,
                    'description': null,
                    'type': null
                }
            }
        },
        watch: {
            inputData(form) {
                this.resetData();
                this.fillData(form);
            }
        },
        computed: {},
        mounted() {
            this.fillData(this.inputData);
        },
        methods: {
            resetData() {
                this.formData = Object.assign({}, {
                    id: null,
                    title: '',
                    description: '',
                    type: 'FORM'
                });
                this.resetErrors();
            },
            resetErrors() {
                this.errors = Object.assign({}, {
                    title: null,
                    description: null,
                    type: null
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
            request() {
                return this.isEditing() ? ProcessMaker.apiClient.put : ProcessMaker.apiClient.post;
            },
            savePath() {
                return this.isEditing() ? 'forms/' + this.formData.id : 'forms';
            },
            onClose() {
                this.$emit('close');
            },
            onSave() {
                this.resetErrors();
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
