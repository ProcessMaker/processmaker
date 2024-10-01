<template>
  <div>
    <SortTable
      id="request-table"
      :columns="columnsConfig"
      :data="data"
      @changeFilter="onChangeFilter" />
    <Pagination
      :total="dataPagination.total"
      :page="dataPagination.page"
      :pages="dataPagination.pages" />
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import { SortTable, Pagination } from "../../../system";
import { getData } from "../api/index";
import { getColumns } from "../config/columns";
import { getRequestId } from "../variables";

export default defineComponent({
  components: { SortTable, Pagination },
  setup() {
    const data = ref(null);
    const columnsConfig = ref(null);
    const dataPagination = ref({
      total: 15,
      page: 1,
      pages: 1,
      perPage: 15,
    });

    const formatData = (filter) => ({
      params: {
        case_number: getRequestId(),
        include: "participants,activeTasks",
        ...filter,
      },
      pagination: {
        page: dataPagination.value.page,
        perPage: dataPagination.value.perPage,
      },
    });

    const onChangeFilter = async (dataFilter) => {
      data.value = await getData(formatData({
        order_by: dataFilter.field,
        order_direction: dataFilter.filter,
      }));
    };

    onMounted(async () => {
      data.value = await getData(formatData({}));
      columnsConfig.value = getColumns("requests");
    });

    return {
      data,
      dataPagination,
      columnsConfig,
      formatData,
      onChangeFilter,
    };
  },
});
</script>
