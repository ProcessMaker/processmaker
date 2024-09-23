<template>
  <div
    class="tw-w-full tw-space-y-3 tw-flex tw-flex-col tw-overflow-hidden">
    <CaseFilter />

    <BadgesSection v-model="badgesData" />

    <FilterableTable
      :columns="columnsConfig"
      :data="data"
      class="tw-grow tw-overflow-y-scroll" />

    <Pagination
      :total="dataPagination.total"
      :page="dataPagination.page"
      :pages="dataPagination.pages"
      :options="optionsPerPage"
      @perPage="onPerPage"
      @go="onGo" />
  </div>
</template>
<script>
import { defineComponent, ref, onMounted } from "vue";
import { t } from "i18next";
import CaseFilter from "./components/CaseFilter.vue";
import BadgesSection from "./components/BadgesSection.vue";
import { Pagination } from "../../base";
import { getColumns } from "./config/columns";
import { FilterableTable } from "../../system";
import { badges } from "./config/badges";
import { getAllData } from "./api";

export default defineComponent({
  components: {
    CaseFilter,
    BadgesSection,
    Pagination,
    FilterableTable,
  },
  props: {
    listId: {
      type: String,
      default: () => "myCases",
    },
  },
  setup(props) {
    const badgesData = ref(badges);
    const columnsConfig = ref();
    const data = ref();

    const dataPagination = ref({
      total: 153,
      page: 0,
      pages: 10,
      perPage: 15,
    });

    const optionsPerPage = [
      {
        value: 15,
        label: `15 ${t("items")}`,
      },
      {
        value: 30,
        label: `30 ${t("items")}`,
      },
      {
        value: 50,
        label: `50 ${t("items")}`,
      },
    ];

    const getData = () => getAllData({
      type: props.listId,
      page: dataPagination.value.page,
      perPage: dataPagination.value.perPage,
    });

    const onGo = async (page) => {
      dataPagination.value.page = page;

      data.value = await getData();
    };

    const onPerPage = async (perPage) => {
      dataPagination.value.perPage = perPage;

      data.value = await getData();
    };

    onMounted(async () => {
      data.value = await getData();

      columnsConfig.value = getColumns(props.listId);
    });

    return {
      dataPagination,
      columnsConfig,
      data,
      badgesData,
      optionsPerPage,
      onGo,
      onPerPage,
    };
  },
});
</script>
