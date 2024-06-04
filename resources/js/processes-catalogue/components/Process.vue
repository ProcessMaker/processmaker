<template>
  <div v-if="selectedProcess">
    <ProcessInfo
      :process="selectedProcess"
      :permission="permission"
      @goBackCategory="goBackCategory"
    />
    <ProcessScreen
      v-if="verifyScreen"
      :process="selectedProcess"
      :permission="permission"
      @goBackCategory="goBackCategory"
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
  props: ["process", "processId", "guidedTemplates", "permission", "isDocumenterInstalled"],
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
    },
    /**
     * Verify if the process open the info or Screen
     */
     verifyScreen() {
      let screenId = 0;
      const unparseProperties = this.process?.launchpad?.properties || null;
      if (unparseProperties !== null) {
        screenId = JSON.parse(unparseProperties)?.screen_id || 0;
      }

      return screenId !== 0;
    },
  },
  methods: {
    goBackCategory() {
      this.$emit("goBackCategory");
    },
  },
  mounted() {
    if (!this.process && this.processId) {
      ProcessMaker.apiClient
        .get(`process_launchpad/${this.processId}`)
        .then((response) => {
          this.loadedProcess = response.data[0];
        });
    }
  }
};
</script>