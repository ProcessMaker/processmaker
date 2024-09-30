<template>
  <div>
    <BaseTable
      id="request-table"
      :columns="columnsConfig"
      :data="data" />
    <Pagination
      :total="dataPagination.total"
      :page="dataPagination.page"
      :pages="dataPagination.pages" />
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import { BaseTable, Pagination } from "../../../base";
import { getData } from "../api/index";
import { getColumns } from "../config/columns";
import { getRequestId } from "../variables";

export default defineComponent({
  components: { BaseTable, Pagination },
  setup() {
    const data = ref(null);
    const columnsConfig = ref(null);
    const dataPagination = ref({
      total: 15,
      page: 1,
      pages: 1,
      perPage: 15,
    });

    const formatData = () => ({
      params: {
        case_number: getRequestId(),
        include: "participants,activeTasks",
      },
      pagination: {
        page: dataPagination.value.page,
        perPage: dataPagination.value.perPage,
      },
    });

    onMounted(async () => {
      data.value = await getData(formatData());
      columnsConfig.value = getColumns("requests");
    });

    return {
      data,
      dataPagination,
      columnsConfig,
      formatData,
    };
  },
});
</script>
