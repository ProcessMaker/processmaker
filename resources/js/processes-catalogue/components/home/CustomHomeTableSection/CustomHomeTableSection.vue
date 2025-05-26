<template>
  <div class="tw-flex tw-flex-col tw-gap-4">
    <CustomHomeFilter
      @enter="onChangeInputFilter" />
    <FilterableTable
      ref="table"
      :columns="columnsConfig"
      :data="data"
      class="tw-text-[#4F5154]"
      :placeholder="showPlaceholder"
      @changeFilter="onChangeFilter"
      @stopResize="onStopResize"
      @resetFilters="onChangeFilter">
      <template #placeholder>
        <TablePlaceholder
          :placeholder="placeholderType"
          class="tw-grow" />
      </template>
    </FilterableTable>
    <Pagination
      ref="paginator"
      :key="dataPagination.page"
      :class="{
        'tw-opacity-50':showPlaceholder,
        'tw-pt-3':true
      }"
      :total="dataPagination.total"
      :page="dataPagination.page"
      :pages="dataPagination.pages"
      @perPage="onPerPage"
      @go="onGo" />
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { FilterableTable, TablePlaceholder } from "../../../../../jscomposition/system";
import { buildColumns, buildFilters } from "../config";
import { Pagination } from "../../../../../jscomposition/base";
import CustomHomeFilter from "./CustomHomeFilter.vue";
import { prepareToGetRequests } from "./CustomHomeTableSection";
import { user } from "../../variables";

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
  advancedFilter: {
    type: Array,
    required: false,
  },
});

const defaultCasesColumns = ref(JSON.parse(props.process?.launchpad?.properties)?.my_cases_columns || []);

const showPlaceholder = ref(false);
const columnsConfig = ref();
const placeholderType = ref("loading");
const dataPagination = ref({
  total: 0,
  page: 1,
  pages: 0,
  perPage: 15,
});

const dataTable = ref({
  filter: "",
  orderDirection: "DESC",
  orderBy: "ID",
  advancedFilter: [], // props.advancedFilter,
  pmql: `(user_id = ${user.id}) AND (process_id = ${props.process.id})`,
});

const data = ref([]);

const setMetaPagination = (meta) => {
  dataPagination.value = {
    total: meta.total,
    page: meta.current_page,
    pages: meta.last_page,
    perPage: meta.per_page,
  };
};

const hookData = async () => {
  placeholderType.value = "loading";
  showPlaceholder.value = true;

  const response = await prepareToGetRequests({
    page: dataPagination.value.page,
    perPage: dataPagination.value.perPage,
    orderDirection: dataTable.value.orderDirection,
    orderBy: dataTable.value.orderBy,
    // nonSystem: true, // Hardcoded to true
    // processesIManage: false, // Hardcoded to false
    // allInbox: true, // Hardcoded to false
    pmql: dataTable.value.pmql,
    filter: dataTable.value.filter,
    // statusFilter: "ACTIVE,CLOSED",
    advancedFilter: dataTable.value.advancedFilter,
  });

  setMetaPagination(response.meta);

  setTimeout(() => {
    data.value = response.data;

    data.value.forEach((item) => {
      item.progress = Math.floor(Math.random() * 101);
      item.stage = ["Grants", "Scholarships", "Loans", "Out of pocket remaining"][Math.floor(Math.random() * 4)];
      item.data = {
        program: {
          name: `Program ${Math.floor(Math.random() * 10) + 1}`,
          type: `Type ${Math.floor(Math.random() * 5) + 1}`,
          source: `Source ${Math.floor(Math.random() * 3) + 1}`,
          deadline: `${Math.floor(Math.random() * 30) + 1} days`,
        },
      };
    });

    if (response.data && !response.data.length) {
      placeholderType.value = "empty-cases";
      return;
    }
    showPlaceholder.value = false;
  }, 300);
};

const onPerPage = async (perPage) => {
  dataPagination.value.perPage = perPage;
  await hookData();
};

const onGo = async (page) => {
  dataPagination.value.page = page;
  await hookData();
};

const onChangeFilter = async (filterData) => {
  const newFilterToAdvancedFilter = buildFilters({
    defaultColumns: defaultCasesColumns.value,
    filterData,
  });
  dataTable.value.advancedFilter = newFilterToAdvancedFilter;

  await hookData();
};

const onChangeInputFilter = async (value) => {
  dataTable.value.filter = value;
  dataPagination.value.page = 1;
  await hookData();
};

const onStopResize = async () => {
};

onMounted(async () => {
  // Default columns from BE
  columnsConfig.value = buildColumns(defaultCasesColumns.value);
  hookData();
});

</script>
