<template>
    <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Task Configuration">
        <form-input :error="errors.title" v-model="title" label="Title" required="required"></form-input>

        <form-select v-model="taskType" label="Task Type" required="required"
                        :options="taskTypes" v-on:input="onTaskTypeChanged">
        </form-select>

        <form-select v-model="taskTypeItem" label="Task Option" required="required"
                     :options="taskTypeItems"></form-select>


        <form-date-picker v-model="dueDate"
                        label="Task is due in"></form-date-picker>

        <label v-uni-for="name">Notifications</label>
        <form-checkbox label="After routing notify next assignee"></form-checkbox>
        <form-checkbox label="Notify the request creator"></form-checkbox>

        <template slot="modal-footer">
            <b-button @click="onClose" class="btn-outline-secondary btn-md">
                CANCEL
            </b-button>
            <b-button @click="onSave()" class="btn-secondary text-light btn-md">
                SAVE
            </b-button>
        </template>

    </b-modal>

</template>

<script>
    import FormInput from "@processmaker/vue-form-elements/src/components/FormInput";
    import FormTextArea from "@processmaker/vue-form-elements/src/components/FormTextArea";
    import FormSelect from "@processmaker/vue-form-elements/src/components/FormSelect";
    import FormDatePicker from "@processmaker/vue-form-elements/src/components/FormDatePicker";
    import FormCheckbox from "@processmaker/vue-form-elements/src/components/FormCheckbox";

    export default {
        components: {FormTextArea, FormInput, FormSelect, FormDatePicker, FormCheckbox},
        data() {
            return {
                'taskType': '',
                'taskTypes': [
                    {value: '', content:''},
                    {value: 'manual', content:'Manual Task'},
                    {value: 'script', content:'Script Task'}
                ],
                'taskTypeItem': '',
                'taskTypeItems': [
                    {value: '', content:''},
                ],
                'title': '',
                'description': '',
                'errors': {
                    'title': null,
                    'description': null
                },
            }
        },
        props: [ 'processUid'],
        methods: {
            onTaskTypeChanged(selectedType) {
                this.taskTypeItems = [];
                switch (selectedType) {
                    case 'manual':
                        ProcessMaker.apiClient
                            .get('process/' + this.processUid + '/forms')
                            .then(response => {
                                let options = [];
                                response.data.data.map(function (form) {
                                    options.push({
                                        value: form.uid,
                                        content: form.title
                                    })
                                });

                                if (options.length === 0) {
                                    options = [{ value: null, content: 'None'}];
                                }

                                this.taskTypeItems = options;
                            });
                        break;
                    case 'script':
                        ProcessMaker.apiClient
                            .get('process/' + this.processUid + '/scripts')
                            .then(response => {
                                let options = [];
                                response.data.data.map(function (form) {
                                    options.push({
                                        value: form.uid,
                                        content: form.title
                                    })
                                });

                                if (options.length === 0) {
                                    options = [{ value: null, content: 'None'}];
                                }

                                this.taskTypeItems = options;
                            });
                        break;
                }

            },
            onHidden() {
                this.$emit('hidden')
            },
            onClose() {
                this.$refs.modal.hide()
            },
            onSave() {
                ProcessMaker.apiClient
                    .post(
                        'process/' +
                        this.processUid +
                        '/form',
                        {
                            title: this.title,
                            description: this.description
                        }
                    )
                    .then(response => {
                        ProcessMaker.alert('New Form Successfully Created', 'success');
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
        },
        mounted() {
            // Show our modal as soon as we're created
            this.$refs.modal.show();
        }
    };
</script>
<style lang="scss" scoped>

</style>