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
                @click="openUpdatePlugin(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Edit')"
                :class="
                  props.rowData.source !== 'git' ||
                  props.rowData.enabled !== 'Enabled'
                    ? 'disabled'
                    : ''
                "
                :disabled="
                  props.rowData.source !== 'git' ||
                  props.rowData.enabled !== 'Enabled'
                "
              >
                <i class="fas fa-edit fa-lg fa-fw"></i>
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
          :state="!getError('url')"
          :invalid-feedback="getError('url') || ''"
          role="alert"
        >
          <template #label>
            <span>
              <span>{{ $t("Repository URL") }}</span>
            </span>
          </template>
          <div class="d-flex align-items-center">
          <b-form-input
            v-model="plugin.url"
            name="url"
            required
            :aria-required="'true'"
          />
          <b-button
                variant="link"
                class="py-0 ml-1"
                type="button"
                :aria-pressed="showGitCredentials ? 'true' : 'false'"
                :aria-label="$t('Credentials')"
                @click="showGitCredentials = !showGitCredentials"
              >
                <span v-b-tooltip.hover.bottom :title="$t('Credentials')">
                  <i class="fas fa-key" />
                </span>
              </b-button>
            </div>
          <b-form-text>{{ $t("Repository URL must be a valid URL") }}</b-form-text>
        </b-form-group>
        <b-collapse v-model="showGitCredentials" class="mb-3">
          <b-row>
            <b-col cols="12" md="6">
              <b-form-group :label="$t('Username')">
                <b-form-input
                  v-model="plugin.gitUsername"
                  name="git_username"
                  autocomplete="username"
                />
              </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
              <b-form-group :label="$t('Token')">
                <b-form-input
                  v-model="plugin.gitToken"
                  type="password"
                  name="git_token"
                  autocomplete="off"
                  :aria-label="$t('Token')"
                />
              </b-form-group>
            </b-col>
          </b-row>
        </b-collapse>
        <b-row>
          <b-col cols="12" md="6">
            <b-form-group
              :label="$t('Branch')"
              :state="!getError('branch')"
              :invalid-feedback="getError('branch') || ''"
              role="alert"
            >
              <b-form-input v-model="plugin.branch" name="branch" :state="!getError('branch')" />
              <b-form-text>{{ $t("Branch must be a valid branch name") }}</b-form-text>
            </b-form-group>
          </b-col>
          <b-col cols="12" md="6">
            <b-form-group
              :label="$t('Tag')"
              :state="!getError('tag')"
              :invalid-feedback="getError('tag') || ''"
              role="alert"
            >
              <b-form-input v-model="plugin.tag" name="tag" :state="!getError('tag')" />
              <b-form-text>{{ $t("Tag must be a valid tag name") }}</b-form-text>
            </b-form-group>
          </b-col>
        </b-row>
        <plugin-output
          :user-id="userId"
          :waiting="isRunning"
          @done="onPluginJobDone"
          @error="onPluginJobError"
          @running="onPluginJobRunning"
        />
      </b-container>
    </pm-modal>

    <pm-modal
      ref="updatePluginModalRef"
      id="updatePluginModal"
      :title="updateModalTitle"
      style="display: none"
      :set-custom-buttons="true"
      :custom-buttons="updateModalButtons"
      @close="hideUpdatePluginModal"
      @hidden="onUpdateModalHidden"
      @continue="onUpdateContinue"
    >
      <b-container class="mb-2">
        <b-form-group>
          <template #label>
            <span>{{ $t("Repository URL") }}</span>
          </template>
          <div class="d-flex align-items-center">
            <b-form-input
              :value="updateRepositoryUrl"
              name="update_plugin_url"
              readonly
              :aria-readonly="true"
            />
            <b-button
              variant="link"
              class="py-0 ml-1"
              type="button"
              :aria-pressed="showUpdateGitCredentials ? 'true' : 'false'"
              :aria-label="$t('Credentials')"
              @click="showUpdateGitCredentials = !showUpdateGitCredentials"
            >
              <span v-b-tooltip.hover.bottom :title="$t('Credentials')">
                <i class="fas fa-key" />
              </span>
            </b-button>
          </div>
        </b-form-group>
        <b-collapse v-model="showUpdateGitCredentials" class="mb-3">
          <b-row>
            <b-col cols="12" md="6">
              <b-form-group :label="$t('Username')">
                <b-form-input
                  v-model="updateGitUsername"
                  name="update_git_username"
                  autocomplete="username"
                />
              </b-form-group>
            </b-col>
            <b-col cols="12" md="6">
              <b-form-group :label="$t('Token')">
                <b-form-input
                  v-model="updateGitToken"
                  type="password"
                  name="update_git_token"
                  autocomplete="off"
                  :aria-label="$t('Token')"
                />
              </b-form-group>
            </b-col>
          </b-row>
        </b-collapse>
        <b-row>
          <b-col cols="12" md="6">
            <b-form-group :label="$t('Branch')">
              <b-form-input v-model="updateBranch" name="update_branch" />
              <b-form-text>{{ $t("Branch must be a valid branch name") }}</b-form-text>
            </b-form-group>
          </b-col>
          <b-col cols="12" md="6">
            <b-form-group :label="$t('Tag')">
              <b-form-input v-model="updateTag" name="update_tag" />
              <b-form-text>{{ $t("Tag must be a valid tag name") }}</b-form-text>
            </b-form-group>
          </b-col>
        </b-row>
      </b-container>
    </pm-modal>

    <pm-modal
      ref="pluginProgressModal"
      id="pluginProgressModal"
      :title="progressTitle"
      style="display: none"
      :set-custom-buttons="true"
      :custom-buttons="progressModalButtons"
      :hide-header-close="true"
      :no-close-on-esc="true"
      @close="hideProgressModal"
      @hidden="resetProgressModal"
    >
      <b-container class="mb-2">
        <p v-if="progressWaiting" class="text-muted mb-2">
          <i class="fas fa-spinner fa-spin" />
          {{ $t("Please wait") }}
        </p>
        <plugin-output
          :key="progressSessionKey"
          :user-id="userId"
          :waiting="progressWaiting || progressIsRunning"
          :output-title="progressOutputTitle"
          :success-message="progressSuccessMessage"
          :error-message="progressErrorMessage"
          @done="onProgressJobDone"
          @error="onProgressJobError"
          @running="onProgressJobRunning"
        />
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
  nextTick,
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
const updatePluginModalRef = ref(null);
const pluginProgressModal = ref(null);
const pagination = ref(null);
const updateModalTitle = ref("");
const updateRepositoryUrl = ref("");
const pendingReinstallItem = ref(null);
const updateModalButtons = ref([]);
const showUpdateGitCredentials = ref(false);
const updateGitUsername = ref("");
const updateGitToken = ref("");
const updateBranch = ref("");
const updateTag = ref("");

