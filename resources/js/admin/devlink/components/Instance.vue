<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import debounce from 'lodash/debounce';
import { useRouter, useRoute } from 'vue-router/composables';
import InstanceTabs from './InstanceTabs.vue';

const vue = getCurrentInstance().proxy;
const router = useRouter();
const route = useRoute();

const bundles = ref([]);
const filter = ref("");
const warnings = ref([]);
const fields = [
  {
    key: 'name',
    label: 'Name'
  },
  {
    key: 'version',
    label: 'Version'
  },
  {
    key: 'created_at',
    label: 'Creation Date'
  },
  {
    key: 'updated_at',
    label: 'Last Modification Date'
  },
  {
    key: 'menu',
    label: ''
  },
];

onMounted(() => {
  load();
});

const showModal = () => {
  $("#warningsModal").modal("show");
};

const closeModal = () => {
  $("#warningsModal").modal("hide");
};

const load = () => {
  ProcessMaker.apiClient
    .get(`/devlink/${route.params.id}/remote-bundles?filter=${filter.value}`)
    .then((result) => {
      bundles.value = result.data.data;
    });
};

// Debounced function
const debouncedLoad = debounce(load, 300);

// Function called on change
const handleFilterChange = () => {
  debouncedLoad();
};

const install = (bundle) => {
  vue.$bvModal.msgBoxConfirm('Are you sure you want to install this bundle?').then((confirm) => {
    if (confirm) {
      ProcessMaker.apiClient
        .post(`/devlink/${route.params.id}/remote-bundles/${bundle.id}/install`)
        .then((response) => {
          warnings.value = response.data.warnings_devlink;
          if (warnings.value.length > 0) {
            showModal();
          }
          window.ProcessMaker.alert('Bundle successfully installed', "success");
        });
    }
  });
};

</script>

<template>
  <div>
    <instance-tabs />
    <div class="top-options">
      <input v-model="filter" class="form-control col-10 search-input" @input="handleFilterChange">
    </div>
    <div class="card instance-card">
      <b-table
        :items="bundles"
        :fields="fields"
        class="instance-table"
      >
        <template #cell(menu)="data">
          <div class="btn-menu-container">
            <button
              class="btn install-bundle-btn"
              @click.prevent="install(data.item)"
            >
              <i class="fp-cloud-download-outline"></i>
            </button>
          </div>
        </template>
      </b-table>
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
.instance-card {
  border-radius: 8px;
  min-height: calc(-355px + 100vh);
}
.install-bundle-btn {
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.125);
}
.btn-menu-container {
  display: flex;
  justify-content: center;
}
</style>
