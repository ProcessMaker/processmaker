<template>
  <div class="p-4 bg-white rounded shadow-md max-w-md">
    <b class="text-lg font-bold mb-2">{{ $t("Order of stages") }}</b>
    <p class="text-sm mb-4">
      {{ $t("Here you have all the stages already set in this process. Define the order you prefer:") }}
    </p>
    <StageList :initialStages="defaultStages"
               @onUpdate="onUpdate"
               @onRemove="onRemove"
               @onChange="onChange"
               @onClickCheckbox="onClickCheckbox"
               @onClickSelected="onClickSelected"
      ></StageList>
    <AgregationProperty />
  </div>
</template>

<script setup>
import StageList from "./StageList.vue";
import { ref, watch, reactive, toRefs, onMounted, computed, getCurrentInstance, nextTick } from "vue";
import AgregationProperty from "./AgregationProperty.vue";

const props = defineProps({
  value: Object
});
const defaultStages = ref([]);
const currentInstance = getCurrentInstance();

const loadStagesFromApi = () => {
  const id = window.ProcessMaker.modeler.process.id;
  ProcessMaker
    .apiClient
    .get(`/processes/${id}/stages`)
    .then((response) => {
      let stages = response.data.data;
      selectItemFromDefinition(stages);
      stages.forEach(item => {
        defaultStages.value = [...defaultStages.value, item];
      });
    });
};

const saveStagesToApi = (stages) => {
  const copy = structuredClone(stages);
  copy.forEach(item => {
    delete item.selected;
  });
  const id = window.ProcessMaker.modeler.process.id;
  const params = {
    stages: copy
  };
  ProcessMaker
    .apiClient
    .post(`/processes/${id}/stages`, params)
    .then((response) => {
    });
};

const getModeler = () => {
   return currentInstance.proxy.$root.$children[0].$refs.modeler;
};

const getHighlightedNode = () => {
  return getModeler().highlightedNode;
};

const getDefinition = () => {
  return getHighlightedNode().definition;
};

const getConfigFromDefinition = (definition) => {
  let config = {};
  try {
    config = JSON.parse(definition.config);
  } catch (error) {
    config = {};
  }
  return config;
};

const selectItemFromDefinition = (stages) => {
  const config = getConfigFromDefinition(getDefinition());
  const id = config?.stage?.id;
  if (id === undefined) {
    return;
  }
  for (const stage of stages) {
    stage.selected = stage.id === id;
  }
};

const updateStagesForAllFlowConfigs = (stages) => {
  const links = getModeler().graph.getLinks();
  for (const link of links) {
    for (const stage of stages) {
      let config = getConfigFromDefinition(link.component.node.definition);
      if (config?.stage?.id === stage.id) {
        config.stage.order = stage.order;
        config.stage.name = stage.name;
        Vue.set(link.component.node.definition, "config", JSON.stringify(config));
      }
    }
    link.component.setStageLabel();
  }
};

const removeStageInAllFlowConfig = (stage) => {
  const links = getModeler().graph.getLinks();
  for (const link of links) {
    let config = getConfigFromDefinition(link.component.node.definition);
    if (config?.stage?.id === stage.id) {
      delete config.stage;
      Vue.set(link.component.node.definition, "config", JSON.stringify(config));
    }
    link.component.removeStageLabels();
  }
};

const applyStageToFlow = (stage) => {
  let config = getConfigFromDefinition(getDefinition());
  config.stage = {
    id: stage.id,
    order: stage.order,
    name: stage.name
  };
  let definition = getDefinition();
  Vue.set(definition, "config", JSON.stringify(config));
  getModeler().getCurrentStageModelComponent().setStageLabel();
};

const removeStageToFlow = () => {
  let definition = getDefinition();
  let config = getConfigFromDefinition(definition);
  delete config.stage;
  Vue.set(definition, "config", JSON.stringify(config));
  getModeler().getCurrentStageModelComponent().removeStageLabels();
};

const onChange = (stages) => {
  updateStagesForAllFlowConfigs(stages);
  saveStagesToApi(stages);
};

const onUpdate = (stages, index, val, Oldal) => {
  updateStagesForAllFlowConfigs(stages);
};

const onRemove = (stages, index, removed) => {
  removeStageInAllFlowConfig(removed);
};

const onClickCheckbox = (stage) => {
  applyStageToFlow(stage);
};

const onClickSelected = (stage) => {
  removeStageToFlow();
};

onMounted(() => {
  loadStagesFromApi();
});
</script>
