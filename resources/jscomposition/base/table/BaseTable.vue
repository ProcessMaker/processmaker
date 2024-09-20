<template>
  <div
    class="tw-w-full tw-relative tw-text-gray-600 tw-text-sm
      tw-border tw-rounded-xl tw-border-gray-300 tw-overflow-hidden tw-overflow-x-auto">
    <table class="tw-w-full tw-border-collapse">
      <thead class="tw-border-b tw-sticky tw-top-0 tw-z-10 tw-bg-gray-100">
        <tr>
          <THeader
            v-for="(column, index) in columns"
            :key="index"
            :columns="columns"
            :column="column">
            <slot :name="`theader-${column.field}`" />
            <template #filter>
              <slot :name="`theader-filter-${column.field}`" />
            </template>
          </THeader>
        </tr>
      </thead>
      <tbody>
        <TRow
          v-for="(row, indexRow) in data"
          :key="indexRow">
          <TCell
            v-for="(column, indexColumn) in columns"
            :key="indexColumn"
            :columns="columns"
            :column="column"
            :row="row">
            <slot :name="`tcell-${indexRow}-${column.field}`" />
          </TCell>
        </TRow>
      </tbody>
    </table>
  </div>
</template>

<script>
import { defineComponent } from "vue";
import THeader from "./THeader.vue";
import TRow from "./TRow.vue";
import TCell from "./TCell.vue";

export default defineComponent({
  components: {
    THeader,
    TRow,
    TCell,
  },
  props: {
    columns: {
      type: Array,
      default: () => [],
    },
    data: {
      type: Array,
      default: () => [],
    },
    config: {
      type: Object,
      default: () => ({}),
    },
  },
  setup() {
    return {};
  },
});
</script>
