<template>
  <div class="p-4 bg-white rounded shadow-md max-w-md">
    <b class="text-lg font-bold mb-2">{{ $t("Order of stages") }}</b>
    <p class="text-sm mb-4">
      {{ $t("Here you have all the stages already set in this process. Define the order you prefer:") }}
    </p>
    <StageList
      :key="keyStage"
      :initial-stages="defaultStages"
      @onUpdate="onUpdate"
      @onRemove="onRemove"
      @onChange="onChange"
      @onClickStage="onClickStage"
      @onClickSelected="onClickSelected" />
    <AgregationProperty />
  </div>
</template>

<script setup>
import {
  ref, onMounted, getCurrentInstance,
} from "vue";
import StageList from "./StageList.vue";
import AgregationProperty from "./AgregationProperty.vue";

const props = defineProps({
  value: Object,
});
const defaultStages = ref([]);
const currentInstance = getCurrentInstance();
const keyStage = ref(0);

const loadStagesFromApi = () => {
  const { id } = window.ProcessMaker.modeler.process;
  ProcessMaker
    .apiClient
    .get(`/processes/${id}/stages`)
    .then((response) => {
      const stages = response.data.data;
      selectItemFromDefinition(stages);
      stages.forEach((item) => {
        defaultStages.value = [...defaultStages.value, item];
      });
      keyStage.value += 1;
    });
};

const saveStagesToApi = (stages) => {
  const copy = structuredClone(stages);
  copy.forEach((item) => {
    delete item.selected;
  });
  const { id } = window.ProcessMaker.modeler.process;
  const params = {
    stages: copy,
  };
  ProcessMaker
    .apiClient
    .post(`/processes/${id}/stages`, params)
    .then((response) => {
    });
};

const getModeler = () => currentInstance.proxy.$root.$children[0].$refs.modeler;

const getHighlightedNode = () => getModeler().highlightedNode;

const getDefinition = () => getHighlightedNode().definition;

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
      const config = getConfigFromDefinition(link.component.node.definition);
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
    const config = getConfigFromDefinition(link.component.node.definition);
    if (config?.stage?.id === stage.id) {
      delete config.stage;
      Vue.set(link.component.node.definition, "config", JSON.stringify(config));
    }
    link.component.removeStageLabels();
  }
};

const applyStageToFlow = (stage) => {
  const config = getConfigFromDefinition(getDefinition());
  config.stage = {
    id: stage.id,
    order: stage.order,
    name: stage.name,
  };
  const definition = getDefinition();
  Vue.set(definition, "config", JSON.stringify(config));
  getModeler().getCurrentStageModelComponent().setStageLabel();
};

const removeStageToFlow = () => {
  const definition = getDefinition();
  const config = getConfigFromDefinition(definition);
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

const onClickStage = (stage) => {
  applyStageToFlow(stage);
  keyStage.value += 1;
};

const onClickSelected = (stage) => {
  removeStageToFlow();
};

onMounted(() => {
  loadStagesFromApi();
});
</script>
