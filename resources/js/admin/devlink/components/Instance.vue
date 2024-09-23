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
    
    <b-table
      :items="bundles"
      :fields="fields"
    >
      <template #cell(menu)="data">
        <a
          href="#"
          @click.prevent="install(data.item)"
        >Install</a>
      </template>
    </b-table>
  </div>

</template>