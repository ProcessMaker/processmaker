<template>
  <div>
    <div class="h-100">
      <panZoom
        class="overflow-hidden position-relative p-0 vh-100"
        data-test="body-container"
        :options="panZoomOptions"
      >
        <ModelerReadonly
          ref="modeler"
          :owner="self"
          :decorations="decorations"
          @set-xml-manager="xmlManager = $event"
          @click="handleClick"
        />
      </panZoom>
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
      process: window.ProcessMaker.modeler.process,
      xmlManager: null,
      decorations: {
        borderOutline: {},
      },
      panZoomOptions: {
        minZoom: 0.5,
        maxZoom: 1.5,
        bounds: true,
        draggable: true,
        scalable: true,
        zoomOnDoubleClick: true,
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
