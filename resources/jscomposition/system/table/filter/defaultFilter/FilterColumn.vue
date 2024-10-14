<template>
  <div>
    <AppPopover
      v-model="show"
      :hover="false"
      position="bottom">
      <i
        class="hover:tw-cursor-pointer tw-px-1 fas fa-ellipsis-v"
        @click.prevent="onClick" />

      <template #content>
        <div
          class="tw-shadow-md tw-text-xs tw-p-4 tw-h-60 tw-space-y-2 tw-flex tw-flex-col tw-justify-between
            tw-font-normal tw-bg-white tw-text-gray-600tw-overflow-hidden tw-rounded-lg tw-border tw-border-gray-300">
          <SortingButtons
            @asc="onAsc"
            @desc="onDesc" />

          <div class="tw-grow tw-overflow-auto tw-space-y-4">
            <FilterOperator
              :key="key"
              ref="filterOperatorsRef"
              :value="filterOperator"
              :operators="filter.operators"
              :type="filter.dataType"
              @change="(e) => onChangeFilterOperator(e)" />
          </div>

          <FooterButtons
            @cancel="onCancel"
            @clear="onClear"
            @apply="onApply" />
        </div>
      </template>
    </AppPopover>
  </div>
</template>
<script setup>
import { ref } from "vue";
import { AppPopover } from "../../../../base/index";
import SortingButtons from "./SortingButtons.vue";
import FilterOperator from "./operator/FilterOperator.vue";
import FooterButtons from "./FooterButtons.vue";

const emit = defineEmits(["change"]);

const props = defineProps({
  // FilterInterface {
  //   operators = []  Array
  //   dataType = null String
  // }
  filter: Object,
  value: Object,
});

const show = ref(false);

const key = ref(1);

// Model that saves the values
const filterOperator = ref(props.value);

const onClick = () => {
  show.value = !show.value;
};

const onAsc = () => {
  emit("change", {
    ...filterOperator.value,
    sortable: "asc",
  });

  show.value = false;
};

const onDesc = () => {
  emit("change", {
    ...filterOperator.value,
    sortable: "desc",
  });

  show.value = false;
};

const onCancel = () => {
  show.value = false;
};

const onClear = () => {
  const filter = filterOperator.value;

  filterOperator.value = null;
  key.value += 1;

  emit("clear", {
    ...filter,
    sortable: null,
  });

  show.value = false;
};

const onChangeFilterOperator = (element) => {
  filterOperator.value = element;
};

const onApply = () => {
  const filter = filterOperator.value;

  emit("change", {
    ...filter,
    sortable: null,
  });

  show.value = false;
};
</script>
