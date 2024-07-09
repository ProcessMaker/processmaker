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
    props: ["wizardTemplateUuid", "processLaunchpadId"],
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

<style scoped>
.wizard-details-text {
  font-weight: 400;
}
.wizard-details-description {
  font-size: 20px;
}

.wizard-details-headline {
  font-size: 26px;
}

.wizard-details-button{
  border-radius: 11px;
  border: none;
  background-color: #1572C2;
  color: #FFFFFF;
  display: inline-flex;
  padding: 7px 16px;
  align-items: center;
}
</style>