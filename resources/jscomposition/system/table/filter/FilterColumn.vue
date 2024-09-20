<template>
  <div>
    <AppPopover
      v-model="show"
      :hover="false"
      position="bottom">
      <i
        class="hover:tw-cursor-pointer tw-px-1 fas fa-ellipsis-v"
        @click.prevent.stop="onClick" />

      <template #content>
        <div
          class="tw-shadow-md tw-text-xs tw-p-4 tw-h-60 tw-space-y-2 tw-flex tw-flex-col tw-justify-between
            tw-font-normal tw-bg-white tw-text-gray-600tw-overflow-hidden tw-rounded-lg tw-border tw-border-gray-300">
          <SortingButtons
            @asc="onAsc"
            @desc="onDesc" />

          <div class="tw-grow tw-overflow-auto tw-space-y-4">
            <FilterOperator
              ref="filterOperatorsRef"
              :operators="filter.operators"
              :type="filter.type"
              @change="(e) => onChangeFilterOperator(e)" />
          </div>

          <FooterButtons />
        </div>
      </template>
    </AppPopover>
  </div>
</template>
<script>
import { defineComponent, ref } from "vue";
import { AppPopover } from "../../../base/index";
import SortingButtons from "./SortingButtons.vue";
import FilterOperator from "./operator/FilterOperator.vue";
import FooterButtons from "./FooterButtons.vue";

export default defineComponent({
  components: {
    AppPopover,
    SortingButtons,
    FilterOperator,
    FooterButtons,
  },
  props: {
    // FilterInterface {
    //   operators = []  Array
    //   type = null String
    // }
    filter: Object,
  },
  setup() {
    const show = ref(false);

    // Model that saves the values
    const filterOperator = ref({
      id: new Date().getTime(),
      value: null,
    });

    const onClick = () => {
      show.value = !show.value;
    };

    const onAsc = () => {};

    const onDesc = () => {};

    const onChangeFilterOperator = (element) => {
      filterOperator.value.value = element;
    };

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
