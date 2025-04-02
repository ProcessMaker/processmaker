<template>
  <div class="main-content">
    <div class="tw-flex tw-items-center tw-justify-center" v-if="loading">
      <div class="spinner-border text-primary" role="status" />
      <span class="visually-hidden">Loading...</span>
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
          data-offset="5, 5"
          aria-haspopup="true"
          aria-expanded="false"
        >
          {{ $t('Reinstall') }}
        </button>
        <div class="dropdown-menu dropdown-menu-right" id="dropdown">
          <a
            v-if="updateAvailable"
            class="dropdown-item"
            href="#"
            @click.prevent="reinstallBundle.show(bundle)"
          >
            <i class="fp-cloud-download-outline" />
            {{ $t('Update Bundle') }}
          </a>
          <a
            v-if="bundle.dev_link_id !== null"
            class="dropdown-item"
            href="#"
            @click.prevent="executeReinstall('copy')"
          >
            <i class="fp-copy-outline" />
            {{ $t('Add a Copy') }}
          </a>
          <a
            v-if="bundle.dev_link_id !== null"
            class="dropdown-item"
            href="#"
            @click.prevent="executeReinstall('update')"
          >
            <i class="fp-update-outline" />
            {{ $t('Update') }}
          </a>
        </div>
        <b-button
          v-if="bundle.dev_link_id === null"
          variant="primary"
          class="btn-publish"
          @click="publishBundle"
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

    <div class="divider" />

    <!-- Assets -->
    <div class="assets-section-title">
      {{ $t('Assets') }}
    </div>
    <BundleAssets
      :assets="bundle.assets"
    />

    <BundleConfigurations
      :configurations="platformConfigurations"
      :values="bundle.settings"
      :disabled="bundle.dev_link_id !== null"
      @config-change="handleConfigChange"
      title="Platform Configurations"
    />

    <BundleConfigurations
      :configurations="settings"
      :values="bundle.settings"
      :disabled="bundle.dev_link_id !== null"
      @config-change="handleConfigChange"
      @open-settings-modal="openSettingsModal"
      title="Settings"
      type="settings"
    />

    <BundleModal
      ref="bundleModal"
      :bundle="bundleForEdit"
      @update="updateBundle"
    />
    <UpdateBundle
      ref="reinstallBundle"
      @installation-complete="loadAssets"
    />

    <BundleSettingsModal
      ref="bundleSettingsModal"
      :editable="bundle.dev_link_id === null"
      @settings-saved="loadAssets"
    />

    <b-modal
      ref="confirmPublishNewVersion"
      centered
      content-class="modal-style"
      title="Publish New Version"
      @ok="executeIncrease"
    >
      <p v-html="confirmPublishNewVersionText"></p>
    </b-modal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, getCurrentInstance } from 'vue';
import { useRoute } from 'vue-router/composables';
import BundleModal, { show as showBundleModal, hide as hideBundleModal } from './BundleModal.vue';
import UpdateBundle from './UpdateBundle.vue';
import BundleSettingsModal from './BundleSettingsModal.vue';
import BundleAssets from './BundleAssets.vue';
import BundleConfigurations from './BundleConfigurations.vue';
import VersionCheck from './VersionCheck.vue';
import platformConfigurations from './platformConfigurations';
import settings from './settings';

const vue = getCurrentInstance().proxy;
const bundle = ref({});
const bundleModal = ref(null);
const bundleSettingsModal = ref(null);
const reinstallBundle = ref(null);
const loading = ref(false);
const route = useRoute();
const bundleId = route.params.id;
const bundleForEdit = ref({});
const updateAvailable = ref(false);
const selected = ref(null);
const confirmPublishNewVersion = ref(null);

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

const openSettingsModal = (event) => {
  bundleSettingsModal.value.show(event);
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

const confirmPublishNewVersionText = computed(() => {
  return vue.$t('Are you sure you increase the version of <strong>{{ bundleName }}</strong>?', { bundleName: bundle.value?.name });
});

const publishBundle = () => {
  selected.value = bundle.value;
  confirmPublishNewVersion.value.show();
};

const executeIncrease = () => {
  ProcessMaker.apiClient
    .post(`devlink/local-bundles/${selected.value.id}/increase-version`)
    .then((result) => {
      confirmPublishNewVersion.value.hide();
    });
};

const handleConfigChange = (event) => {
  if (event.value) {
    ProcessMaker.apiClient.post(`devlink/local-bundles/${bundle.value.id}/add-settings`, {
      setting: event.key,
      config: null,
      type: event.type,
    })
      .then(() => {
        loadAssets();
      });
  } else {
    ProcessMaker.apiClient.delete(`devlink/local-bundles/settings/${event.settingId}`)
      .then(() => {
        loadAssets();
      });
  }
};

const executeReinstall = (type) => {
  reinstallBundle.value.show(bundle.value, true, type);
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
.divider {
  border-top: 1px solid #E9ECF1;
  height: 1px;
  margin-top: 12px;
  margin-bottom: 24px;
  margin-left: 24px;
  margin-right: 24px;
}
.assets-section-title {
  font-size: 18px;
  font-weight: 500;
  padding-left: 24px;
  padding-right: 24px;
  margin-bottom: 8px;
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
