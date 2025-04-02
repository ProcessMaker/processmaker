<template>
  <div class="tw-w-full tw-flex tw-flex-col tw-overflow-hidden tw-grow">
    <CaseFilter
      class="tw-pb-3"
      @enter="onChangeSearch" />
    <BadgesSection
      v-model="badgesData"
      class="tw-pb-3"
      @remove="onRemoveBadge">
      <template #endsection>
        <div
          id="reset-table-btn"
          class="tw-flex tw-text-gray-500 tw-space-x-2 tw-bg-transparent
            tw-text-xs tw-border-l tw-border-gray-300 tw-pl-2
            hover:tw-opacity-80 hover:tw-cursor-pointer tw-justify-center tw-items-center"
          @click="onResetTable">
          <i class="fas fa-reply" />
          <span>{{ $t("Reset Table") }}</span>
        </div>
      </template>
    </BadgesSection>
    <FilterableTable
      ref="table"
      :columns="columnsConfig"
      :data="data"
      class="tw-text-[#4F5154]"
      :placeholder="showPlaceholder"
      @changeFilter="onChangeFilter"
      @stopResize="onStopResize"
      @resetFilters="onChangeFilter">
      <template #placeholder>
        <TablePlaceholder
          :placeholder="placeholderType"
          class="tw-grow" />
      </template>
    </FilterableTable>
    <Pagination
      ref="paginator"
      :key="dataPagination.page"
      :class="{
        'tw-opacity-50':showPlaceholder,
        'tw-pt-3':true
      }"
      :total="dataPagination.total"
      :page="dataPagination.page"
      :pages="dataPagination.pages"
      @perPage="onPerPage"
      @go="onGo" />
  </div>
</template>
<script setup>
import { ref, onMounted, nextTick } from "vue";
import { useRouter, useRoute } from "vue-router/composables";
import CaseFilter from "./components/CaseFilter.vue";
import BadgesSection from "./components/BadgesSection.vue";
import { Pagination } from "../../base";
import columns, { getColumns } from "./config/columns";
import { FilterableTable, TablePlaceholder } from "../../system";
import * as api from "./api";
import { user, useStore } from "./variables";
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
const paginator = ref();
const showPlaceholder = ref(false);
const placeholderType = ref("loading");
const route = useRoute();
const defaultSort = ref("case_number:desc");
const store = useStore();

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
  saveFilters(filters.value);
  // Remove filter from table
  table.value.removeFilter(index);
  await hookGetData();
};

const onResetTable = async () => {
  filters.value = [];
  badgesData.value = [];
  dataPagination.value.page = 1; // Reset page to 1
  saveFilters(filters.value);

  table.value.removeAllFilters();
  await hookGetData();
};

const autoPagination = () => {
  const heightTable = table.value.getHeightTBody();
  const heightThead = table.value.getHeightThead();

  const rowsNumber = heightTable / heightThead;

  const pageSizes = [15, 30, 50];
  const appropriateSize = pageSizes.find((size) => rowsNumber < size) || pageSizes[pageSizes.length - 1];

  dataPagination.value.page = 1;
  dataPagination.value.perPage = appropriateSize;
  paginator.value.setPerPage(appropriateSize);
};

const updateUserConfiguration = async () => {
  const userConf = store.getters["core:cases/getUserConfiguration"];

  const response = await api.updateUserConfiguration(userConf);
  return response;
};

const onStopResize = (column) => {
  store.commit("core:cases/updateColumnWidth", {
    column: column.field,
    width: column.width,
  });
  updateUserConfiguration();
};

const updateColumnWithUserConfiguration = (columnsDefault) => {
  const casesColumns = store.getters["core:cases/getCasesColumns"] || {};

  columnsDefault.forEach((column) => {
    if (casesColumns[column.field]) {
      column.width = casesColumns[column.field].width;
    }
  });

  return columnsDefault;
};

onMounted(() => {
  columnsConfig.value = getColumns(props.listId);
  columnsConfig.value = updateColumnWithUserConfiguration(columnsConfig.value);
  getFilters().then((response) => {
    const filtersSaved = formatFilterSaved(response.data.filters);
    filters.value = filtersSaved;
    badgesData.value = formatFilterBadges(filtersSaved, columnsConfig.value);
    table.value.addFilters(filtersSaved);

    // This section only for auto pagination
    nextTick(() => {
      autoPagination();
      hookGetData();
    });
  });
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
