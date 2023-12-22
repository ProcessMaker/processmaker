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
import wizardHelperProcessModalMixin from "./mixins/wizardHelperProcessModal";

export default {
    mixins: [wizardHelperProcessModalMixin],
    components: { Modal, Task},
    props: ["wizardTemplateUuid"],
    data() {
        return {
            helperProcessId: null,
            startEvents: null,
            shouldImportProcessTemplate: false,
            showHelperProcess: false,
        }
    }
}
</script>