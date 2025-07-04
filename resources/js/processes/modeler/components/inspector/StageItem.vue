<template>
  <div class="tw-flex tw-items-center tw-space-x-2 tw-w-full tw-py-1">
    <span class="tw-w-6 text-center stage-item-number">{{ order }}</span>
    <i class="fas fa-grip-vertical stage-item-grip-vertical tw-cursor-move" />
    <div
      class="tw-flex-1 tw-cursor-pointer"
      @click="check"
      @dblclick.stop="uncheck"
    >
      <template v-if="editing">
        <input
          v-model="localName"
          class="tw-w-full tw-border tw-rounded px-2 form-control"
          :class="{ 'is-invalid': !stateName }"
          maxlength="200"
          @keyup.enter="onKeyupEnter"
          @input="onInput"
          @paste="onPaste"
        >
        <small class="tw-text-gray-500 tw-text-xs">
          {{ localName.length }}/200 caracteres
        </small>
      </template>
      <template v-else>
        <span
          class="tw-line-clamp-2"
          :class="{ 'font-bold stage-item-selected': selected }"
        >
          {{ name }}
        </span>
      </template>
    </div>
    <button
      class="p-1 bg-transparent border-0"
      @click="editing = !editing"
    >
      <i class="fas fp-pen-edit stage-item-color" />
    </button>
    <button
      class="p-1 bg-transparent border-0"
      @click="$emit('onRemove')"
    >
      <i class="fas fp-trash-blue stage-item-color" />
    </button>
  </div>
</template>

<script setup>
import { ref, watch, computed } from "vue";

const props = defineProps({
  id: {
    type: Number,
    default: 0,
  },
  order: {
    type: Number,
    default: 0,
  },
  name: {
    type: String,
    default: "",
  },
  selected: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["onRemove", "onUpdate", "onClickCheckbox", "onClickSelected"]);

const editing = ref(false);
const localName = ref(props.name);
const isChecked = ref(false);
const stateName = computed(() => localName.value.trim());

const onInput = (event) => {
  // Limit to 200 characters
  if (localName.value.length > 200) {
    localName.value = localName.value.substring(0, 200);
  }
};

const onPaste = (event) => {
  // Handle paste event to ensure proper character counting
  setTimeout(() => {
    if (localName.value.length > 200) {
      localName.value = localName.value.substring(0, 200);
    }
  }, 0);
};

const onKeyupEnter = () => {
  if (localName.value && localName.value.trim()) {
    emit("onUpdate", localName.value);
    editing.value = false;
  }
};

const check = () => {
  isChecked.value = true;
  emit("onClickCheckbox");
};

const uncheck = () => {
  isChecked.value = false;
  emit("onClickSelected");
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
