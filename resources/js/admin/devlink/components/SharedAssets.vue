<script setup>
import { ref, onMounted } from 'vue';
import types from './assetTypes';

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
    console.log('Asset added:', response.data);
    load();
  } catch (error) {
    console.error('Error adding asset:', error);
  }
};

const removeSharedAsset = async (assetId) => {
  try {
    const response = await ProcessMaker.apiClient.delete(`/devlink/remove-shared-asset/${assetId}`);
    console.log('Asset removed:', response.data);
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
    label: 'Name'
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
          <span><i class="fp-folder-outline" style="margin-right: 10px;"></i>{{ data.item.name }}</span>
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
.shared-assets-card {
    border-radius: 8px;
    min-height: calc(-355px + 100vh);
}
</style>
