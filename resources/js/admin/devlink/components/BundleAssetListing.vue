<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import types from './assetTypes';
import moment from 'moment';

const vue = getCurrentInstance().proxy;
const router = useRouter();
const route = useRoute();
const loading = ref(false);
const bundle = ref({});
const bundleId = route.params.id;

const typeConfig = types.find((type) => type.type === route.params.type);
const items = ref([]);

const loadAssets = async () => {
  loading.value = true;
  const response = await window.ProcessMaker.apiClient.get(`/api/1.0/devlink/local-bundles/${bundleId}`);
  bundle.value = response.data;
  items.value = response.data.assets.filter(
    (asset) => asset.type && asset.type.toUpperCase() === route.params.type.toUpperCase(),
  );
  loading.value = false;
};

const remove = async (asset) => {
  const confirm = await vue.$bvModal.msgBoxConfirm(
    vue.$t('Remove this association from the bundle? The underlying asset will not be deleted.'),
    {
      okTitle: vue.$t('Remove from bundle'),
      okVariant: 'danger',
      cancelTitle: vue.$t('Cancel'),
    },
  );
  if (!confirm) {
    return;
  }
  await window.ProcessMaker.apiClient.delete(`/api/1.0/devlink/local-bundles/assets/${asset.id}`);
  await loadAssets();
};

const dateFormatter = (value) => {
  if (!value) return '';
  return moment(value).format(ProcessMaker.user.datetime_format);
};

onMounted(() => {
  loadAssets();
});

const goToAssets = () => {
  const url = typeConfig?.listingUrl || '#';
  window.location.href = url;
};

const fields = [
  {
    key: "id",
    label: "ID",
  },
  {
    key: "name",
    label: "Name",
  },
  ...(typeConfig?.type === "process" ? [{
    key: "owner_name",
    label: "Owner",
  }] : []),
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
    <div>
      <div class="asset-listing-card" v-if="!typeConfig">
        No assets found
      </div>
      <div v-else>
        <div class="top-options row">
          <div class="col-9 col-md-10" />
          <div class="col-3 col-md-2">
            <button
              v-if="bundle.remote_id === null"
              class="btn btn-primary go-to-assets-btn"
              @click="goToAssets()"
            >
              <i class="fp-link-icon go-to-assets-btn-icon" />
              {{ $t('Go to Assets') }}
            </button>
          </div>
        </div>
        <div class="card asset-listing-card">
          <b-table
            :items="items"
            :fields="fields"
            class="asset-listing-table"
          >
            <template #cell(name)="data">
              <a
                v-if="data.item.integrity_status === 'valid'"
                :href="data.item.url"
              >
                {{ data.item.name }}
              </a>
              <span v-else>
                {{ data.item.name }}
                <b-badge variant="warning">
                  {{ $t('Unavailable') }}
                </b-badge>
              </span>
            </template>
            <template #cell(menu)="data">
              <div class="btn-menu-container">
                <button
                  v-if="bundle.remote_id === null || data.item.integrity_status !== 'valid'"
                  class="btn install-asset-btn"
                  @click.prevent="remove(data.item)"
                >
                  <i class="fp-remove-outlined" />
                </button>
              </div>
            </template>
          </b-table>
        </div>
      </div>
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
  margin-top: 24px;
  margin-left: 24px;
}
.go-to-assets-btn {
  text-transform: none;
  font-size: 14px;
  font-weight: 500;
}
.go-to-assets-btn-icon {
  margin-right: 8px;
}
</style>
