<script setup>
import { ref, watch, onMounted, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router/composables';
import debounce from 'lodash/debounce';
import InstanceTabs from './InstanceTabs.vue';
import types from './assetTypes';
import moment from 'moment';
import Header from './Header.vue';
import InstallProgress from './InstallProgress.vue';

const route = useRoute();
const vue = getCurrentInstance().proxy;

const typeConfig = types.find((type) => type.type === route.params.type);

const items = ref([]);
const meta = ref({});
const filter = ref("");
const showInstallModal = ref(false);
const showConfirmModal = ref(false);
const selectedAsset = ref(null);
const installMode = ref('update');
const page = ref(1);
const perPage = ref(15);

const load = () => {
  if (!typeConfig) {
    return;
  }
  const queryParams = {
    url: typeConfig.url,
    filter: filter.value,
    per_page: perPage.value,
    page: page.value
  };
  ProcessMaker.apiClient
    .get(`devlink/${route.params.id}/remote-assets-listing`, { params: queryParams })
    .then((result) => {
      items.value = result.data.data;
      meta.value = result.data.meta;
    });
};

watch(page, () => {
  load();
});

watch(perPage, () => {
  load();
});

const dateFormatter = (value) => {
  if (!value) {
    return '';
  }
  return moment(value).format(ProcessMaker.user.datetime_format);
}

const fields = [
  {
    key: 'id',
    label: vue.$t('ID'),
  },
  {
    key: typeConfig?.nameField || 'name',
    label: vue.$t('Name'),
  },
  {
    key: 'created_at',
    label: vue.$t('Created'),
    formatter: dateFormatter,
  },
  {
    key: 'updated_at',
    label: vue.$t('Last Modified'),
    formatter: dateFormatter,
  },
  {
    key: 'menu',
    label: '',
  },
];


const install = (asset) => {
  selectedAsset.value = asset;
  showConfirmModal.value = true;
};

const confirmInstall = () => {
  if (selectedAsset.value) {
    showConfirmModal.value = false;
    showInstallModal.value = true;
    const params = {
      class: typeConfig.class,
      id: selectedAsset.value.id,
      updateType: installMode.value
    };
    ProcessMaker.apiClient
      .post(`/devlink/${route.params.id}/install-remote-asset`, params)
      .then(() => {
        selectedAsset.value = null;
      });
  }
};

const cancelInstall = () => {
  selectedAsset.value = null;
  showConfirmModal.value = false;
};

onMounted(() => {
  load();
});

// Debounced function
const debouncedLoad = debounce(load, 300);

// Function called on change
const handleFilterChange = () => {
  debouncedLoad();
};
</script>

<template>
  <div>
    <instance-tabs><template #assets>
    <div>
      <Header back="assets">{{ typeConfig.name }}</Header>
      <div class="top-options row">
        <div class="col">
          <input v-model="filter" class="form-control search-input" @input="handleFilterChange">
        </div>
      </div>
      <div class="card asset-listing-card">
        <div v-if="!typeConfig">
          Invalid asset type
        </div>
        <b-table
          v-else
          :items="items"
          :fields="fields"
          class="asset-listing-table"
        >
          <template #cell(menu)="data">
            <div class="btn-menu-container">
              <button
                class="btn install-asset-btn"
                @click.prevent="install(data.item)"
              >
                <i class="fp-cloud-download-outline"></i>
              </button>
            </div>
          </template>
        </b-table>
      </div>
    </div>
    </template></instance-tabs>
    <pagination-table
        :meta="meta"
        data-cy="process-pagination"
        @page-change="page = $event"
        @per-page-change="perPage = $event"
      />
    <!-- Confirmation Modal -->
    <b-modal 
      id="install-confirm" 
      v-model="showConfirmModal" 
      :title="$t('Install Asset')" 
      @ok="confirmInstall"
      @cancel="cancelInstall"
      :ok-title="$t('Install')"
      :cancel-title="$t('Cancel')"
    >
      <div class="mb-3">
        <p>{{ $t('Do you want to proceed with installing the asset on your instance?') }}</p>
        <p v-if="selectedAsset" class="font-weight-bold">{{ selectedAsset.name || selectedAsset.title }}</p>
      </div>
      
      <div class="form-group">
        <label class="font-weight-bold mb-2">{{ $t('Installation Mode:') }}</label>
        <div class="custom-control custom-radio">
          <input 
            id="update-mode" 
            v-model="installMode" 
            type="radio" 
            value="update" 
            class="custom-control-input"
          >
          <label for="update-mode" class="custom-control-label">
            <strong>{{ $t('Update') }}</strong>
            <div class="text-muted small">{{ $t('Update existing asset with the same name (recommended)') }}</div>
          </label>
        </div>
        <div class="custom-control custom-radio mt-2">
          <input 
            id="copy-mode" 
            v-model="installMode" 
            type="radio" 
            value="copy" 
            class="custom-control-input"
          >
          <label for="copy-mode" class="custom-control-label">
            <strong>{{ $t('Copy') }}</strong>
            <div class="text-muted small">{{ $t('Create a new asset even if one with the same name exists') }}</div>
          </label>
        </div>
      </div>
    </b-modal>

    <!-- Progress Modal -->
    <b-modal id="install-progress" size="lg" v-model="showInstallModal" :title="$t('Installation Progress')" hide-footer>
      <install-progress />
    </b-modal>
  </div>
</template>
<style lang="scss" scoped>
.top-options {
  display: flex;
  justify-content: space-between;
  padding-bottom: 16px;
}
.search-input {
  background: url(/img/search-icon.svg) no-repeat left;
  background-position: 7px 8px;
  background-size: 15px;
  border-radius: 8px;
  padding-left: 30px;
}

@import "styles/components/table";

.asset-listing-card {
  border-radius: 8px;
  min-height: calc(-495px + 100vh);
}
.install-asset-btn {
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.125);
}
.btn-menu-container {
  display: flex;
  justify-content: center;
}
</style>
