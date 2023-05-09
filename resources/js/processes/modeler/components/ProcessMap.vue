<template>
  <b-container class="container p-0">
    <b-card
      no-body
      class="h-100 border-top-0"
    >
      <b-card-body
        class="overflow-hidden position-relative p-0 vh-100"
        data-test="body-container"
      >
        <modeler
          ref="modeler"
          :owner="self"
          :decorations="decorations"
          :inflight="true"
          @validate="validationErrors = $event"
          @warnings="warnings = $event"
          @set-xml-manager="xmlManager = $event"
        />
      </b-card-body>
    </b-card>
  </b-container>
</template>

<script>
import { Modeler } from "@processmaker/modeler";

export default {
  name: "ProcessMap",
  components: {
    Modeler,
  },
  data() {
    return {
      self: this,
      decorations: {
        borderOutline: {},
      },
      process: window.ProcessMaker.modeler.process,
      autoSaveDelay: window.ProcessMaker.modeler.autoSaveDelay,
      isVersionsInstalled: window.ProcessMaker.modeler.isVersionsInstalled,
      isDraft: window.ProcessMaker.modeler.isDraft,
      validationErrors: {},
      warnings: [],
      xmlManager: null,
      processName: window.ProcessMaker.modeler.process.name,
      processId: window.ProcessMaker.modeler.process.id,
      currentUserId: window.ProcessMaker.modeler.process.user_id,
      closeHref: "/processes",
    };
  },
  mounted() {
    ProcessMaker.$modeler = this.$refs.modeler;
    window.ProcessMaker.EventBus.$emit("modeler-app-init", this);
  },
  methods: {
    refreshSession: _.throttle(() => {
      ProcessMaker.apiClient({
        method: "POST",
        url: "/keep-alive",
        baseURL: "/",
      });
    }, 60000),
    updateBpmnValidations() {},
    getTaskNotifications() {},
    emitSaveEvent() {},
    emitDiscardEvent() {},
    discardDraft() {},
    saveProcess() {},
    setVersionIndicator() {},
    setLoadingState() {},
    publishTemplate() {},
  },
};
</script>
