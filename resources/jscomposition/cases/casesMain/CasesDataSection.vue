<template>
  <div class="tw-w-full tw-space-y-3 tw-flex tw-flex-col tw-overflow-hidden tw-grow">
    <CaseFilter @enter="onChangeSearch" />
    <BadgesSection
      v-model="badgesData"
      @remove="onRemoveBadge"
    />
    <FilterableTable
      ref="table"
      :columns="columnsConfig"
      :data="data"
      :placeholder="showPlaceholder"
      @changeFilter="onChangeFilter"
    >
      <template #placeholder>
        <TablePlaceholder
          :placeholder="placeholderType"
          class="tw-grow"
        />
      </template>
    </FilterableTable>
    <Pagination
      :key="dataPagination.page"
      :class="{
        ' tw-opacity-50':showPlaceholder
      }"
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
import { useRouter, useRoute } from "vue-router/composables";
import CaseFilter from "./components/CaseFilter.vue";
import BadgesSection from "./components/BadgesSection.vue";
import { Pagination } from "../../base";
import { getColumns } from "./config/columns";
import { FilterableTable, TablePlaceholder } from "../../system";
import * as api from "./api";
import { user } from "./variables";
import {
  formatFilters, formatFilterBadges, formattedFilter, formatFilterSaved,
} from "./utils";

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
const showPlaceholder = ref(false);
const placeholderType = ref("loading");
const route = useRoute();
const defaultSort = ref("case_number:desc");

const dataPagination = ref({
  total: 0,
  page: 1,
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

const getFilters = async () => {
  const response = await api.getCaseFilters(route.params?.id);
  return response;
};

const getData = async () => {
  const sortFilter = filters.value.find((e) => e.sortable); // Searching the filter sortable

  const response = await api.getCaseData(props.listId, {
    params: {
      pageSize: dataPagination.value.perPage,
      page: dataPagination.value.page,
      userId: route.params?.id === "all" ? null : user.id,
      search: search.value || null,
      filterBy: JSON.stringify(formatFilters(filters.value)), // Format filters without sortable
      sortBy: sortFilter ? `${sortFilter.field}:${sortFilter.sortable}` : defaultSort.value,
    },
  });
  return response;
};

const hookGetData = async () => {
  placeholderType.value = "loading";
  showPlaceholder.value = true;

  const response = await getData();

  setMetaPagination(response.meta);

  setTimeout(() => {
    data.value = response.data;
    if (response.data && !response.data.length) {
      placeholderType.value = "empty-cases";
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

const onChangeSearch = async (val) => {
  search.value = val;
  dataPagination.value.page = 1; // Reset page to 1

  await hookGetData();
};

const saveFilters = async (filtersData) => {
  const response = await api.saveCaseFilters(formattedFilter(filtersData), route.params?.id);
  return response;
};

const onChangeFilter = async (filtersData) => {
  saveFilters(filtersData);

  filters.value = filtersData;
  dataPagination.value.page = 1; // Reset page to 1

  badgesData.value = formatFilterBadges(filtersData, columnsConfig.value);

  await hookGetData();
};

const onRemoveBadge = async (badge, index) => {
  badgesData.value.splice(index, 1);
  filters.value.splice(index, 1);
  dataPagination.value.page = 1; // Reset page to 1

  // Remove filter from table
  table.value.removeFilter(index);
  await hookGetData();
};

onMounted(async () => {
  getFilters().then((response) => {
    const filtersSaved = formatFilterSaved(response.data.filters);
    filters.value = filtersSaved;
    badgesData.value = formatFilterBadges(filtersSaved, columnsConfig.value);
    hookGetData();
  });

  columnsConfig.value = getColumns(props.listId);
});
</script>
<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.9s ease;
}
.fade-enter, .fade-leave-to {
  opacity: 0;
}
</style>
