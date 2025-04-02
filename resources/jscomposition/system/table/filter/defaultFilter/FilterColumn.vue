<template>
  <div>
    <AppPopover
      v-model="show"
      :hover="false"
      position="bottom">
      <div
        class="tw-text-xs tw-py-1 tw-px-1 hover:tw-cursor-pointer
        hover:tw-bg-gray-200 tw-rounded"
        @click.prevent="onClick">
        <i :class="iconClass()" />
      </div>
      <template #content>
        <div
          :class="{
            'tw-h-60': filter.operators,
          }"
          class="tw-shadow-md tw-text-xs tw-space-y-2 tw-flex tw-flex-col tw-justify-between
            tw-font-normal tw-bg-white tw-text-gray-600 tw-overflow-hidden tw-rounded-lg tw-border tw-border-gray-300">
          <div
            v-if="filter.operators"
            class="tw-flex tw-flex-col tw-space-y-2 tw-px-4 tw-pt-4">
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
                :config="filter.config"
                @change="(e) => onChangeFilterOperator(e)" />
            </div>

            <FooterButtons
              @cancel="onCancel"
              @clear="onClear"
              @apply="onApply" />
          </div>
          <div
            v-if="filter.resetTable"
            :class="{
              'tw-border-t': filter.operators,
            }"
            class="tw-flex tw-border-gray-300 tw-justify-start tw-p-4">
            <div
              id="reset-table-btn"
              class="tw-flex tw-text-gray-500 tw-space-x-2 tw-bg-transparent
                hover:tw-opacity-80 hover:tw-cursor-pointer tw-justify-center tw-items-center"
              @click="onResetTable">
              <i class="fas fa-reply" />
              <span>{{ $t("Reset Table") }}</span>
            </div>
          </div>
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
  filterOperator.value = null;
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

const onResetTable = () => {
  emit("resetTable");

  show.value = false;
};

const onChangeFilterOperator = (element) => {
  filterOperator.value = element;
};

const onApply = () => {
  const filter = filterOperator.value;

  emit("change", {
    ...props.value,
    ...filter,
  });

  show.value = false;
};

const iconClass = () => {
  const { operator, sortable } = props.value || {};

  if (operator) {
    return "fas fa-filter";
  }

  const sortIcons = {
    asc: "fas fa-sort-amount-up-alt",
    desc: "fas fa-sort-amount-down-alt",
  };

  return sortIcons[sortable] || "fas fa-ellipsis-v";
};
</script>
