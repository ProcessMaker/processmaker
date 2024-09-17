<template>
  <div class="tw-w-full tw-relative tw-text-gray-600 tw-text-sm
      tw-border tw-rounded-xl tw-border-gray-300 tw-overflow-hidden tw-overflow-x-auto">
    <table class="tw-w-full tw-border-collapse">
      <thead class="tw-border-b tw-sticky tw-top-0 tw-bg-gray-100">
        <tr>
          <THeader
            v-for="(column, index) in columns"
            :key="index"
            :columns="columns"
            :column="column">
            <slot :name="`theader-${column.header}`"> </slot>
          </THeader>
        </tr>
      </thead>
      <tbody>
        <TRow v-for="(row, indexRow) in data" :key="indexRow">
          <TCell
            v-for="(column, indexColumn) in columns"
            :key="indexColumn"
            :columns="columns"
            :column="column"
            :row="row">
            <slot :name="`tcell-${indexRow}-${column.field}`"> </slot>
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

function generateUniqueId(length = 8) {
  const charset =
    "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  let uniqueId = "";
  for (let i = 0; i < length; i++) {
    const randomIndex = Math.floor(Math.random() * charset.length);
    uniqueId += charset[randomIndex];
  }
  return uniqueId;
}

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
  setup(props, { emit }) {
    return {};
  },
});
</script>
