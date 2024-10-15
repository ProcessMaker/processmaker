<script setup>
import { ref, onMounted, getCurrentInstance, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';

const vue = getCurrentInstance().proxy;
const route = useRoute();
const bundleId = route.params.id;
const bundle = ref({});
const loading = ref(true);
const fields = [
  { key: 'name', label: 'Name' },
  { key: 'type', label: 'Type' },
  { key: 'updated_at', label: 'Modified' },
  { key: 'created_at', label: 'Created' },
  { key: 'menu', label: '' },
];

const computedFields = computed(() => {
  if (bundle.value.dev_link_id === null) {
    return fields;
  }
  // remove the menu field
  return fields.filter(field => field.key !== 'menu');
});

const loadAssets = async () => {
  loading.value = true;
  const response = await window.ProcessMaker.apiClient.get(`/api/1.0/devlink/local-bundles/${bundleId}`);
  bundle.value = response.data;
  loading.value = false;
}

onMounted(async () => {
  await loadAssets();
});

const remove = async (asset) => {
  const confirm = await vue.$bvModal.msgBoxConfirm('Are you sure you want to remote this asset from the bundle?');
  if (!confirm) {
    return;
  }
  await window.ProcessMaker.apiClient.delete(`/api/1.0/devlink/local-bundles/assets/${asset.id}`);
  await loadAssets();
};
</script>

<template>
  <div v-if="loading">
    Loading...
  </div>
  <div v-else>
    <h1>{{ bundle.name }} {{ $t("Assets") }}</h1>
    <div v-if="bundle.assets.length">
      <b-table
        :items="bundle.assets"
        :fields="computedFields"
      >
        <template #cell(name)="data">
          <a :href="data.item.url" target="_blank">{{ data.item.name }}</a>
        </template>

        <template #cell(menu)="data">
          <button
            class="btn install-asset-btn"
            @click.prevent="remove(data.item)"
          >
            <i class="fa fa-trash"></i>
          </button>
        </template>
      </b-table>
    </div>
    <div v-else>
      No assets found in this bundle.
    </div>
  </div>
</template>