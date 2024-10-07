<template>
  <div
    class="tw-w-full tw-space-y-3 tw-flex tw-flex-col tw-overflow-hidden"
  >
    <CaseFilter @enter="onChangeSearch" />

    <BadgesSection v-model="badgesData" />

    <FilterableTable
      :columns="columnsConfig"
      :data="data"
      class="tw-grow tw-overflow-y-scroll"
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
<script>
import { defineComponent, ref, onMounted } from "vue";
import CaseFilter from "./components/CaseFilter.vue";
import BadgesSection from "./components/BadgesSection.vue";
import { Pagination } from "../../base";
import { getColumns } from "./config/columns";
import { FilterableTable } from "../../system";
import { badges } from "./config/badges";
import * as api from "./api";
import { user } from "./variables";

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
      default: () => "",
    },
  },
  setup(props) {
    const badgesData = ref(badges);
    const columnsConfig = ref();
    const data = ref();
    const search = ref();

    const dataPagination = ref({
      total: 153,
      page: 1,
      pages: 10,
      perPage: 15,
    });

    const setMetaPagination = (meta) => {
      dataPagination.value = {
        total: meta.total,
        page: meta.currentPage,
        pages: meta.lastPage,
        perPage: meta.perPage,
      };
    };

    const getData = async () => {
      const response = await api.getCaseData(props.listId, {
        params: {
          pageSize: dataPagination.value.perPage,
          page: dataPagination.value.page,
          userId: user.id,
          search: search.value,
        },
      });

      data.value = response.data;
      setMetaPagination(response.meta);
    };

    const onGo = async (page) => {
      dataPagination.value.page = page;

      await getData();
    };

    const onPerPage = async (perPage) => {
      dataPagination.value.perPage = perPage;

      await getData();
    };

    const onChangeSearch = async (val) => {
      search.value = val;

      await getData();
    };

    onMounted(async () => {
      await getData();

      columnsConfig.value = getColumns(props.listId);
    });

    return {
      dataPagination,
      columnsConfig,
      data,
      badgesData,
      onGo,
      onPerPage,
      onChangeSearch,
    };
  },
});
</script>
