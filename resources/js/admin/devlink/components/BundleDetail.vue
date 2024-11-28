<template>
  <div class="main-content">
    <!-- Header -->
    <div class="content-header">
      <div class="content-header-title">{{ bundle.name }}</div>
      <div class="header-actions">
        <b-button variant="outline-secondary" class="mr-2">
          {{ $t('Install') }}
        </b-button>
        <b-button variant="primary">
          {{ $t('Publish') }}
        </b-button>
      </div>
    </div>

    <!-- Description -->
    <div class="description-section">
      <h3>{{ $t('Description') }}</h3>
      <p>{{ bundle.description || $t('No description available') }}</p>
    </div>

    <BundleAssets
      :assets="bundle.assets"
    />

    <BundleConfigurations
      :configurations="platformConfigs"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router/composables';
import BundleAssets from './BundleAssets.vue';
import BundleConfigurations from './BundleConfigurations.vue';

const bundle = ref({});
const loading = ref(false);
const route = useRoute();
const bundleId = route.params.id;

const platformConfigs = ref([
  { key: 'users', label: 'Users', status: 'All', enabled: true },
  { key: 'groups', label: 'Groups', status: 'Not shared', enabled: false },
  // ... otras configuraciones
]);

const loadAssets = async () => {
  loading.value = true;
  const response = await window.ProcessMaker.apiClient.get(`/api/1.0/devlink/local-bundles/${bundleId}`);
  bundle.value = response.data;
  loading.value = false;
};

onMounted(async () => {
  await loadAssets();
});
</script>

<style lang="scss" scoped>
.main-content {
width: 100%;
}
.content-header {
  display: flex;
  justify-content: space-between;
  padding: 24px;
}
.content-header-title {
  font-size: 20px;
  font-weight: 500;
}
</style>
