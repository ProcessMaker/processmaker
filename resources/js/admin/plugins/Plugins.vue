<template>
  <div class="data-table px-3 page-content">
    <div class="d-flex flex-column flex-md-row mb-2" v-show="!shouldShowLoader">
      <div class="flex-grow-1 mr-md-2 mb-3 mb-md-0">
        <b-input-group class="w-100">
          <b-form-input
            id="search-box"
            v-model="filter"
            :placeholder="$t('Search')"
            :aria-label="$t('Search')"
          />
          <b-input-group-append>
            <b-button variant="primary" type="button" :aria-label="$t('Search')">
              <i class="fas fa-search" />
            </b-button>
          </b-input-group-append>
        </b-input-group>
      </div>
      <div class="d-flex ml-md-2">
        <b-button variant="secondary" type="button" @click="showCreateModal" :aria-label="$t('Create Plugin')">
          <i class="fas fa-plus" />
          {{ $t("Plugin") }}
        </b-button>
      </div>
    </div>

    <data-loading
      :for="/\/plugins(\?|$)/"
      :data-loading-id="dataLoadingId"
      v-show="shouldShowLoader"
      :empty="$t('No Data Available')"
      :empty-desc="$t('No Data Available')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="card card-body table-card">
      <vuetable
        :load-on-start="false"
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        track-by="name"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        :no-data-template="$t('No Data Available')"
        pagination-path="meta"
      >
        <template slot="name" slot-scope="props">
          <span :data-cy="'plugin-name-' + props.rowData.name">{{ props.rowData.name }}</span>
        </template>

        <template slot="enabled" slot-scope="props">
          {{ formatEnabled(props.rowData.enabled) }}
        </template>

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="edit(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Edit')"
                :class="props.rowData.enabled !== 'Enabled' ? 'disabled' : ''"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="doDelete(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Delete')"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="doToggle(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Toggle')"
              >
                <i class="fas fa-lg fa-fw" :class="{ 'fa-toggle-on': props.rowData.enabled === 'Enabled', 'fa-toggle-off': props.rowData.enabled === 'Disabled' }"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        :single="$t('Plugin')"
        :plural="$t('Plugins')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      />
    </div>

    <pm-modal
      ref="createPlugin"
      id="createPlugin"
      :title="title"
      style="display: none"
      :set-custom-buttons="true"
      :custom-buttons="customModalButtons"
      @close="hidePluginModal"
      @hidden="resetValues"
      @save="save"
    >
      <required />
      <b-container class="mb-2">
        <b-form-group
          required
          :label="$t('Repository URL')"
          :state="!getError('url')"
          :invalid-feedback="getError('url') || ''"
          role="alert"
        >
          <b-form-input
            v-model="plugin.url"
            name="url"
            required
            :aria-required="'true'"
          />
          <b-form-text>{{ $t("Repository URL must be a valid URL") }}</b-form-text>
        </b-form-group>
        <b-form-group
          :label="$t('Branch')"
          :state="!getError('branch')"
          :invalid-feedback="getError('branch') || ''"
          role="alert"
        >
          <b-form-input v-model="plugin.branch" name="branch" :state="!getError('branch')" />
          <b-form-text>{{ $t("Branch must be a valid branch name") }}</b-form-text>
        </b-form-group>
        <b-form-group
          :label="$t('Tag')"
          :state="!getError('tag')"
          :invalid-feedback="getError('tag') || ''"
          role="alert"
        >
          <b-form-input v-model="plugin.tag" name="tag" :state="!getError('tag')" />
          <b-form-text>{{ $t("Tag must be a valid tag name") }}</b-form-text>
        </b-form-group>
        <plugin-output :user-id="userId" />
      </b-container>
    </pm-modal>
  </div>
</template>

<script setup>
import {
  ref,
  reactive,
  computed,
  watch,
  onMounted,
  onBeforeMount,
  onUnmounted,
  getCurrentInstance,
} from "vue";
import Vuetable from "vuetable-2/src/components/Vuetable";
import Pagination from "../../components/common/Pagination";
import DataLoading from "../../components/common/DataLoading.vue";
import debounce from "lodash/debounce";
import PluginOutput from "./components/PluginOutput.vue";

const props = defineProps({
  permission: {
    type: [Boolean, Object, Array],
    default: false,
  },
});

const instance = getCurrentInstance();
const $t = (...args) => instance?.proxy?.$t(...args);

const createPlugin = ref(null);
const pagination = ref(null);

const plugin = reactive({
  id: null,
  url: "",
  branch: "",
  tag: "",
});

const filter = ref("");
const errors = ref({});
const disabled = ref(false);
const title = ref("");
const isRunning = ref(false);
const isDone = ref(false);
const customModalButtons = ref([]);
const loading = ref(true);
const data = ref([]);
const page = ref(1);
const perPage = ref(15);
const orderBy = ref("name");
const orderDirection = ref("asc");

const dataLoadingId = Math.random();
const apiDataLoading = ref(true);
const apiNoResults = ref(false);

