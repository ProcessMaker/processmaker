<template>
  <div
    class="tw-w-full tw-h-full tw-overflow-hidden tw-relative"
    data-test="body-container"
  >
    <MapLegend />
    <ProcessMapTooltip
      v-show="showTooltip"
      ref="tooltipRef"
      :enabled="enableTooltip"
      :node-id="tooltip.nodeId"
      :node-name="tooltip.nodeName"
      :request-id="inflightData.requestId"
      :style="{
        left: `${tooltip.newX}px`,
        top: `${tooltip.newY}px`
      }"
      @is-loading="isTooltipLoading"
    />
    <transition
      name="fade"
      mode="in-out"
    >
      <Modeler
        ref="modelerRef"
        :key="keyModeler"
        :decorations="decorations"
        :request-completed-nodes="inflightData.requestCompletedNodes"
        :request-in-progress-nodes="inflightData.requestInProgressNodes"
        :request-idle-nodes="inflightData.requestIdleNodes"
        :read-only="true"
        :preview="true"
        @set-xml-manager="xmlManager = $event"
        @click="handleClick"
      />
    </transition>
  </div>
</template>

<script setup>
import {
  ref, watchEffect, onMounted, computed, nextTick, onBeforeUnmount,
} from "vue";
import { Modeler } from "@processmaker/modeler";
import ProcessMapTooltip from "../../../../js/processes/modeler/components/ProcessMapTooltip.vue";
import MapLegend from "./MapLegend.vue";
import { getInflightData, getProcessName } from "../variables";

const translate = ProcessMaker.i18n;
const processTitle = ref(`${getProcessName()} ${translate.t("In-Flight Map")}`);
const keyModeler = ref(Math.random());
const modelerRef = ref("");
const tooltipRef = ref(null);
const enableTooltip = ref(true);
const decorations = ref({
  borderOutline: {},
});
const xmlManager = ref();
const tooltip = ref({
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
});
const inflightData = ref(getInflightData());

const isMappingActive = computed(() => (window.ProcessMaker.modeler.enableProcessMapping !== undefined
  ? window.ProcessMaker.modeler.enableProcessMapping
  : true));

const showTooltip = computed(() => enableTooltip.value && tooltip.value.isActive);

const calculateTooltipPosition = () => {
  const rectTooltip = tooltipRef.value.$el.getBoundingClientRect();
  tooltip.value.newY = tooltip.value.coordinates.y - rectTooltip.height - 20;
  if (tooltip.value.newY <= 0) {
    tooltip.value.newY = 10;
  }
  tooltip.value.newX = tooltip.value.coordinates.x - (rectTooltip.width / 2);
  if (tooltip.value.newX < 0) {
    tooltip.value.newX = 0;
  } else if (tooltip.value.newX + rectTooltip.width > window.innerWidth) {
    tooltip.value.newX = window.innerWidth - rectTooltip.width;
  }
};

const setupTooltip = ({ event, node }) => {
  const isNodeTooltipAllowed = tooltip.value.allowedNodes.includes(node.$type);
  if ((isNodeTooltipAllowed && !tooltip.value.isActive)
    || (isNodeTooltipAllowed && tooltip.value.nodeId !== node.id)) {
    tooltip.value.nodeId = node.id;
    tooltip.value.nodeName = node.name;
    tooltip.value.isActive = true;
    nextTick(() => {
      tooltip.value.coordinates = { x: event.clientX, y: event.clientY };
      calculateTooltipPosition();
    });
  } else if (tooltip.value.nodeId === node.id && tooltip.value.isActive) {
    tooltip.value.isActive = false;
  }
};

const isTooltipLoading = (value) => {
  tooltip.value.isLoading = value;
};

const handleClick = (payload) => {
  if (isMappingActive.value) {
    setupTooltip(payload);
  }
};

watchEffect(() => {
  if (!tooltip.value.isLoading) {
    nextTick(() => {
      calculateTooltipPosition();
    });
  }
});

onMounted(() => {
  ProcessMaker.$modeler = modelerRef.value;
});

onBeforeUnmount(() => {
  ProcessMaker.$modeler = null;
  modelerRef.value.reset({ readOnly: true });
  modelerRef.value.reset({ panMode: true });
  modelerRef.value = null;
  tooltipRef.value = null;
});
</script>
