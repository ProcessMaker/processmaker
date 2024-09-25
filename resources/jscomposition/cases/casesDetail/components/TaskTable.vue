<template>
  <div>
    <BaseTable
      id="task-table"
      :columns="columnsConfig"
      :data="data" />
    <Pagination
      :total="dataPagination.total"
      :page="dataPagination.page"
      :pages="dataPagination.pages" />
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { BaseTable, Pagination } from "../../../base";
import { getData } from "../api/index";
import { getColumns } from "../config/columns";

const data = ref(null);
const columnsConfig = ref(null);
const dataPagination = ref({
  total: 153,
  page: 0,
  pages: 10,
  perPage: 15,
});

onMounted(async () => {
  data.value = await getData();
  columnsConfig.value = getColumns("tasks");
});
</script>
