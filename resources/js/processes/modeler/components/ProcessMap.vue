<template>
  <div>
    <div class="h-100">
      <div
        class="overflow-hidden position-relative p-0 vh-100"
        data-test="body-container"
      >
        <ProcessMapTooltip/>
        <ModelerReadonly
          ref="modeler"
          :owner="self"
          :decorations="decorations"
          @set-xml-manager="xmlManager = $event"
          @click="handleClick"
        />
        
      </div>
    </div>
  </div>
</template>

<script>
import { ModelerReadonly } from "@processmaker/modeler";
import ProcessMapTooltip from "./ProcessMapTooltip.vue";

export default {
  name: "ProcessMap",
  components: {
    ModelerReadonly,
    ProcessMapTooltip
  },
  data() {
    return {
      self: this,
      process: window.ProcessMaker.modeler.process,
      xmlManager: null,
      decorations: {
        borderOutline: {},
      },
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
    handleClick() {
      //
    },
  },
};
</script>
