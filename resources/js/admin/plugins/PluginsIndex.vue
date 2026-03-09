<script setup>
import { ref, onMounted } from 'vue';
import {
  BApp,
  BButton,
  BModal,
  BTable,
  BSpinner,
  BAlert,
  BBadge,
  BFormGroup,
  BFormInput,
  BFormFile,
} from 'bootstrap-vue-next';

const plugins = ref([]);
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const showInstallModal = ref(false);
const installSource = ref('url');
const installUrl = ref('');
const installZip = ref(null);
const installing = ref(false);
const installError = ref('');

const fields = [
  { key: 'name', label: 'Name' },
  { key: 'description', label: 'Description' },
  { key: 'version', label: 'Version' },
  { key: 'branch', label: 'Branch/Tag' },
  { key: 'enabled', label: 'Status' },
  { key: 'actions', label: 'Actions' },
];

const fetchPlugins = async () => {
  loading.value = true;
  errorMessage.value = '';
  try {
    const response = await window.ProcessMaker.apiClient.get('plugins');
    plugins.value = response.data.data;
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Failed to load plugins.';
  } finally {
    loading.value = false;
  }
};

const openInstallModal = () => {
  installSource.value = 'url';
  installUrl.value = '';
  installZip.value = null;
  installError.value = '';
  showInstallModal.value = true;
};

const handleInstall = async () => {
  installError.value = '';
  installing.value = true;

  try {
    if (installSource.value === 'zip') {
      const formData = new FormData();
      formData.append('zip', installZip.value);
      await window.ProcessMaker.apiClient.post('plugins/install', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
    } else {
      await window.ProcessMaker.apiClient.post('plugins/install', { url: installUrl.value });
    }

    showInstallModal.value = false;
    successMessage.value = 'Plugin installed successfully.';
    await fetchPlugins();
  } catch (e) {
    installError.value = e.response?.data?.message || 'Installation failed.';
  } finally {
    installing.value = false;
  }
};

const togglePlugin = async (plugin) => {
  try {
    const response = await window.ProcessMaker.apiClient.patch(`plugins/${plugin.folder}/toggle`);
    plugin.enabled = response.data.enabled;
    plugin.folder = response.data.enabled ? plugin.name : `_${plugin.name}`;
    successMessage.value = response.data.message;
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Failed to toggle plugin.';
  }
};

const deletePlugin = async (plugin) => {
  if (!confirm(`Are you sure you want to uninstall "${plugin.name}"? This cannot be undone.`)) {
    return;
  }
  try {
    await window.ProcessMaker.apiClient.delete(`plugins/${plugin.folder}`);
    successMessage.value = `Plugin "${plugin.name}" uninstalled.`;
    await fetchPlugins();
  } catch (e) {
    errorMessage.value = e.response?.data?.message || 'Failed to uninstall plugin.';
  }
};

onMounted(fetchPlugins);
</script>

<template>
  <BApp>
    <div class="card card-body mb-3">
      <div class="d-flex justify-content-between align-items-center">
        <h4 class="mb-0">
          <i class="fas fa-puzzle-piece me-2" />
          {{ $t('Plugins') }}
        </h4>
        <BButton variant="primary" @click="openInstallModal">
          <i class="fas fa-plus me-1" />
          {{ $t('Install Plugin') }}
        </BButton>
      </div>
    </div>

    <BAlert v-if="successMessage" variant="success" dismissible @dismissed="successMessage = ''" show>
      {{ successMessage }}
    </BAlert>

    <BAlert v-if="errorMessage" variant="danger" dismissible @dismissed="errorMessage = ''" show>
      {{ errorMessage }}
    </BAlert>

    <div class="card">
      <div class="card-body p-0">
        <div v-if="loading" class="text-center p-4">
          <BSpinner variant="primary" />
        </div>

        <BTable
          v-else
          :items="plugins"
          :fields="fields"
          striped
          hover
          responsive
          show-empty
          :empty-text="$t('No plugins installed.')"
        >
          <template #cell(enabled)="{ item }">
            <BBadge :variant="item.enabled ? 'success' : 'secondary'">
              {{ item.enabled ? $t('Enabled') : $t('Disabled') }}
            </BBadge>
          </template>

          <template #cell(version)="{ item }">
            {{ item.version || '—' }}
          </template>

          <template #cell(branch)="{ item }">
            {{ item.branch || '—' }}
          </template>

          <template #cell(actions)="{ item }">
            <BButton
              size="sm"
              :variant="item.enabled ? 'outline-warning' : 'outline-success'"
              class="me-2"
              @click="togglePlugin(item)"
            >
              <i :class="item.enabled ? 'fas fa-toggle-off' : 'fas fa-toggle-on'" />
              {{ item.enabled ? $t('Disable') : $t('Enable') }}
            </BButton>

            <BButton
              size="sm"
              variant="outline-danger"
              class="ml-2"
              @click="deletePlugin(item)"
            >
              <i class="fas fa-trash" />
              {{ $t('Uninstall') }}
            </BButton>
          </template>
        </BTable>
      </div>
    </div>

    <BModal
      v-model="showInstallModal"
      :title="$t('Install Plugin')"
      :ok-title="installing ? $t('Installing...') : $t('Install')"
      :ok-disabled="installing"
      @ok.prevent="handleInstall"
      @hidden="installError = ''"
    >
      <BAlert v-if="installError" variant="danger" show>
        {{ installError }}
      </BAlert>

      <BFormGroup :label="$t('Source')" class="mb-3">
        <div class="d-flex gap-3">
          <div class="form-check">
            <input
              id="source-url"
              v-model="installSource"
              class="form-check-input"
              type="radio"
              value="url"
            />
            <label class="form-check-label" for="source-url">
              {{ $t('GitHub URL') }}
            </label>
          </div>
          <div class="form-check">
            <input
              id="source-zip"
              v-model="installSource"
              class="form-check-input"
              type="radio"
              value="zip"
            />
            <label class="form-check-label" for="source-zip">
              {{ $t('Upload ZIP') }}
            </label>
          </div>
        </div>
      </BFormGroup>

      <BFormGroup v-if="installSource === 'url'" :label="$t('GitHub Repository URL')">
        <BFormInput
          v-model="installUrl"
          type="url"
          :placeholder="$t('https://github.com/owner/repo')"
          :disabled="installing"
        />
      </BFormGroup>

      <BFormGroup v-else :label="$t('ZIP File')">
        <BFormFile
          v-model="installZip"
          accept=".zip"
          :placeholder="$t('Choose a zip file...')"
          :disabled="installing"
        />
      </BFormGroup>

      <div v-if="installing" class="text-center mt-3">
        <BSpinner variant="primary" small />
        <span class="ms-2">{{ $t('Installing, this may take a moment...') }}</span>
      </div>
    </BModal>
  </BApp>
</template>
