<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import debounce from 'lodash/debounce';
import InstanceTabs from './InstanceTabs.vue';
import types from './assetTypes';
import moment from 'moment';
import Header from './Header.vue';
import InstallProgress from './InstallProgress.vue';

const router = useRouter();
const route = useRoute();
const vue = getCurrentInstance().proxy;

const typeConfig = types.find((type) => type.type === route.params.type);

const items = ref([]);
const filter = ref("");
const showInstallModal = ref(false);

const dateFormatter = (value) => {
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
    label: ''
  },
];


const install = (asset) => {
  vue.$bvModal.msgBoxConfirm(vue.$t('Are you sure you want to install this asset onto this instance?'), {
    okTitle: vue.$t('Ok'),
    cancelTitle: vue.$t('Cancel')
  }).then((confirm) => {
    if (confirm) {
      showInstallModal.value = true;
      const params = {
        class: typeConfig.class,
        id: asset.id
      };
      ProcessMaker.apiClient
        .post(`/devlink/${route.params.id}/install-remote-asset`, params)
        .then((response) => {
        });
    }
  });
};

onMounted(() => {
  load();
});

const load = () => {
  if (!typeConfig) {
    return;
  }
  ProcessMaker.apiClient
    .get(`devlink/${route.params.id}/remote-assets-listing?url=${typeConfig.url}&filter=${filter.value}`)
    .then((result) => {
      items.value = result.data.data;
    });
};

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
}

@import "~styles/components/table";

.asset-listing-card {
  border-radius: 8px;
  min-height: calc(-355px + 100vh);
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
