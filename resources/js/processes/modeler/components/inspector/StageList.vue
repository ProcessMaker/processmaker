<template>
  <div>
    <draggable
      v-model="stages"
      item-key="id"
      handle=".fa-grip-vertical"
      class="divide-y"
      @end="onReorder"
    >
      <StageItem
        v-for="(item, index) in stages"
        :id="item.id"
        :key="index"
        :order="item.order"
        :name="item.name"
        :selected="item.selected"
        @onUpdate="onUpdate(index, $event)"
        @onRemove="onRemove(index)"
        @onClickCheckbox="onClickCheckbox(index)"
        @onClickSelected="onClickSelected(index)"
      />
    </draggable>

    <div
      v-show="adding"
      class="tw-flex tw-items-center tw-space-x-2 tw-w-full tw-py-1"
    >
      <span class="tw-w-6 text-center stage-item-number">{{ totalStages + 1 }}</span>
      <i class="fas fa-grip-vertical stage-item-grip-vertical tw-cursor-move" />
      <div class="tw-flex-1">
        <input
          v-model="newStage"
          class="tw-w-full tw-border tw-rounded px-2 form-control"
          :class="{ 'is-invalid': !stateNewStage }"
          :placeholder="$t('Enter name')"
          maxlength="200"
          @keyup.enter="onKeyupEnter"
          @input="onInput"
          @paste="onPaste"
        >
        <small class="tw-text-gray-500 tw-text-xs">
          {{ newStage.length }}/200 {{ $t('characters') }}
        </small>
      </div>
    </div>
    <div class="tw-flex tw-justify-end tw-mt-2">
      <button
        v-if="!loadingStages"
        :disabled="disableButton"
        class="tw-bg-blue-500 text-white tw-text-sm tw-px-2 tw-py-0.5 tw-rounded"
        :class="{'tw-bg-gray-300 tw-cursor-not-allowed': disableButton}"
        @click="onClickAdd"
      >
        <i class="fas fa-plus" />
      </button>
    </div>
  </div>
</template>

<script setup>
import {
  ref, computed, watch,
} from "vue";
import draggable from "vuedraggable";
import { t } from "i18next";
import StageItem from "./StageItem.vue";

const props = defineProps({
  initialStages: {
    type: Array,
    default: () => [],
  },
  loadingStages: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(["onAdd", "onUpdate", "onRemove", "onChange", "onClickCheckbox", "onClickSelected"]);

const adding = ref(false);
const newStage = ref("");
const stages = ref([...props.initialStages]);
const totalStages = computed(() => stages.value.length);
const disableButton = computed(() => totalStages.value >= 8);
const stateNewStage = ref(true);

const onInput = (event) => {
  // Limit to 200 characters
  if (newStage.value.length > 200) {
    newStage.value = newStage.value.substring(0, 200);
  }
};

const onPaste = (event) => {
  // Handle paste event to ensure proper character counting
  setTimeout(() => {
    if (newStage.value.length > 200) {
      newStage.value = newStage.value.substring(0, 200);
    }
  }, 0);
};

const onClickAdd = () => {
  if (disableButton.value) {
    return;
  }
  adding.value = !adding.value;
  newStage.value = "";
};

const generateUniqueId = () => {
  const timestamp = Math.floor(Date.now() / 1000);
  const random = Math.floor(Math.random() * 1000);
  const paddedRandom = random.toString().padStart(3, "0");
  const combined = `${timestamp}${paddedRandom}`.slice(0, 10);
  return Number(combined);
};

const onKeyupEnter = () => {
  if (newStage.value.trim()) {
    stateNewStage.value = true;
    const uniqueId = generateUniqueId();
    const order = stages.value.length + 1;
    stages.value.push({
      id: uniqueId,
      order,
      name: newStage.value,
      selected: false,
    });
    newStage.value = "";
    adding.value = false;
    emit("onAdd", stages.value, stages.value.length - 1);
    emit("onChange", stages.value);
  } else {
    stateNewStage.value = false;
  }
};

const onReorder = () => {
  stages.value.forEach((item, index) => {
    item.order = index + 1;
  });
  emit("onChange", stages.value);
};

const onUpdate = (index, newName) => {
  const oldName = stages.value[index].name;
  stages.value[index].name = newName;
  emit("onUpdate", stages.value, index, newName, oldName);
  emit("onChange", stages.value);
};

const onRemove = (index) => {
  ProcessMaker.confirmModal(
    t("Caution!"),
    t("Are you sure you want to delete the stage?"),
    "",
    () => {
      const removed = stages.value.splice(index, 1);
      onReorder();
      emit("onRemove", stages.value, index, removed[0]);
      emit("onChange", stages.value);
    },
  );
};

const onClickCheckbox = (index) => {
  stages.value.forEach((stage, i) => {
    Vue.set(stage, "selected", i === index);
  });
  emit("onClickCheckbox", stages.value[index]);
};

const onClickSelected = (index) => {
  stages.value.forEach((stage, i) => {
    if (i === index) {
      Vue.set(stage, "selected", false);
    }
  });
  emit("onClickSelected", stages.value[index]);
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