const plugin = reactive({
  id: null,
  url: "",
  branch: "",
  tag: "",
  gitUsername: "",
  gitToken: "",
});

const showGitCredentials = ref(false);

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

const pendingVerb = ref("installed");
const progressTitle = ref("");
const progressOutputTitle = ref("");
const progressSuccessMessage = ref("");
const progressErrorMessage = ref("");
const progressSessionKey = ref(0);
const progressIsRunning = ref(false);
const progressIsDone = ref(false);
const progressWaiting = ref(false);
const progressModalButtons = ref([]);

const save = () => {
  if (disabled.value) {
    return;
  }
  disabled.value = true;
  isRunning.value = true;
  isDone.value = false;
  pendingVerb.value = plugin.description ? "toggled" : "installed";
  ProcessMaker.apiClient({
    method: "POST",
    url: "/plugins/install",
    baseURL: "/api/1.0/",
    timeout: 0,
    data: {
      url: plugin.url,
      branch: plugin.branch,
      tag: plugin.tag,
      ...(plugin.gitUsername || plugin.gitToken
        ? {
            git_username: plugin.gitUsername || undefined,
            git_token: plugin.gitToken || undefined,
          }
        : {}),
    },
  })
    .then(() => {
      // Completion is driven by PluginLog events from the queued job.
      isRunning.value = true;
    })
    .catch((error) => {
      disabled.value = false;
      isRunning.value = false;
      isDone.value = true;
      errors.value = _.get(error, "response.data.errors", {});
      const message = _.get(error, "response.data.message");
      if (message) {
        ProcessMaker.alert(message, "danger");
      }
    });
};

