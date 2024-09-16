<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';

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
  ProcessMaker.apiClient
    .post(`/devlink/${route.params.id}/remote-bundles/${bundle.id}/install`)
    .then((result) => {
      load();
    });
};

</script>

<template>
  <div>
    Instance {{ route.params.id }}
    <b-table
      :items="bundles"
      :fields="fields"
    >
      <template #cell(menu)="data">
        <a href="#" @click.prevent="install(data.item)">Install</a>
      </template>
    </b-table>
  </div>

</template>