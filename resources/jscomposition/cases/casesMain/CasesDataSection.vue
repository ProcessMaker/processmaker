<template>
  <div
    class="tw-w-full tw-space-y-3 tw-flex tw-flex-col tw-overflow-hidden">
    <CaseFilter />

    <BadgesSection v-model="badgesData" />

    <FilterableTable
      :columns="columnsConfig"
      :data="data"
      class="tw-grow tw-overflow-y-scroll">
    </FilterableTable>

    <!-- <BaseTable :columns="columnsConfig" :data="data" class="tw-grow tw-overflow-y-scroll">
      <template v-for="(column, index) in columnsConfig" v-slot:[`theader-filter-${column.field}`]>
        <FilterColumn></FilterColumn>
      </template>
    </BaseTable>   -->

    <Pagination />
  </div>
</template>
<script>
import { defineComponent, ref, onMounted } from "vue";
import CaseFilter from "./components/CaseFilter.vue";
import BadgesSection from "./components/BadgesSection.vue";
import { Pagination } from "../../base";
import { getColumns } from "./config/columns";
import { Breadcrums } from "../../system";
import { badges } from "./config/badges";
import { getData, getAllData } from "./api";
import { FilterableTable } from "../../system";

export default defineComponent({
  props: {
    listId: {
      type: String,
      default: () => "myCases",
    },
  },
  components: {
    CaseFilter,
    BadgesSection,
    Pagination,
    Breadcrums,
    FilterableTable,
  },
  setup(props, { emit }) {
    const badgesData = ref(badges);
    const columnsConfig = ref();
    const data = ref();

    onMounted(async () => {
      data.value = await getAllData({ type: props.listId, page: 15 });

      columnsConfig.value = getColumns(props.listId);
    });

    return {
      columnsConfig,
      data,
      badgesData,
    };
  },
});
</script>
