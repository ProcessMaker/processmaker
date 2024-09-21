<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import InstanceTabs from './InstanceTabs.vue';
import types from './assetTypes';
import moment from 'moment';

const router = useRouter();
const route = useRoute();
const vue = getCurrentInstance().proxy;

const typeConfig = types.find((type) => type.type === route.params.type);

const items = ref([]);

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
        .then(() => {
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
    .get(typeConfig.url)
    .then((result) => {
      console.log("Got", result.data.data);
      items.value = result.data.data;
    });
};
</script>

<template>
  <div>
    <instance-tabs />
    <h3>{{ typeConfig.name }}</h3>
    <div v-if="!typeConfig">
      Invalid asset type
    </div>
    <b-table
      v-else
      :items="items"
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