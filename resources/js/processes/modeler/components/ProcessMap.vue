<template>
  <div>
    <div class="h-100">
      <div
        class="overflow-hidden position-relative p-0 vh-100"
        data-test="body-container"
      >
        <ModelerReadonly
          ref="modeler"
          :owner="self"
          :decorations="decorations"
          :request-completed-nodes="requestCompletedNodes"
          :request-in-progress-nodes="requestInProgressNodes"
          @set-xml-manager="xmlManager = $event"
          @click="handleClick"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ModelerReadonly } from "@processmaker/modeler";

export default {
  name: "ProcessMap",
  components: {
    ModelerReadonly,
  },
  data() {
    return {
      self: this,
      validationBar: [],
      process: window.ProcessMaker.modeler.process,
      xmlManager: null,
      decorations: {
        borderOutline: {},
      },
      requestCompletedNodes: window.ProcessMaker.modeler.requestCompletedNodes,
      requestInProgressNodes: window.ProcessMaker.modeler.requestInProgressNodes,
    };
  },
  mounted() {
    ProcessMaker.$modeler = this.$refs.modeler;
  },
  methods: {
    refreshSession: _.throttle(() => {
      ProcessMaker.apiClient({
        method: "POST",
        url: "/keep-alive",
        baseURL: "/",
      });
    }, 60000),
    handleClick() {
      //
    },
  },
};
</script>
