<template>
  <div class="tw-flex tw-items-center tw-space-x-2 tw-w-full tw-py-1">
    <span class="tw-w-6 text-center stage-item-number">{{ order }}</span>
    <i class="fas fa-grip-vertical stage-item-grip-vertical tw-cursor-move" />
    <div class="tw-flex-1">
      <template v-if="editing">
        <input
          v-model="localName"
          class="tw-w-full tw-border tw-rounded px-2"
          @keyup.enter="onKeyupEnter">
      </template>
      <template v-else>
        <span
          class="tw-cursor-pointer"
          :class="{ 'font-bold stage-item-selected': selected }"
          @click.stop.prevent="handleClick">{{ name }}
        </span>
      </template>
    </div>
    <button
      class="p-1 bg-transparent border-0"
      @click="editing = !editing">
      <i class="fas fp-pen-edit stage-item-color" />
    </button>
    <button
      class="p-1 bg-transparent border-0"
      @click="$emit('onRemove')">
      <i class="fas fp-trash-blue stage-item-color" />
    </button>
  </div>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
  id: Number,
  order: Number,
  name: String,
  selected: Boolean,
});

const emit = defineEmits(["onRemove", "onUpdate", "onClickSelected", "onClickStage"]);
const editing = ref(false);
const localName = ref(props.name);
const clicks = ref(0);
const delay = 300;

const onKeyupEnter = () => {
  emit("onUpdate", localName.value);
  editing.value = false;
};

const onClickSelected = () => {
  emit("onClickSelected");
};

const handleClick = () => {
  clicks.value += 1;
  if (clicks.value === 1) {
    setTimeout(() => {
      if (clicks.value === 1) {
        emit("onClickStage", true);
      } else if (clicks.value === 2) {
        emit("onClickStage", false);
      }
      clicks.value = 0;
    }, delay);
  }
};
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
