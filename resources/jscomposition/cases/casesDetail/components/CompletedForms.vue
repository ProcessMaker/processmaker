<template>
  <div>
    <BaseTable
      id="completed-forms"
      :columns="columnsConfig"
      :data="data"
    >
      <template
        v-for="(item, index) in data"
        #[`container-row-${index}`]
      >
        <display-form
          :key="`display-${index}`"
          :data="item"
        />
      </template>
      <template
        v-for="(item, index) in data"
        #[`ellipsis-menu-${index}`]
      >
        <ellipsis-menu
          :key="`ellipsis-${index}`"
          :data="item"
        />
      </template>
    </BaseTable>
    <Pagination
      :total="dataPagination.total"
      :page="dataPagination.page"
      :pages="dataPagination.pages"
      @perPage="onPerPage"
      @go="onGo"
    />
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import { BaseTable } from "../../../base";
import { SortTable, Pagination } from "../../../system";
import DisplayForm from "./DisplayForm.vue";
import EllipsisMenu from "./EllipsisMenu.vue";
import { getDataTask } from "../api/index";
import { getColumns } from "../config/columns";
import { getRequestId } from "../variables";

export default defineComponent({
  components: {
    SortTable, Pagination, DisplayForm, EllipsisMenu, BaseTable,
  },
  setup() {
    const data = ref(null);
    const columnsConfig = ref(null);
    const dataPagination = ref({
      total: 153,
      page: 1,
      pages: 10,
      perPage: 15,
    });

    const formatData = () => ({
      params: {
        case_number: getRequestId(),
        status: "CLOSED",
        includeScreen: 1,
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

    onMounted(async () => {
      data.value = await getDataTask(formatData({}));
      columnsConfig.value = getColumns("completed_forms");
    });

    return {
      data,
      columnsConfig,
      dataPagination,
      onGo,
      onPerPage,
    };
  },
});
</script>
