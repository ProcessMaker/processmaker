<template>
  <div>
    <modal
      id="wizardTemplateDetails"
      class="wizard-template-modal"
      size="huge"
      :hide-footer="true"
      @close="close"
    >
      <b-row v-if="!showHelperProcess">
        <b-col>
          <div class="wizard-details-container text-left mb-3">
            <span>
              <img
                :src="templateIcon"
                :alt="template.name + ' icon'"
                width="45px"
                class="mb-3 d-block"
              >
            </span>
            <span>
              <h5 class="text-uppercase mb-1 d-inline-block font-weight-bold template-name">{{ template.name | str_limit(30) }}</h5>
            </span>
            <div class="wizard-details-text">
              <h2 class="wizard-details-headline">{{ templateDetails['modal-excerpt'] | str_limit(150) }}</h2>
              <p class="wizard-details-description">{{ templateDetails['modal-description'] | str_limit(150) }}</p>
              <button class="wizard-details-button text-uppercase"  @click.prevent="triggerHelperProcessStartEvent">
                <i class="fas fa-play-circle mr-1" />
                {{ $t('Use Now') }}
              </button>
            </div>
          </div>
        </b-col>
        <b-col>
          <b-carousel class="d-flex align-items-center justify-content-center h-100 w-100" fade :interval="slideInterval">
            <b-carousel-slide
              v-for="(image, index) in templateSlides"
              :key="index"
              :img-src="image"
            />
          </b-carousel>
        </b-col>
      </b-row>
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
import Modal from "../shared/Modal.vue";
import {Task} from "@processmaker/screen-builder";

export default {
  components: { Modal, Task },
  mixins: [],
  props: ["template"],
  data() {
    return {
      showHelperProcess: false,
      src: "",
      formData: {},
      task: {},
      currentUserId: null,
    };
  },
  computed: {
    templateDetails() {
      return JSON.parse(this.template?.template_details);
    },
    templateIcon() {
      return this.template?.template_media?.icon;
    },
    templateSlides() {
      return this.template?.template_media?.slides;
    },
    slideInterval() {
      return Object.keys(this.template?.template_media?.slides).length > 1 ? 3000 : 0;
    }
  },
  methods: {
    show() {
      this.$bvModal.show("wizardTemplateDetails");
    },
    close() {
      this.$bvModal.hide("wizardTemplateDetails");
      if (this.showHelperProcess) {
        // Cancels the associated process request to prevent orphaned processes.
        this.cancelHelperProcessRequest();
      }
    },
    async triggerHelperProcessStartEvent() {
      try {
        const startEvents = this.template.process.start_events.filter(event => !event.eventDefinitions || event.eventDefinitions.length === 0);
        const helperProcessId = this.template.process.id;
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
      // TODO: Redirect the user to the created process launchpad page
      this.showHelperProcess = false;
      this.close();
    },
    error(processRequestId) {
      console.error('error', processRequestId);
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
    }
  },
};
</script>

<style scoped>
.wizard-details-text {
  font-weight: 400;
}
.wizard-details-description {
  font-size: 16px;
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
