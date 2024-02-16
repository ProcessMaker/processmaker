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
              <button class="wizard-details-button text-uppercase"  @click.prevent="getHelperProcessStartEvent('wizard-details-modal')">
                <i class="fas fa-play-circle mr-1" />
                {{ $t('Get Started') }}
              </button>
            </div>
          </div>
        </b-col>
        <b-col>
          <b-carousel fade :interval="slideInterval">
            <b-carousel-slide v-for="(image, index) in templateSlides" :key="index" :img-src="image"/>
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
      ></task>
    </modal>
  </div>
</template>

<script>
import Modal from "../shared/Modal.vue";
import {Task} from "@processmaker/screen-builder";
import wizardHelperProcessModalMixin from "./mixins/wizardHelperProcessModal";

export default {
  components: { Modal, Task },
  mixins: [wizardHelperProcessModalMixin],
  props: ["template"],
  data() {
    return {
      showHelperProcess: false,
      src: "",
      formData: {},
      task: {},
      currentUserId: null,
      helperProcessId: null,
      startEvents: null,
      shouldImportProcessTemplate: true,
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
      
      // Remove template parameter from the URL
      let url = new URL(window.location.href);
      if (url.search.includes('?guided_templates=true&template=')) {
        url.searchParams.delete('template');
        history.pushState(null, '', url); // Update the URL without triggering a page reload
      }
      
      // Cancels the associated process request to prevent orphaned processes.
      if (this.showHelperProcess) {
        this.cancelHelperProcessRequest();
      }
    },
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
