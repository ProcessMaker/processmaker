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
        <ModelerReadonly
          ref="modeler"
          :owner="self"
          :decorations="decorations"
          @set-xml-manager="xmlManager = $event"
          @click="handleClick"
        />
      </b-card-body>
    </b-card>
  </b-container>
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
