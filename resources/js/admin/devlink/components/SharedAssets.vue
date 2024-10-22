<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import types from './assetTypes';

const vue = getCurrentInstance().proxy;
const assets = ref([]);

onMounted(() => {
  load();
});

const load = () => {
  ProcessMaker.apiClient
    .get(`/devlink/shared-assets`)
    .then((result) => {
      assets.value = result.data;
    });
};

const isInAssets = (type) => {
  return assets.value.some(asset => asset.config === type.class);
};

const getAssetById = (type) => {
  return assets.value.find(asset => asset.config === type.class);
};

const addSharedAsset = async (type) => {
  try {
    const response = await ProcessMaker.apiClient.post('/devlink/add-shared-asset', {
      key: 'devlink.' + type.type,
      config: type.class,
      name: type.name,
    });
    load();
  } catch (error) {
    console.error('Error adding asset:', error);
  }
};

const removeSharedAsset = async (assetId) => {
  try {
    const response = await ProcessMaker.apiClient.delete(`/devlink/remove-shared-asset/${assetId}`);
    load();
  } catch (error) {
    console.error('Error removing asset:', error);
  }
};

const updateEnabled = (type) => {
  const asset = getAssetById(type);

  if (asset) {
    removeSharedAsset(asset.id);
  } else {
    addSharedAsset(type);
  }
};

const fields = [
  {
    key: 'name',
    label: vue.$t('Type')
  },
  {
    key: 'option',
    label: ''
  },
  {
    key: 'blank',
    label: ''
  },
];

</script>

<template>
  <div>
    <div class="card shared-assets-card">
      <b-table
        :items="types"
        :fields="fields"
      >
        <template #cell(name)="data">
          <span><i class="fp-folder-outline" style="margin-right: 10px;"></i>{{ $t(data.item.name) }}</span>
        </template>
        <template #cell(option)="data">
          <b-form-checkbox
            :checked="isInAssets(data.item)"
            switch
            @change="updateEnabled(data.item)"
          />
        </template>
      </b-table>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import 'styles/components/table';
.shared-assets-card {
    border-radius: 8px;
    min-height: calc(-355px + 100vh);
}
</style>
