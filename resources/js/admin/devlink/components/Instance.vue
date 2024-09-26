<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import InstanceTabs from './InstanceTabs.vue';

const vue = getCurrentInstance().proxy;
const router = useRouter();
const route = useRoute();

const bundles = ref([]);
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

const load = () => {
  ProcessMaker.apiClient
    .get(`/devlink/${route.params.id}/remote-bundles`)
    .then((result) => {
      bundles.value = result.data;
    });
};

const install = (bundle) => {
  vue.$bvModal.msgBoxConfirm('Are you sure you want to install this bundle?').then((confirm) => {
    if (confirm) {
      ProcessMaker.apiClient
        .post(`/devlink/${route.params.id}/remote-bundles/${bundle.id}/install`)
        .then(() => {
          window.ProcessMaker.alert('Bundle successfully installed', "success");
        });
    }
  })
};

</script>

<template>
  <div>
    <instance-tabs />
    <div class="card instance-card">
      <b-table
        :items="bundles"
        :fields="fields"
        class="instance-table"
      >
        <template #cell(menu)="data">
          <button
            class="btn install-bundle-btn"
            @click.prevent="install(data.item)"
          >
            <i class="fp-cloud-download-outline"></i>
          </button>
        </template>
      </b-table>
    </div>
  </div>

</template>

<style lang="scss" scoped>
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
</style>