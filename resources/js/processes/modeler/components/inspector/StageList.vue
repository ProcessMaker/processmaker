<template>
  <div>
    <draggable v-model="stages" item-key="id" handle=".fa-grip-vertical" class="divide-y">
      <StageItem
        v-for="(item, index) in stages" 
        :key="item.id" 
        :item="item"
        :label="item.label"
        :index="index"
        :selected="item.selected"
        @update="updateLabel(index, $event)"
        @remove="removeStage(index)"
      ></StageItem>
    </draggable>

    <div v-show="adding" class="tw-flex tw-items-center tw-space-x-2 tw-w-full tw-py-1">
      <span class="tw-w-6 text-center stage-item-number">{{ totalStages + 1 }}</span>
      <i class="fas fa-grip-vertical stage-item-grip-vertical tw-cursor-move"></i>
      <input
        v-model="newStage"
        class="tw-flex-1 tw-border tw-rounded px-2"
        placeholder="Finished Request"
        @keyup.enter="addStage"
      />
    </div>
    <div class="tw-flex tw-justify-end tw-mt-2">
      <button @click="adding = !adding; newStage = '';" class="tw-bg-blue-500 text-white tw-text-sm tw-px-2 tw-py-0.5 tw-rounded">
        <i class="fas fa-plus"></i>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import draggable from 'vuedraggable';
import StageItem from './StageItem.vue';

const props = defineProps({
  initialStages: Array,
});
const emit = defineEmits(['change']);

const adding = ref(false);
const stages = ref([...props.initialStages]);
const newStage = ref('');
const totalStages = computed(() => stages.value.length);

const addStage = () => {
  if (newStage.value.trim()) {
    stages.value.push({
      id: Date.now(),
      label: newStage.value,
      selected: false,
    });
    newStage.value = '';
    adding.value = false;
    emit('change', stages.value);
  }
};

const updateLabel = (index, newLabel) => {
  stages.value[index].label = newLabel;
  emit('change', stages.value);
};

const removeStage = (index) => {
  ProcessMaker.confirmModal(
    "Caution!",
    "Are you sure you want to delete the stage?",
    "",
    () => {
      stages.value.splice(index, 1);
      emit('change', stages.value);  
    }
  );
};
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