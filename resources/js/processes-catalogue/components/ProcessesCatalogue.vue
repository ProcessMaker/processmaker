<template>
  <div class="d-flex w-100">
    <div class="w-25">
      <h4> {{ $t('Processes Browser') }} </h4>
      <ul>
        <li>Wizard Templates</li>
      </ul>
    </div>
    <div class="w-100">
      <div v-if="!showWizardTemplates && !fields.length" class="d-flex justify-content-center py-5 w-100">
        <div>
          <div class="d-flex justify-content-center my-5">
            <img
              class="image d-flex"
              src="/img/processes-catalogue-empty.svg"
              alt="recent projects"
            >
          </div>
          <h4 class="text-center">
            {{ $t('Currently you dont have processes created') }}
          </h4>
          <p class="text-center">
            {{ $t('We encourage you to create new processes using our templates') }}
          </p>
          <p class="text-center my-4">
            <button
              type="button"
              class="btn btn-primary text-capitalize"
            >
              {{ $t("Show Me The Templates") }}
            </button>
          </p>
        </div>
      </div>
      <wizard-templates v-if="showWizardTemplates"></wizard-templates>
    </div>
  </div>
</template>

<script>
import WizardTemplates from './WizardTemplates.vue';
export default {
  components: { WizardTemplates },
  data() {
    return {
      fields: [],
      wizardTemplates: [],
      showWizardTemplates: false,
    };
  },
  methods: {
    fetchWizardTemplates() {
      console.log('fetchWizardTemplates', this.wizardTemplates);
      this.showWizardTemplates = true;
    }
  },
  mounted() {
    this.fetchWizardTemplates();
  }
};
</script>

