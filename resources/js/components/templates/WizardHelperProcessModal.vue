<template>
    <div>
        <modal
            id="processWizard"
            class="wizard-template-modal"
            size="huge"
            :hide-footer="true"
            @close="close"
        >
            <task
                v-if="showHelperProcess"
                ref="task"
                class="card border-0"
                v-model="formData"
                :initial-task-id="task.id"
                :initial-request-id="task.process_request_id"
                :user-id="currentUserId"
                @task-updated="taskUpdated"
                @submit="submit"
                @completed="completed"
                @@error="error"
            ></task>
        </modal>
    </div>
</template>

<script>
import Modal from "../../components/shared/Modal.vue";
import {Task} from "@processmaker/screen-builder";
export default {
    components: { Modal, Task},
    props: ["wizardTemplateUuid"],
    data() {
        return {
            task: null,
            currentUserId: null,
            showHelperProcess: false,
            formData: {},
        }
    },
    methods: {
        async getHelperProcess() {
            if (this.wizardTemplateUuid !== null) {
                const response = await ProcessMaker.apiClient.get(`wizard-templates/${this.wizardTemplateUuid}/get-helper-process`);
                if (response.data) {
                    const helperProcessId = response.data.helper_process_id;
                    const startEvents = JSON.parse(response.data.start_events).filter(event => !event.eventDefinitions || event.eventDefinitions.length === 0);
                    
                    this.triggerHelperProcessStartEvent(helperProcessId, startEvents);
                }
            }
        },
        async triggerHelperProcessStartEvent(helperProcessId, startEvents) {
            try {
                const startEventId = startEvents[0].id;
                const url = `/process_events/${helperProcessId}?event=${startEventId}`;

                // Start the helper process
                const response = await window.ProcessMaker.apiClient.post(url);
                const processRequestId = response.data.id;

                this.getNextTask(processRequestId);
            } catch (err) {
                const data = err.response?.data;
                if (data && data.message) {
                    ProcessMaker.alert(data.message, 'danger');
                }
            }
        },
        async getNextTask(processRequestId) {
            try {
                const response = await ProcessMaker.apiClient.get(`tasks`, {
                params: {
                    page: 1,
                    include: 'user,assignableUsers',
                    process_request_id: processRequestId,
                    status: 'ACTIVE',
                    per_page: 10,
                    order_by: 'due_at',
                    order_direction: 'asc'
                }
                });

                const taskData = response.data.data;

                if (taskData.length > 0) {
                    this.task = taskData[0];
                    this.currentUserId = parseInt(document.head.querySelector('meta[name="user-id"]').content);
                    this.$bvModal.show('processWizard');
                    this.showHelperProcess = true;
                } else {
                    // Process is completed hide the helper process and close the modal
                    this.showHelperProcess = false;
                    this.close();
                }
            } catch (error) {
                if (error && error.message) {
                    ProcessMaker.alert(error.message, 'danger');
                }
            }
        },
        close() {
            this.$bvModal.hide('processWizard');
            // Cancels the associated process request to prevent orphaned processes.
            this.cancelHelperProcessRequest();
        },
        async cancelHelperProcessRequest() {
            const {process_request_id: processRequestId } = this.task;

            try {
                await ProcessMaker.apiClient.put(`requests/${processRequestId}`, {
                    status: "CANCELED"
                });
                this.showHelperProcess = false;
            } catch (error) {
                if (data && data.message) {
                    ProcessMaker.alert(data.message, 'danger');
                }
            }
        },
        taskUpdated(task) {
            this.task = task;
        },
        async submit(task) {
            const { id: taskId, process_request_id: processRequestId } = task;

            try {
                await ProcessMaker.apiClient.put(`tasks/${taskId}`, {
                status: "COMPLETED",
                data: this.formData
                });

                // Successfully completed task, get the next one
                await this.getNextTask(processRequestId);
            } catch (error) {
                if (error && error.message) {
                ProcessMaker.alert(error.message, 'danger');
                }
            }
        },
        completed(processRequestId) {
            console.log("task completed", processRequestId);
        },
        error(processRequestId) {
            console.error('error', processRequestId);
        },
   },
}
</script>