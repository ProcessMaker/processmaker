<template>
  <div>
    <buttons-start :process="process" />
    <process-counter :process="process" />
    <b-button v-if="createdFromWizardTemplate" class="mt-2 wizard-link" variant="link" @click="getHelperProcess">
      <img src="../../../img/wizard-icon.svg" :alt="$t('Guided Template Icon')" />
      {{ $t('Re-run Wizard') }}
    </b-button>
    <chart-save-search :process="process" />

    <wizard-helper-process-modal
      v-if="createdFromWizardTemplate"
      id="wizardHelperProcessModal" 
      ref="wizardHelperProcessModal"
      :wizardTemplateUuid="wizardTemplateUuid"
    />
  </div>
</template>

<script>
import ButtonsStart from "./optionsMenu/ButtonsStart.vue";
import ProcessCounter from "./optionsMenu/ProcessCounter.vue";
import ChartSaveSearch from "./optionsMenu/ChartSaveSearch.vue";
import WizardHelperProcessModal from '../../components/templates/WizardHelperProcessModal.vue';

export default {
  components: { ButtonsStart, ProcessCounter, ChartSaveSearch, WizardHelperProcessModal },
  props: ["process"],
  computed: {
    createdFromWizardTemplate() {
      return this.process?.properties?.wizardTemplateUuid ? true : false;
    },
    wizardTemplateUuid() {
      return this.process?.properties?.wizardTemplateUuid;
    }
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
</style>
