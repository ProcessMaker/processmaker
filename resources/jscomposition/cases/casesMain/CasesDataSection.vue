<template>
  <div
    class="tw-w-full tw-space-y-3 tw-flex tw-flex-col tw-overflow-hidden">
    <CaseFilter @enter="onChangeSearch" />

    <BadgesSection
      v-model="badgesData"
      @remove="onRemoveBadge" />

    <FilterableTable
      ref="table"
      :columns="columnsConfig"
      :data="data"
      class="tw-grow tw-overflow-y-scroll"
      @changeFilter="onChangeFilter" />

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
import CaseFilter from "./components/CaseFilter.vue";
import BadgesSection from "./components/BadgesSection.vue";
import { Pagination } from "../../base";
import { getColumns } from "./config/columns";
import { FilterableTable } from "../../system";
import * as api from "./api";
import { user } from "./variables";
import { formatFilters, formatFilterBadges } from "./utils";

const props = defineProps({
  listId: {
    type: String,
    default: () => "",
  },
});

const badgesData = ref([]);
const columnsConfig = ref();
const data = ref();
const search = ref();
const filters = ref([]);
const table = ref();

const dataPagination = ref({
  total: 0,
  page: 0,
  pages: 0,
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
  const sortFilter = filters.value.find((e) => e.sortable); // Searching the filter sortable

  const response = await api.getCaseData(props.listId, {
    params: {
      pageSize: dataPagination.value.perPage,
      page: dataPagination.value.page,
      userId: user.id,
      search: search.value || null,
      filterBy: JSON.stringify(formatFilters(filters.value)), // Format filters without sortable
      sortBy: sortFilter ? `${sortFilter.field}:${sortFilter.sortable}` : null,
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

const onChangeFilter = async (filtersData) => {
  filters.value = filtersData;

  badgesData.value = formatFilterBadges(filtersData, columnsConfig.value);

  await getData();
};

const onRemoveBadge = async (badge, index) => {
  badgesData.value.splice(index, 1);
  filters.value.splice(index, 1);

  // Remove filter from table
  table.value.removeFilter(index);
  await getData();
};

onMounted(async () => {
  await getData();

  columnsConfig.value = getColumns(props.listId);
});

</script>
