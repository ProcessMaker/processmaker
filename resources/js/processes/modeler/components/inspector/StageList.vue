<template>
  <div>
    <draggable 
      v-model="stages" 
      item-key="id" 
      handle=".fa-grip-vertical" 
      class="divide-y"
      @end="onReorder">
      <StageItem
        v-for="(item, index) in stages" 
        :key="index"
        :id="item.id"
        :order="item.order"
        :label="item.label"
        :selected="item.selected"
        @onUpdate="onUpdate(index, $event)"
        @onRemove="onRemove(index)"
        @onClickCheckbox="onClickCheckbox(index)"
        @onClickSelected="onClickSelected(index)"
      ></StageItem>
    </draggable>

    <div v-show="adding" class="tw-flex tw-items-center tw-space-x-2 tw-w-full tw-py-1">
      <span class="tw-w-6 text-center stage-item-number">{{ totalStages + 1 }}</span>
      <i class="fas fa-grip-vertical stage-item-grip-vertical tw-cursor-move"></i>
      <input
        v-model="newStage"
        class="tw-flex-1 tw-border tw-rounded px-2"
        :placeholder="$t('Enter label')"
        @keyup.enter="onKeyupEnter"
      />
    </div>
    <div class="tw-flex tw-justify-end tw-mt-2">
      <button 
        @click="onClickAdd" 
        :disabled="disableButton"
        class="tw-bg-blue-500 text-white tw-text-sm tw-px-2 tw-py-0.5 tw-rounded" 
        :class="{'tw-bg-gray-300 tw-cursor-not-allowed': disableButton}">
        <i class="fas fa-plus"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import draggable from 'vuedraggable';
import StageItem from './StageItem.vue';

const props = defineProps({
  initialStages: {
    type: Array,
    default: () => []
  }
});
const emit = defineEmits(['onAdd', 'onUpdate', 'onRemove', 'onChange', 'onClickCheckbox', 'onClickSelected']);
const adding = ref(false);
const newStage = ref('');
const stages = ref([...props.initialStages]);
const totalStages = computed(() => stages.value.length);
const disableButton = computed(() => totalStages.value >= 8);
const $t = window.ProcessMaker.i18n.t;

const onClickAdd = () => {
  if (disableButton.value) {
    return;
  }
  adding.value = !adding.value; 
  newStage.value = '';
};

const onKeyupEnter = () => {
  if (newStage.value.trim()) {
    const uniqueId = Number(`${Date.now()}${Math.floor(Math.random() * 900 + 100)}`);
    const order = stages.value.length + 1;
    stages.value.push({
      id: uniqueId,
      order: order,
      label: newStage.value,
      selected: false
    });
    newStage.value = '';
    adding.value = false;
    emit('onAdd', stages.value, stages.value.length - 1);
    emit('onChange', stages.value);
  }
};

const onUpdate = (index, newLabel) => {
  const oldLabel = stages.value[index].label;
  stages.value[index].label = newLabel;
  emit('onUpdate', stages.value, index, newLabel, oldLabel);
  emit('onChange', stages.value);
};

const onRemove = (index) => {
  ProcessMaker.confirmModal(
    $t("Caution!"),
    $t("Are you sure you want to delete the stage?"),
    "",
    () => {
      const removed = stages.value.splice(index, 1);
      emit('onRemove', stages.value, index, removed[0]);
      emit('onChange', stages.value);
    }
  );
};

const onClickCheckbox = (index) => {
  stages.value.forEach((stage, i) => {
    stage.selected = i === index;
  });
  emit('onClickCheckbox', stages.value[index]);
};

const onClickSelected = (index) => {
  stages.value.forEach((stage, i) => {
    if (i === index) {
      stage.selected = false;
    }
  });
  emit('onClickSelected', stages.value[index]);
};

const onReorder = () => {
  stages.value.forEach((item, index) => {
    item.order = index + 1;
  });
  emit('onChange', stages.value);
};

watch(() => props.initialStages, (newVal) => {
  stages.value = [...newVal];
});
</script>

<style scoped>
  .stage-item-number {
    background-color: #788793;
    font-weight: bold;
    border-radius: 0.25rem;
    color: white;
    min-width: 24px;
  }
  .stage-item-grip-vertical {
    color: #788793;
  }
</style>