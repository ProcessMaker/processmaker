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

        <div class="form-group" v-show="isScriptTask()">
          <label>Configuration</label>
          <monaco-editor
              class="editor form-control"
              v-model="configuration"
              :options="monacoOptions"
              language="javascript"
              style="">
          </monaco-editor>
        </div>

        <div class="form-group">
          <label>Notifications</label>
          <form-checkbox v-model="notifyAfterRouting" label="After routing notify next assignee"></form-checkbox>
          <form-checkbox v-model="notifyToRequestCreator" label="Notify the request creator"></form-checkbox>
        </div>

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
            const isScriptTask = this.selectedElement.type.toLowerCase() === 'scripttask';
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
                'taskType123': isScriptTask ? 'scriptTask': 'task',
                'taskType': isScriptTask ? 'scriptTask': 'task',
                'taskTypes': [
                    {value: '', content:''},
                    {value: 'task', content:'Form'},
                    {value: 'scriptTask', content:'Script'}
                ],
                'taskTypeItem': isScriptTask ? this.selectedElement.attributes['pm:scriptRef']: this.selectedElement.attributes['pm:formRef'],
                'taskTypeItems': [
                    {value: '', content:''},
                ],
                'taskTitle': this.selectedElement.attributes.name,
                'notifyAfterRouting': false,
                'notifyToRequestCreator': false,
                'description': '',
                'configuration': '{}',
                'monacoOptions': {
                  automaticLayout: true
                },
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
            isScriptTask () {
              return this.taskType==='scriptTask';
            },
            onTaskTypeChanged(selectedType) {
                let options = this.taskTypeItems;
                switch (selectedType) {
                    case 'task':
                        ProcessMaker.apiClient
                            .get('process/' + this.processUid + '/forms')
                            .then(response => {
                                options.splice(0);
                                response.data.data.map(function (form) {
                                    options.push({
                                        value: form.uid,
                                        content: form.title
                                    })
                                });

                                if (options.length === 0) {
                                    options.push({ value: null, content: 'None'});
                                }
                            });
                        break;
                    case 'scriptTask':
                        ProcessMaker.apiClient
                            .get('process/' + this.processUid + '/scripts')
                            .then(response => {
                                options.splice(0);
                                response.data.data.map(function (script) {
                                    options.push({
                                        value: script.uid,
                                        content: script.title
                                    })
                                });

                                if (options.length === 0) {
                                    options.push({ value: null, content: 'None'});
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
                    $type: this.taskType,
                    formRef: (this.taskType === 'task' ? this.taskTypeItem : undefined),
                    scriptRef: (this.taskType === 'scriptTask' ? this.taskTypeItem : undefined),
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

                this.$refs.modal.hide();
            },
            setDataFromSelectedElement() {
                this.taskTitle = this.selectedElement.attributes.name;
                this.onTaskTypeChanged(this.taskType);
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

<style>
.editor {
  width: 100%;
  height: 200px;
  overflow: hidden;
}
</style>
