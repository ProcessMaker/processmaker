<template>
  <div>
    <modal
      id="wizardTemplateDetails"
      :content-class="!showHelperProcess ? 'first_slide' : ''"
      size="huge"
      :hide-footer="true"
      @close="close"
    >
      <b-row v-if="!showHelperProcess">
        <b-col class="wizard-template-carousel col-5">
          <b-carousel fade :interval="slideInterval">
            <b-carousel-slide v-for="(image, index) in templateSlides" :key="index" :img-src="image"/>
          </b-carousel>
        </b-col>
        <b-col>
          <div class="wizard-details-container text-left mb-3">
            <div class="wizard-details-text pl-3">
              <h1 class="mb-3 d-inline-block font-weight-bold template-name">{{ templateDetails['modal-title']| str_limit(30) }}</h1>
              <h3 class="wizard-details-headline text-white">{{ templateDetails['modal-excerpt'] | str_limit(150) }}</h3>
              <div v-for="item in templateModalDescriptionItems" class="mb-3 wizard-details-description text-white d-flex align-items-center">
                <span v-if="templateListIcon" class="mr-3">
                  <img
                    :src="templateListIcon"
                    :alt="template.name + ' icon'"
                    width="30px"
                  >
                </span>
                <span class="template-list-item">{{ item | str_limit(150) }}</span>
              </div>
              <hr class="template-divider mx-2"/>
              <button class="wizard-details-button text-uppercase"  @click.prevent="getHelperProcessStartEvent('wizard-details-modal')">
                <i class="fas fa-play-circle mr-1" />
                {{ $t('Get Started') }}
              </button>
            </div>
          </div>
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
    templateListIcon() {
      return this.template?.template_media?.listIcon;
    },
    templateSlides() {
      return this.template?.template_media?.slides;
    },
    templateModalDescriptionItems() {
      return this.templateDetails['modal-description'].split(';');
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
  font-size: 18px;
  line-height: 3px;
  font-weight: lighter;
}

.wizard-details-description .template-list-item{
  line-height:1.5rem;
}

.wizard-details-headline {
  font-size: 20px;
  margin-bottom: 22px;
  font-weight:lighter;
}

.wizard-details-button {
  border-radius: 11px;
  border: none!important;
  background-color: #1572C2;
  color: #FFFFFF;
  display: inline-flex;
  padding: 7px 16px;
  align-items: center;
}

.wizard-details-button:focus {
  border:none;
  outline: none;
}

.template-divider {
  background-color: #0081D8;
  margin-top: 2rem;
  margin-bottom: 2rem;
}
</style>