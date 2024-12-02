<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import types from './assetTypes';
import moment from 'moment';

const router = useRouter();
const route = useRoute();
const loading = ref(false);
const bundleId = route.params.id;

const typeConfig = types.find((type) => type.type === route.params.type);
const items = ref([]);

const loadAssets = async () => {
  loading.value = true;
  const response = await window.ProcessMaker.apiClient.get(`/api/1.0/devlink/local-bundles/${bundleId}`);
  items.value = response.data.assets.filter(asset => asset.type.toUpperCase() === route.params.type.toUpperCase());
  loading.value = false;
};

const dateFormatter = (value) => {
  if (!value) return '';
  return moment(value).format(ProcessMaker.user.datetime_format);
};

onMounted(() => {
  loadAssets();
});

const fields = [
  {
    key: "id",
    label: "ID",
  },
  {
    key: typeConfig?.nameField || "name",
    label: "Name",
  },
  {
    key: "updated_at",
    label: "Modified",
    formatter: dateFormatter,
  },
  {
    key: "created_at",
    label: "Created",
    formatter: dateFormatter,
  },
  {
    key: "menu",
    label: "",
  },
];
</script>

<template>
  <div class="asset-listing-container">
    <div class="asset-listing-header">
      {{ typeConfig?.name }}
    </div>
    <div class="card asset-listing-card">
      <div v-if="!typeConfig">
        No assets found
      </div>
      <b-table
        v-else
        :items="items"
        :fields="fields"
        class="asset-listing-table"
      >
        <template #cell(name)="data">
          <a :href="data.item.url" target="_blank">{{ data.item.name }}</a>
        </template>
        <template #cell(menu)="data">
          <div class="btn-menu-container">
            <button
              class="btn install-asset-btn"
              @click.prevent="removeAsset(data.item)"
            >
              <i class="fp-remove-outlined"></i>
            </button>
          </div>
        </template>
      </b-table>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@import "styles/components/table";

.asset-listing-container {
  width: 100%;
}

.asset-listing-card {
  border-radius: 8px;
  margin: 24px;
  min-height: calc(-495px + 100vh);
}

.asset-listing-header {
  font-size: 20px;
  font-weight: 500;
  margin-left: 24px;
}
</style>
