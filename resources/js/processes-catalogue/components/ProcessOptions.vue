<template>
  <div class="section-options">
    <div class="counter-wizard">
      <process-counter :process="process" />
      <b-button
        v-if="createdFromWizardTemplate"
        class="mt-2 wizard-link"
        variant="link"
        @click="getHelperProcess"
      >
        <img
          src="../../../img/wizard-icon.svg"
          :alt="$t('Guided Template Icon')"
        >
        {{ $t('Re-run Wizard') }}
      </b-button>
    </div>
    <chart-save-search :process="process" />
    <wizard-helper-process-modal
      v-if="createdFromWizardTemplate"
      id="wizardHelperProcessModal"
      ref="wizardHelperProcessModal"
      :process-launchpad-id="process.id"
      :wizard-template-uuid="wizardTemplateUuid"
    />
  </div>
</template>

<script>
import ProcessCounter from "./optionsMenu/ProcessCounter.vue";
import ChartSaveSearch from "./optionsMenu/ChartSaveSearch.vue";
import WizardHelperProcessModal from "../../components/templates/WizardHelperProcessModal.vue";

export default {
  components: { ProcessCounter, ChartSaveSearch, WizardHelperProcessModal },
  props: ["process"],
  computed: {
    createdFromWizardTemplate() {
      return !!this.process?.properties?.wizardTemplateUuid;
    },
    wizardTemplateUuid() {
      return this.process?.properties?.wizardTemplateUuid;
    },
  },
  methods: {
    getHelperProcess() {
      this.$refs.wizardHelperProcessModal.getHelperProcessStartEvent();
    },
  },
};
</script>

<style scoped>
.wizard-link {
  text-transform: none;
}
.section-options {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
@media (width < 1200px) {
  .section-options {
    flex-direction: row;
  }
}
</style>
