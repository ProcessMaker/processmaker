<template>
  <div class="tw-flex tw-flex-col tw-w-full tw-grow tw-py-3 tw-overflow-hidden tw-space-y-3">
    <SortTable
      id="task-table"
      :columns="columnsConfig"
      :data="data"
      :placeholder="showPlaceholder"
      @changeFilter="onChangeFilter"
    >
      <template #placeholder>
        <TablePlaceholder
          :placeholder="placeholderType"
          class="tw-grow" />
      </template>
    </SortTable>

    <Pagination
      :total="dataPagination.total"
      :page="dataPagination.page"
      :pages="dataPagination.pages"
      @perPage="onPerPage"
      @go="onGo" />
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue";
import { SortTable, Pagination, TablePlaceholder } from "../../../system";
import { getDataRequests } from "../api/index";
import { getColumns } from "../config/columns";
import { getCaseNumber } from "../variables";

const data = ref(null);
const columnsConfig = ref(null);
const filter = ref();
// Placeholder variables
const showPlaceholder = ref(false);
const placeholderType = ref("loading");

// Pagination variable
const dataPagination = ref({
  total: 0,
  page: 1,
  pages: 0,
  perPage: 15,
});

const getData = async () => {
  const response = await getDataRequests({
    params: {
      case_number: getCaseNumber(),
      status: "ACTIVE",
      include: "activeTasks",
      order_by: filter.value?.field,
      order_direction: filter.value?.value,
      page: dataPagination.value.page,
      per_page: dataPagination.value.perPage,
    },
  });

  return response;
};

const setMetaPagination = (meta) => {
  dataPagination.value = {
    total: meta.total,
    page: meta.currentPage,
    pages: meta.lastPage,
    perPage: meta.perPage,
  };
};

const hookGetData = async () => {
  placeholderType.value = "loading";
  showPlaceholder.value = true;

  const response = await getData();

  setMetaPagination(response.meta);

  setTimeout(() => {
    data.value = response.data;
    if (response.data && !response.data.length) {
      placeholderType.value = "empty-tasks";
      return;
    }
    showPlaceholder.value = false;
  }, 300);
};

const onGo = async (page) => {
  dataPagination.value.page = page;

  await hookGetData();
};

const onPerPage = async (perPage) => {
  dataPagination.value.perPage = perPage;

  await hookGetData();
};

const onChangeFilter = async (dataFilter) => {
  filter.value = dataFilter;

  await hookGetData();
};

onMounted(async () => {
  columnsConfig.value = getColumns("requests");

  await hookGetData();
});
</script>
