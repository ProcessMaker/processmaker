<template>
  <div v-if="selectedProcess">
    <ProcessInfo
      :process="selectedProcess"
    />
    <ProcessScreen
      :process="selectedProcess"
    />
    <wizard-templates
      :template="guidedTemplates"
    />
  </div>
</template>

<script>
import ProcessInfo from "./ProcessInfo.vue";
import WizardTemplates from "./WizardTemplates.vue";
import ProcessScreen from "./ProcessScreen.vue";

export default {
  props: ["process", "processId", "guidedTemplates"],
  components: {
    ProcessInfo, WizardTemplates, ProcessScreen
  },
  data() {
    return {
      loadedProcess: null,
    };
  },
  computed: {
    /**
     * if we pass in a process, use that. Otherwise load the process by ID
     **/
    selectedProcess() {
      if (this.process) {
        return this.process;
      }

      if (this.loadedProcess) {
        return this.loadedProcess;
      }

      return null;
    }
  },
  mounted() {
    if (!this.process && this.processId) {
      ProcessMaker.apiClient
        .get(`processes/${this.processId}`)
        .then((response) => {
          this.loadedProcess = response.data;
        });
    }
  }
};
</script>