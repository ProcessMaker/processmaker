<template>
  <div>
    <div class="h-100">
      <div
        class="overflow-hidden position-relative p-0 vh-100"
        data-test="body-container"
      >
        <ProcessMapTooltip
          ref="tooltip"
          v-show="showTooltip"
          :nodeId="nodeId"
          :style="{
            left: newX  + 'px',
            top: newY  + 'px'
          }"
        />       
        <ModelerReadonly
          ref="modeler"
          :owner="self"
          :decorations="decorations"
          @set-xml-manager="xmlManager = $event"
          @click="handleClick"
          @highlighted-node="handleNode"
          @click-coordinates="handleCoordinates"
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
      onHighlightedNode: {},
      nodeType: null,
      nodeTypeArray: [
        'bpmn:Task', 
        'bpmn:ManualTask', 
        'bpmn:SequenceFlow', 
        'bpmn:ScriptTask', 
        'bpmn:CallActivity'
      ],
      nodeId: null,
      coordinates: {},
      showTooltip: false,
      recTooltip: {},
      newX: 0,
      newY: 0,
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
    handleNode(value) {
      this.onHighlightedNode = value
    },
    handleCoordinates(coordinates) {
      this.coordinates = coordinates;
    },
    calculateTooltipPosition() {
      this.rectTooltip = this.$refs.tooltip.$el.getBoundingClientRect();
      this.newY = this.coordinates.y - this.rectTooltip.height - 20;
      if (this.newY <= 0) {
        this.newY = 10;
      }
      this.newX = this.coordinates.x - (this.rectTooltip.width / 2);
    },    
  },
  watch: {
    onHighlightedNode(value) {
      this.nodeType = value.$type;
      this.nodeId = value.id;
      if (this.nodeTypeArray.includes(this.nodeType)) {
        this.calculateTooltipPosition();
        this.showTooltip = true;
      } else 
        this.showTooltip = false;
    },
    coordinates(value) {
      if (this.showTooltip) {
        this.calculateTooltipPosition();
      }
    },
  }
};
</script>
