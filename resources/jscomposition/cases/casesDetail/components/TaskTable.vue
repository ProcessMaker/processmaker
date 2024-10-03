<template>
  <div class="tw-flex tw-flex-col tw-w-full tw-py-3 tw-overflow-hidden tw-space-y-3">
    <SortTable
      id="task-table"
      :columns="columnsConfig"
      :data="data"
      class="tw-grow tw-overflow-y-scroll tw-overflow-hidden"
      @changeFilter="onChangeFilter"
    />

    <Pagination
      :total="dataPagination.total"
      :page="dataPagination.page"
      :pages="dataPagination.pages"
      @perPage="onPerPage"
      @go="onGo"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { SortTable, Pagination } from "../../../system";
import { getDataTask } from "../api/index";
import { getColumns } from "../config/columns";
import { getRequestId } from "../variables";

const data = ref(null);
const columnsConfig = ref(null);
const dataPagination = ref({
  total: 153,
  page: 0,
  pages: 10,
  perPage: 15,
});

const formatData = (filter) => ({
  params: {
    case_number: getRequestId(),
    status: "ACTIVE",
    ...filter,
  },
  pagination: {
    page: dataPagination.value.page,
    perPage: dataPagination.value.perPage,
  },
});

const onGo = async (page) => {
  dataPagination.value.page = page;

  data.value = await getDataTask(formatData({}));
};

const onPerPage = async (perPage) => {
  dataPagination.value.perPage = perPage;

  data.value = await getDataTask(formatData({}));
};

const onChangeFilter = async (dataFilter) => {
  data.value = await getDataTask(formatData({
    order_by: dataFilter.field,
    order_direction: dataFilter.filter,
  }));
};

onMounted(async () => {
  data.value = await getDataTask(formatData({}));
  columnsConfig.value = getColumns("tasks");
});
</script>