const shouldShowLoader = computed(
  () => apiDataLoading.value || apiNoResults.value
);

const userId = computed(
  () =>
    _.get(document.querySelector('meta[name="user-id"]'), "content") || ""
);

const menuActions = [
  {
    value: "toggle-item",
    content: "Toggle Plugin",
    icon: "fas fa-toggle-on",
    ariaDescribedBy: "data.name",
  },
  {
    value: "delete-item",
    content: "Delete Plugin",
    icon: "fas fa-trash-alt",
    ariaDescribedBy: "data.id",
  },
];

const sortOrder = ref([
  {
    field: "name",
    sortField: "name",
    direction: "asc",
  },
]);

const fields = computed(() => [
  {
    title: () => $t("Name"),
    name: "__slot:name",
    sortField: "name",
  },
  {
    title: () => $t("Description"),
    name: "description",
    sortField: "description",
  },
  {
    title: () => $t("Status"),
    name: "__slot:enabled",
    sortField: "enabled",
  },
  {
    name: "__slot:actions",
    title: "",
  },
]);

const css = {
  tableClass: "table table-hover table-responsive-lg text-break mb-0",
  loadingClass: "loading",
  detailRowClass: "vuetable-detail-row",
  handleIcon: "grey sidebar icon",
  sortableIcon: "fas fa-sort",
  ascendingIcon: "fas fa-sort-up",
  descendingIcon: "fas fa-sort-down",
  ascendingClass: "ascending",
  descendingClass: "descending",
  renderIcon(classes, options) {
    return `<i class="${classes.join(" ")}"></i>`;
  },
};

const jsonRows = (rows) => {
  if (rows.length === 0 || !_.has(_.head(rows), "_json")) {
    if (!Array.isArray(rows) && typeof rows === "object") {
      return Object.values(rows);
    }
    return rows;
  }
  return rows.map((row) => JSON.parse(row._json));
};

const transform = (payload) => {
  const out = payload;
  out.meta.last_page = out.meta.total_pages;
  out.meta.from = (out.meta.current_page - 1) * out.meta.per_page;
  out.meta.to = out.meta.from + out.meta.count;
  out.data = jsonRows(out.data);
  return out;
};

const buildPagedPayload = (pluginsData) => {
  let rows = [...pluginsData];
  if (filter.value) {
    const filterLower = filter.value.toLowerCase();
    rows = rows.filter((item) => {
      const name = (item.name || "").toLowerCase();
      const status = String(item.enabled).toLowerCase();
      return (
        name.indexOf(filterLower) > -1 ||
        status.indexOf(filterLower) > -1
      );
    });
  }
  const field = orderBy.value || "name";
  const dir = orderDirection.value === "desc" ? "desc" : "asc";
  rows = _.orderBy(rows, [field], [dir]);

  const per = parseInt(perPage.value, 10);
  const total = rows.length;
  const totalPages = Math.max(1, Math.ceil(total / per));

  if (page.value > totalPages) {
    page.value = totalPages;
  }
  if (page.value < 1) {
    page.value = 1;
  }

  const from = (page.value - 1) * per;
  const pageRows = rows.slice(from, from + per);

  return {
    data: pageRows,
    meta: {
      total,
      total_pages: totalPages,
      current_page: page.value,
      per_page: per,
      count: pageRows.length,
    },
  };
};

const fetch = () => {
  loading.value = true;
  ProcessMaker.apiClient
    .get("/plugins", { baseURL: "/api/1.0/" })
    .then((response) => {
      const plugins = response.data.data || [];
      data.value = transform(buildPagedPayload(plugins));
      loading.value = false;
    })
    .catch(() => {
      loading.value = false;
    });
};

const dataManager = (sortOrderArg) => {
  const so = sortOrderArg[0];
  if (so.sortField !== undefined) {
    orderBy.value = so.sortField;
  } else {
    orderBy.value = so.field;
  }
  orderDirection.value = so.direction;
  fetch();
};

const onPaginationData = (paginationData) => {
  pagination.value?.setPaginationData(paginationData);
};

const changePerPage = (value) => {
  perPage.value = value;
  const pg = pagination.value?.tablePagination;
  const total = pg ? pg.total : 0;
  if (page.value * value > total) {
    page.value = Math.floor(total / value) + 1 || 1;
  }
  fetch();
};

const onPageChange = (p) => {
  if (p === "next") {
    page.value += 1;
  } else if (p === "prev") {
    page.value -= 1;
  } else {
    page.value = p;
  }
  if (page.value <= 0) {
    page.value = 1;
  }
  const meta = pagination.value?.tablePagination;
  if (meta && page.value > meta.last_page) {
    page.value = meta.last_page;
  }
  fetch();
};

const formatEnabled = (enabled) => {
  if (enabled === true || String(enabled).toLowerCase() === "enabled") {
    return $t("Enabled");
  }
  if (enabled === false || String(enabled).toLowerCase() === "disabled") {
    return $t("Disabled");
  }
  return enabled != null ? String(enabled) : "";
};

