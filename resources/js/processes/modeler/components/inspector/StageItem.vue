<template>
  <div class="tw-flex tw-items-center tw-space-x-2 tw-w-full tw-py-1">
    <span class="tw-w-6 text-center stage-item-number">{{ index + 1 }}</span>
    <i class="fas fa-grip-vertical stage-item-grip-vertical tw-cursor-move"></i>
    <div class="tw-flex-1">
      <template v-if="editing">
        <input
          v-model="localLabel"
          class="tw-w-full tw-border tw-rounded px-2"
          @keyup.enter="save"
        />
      </template>
      <template v-else>
        <span :class="{ 'font-bold stage-item-selected': selected }">{{ label }}</span>
      </template>
    </div>
    <button @click="editing = !editing" class="p-1 bg-transparent border-0">
      <i class="fas fp-pen-edit stage-item-color"></i>
    </button>
    <button @click="$emit('remove')" class="p-1 bg-transparent border-0">
      <i class="fas fp-trash-blue stage-item-color"></i>
    </button>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  label: String,
  index: Number,
  selected: Boolean,
});
const emit = defineEmits(['update', 'remove']);

const editing = ref(false);
const localLabel = ref(props.label);

const save = () => {
  emit('update', localLabel.value);
  editing.value = false;
};

watch(() => props.label, (val) => {
  localLabel.value = val;
});
</script>
<style scoped>
  .stage-item-selected {
    font-weight: bold;
    color: #2773F3;
  }
  .stage-item-color {
    color: #2773F3;
  }
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