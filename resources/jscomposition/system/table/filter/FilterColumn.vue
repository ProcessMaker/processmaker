<template>
  <div>
    <AppPopover
      v-model="show"
      :hover="false"
      position="bottom">
      <i
        class="hover:tw-cursor-pointer tw-px-1 fas fa-ellipsis-v"
        @click.prevent.stop="onClick"></i>

      <template #content>
        <div
          class="tw-shadow-md tw-text-xs tw-p-4 tw-h-60 tw-space-y-2 tw-flex tw-flex-col tw-justify-between tw-font-normal tw-bg-white tw-text-gray-600 tw-overflow-hidden tw-rounded-lg tw-border tw-border-gray-300">
          <SortingButtons
            @asc="onAsc"
            @desc="onDesc" />

          <div class="tw-grow tw-overflow-auto tw-space-y-4">
            <FilterOperator
              ref="filterOperatorsRef"
              v-model="filterOperator"
              :operators="filter.operators"
              @change="(e) => onChangeFilterOperator(e)">
            </FilterOperator>
          </div>

          <FooterButtons />
        </div>
      </template>
    </AppPopover>
  </div>
</template>
<script>
import { defineComponent, computed, ref, onMounted } from "vue";
import { AppPopover, OutlineButton } from "../../../base/index";
import SortingButtons from "./SortingButtons.vue";
import FilterOperator from "./operator/FilterOperator.vue";
import FooterButtons from "./FooterButtons.vue";

// interface FilterInterface {
//   operators = []; // Array
//   type = null; // String
// }

export default defineComponent({
  components: {
    AppPopover,
    SortingButtons,
    FilterOperator,
    OutlineButton,
    FooterButtons,
  },
  props: {
    filter: Object // FilterInterface,
  },
  setup(props, { emit }) {
    const show = ref(false);
    const filterOperator = ref({
      operator: "=",
      id: new Date().getTime(),
      value: null,
      type: "string",
    });

    const onClick = () => {
      show.value = !show.value;
    };

    const onAsc = () => {};

    const onDesc = () => {};

    const onChangeFilterOperator = (element) => {
      filterOperator.value.value = element;
    };

    onMounted(() => {});

    return {
      show,
      filterOperator,
      onClick,
      onAsc,
      onDesc,
      onChangeFilterOperator,
    };
  },
});
</script>
