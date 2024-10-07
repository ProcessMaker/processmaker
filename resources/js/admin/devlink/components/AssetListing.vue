<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import debounce from 'lodash/debounce';
import InstanceTabs from './InstanceTabs.vue';
import types from './assetTypes';
import moment from 'moment';

const router = useRouter();
const route = useRoute();
const vue = getCurrentInstance().proxy;

const typeConfig = types.find((type) => type.type === route.params.type);

const items = ref([]);
const filter = ref("");

const dateFormatter = (value) => {
  return moment(value).format(ProcessMaker.user.datetime_format);
}

const fields = [
  {
    key: 'id',
    label: 'ID'
  },
  {
    key: typeConfig?.nameField || 'name',
    label: 'Name'
  },
  {
    key: 'created_at',
    label: 'Created',
    formatter: dateFormatter,
  },
  {
    key: 'updated_at',
    label: 'Last Modified',
    formatter: dateFormatter,
  },
  {
    key: 'menu',
    label: ''
  }
];

const install = (asset) => {
  vue.$bvModal.msgBoxConfirm('Are you sure you want to install this asset onto this instance?').then((confirm) => {
    if (confirm) {
      const params = {
        class: typeConfig.class,
        id: asset.id
      };
      ProcessMaker.apiClient
        .post(`/devlink/${route.params.id}/install-remote-asset`, params)
        .then((response) => {
          console.log(response);
          window.ProcessMaker.alert('Asset successfully installed', "success");
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
      console.log("Got", result.data.data);
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
    <instance-tabs />
    <h3>{{ typeConfig.name }}</h3>
    <div class="top-options">
      <input v-model="filter" class="form-control col-10 search-input" @input="handleFilterChange">
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
</template>
<style lang="scss" scoped>
.top-options {
  display: flex;
  justify-content: space-between;
  padding-bottom: 16px;
}
.search-input {
  padding-left: 30px;
  background: url(/img/search-icon.svg) no-repeat left;
  background-position: 7px 8px;
  background-size: 15px;
  border-radius: 8px;
}
::v-deep .table {
  border-bottom: 1px solid #e9edf1;
}
::v-deep .table > thead > tr > th {
  border-top: none;
  background-color: #FBFBFC;
  border-right: 1px solid rgba(0, 0, 0, 0.125);
  color: #4E5663;
  font-weight: 600;
  font-size: 14px;
}
::v-deep .table > tbody > tr > td {
  color: #4E5663;
  font-size: 14px;
  font-weight: 400;
}
::v-deep .table > thead > tr > th:last-child {
  border-right: none !important;
  border-top-right-radius: 8px;
}
::v-deep .table > thead > tr > th:first-child {
  border-top-left-radius: 8px;
}
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
