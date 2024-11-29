<template>
  <div class="main-content">
    <div v-if="loading">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <!-- Header -->
    <div v-else class="content-header">
      <div class="content-header-title">
        {{ bundle.name }}
        <VersionCheck @updateAvailable="updateAvailable = $event" :dev-link="bundle"></VersionCheck>
      </div>
      <div class="header-actions">
        <b-button
          v-if="bundle.dev_link_id === null"
          class="btn text-secondary icon-button"
          variant="light"
          :aria-label="$t('Edit Bundle')"
          v-b-tooltip.hover
          :title="$t('Edit Bundle')"
          @click.prevent="openBundleModalForEdit()"
        >
          <i class="fp-edit-outline" />
        </b-button>
        <button
          v-if="bundle.dev_link_id !== null"
          class="btn btn-outline-secondary mr-2 dropdown-toggle"
          data-toggle="dropdown"
          aria-haspopup="true"
          aria-expanded="false"
        >
          <i class="fas fp-plus" />
          {{ $t('Install') }}
        </button>
        <div class="dropdown-menu" id="dropdown">
          <a
            v-if="updateAvailable"
            class="dropdown-item"
            href="#"
            @click.prevent="reinstallBundle.show(bundle)"
          >
            {{ $t('Update Bundle') }}
          </a>
          <a
            v-if="bundle.dev_link_id !== null"
            class="dropdown-item"
            href="#"
            @click.prevent="reinstallBundle.show(bundle, true)"
          >
            {{ $t('Reinstall This Bundle') }}
          </a>
        </div>
        <b-button
          v-if="bundle.dev_link_id === null"
          variant="primary"
          class="btn-publish"
        >
          {{ $t('Publish') }}
        </b-button>
      </div>
    </div>

    <!-- Description -->
    <div class="description-section">
      <div class="description-section-title">
        {{ $t('Description:') }}
      </div>
      <p class="description-section-text">{{ bundle.description || $t('No description available') }}</p>
    </div>

    <BundleAssets
      :assets="bundle.assets"
    />

    <BundleConfigurations
      :configurations="platformConfigs"
    />
    <BundleModal
      ref="bundleModal"
      :bundle="bundleForEdit"
      @update="updateBundle"
    />
    <UpdateBundle
      ref="reinstallBundle"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router/composables';
import BundleModal, { show as showBundleModal, hide as hideBundleModal } from './BundleModal.vue';
import UpdateBundle from './UpdateBundle.vue';
import BundleAssets from './BundleAssets.vue';
import BundleConfigurations from './BundleConfigurations.vue';
import VersionCheck from './VersionCheck.vue';

const bundle = ref({});
const bundleModal = ref(null);
const reinstallBundle = ref(null);
const loading = ref(false);
const route = useRoute();
const bundleId = route.params.id;
const bundleForEdit = ref({});
const updateAvailable = ref(false);

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

const openBundleModalForEdit = () => {
  bundleForEdit.value = { ...bundle.value };
  if (bundleModal.value) {
    bundleModal.value.show();
  }
};

const updateBundle = () => {
  if (bundleForEdit.value.id === null) {
    return;
  }
  ProcessMaker.apiClient
    .put(`/devlink/local-bundles/${bundleForEdit.value.id}`, bundleForEdit.value)
    .then(() => {
      loadAssets();
    });
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
  padding-top: 24px;
  padding-bottom: 8px;
  padding-left: 24px;
  padding-right: 24px;
}
.content-header-title {
  font-size: 20px;
  font-weight: 500;
}
.btn-publish {
  width: 104px;
}
.icon-button {
  background-color: #E9ECF1;
  width: 40px;
  height: 40px;
  border: none;
}
.description-section {
  font-size: 14px;
  font-weight: 600;
  padding-top: 8px;
  padding-bottom: 8px;
  padding-left: 24px;
  padding-right: 24px;
}
.description-section-title {
  color: #464B52;
  font-weight: 600;
  margin-bottom: 8px;
}
.description-section-text {
  color: #4E5663;
  font-weight: 400;
  text-align: justify;
}
::v-deep .btn {
  font-size: 14px;
  border-radius: 8px;
  text-transform: none;
  margin-left: 8px;
}
::v-deep .dropdown-menu {
  border-radius: 8px;
  font-size: 14px;
  font-weight: 400;
  color: #464B52;
}
</style>