const getError = (name) => _.get(errors.value, `${name}.0`, false);

const initCustomModalButtons = () => {
  customModalButtons.value = [
    {
      content: "Cancel",
      action: "close",
      variant: "outline-secondary",
      disabled: false,
      hidden: false,
    },
    {
      content: "Save",
      action: "save",
      variant: "primary",
      disabled: false,
      hidden: false,
    },
    {
      content: "Close",
      action: "close",
      variant: "secondary",
      disabled: false,
      hidden: true,
    },
  ];
};

const showCreateModal = () => {
  createPlugin.value?.show();
};

const hidePluginModal = () => {
  resetValues();
  createPlugin.value?.hide();
  fetch();
};

const save = () => {
  if (disabled.value) {
    return;
  }
  disabled.value = true;
  isRunning.value = true;
  let method = "POST";
  let url = "/plugins/install";
  let verb = "installed";
  if (plugin.description) {
    verb = "toggled";
  }
  ProcessMaker.apiClient({
    method,
    url,
    baseURL: "/api/1.0/",
    data: { ...plugin },
  })
    .then(() => {
      ProcessMaker.alert($t("The plugin was ") + verb + ".", "success");
    })
    .catch((error) => {
      disabled.value = false;
      errors.value = _.get(error, "response.data.errors", {});
    })
    .finally(() => {
      isRunning.value = false;
      isDone.value = true;
    });
};

const resetValues = () => {
  title.value = $t("Create Plugin");
  Object.assign(plugin, {
    id: null,
    url: "",
    branch: "",
    tag: "",
  });
  delete plugin.description;
  errors.value = {
    url: null,
    branch: null,
    tag: null,
  };
  disabled.value = false;
  isRunning.value = false;
  isDone.value = false;
  initCustomModalButtons();
};

const edit = (item) => {
  if (item.enabled !== 'Enabled') {
    return;
  }
  title.value = $t("Edit Plugin");
  Object.assign(plugin, _.cloneDeep(item));
  errors.value = {
    url: null,
    branch: null,
    tag: null,
  };
  disabled.value = false;
  isRunning.value = false;
  isDone.value = false;
  initCustomModalButtons();
  createPlugin.value?.show();
};

const onNavigate = (action, row) => {
  switch (action.value) {
    case "toggle-item":
      doToggle(row);
      break;
    case "delete-item":
      doDelete(row);
      break;
    default:
      break;
  }
};

const doToggle = (item) => {
  ProcessMaker.apiClient
    .patch(`/plugins/${item.name}/toggle`, { baseURL: "/api/1.0/" })
    .then(() => {
      ProcessMaker.alert($t("The plugin was toggled."), "success");
      fetch();
    });
};

const doDelete = (item) => {
  ProcessMaker.confirmModal(
    $t("Caution!"),
    $t("Are you sure you want to delete the plugin {{ name }}?", {
      name: item.name,
    }),
    "",
    () => {
      ProcessMaker.apiClient
        .delete(`/plugins/${item.name}`, { baseURL: "/api/1.0/" })
        .then(() => {
          ProcessMaker.alert($t("The plugin was deleted."), "success");
          fetch();
        })
        .catch((error) => {
          const msg = _.get(error, "response.data.errors.delete.0");
          if (msg) {
            ProcessMaker.alert(msg, "danger");
          }
        });
    }
  );
};

watch(isRunning, (val) => {
  if (val && customModalButtons.value.length >= 2) {
    customModalButtons.value[0].disabled = true;
    customModalButtons.value[1].disabled = true;
  }
});

watch(isDone, (val) => {
  if (val && customModalButtons.value.length >= 3) {
    customModalButtons.value[2].hidden = false;
    customModalButtons.value[0].hidden = true;
    customModalButtons.value[1].hidden = true;
  }
});

const debouncedOnFilter = debounce(() => {
  if (!loading.value) {
    page.value = 1;
    fetch();
  }
}, 250);

watch(filter, () => {
  debouncedOnFilter();
});

onBeforeMount(() => {
  resetValues();
});

let onApiDataLoading;
let onApiDataNoResults;

onMounted(() => {
  onApiDataLoading = (val, id) => {
    if (typeof id === "undefined" || dataLoadingId === id) {
      apiDataLoading.value = val;
    }
  };
  onApiDataNoResults = (val, id) => {
    if (typeof id === "undefined" || dataLoadingId === id) {
      apiNoResults.value = val;
    }
  };
  ProcessMaker.EventBus.$on("api-data-loading", onApiDataLoading);
  ProcessMaker.EventBus.$on("api-data-no-results", onApiDataNoResults);
  fetch();
});

onUnmounted(() => {
  if (onApiDataLoading) {
    ProcessMaker.EventBus.$off("api-data-loading", onApiDataLoading);
  }
  if (onApiDataNoResults) {
    ProcessMaker.EventBus.$off("api-data-no-results", onApiDataNoResults);
  }
  debouncedOnFilter.cancel();
});
</script>

<style scoped>
.page-content {
  padding-top: 0;
}
</style>
