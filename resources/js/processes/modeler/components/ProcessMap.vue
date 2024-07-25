<template>
  <div id="modeler-app">
    <div class="h-100">
      <div
        class="overflow-hidden position-relative p-0 vh-100"
        data-test="body-container"
      >
        <ProcessMapTooltip
          v-show="showTooltip"
          ref="tooltip"
          :enabled="enableTooltip"
          :node-id="tooltip.nodeId"
          :node-name="tooltip.nodeName"
          :request-id="requestId"
          :style="{
            left: `${tooltip.newX}px`,
            top: `${tooltip.newY}px`
          }"
          @is-loading="getIsLoading"
        />
        <Modeler
          ref="modeler"
          :owner="self"
          :decorations="decorations"
          :request-completed-nodes="requestCompletedNodes"
          :request-in-progress-nodes="requestInProgressNodes"
          :request-idle-nodes="requestIdleNodes"
          :read-only="true"
          :for-documenting="forDocumenting"
          @set-xml-manager="xmlManager = $event"
          @click="handleClick"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { Modeler } from "@processmaker/modeler";
import ProcessMapTooltip from "./ProcessMapTooltip.vue";

export default {
  name: "ProcessMap",
  components: {
    Modeler,
    ProcessMapTooltip,
  },
  props: {
    forDocumenting: {
      type: Boolean,
      default: false,
    },
    enableTooltip: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      self: this,
      validationBar: [],
      xmlManager: null,
      decorations: {
        borderOutline: {},
      },
      tooltip: {
        isActive: false,
        isLoading: false,
        nodeId: null,
        nodeName: null,
        allowedNodes: [
          "bpmn:Task",
          "bpmn:ManualTask",
          "bpmn:SequenceFlow",
          "bpmn:ScriptTask",
          "bpmn:CallActivity",
          "bpmn:ServiceTask",
        ],
        coordinates: { x: 0, y: 0 },
        newX: 0,
        newY: 0,
      },
      requestCompletedNodes: window.ProcessMaker.modeler.requestCompletedNodes,
      requestInProgressNodes: window.ProcessMaker.modeler.requestInProgressNodes,
      requestIdleNodes: window.ProcessMaker.modeler.requestIdleNodes,
      requestId: window.ProcessMaker.modeler.requestId,
    };
  },
  computed: {
    isMappingActive() {
      return window.ProcessMaker.modeler.enableProcessMapping !== undefined
        ? window.ProcessMaker.modeler.enableProcessMapping
        : true;
    },
    showTooltip() {
      return this.enableTooltip && this.tooltip.isActive;
    },
  },
  watch: {
    "tooltip.isLoading": {
      handler(value) {
        if (!value) {
          this.$nextTick(() => {
            this.calculateTooltipPosition();
          });
        }
      },
      deep: true,
    },
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
    handleClick(payload) {
      if (this.isMappingActive) {
        this.setupTooltip(payload);
      }
    },
    setupTooltip({ event, node }) {
      const isNodeTooltipAllowed = this.tooltip.allowedNodes.includes(node.$type);
      if ((isNodeTooltipAllowed && this.tooltip.isActive === false)
        || (isNodeTooltipAllowed && this.tooltip.nodeId !== node.id)) {
        this.tooltip.nodeId = node.id;
        this.tooltip.nodeName = node.name;
        this.tooltip.isActive = true;
        this.$nextTick(() => {
          this.tooltip.coordinates = { x: event.clientX, y: event.clientY };
          this.calculateTooltipPosition();
        });
      } else if (this.tooltip.nodeId === node.id && this.tooltip.isActive === true) {
        this.tooltip.isActive = false;
      }
    },
    calculateTooltipPosition() {
      this.rectTooltip = this.$refs.tooltip.$el.getBoundingClientRect();
      this.tooltip.newY = this.tooltip.coordinates.y - this.rectTooltip.height - 20;
      if (this.tooltip.newY <= 0) {
        this.tooltip.newY = 10;
      }
      this.tooltip.newX = this.tooltip.coordinates.x - (this.rectTooltip.width / 2);
      if (this.tooltip.newX < 0) {
        this.tooltip.newX = 0;
      } else if (this.tooltip.newX + this.rectTooltip.width > window.innerWidth) {
        this.tooltip.newX = window.innerWidth - this.rectTooltip.width;
      }
    },
    getIsLoading(value) {
      this.tooltip.isLoading = value;
    },
  },
};
</script>
