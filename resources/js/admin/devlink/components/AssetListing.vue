<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import debounce from 'lodash/debounce';
import InstanceTabs from './InstanceTabs.vue';
import types from './assetTypes';
import moment from 'moment';
import Header from './Header.vue';

const router = useRouter();
const route = useRoute();
const vue = getCurrentInstance().proxy;

const typeConfig = types.find((type) => type.type === route.params.type);

const items = ref([]);
const filter = ref("");
const warnings = ref([]);

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

const showModal = () => {
  $("#warningsModal").modal("show");
};

const closeModal = () => {
  $("#warningsModal").modal("hide");
};

const install = (asset) => {
  vue.$bvModal.msgBoxConfirm(vue.$t('Are you sure you want to install this asset onto this instance?'), {
    okTitle: vue.$t('Ok'),
    cancelTitle: vue.$t('Cancel')
  }).then((confirm) => {
    if (confirm) {
      const params = {
        class: typeConfig.class,
        id: asset.id
      };
      ProcessMaker.apiClient
        .post(`/devlink/${route.params.id}/install-remote-asset`, params)
        .then((response) => {
          window.ProcessMaker.alert(vue.$t('Asset successfully installed'), "success");
          warnings.value = response.data.warnings_devlink;
          if (warnings.value.length > 0) {
            showModal();
          }
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
    <div class="modal fade" id="warningsModal" tabindex="-1" role="dialog" aria-labelledby="warningsModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-body">
            <h5>Warnings</h5>
            <ul>
              <li
                v-for="(warning, index) in warnings"
                :key="index"
              >
                {{ warning }}
              </li>
            </ul>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModal()" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    </template></instance-tabs>
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

@import "styles/components/table";

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
