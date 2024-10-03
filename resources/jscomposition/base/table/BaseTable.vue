<template>
  <div
    class="tw-w-full tw-relative tw-text-gray-600 tw-text-sm
      tw-border tw-rounded-xl tw-border-gray-300 tw-overflow-hidden tw-overflow-x-auto"
  >
    <table class="tw-w-full tw-border-collapse tw-table-fixed">
      <thead class="tw-border-b tw-sticky tw-top-0 tw-z-10 tw-bg-gray-100">
        <tr>
          <THeader
            v-for="(column, index) in columns"
            :key="index"
            :columns="columns"
            :column="column"
          >
            <slot :name="`theader-${column.field}`" />
            <template #filter>
              <slot :name="`theader-filter-${column.field}`" />
            </template>
          </THeader>
          <th class="tw-sticky tw-w-0" />
        </tr>
      </thead>
      <tbody>
        <template v-for="(row, indexRow) in data">
          <TRow :key="`row-${indexRow}`">
            <template #[`cell`]>
              <TCell
                v-for="(column, indexColumn) in columns"
                :key="indexColumn"
                :columns="columns"
                :column="column"
                :row="row"
                @toogleContainer="(e)=>toogleContainer(e, indexRow)"
              >
                <slot :name="`tcell-${indexRow}-${column.field}`" />
              </TCell>
            </template>
            <template
              v-if="`checkEllpisisMenu(${indexRow})`"
              #[`menu`]
            >
              <slot :name="`ellipsis-menu-${indexRow}`" />
            </template>
          </TRow>
          <ContainerRow
            v-if="`checkContainerRow(${indexRow})`"
            :key="`container-${indexRow}`"
            :show-row="getShowContainer(indexRow)"
            :columns="columns"
          >
            <slot :name="`container-row-${indexRow}`" />
          </ContainerRow>
        </template>
      </tbody>
    </table>
  </div>
</template>

<script>
import {
  defineComponent, ref, onUpdated, useSlots, computed,
} from "vue";
import THeader from "./THeader.vue";
import TRow from "./TRow.vue";
import TCell from "./TCell.vue";
import ContainerRow from "./ContainerRow.vue";

export default defineComponent({
  components: {
    THeader,
    TRow,
    TCell,
    ContainerRow,
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
  setup(props) {
    const slots = useSlots();
    const configRow = ref([]);
    const showContainer = ref(false);

    const toogleContainer = (toogle, index) => {
      configRow.value.splice(index, 1, { showContainer: toogle });
    };

    const checkContainerRow = computed((index) => Object.hasOwn(slots, `container-row-${index}`));
    const checkEllpisisMenu = computed((index) => Object.hasOwn(slots, `ellipsis-menu-${index}`));

    onUpdated(async () => {
      if (configRow.value.length === 0) {
        configRow.value = props.data ? structuredClone(props.data).fill({ showContainer: false }) : [];
      }
    });

    const getShowContainer = (index) => configRow.value[index]?.showContainer;

    return {
      configRow,
      showContainer,
      toogleContainer,
      getShowContainer,
      slots,
      checkContainerRow,
      checkEllpisisMenu,
    };
  },
});
</script>