const onPluginJobRunning = () => {
  isRunning.value = true;
  isDone.value = false;
};

const onPluginJobDone = () => {
  if (!isRunning.value) {
    return;
  }
  isRunning.value = false;
  isDone.value = true;
  disabled.value = false;
  reloadAfterPluginChange($t("The plugin was ") + pendingVerb.value + ".");
};

const onPluginJobError = () => {
  if (!isRunning.value) {
    return;
  }
  isRunning.value = false;
  isDone.value = true;
  disabled.value = false;
};

const reloadAfterPluginChange = (message) => {
  ProcessMaker.alert(message, "success", 5, true);
  window.location.reload();
};

const initUpdateModalButtons = () => {
  updateModalButtons.value = [
    {
      content: "Cancel",
      action: "close",
      variant: "outline-secondary",
      disabled: false,
      hidden: false,
    },
    {
      content: "Continue",
      action: "continue",
      variant: "primary",
      disabled: false,
      hidden: false,
    },
  ];
};

const resetUpdateModalForm = () => {
  showUpdateGitCredentials.value = false;
  updateGitUsername.value = "";
  updateGitToken.value = "";
  updateBranch.value = "";
  updateTag.value = "";
};

const hideUpdatePluginModal = () => {
  if (updatePluginModalRef.value?.hide) {
    updatePluginModalRef.value.hide();
  } else {
    instance?.proxy?.$bvModal?.hide("updatePluginModal");
  }
};

const showUpdatePluginModal = () => {
  nextTick(() => {
    if (updatePluginModalRef.value?.show) {
      updatePluginModalRef.value.show();
      return;
    }
    instance?.proxy?.$bvModal?.show("updatePluginModal");
  });
};

const onUpdateModalHidden = () => {
  pendingReinstallItem.value = null;
  updateRepositoryUrl.value = "";
  resetUpdateModalForm();
};

const onUpdateContinue = () => {
  const item = pendingReinstallItem.value;
  if (!item) {
    return;
  }
  const gitUsername = updateGitUsername.value;
  const gitToken = updateGitToken.value;
  const branch = updateBranch.value?.trim() || undefined;
  const tag = updateTag.value?.trim() || undefined;
  hideUpdatePluginModal();
  openPluginProgressModal({
    title: $t("Update Plugin") + `: ${item.name}`,
    outputTitle: $t("Plugin Update Output"),
    successMessage: $t(
      "Plugin successfully reinstalled. You can now close this window."
    ),
    errorMessage: $t("Error reinstalling plugin. See output above."),
    verb: "reinstalled",
  });
  ProcessMaker.apiClient({
    method: "POST",
    url: `/plugins/${item.name}/reinstall`,
    baseURL: "/api/1.0/",
    timeout: 0,
    data: {
      ...(branch ? { branch } : {}),
      ...(tag ? { tag } : {}),
      ...(gitUsername || gitToken
        ? {
            git_username: gitUsername || undefined,
            git_token: gitToken || undefined,
          }
        : {}),
    },
  })
    .then(() => {
      progressIsRunning.value = true;
    })
    .catch((error) => {
      onPluginProgressApiError(error, $t("Unable to reinstall plugin."));
    });
};

const openUpdatePlugin = (item) => {
  if (item.source !== "git" || item.enabled !== "Enabled") {
    return;
  }
  pendingReinstallItem.value = item;
  updateModalTitle.value = $t("Edit Plugin") + `: ${item.name}`;
  resetUpdateModalForm();
  updateRepositoryUrl.value = item.url || "";
  updateBranch.value = item.branch || "";
  updateTag.value = item.tag || "";
  initUpdateModalButtons();
  showUpdatePluginModal();
};

const initProgressModalButtons = () => {
  progressModalButtons.value = [
    {
      content: "Close",
      action: "close",
      variant: "secondary",
      disabled: true,
      hidden: false,
    },
  ];
};

