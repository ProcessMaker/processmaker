<template>
  <div class="tw-flex tw-items-center tw-space-x-2 tw-w-full tw-py-1">
    <span class="tw-w-6 text-center stage-item-number">{{ order }}</span>
    <i class="fas fa-grip-vertical stage-item-grip-vertical tw-cursor-move"></i>
    <div class="tw-flex-1">
      <template v-if="editing">
        <input
          v-model="localName"
          class="tw-w-full tw-border tw-rounded px-2"
          @keyup.enter="onKeyupEnter"
        />
      </template>
      <template v-else>
        <span :class="{ 'font-bold stage-item-selected': selected }">{{ name }}</span>
      </template>
    </div>
    <i v-if="selected" class="fas fp-check-circle-blue stage-item-color" @click="onClickSelected"></i>
    <input v-else type="checkbox" v-model="isChecked" @change="onClickCheckbox"/>
    <button @click="editing = !editing" class="p-1 bg-transparent border-0">
      <i class="fas fp-pen-edit stage-item-color"></i>
    </button>
    <button @click="$emit('onRemove')" class="p-1 bg-transparent border-0">
      <i class="fas fp-trash-blue stage-item-color"></i>
    </button>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  id: Number,
  order: Number,
  name: String,
  selected: Boolean
});

const emit = defineEmits(['onRemove', 'onUpdate', 'onClickCheckbox', 'onClickSelected']);
const editing = ref(false);
const localName = ref(props.name);
const isChecked = ref(false);

const onKeyupEnter = () => {
  emit('onUpdate', localName.value);
  editing.value = false;
};

const onClickCheckbox = () => {
  emit('onClickCheckbox');
};

const onClickSelected = () => {
  emit('onClickSelected');
};

watch(() => props.name, (val) => {
  localName.value = val;
});

watch(() => props.selected, (val) => {
  isChecked.value = val;
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