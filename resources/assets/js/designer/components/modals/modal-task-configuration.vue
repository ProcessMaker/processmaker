<template>
    <b-modal ref="modal" size="md" @hidden="onHidden" centered title="Task Configuration">
        <form-input  v-model="taskTitle" label="Title" required="required"></form-input>

        <form-select v-model="taskType" label="Task Type" required="required"
                        :options="taskTypes" v-on:input="onTaskTypeChanged">
        </form-select>

        <form-select v-model="taskTypeItem" label="Task Option" required="required"
                     :options="taskTypeItems"></form-select>


        <form-select v-model="taskDueDate" label="Task is due in" required="required"
                     :options="taskDueDates"></form-select>

        <label>Notifications</label>
        <form-checkbox v-model="notifyAfterRouting" label="After routing notify next assignee"></form-checkbox>
        <form-checkbox v-model="notifyToRequestCreator" label="Notify the request creator"></form-checkbox>

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
    import actions from '../../actions';
    import EventBus from '../../lib/event-bus';

    export default {
        components: {FormTextArea, FormInput, FormSelect, FormDatePicker, FormCheckbox},
        data() {
            return {
                'taskDueDate': '',
                'taskDueDates': [
                    {value: '', content:''},
                    {value: '2', content:'2h'},
                    {value: '4', content:'4h'},
                    {value: '8', content:'8h'},
                    {value: '12', content:'12h'},
                    {value: '24', content:'24h'},
                    {value: '48', content:'48h'}
                ],
                'taskType': '',
                'taskTypes': [
                    {value: '', content:''},
                    {value: 'form', content:'Form'},
                    {value: 'script', content:'Script'}
                ],
                'taskTypeItem': '',
                'taskTypeItems': [
                    {value: '', content:''},
                ],
                'taskTitle': '',
                'notifyAfterRouting': false,
                'notifyToRequestCreator': false,
                'description': '',
                'errors': {
                    'title': null,
                    'description': null
                },
            }
        },
        props: {
            'processUid': String,
            'selectedElement': Object
        },
        methods: {
            onTaskTypeChanged(selectedType) {
                this.taskTypeItems = [];
                switch (selectedType) {
                    case 'form':
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
                                if (options.length === 1) {
                                    this.taskTypeItem = options[0].value;
                                }
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

                                if (options.length === 1) {
                                    this.taskTypeItem = options[0].value;
                                }
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
                //save task config
                let data = {
                    id: this.selectedElement.id,
                    name: this.taskTitle,
                    type: this.taskType,
                    formRef: (this.taskType === 'form' ? this.taskTypeItem : ''),
                    scriptRef: (this.taskType === 'script' ? this.taskTypeItem : ''),
                    dueDate: this.taskDueDate,
                    notifyAfterRouting: this.notifyAfterRouting,
                    notifyToRequestCreator: this.notifyToRequestCreator
                };

                let hideCrownAction = actions.designer.crown.hide();
                EventBus.$emit(hideCrownAction.type, hideCrownAction.payload);

                let updateTaskAction = actions.bpmn.task.update(data);
                EventBus.$emit(updateTaskAction.type, updateTaskAction.payload);

                let reloadModelAction = actions.designer.bpmn.loadFromModel();
                EventBus.$emit(reloadModelAction.type, reloadModelAction.payload);

                this.$refs.modal.hide()
            },
            setDataFromSelectedElement() {
                this.taskTitle = this.selectedElement.attributes.name;
                this.taskType = this.selectedElement.attributes.type;
                this.onTaskTypeChanged(this.taskType);
                this.taskTypeItem = this.taskType === 'form'
                    ? this.selectedElement.attributes.formRef
                    : this.selectedElement.attributes.scriptRef;
                this.taskDueDate = this.selectedElement.attributes.dueDate;
                this.notifyAfterRouting = this.selectedElement.attributes.notifyAfterRouting;
                this.notifyToRequestCreator = this.selectedElement.attributes.notifyToRequestCreator;
            }
        },
        mounted() {
            // Show our modal as soon as we're created
            this.$refs.modal.show();
            this.setDataFromSelectedElement();
        }
    };
</script>