const openPluginProgressModal = ({
  title,
  outputTitle,
  successMessage,
  errorMessage,
  verb,
}) => {
  pendingVerb.value = verb;
  progressTitle.value = title;
  progressOutputTitle.value = outputTitle;
  progressSuccessMessage.value = successMessage;
  progressErrorMessage.value = errorMessage;
  progressSessionKey.value += 1;
  progressIsRunning.value = false;
  progressIsDone.value = false;
  progressWaiting.value = true;
  initProgressModalButtons();
  pluginProgressModal.value?.show();
};

const enableProgressModalClose = () => {
  if (progressModalButtons.value.length) {
    progressModalButtons.value[0].disabled = false;
  }
};

const onProgressJobRunning = () => {
  progressWaiting.value = false;
  progressIsRunning.value = true;
  progressIsDone.value = false;
};

const onProgressJobDone = () => {
  progressWaiting.value = false;
  progressIsRunning.value = false;
  progressIsDone.value = true;
  enableProgressModalClose();
};

const onProgressJobError = () => {
  progressWaiting.value = false;
  progressIsRunning.value = false;
  progressIsDone.value = true;
  enableProgressModalClose();
};

const hideProgressModal = () => {
  if (!progressIsDone.value) {
    return;
  }
  pluginProgressModal.value?.hide();
  fetch();
};

const resetProgressModal = () => {
  progressTitle.value = "";
  progressOutputTitle.value = "";
  progressSuccessMessage.value = "";
  progressErrorMessage.value = "";
  progressIsRunning.value = false;
  progressIsDone.value = false;
  progressWaiting.value = false;
  initProgressModalButtons();
};

const onPluginProgressApiError = (error, fallbackMessage) => {
  progressWaiting.value = false;
  progressIsRunning.value = false;
  progressIsDone.value = true;
  enableProgressModalClose();
  const message =
    _.get(error, "response.data.errors.delete.0") ||
    _.get(error, "response.data.message") ||
    fallbackMessage;
  if (message) {
    ProcessMaker.alert(message, "danger");
  }
};

const confirmDeletePlugin = (item, onConfirm) => {
  const showConfirm = ProcessMaker.confirmModal;
  if (typeof showConfirm !== "function") {
    ProcessMaker.alert($t("Unable to open confirmation dialog. Please refresh the page."), "danger");
    return;
  }
  showConfirm(
    $t("Caution!"),
    $t("Are you sure you want to delete the plugin {{ name }}?", {
      name: item.name,
    }),
    "",
    onConfirm
  );
};

const resetValues = () => {
  title.value = $t("Create Plugin");
  Object.assign(plugin, {
    id: null,
    url: "",
    branch: "",
    tag: "",
    gitUsername: "",
    gitToken: "",
  });
  showGitCredentials.value = false;
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
  openPluginProgressModal({
    title: $t("Toggle Plugin") + `: ${item.name}`,
    outputTitle: $t("Plugin Toggle Output"),
    successMessage: $t(
      "Plugin successfully updated. You can now close this window."
    ),
    errorMessage: $t("Error toggling plugin. See output above."),
    verb: "toggled",
  });
  ProcessMaker.apiClient
    .patch(`/plugins/${item.name}/toggle`, null, {
      baseURL: "/api/1.0/",
      timeout: 0,
    })
    .then(() => {
      progressIsRunning.value = true;
    })
    .catch((error) => {
      onPluginProgressApiError(error, $t("Unable to toggle plugin."));
    });
};

const doDelete = (item) => {
  confirmDeletePlugin(item, () => {
    openPluginProgressModal({
      title: $t("Uninstall Plugin") + `: ${item.name}`,
      outputTitle: $t("Plugin Uninstallation Output"),
      successMessage: $t(
        "Plugin successfully uninstalled. You can now close this window."
      ),
      errorMessage: $t("Error uninstalling plugin. See output above."),
      verb: "deleted",
    });
    ProcessMaker.apiClient
      .delete(`/plugins/${item.name}`, {
        baseURL: "/api/1.0/",
        timeout: 0,
      })
      .then(() => {
        progressIsRunning.value = true;
      })
      .catch((error) => {
        onPluginProgressApiError(error, $t("Unable to delete plugin."));
      });
  });
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
  initProgressModalButtons();
  initUpdateModalButtons();
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
