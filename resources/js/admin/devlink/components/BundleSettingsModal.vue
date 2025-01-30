<template>
  <b-modal
    ref="bundleSettingsModal"
    centered
    size="lg"
    :title="modalTitle"
    @ok="onOk"
    ok-title="Save"
    :cancel-title="'Cancel'"
  >
    <p>
      These settings will be saved as they are now in the platform. Future changes to the platform's settings won't affect them, as this is a snapshot of the current configuration. To replace this saved configuration with the current one, click "Refresh bundle with current settings".
    </p>
    <div class="card settings-listing-card">
      <b-table
        :items="settings"
        :fields="fields"
        responsive="sm"
        class="asset-listing-table"
      >
        <template #head(toggle)>
          <b-form-checkbox
            v-model="allSelected"
            @change="toggleAll"
            switch
          />
        </template>
        <template #cell(toggle)="data">
          <b-form-checkbox
            v-model="data.item.enabled"
            @change="toggleSetting(data.item.key)"
            switch
          />
        </template>
      </b-table>
    </div>
    <p class="mt-3">Setting's date: 12 may 2024 12:12</p>
  </b-modal>
</template>
<script setup>
import { ref } from 'vue';
import { useRoute } from 'vue-router/composables';

const emit = defineEmits(['settings-saved']);

const fields = [
  { key: 'name', label: 'Name' },
  { key: 'toggle', label: '', class: 'text-center' },
];

const bundleSettingsModal = ref(null);
const route = useRoute();
const bundleId = route.params.id;
const settings = ref([]);
const modalTitle = ref('');
const configs = ref({});
const selectedIds = ref([]);
const allSelected = ref(false);

const onOk = async () => {
  configs.value = {
    id: selectedIds.value,
  };
  await window.ProcessMaker.apiClient.post(`devlink/local-bundles/${bundleId}/add-settings`, {
    setting: modalTitle.value,
    config: JSON.stringify(configs.value),
    type: null
  });
  window.ProcessMaker.alert('Settings saved', 'success');
  emit('settings-saved');
  hide();
};

const show = (config) => {
  modalTitle.value = config.key;
  if (bundleSettingsModal.value) {
    bundleSettingsModal.value.show();
    loadSettings();
  }
};

const hide = () => {
  if (bundleSettingsModal.value) {
    bundleSettingsModal.value.hide();
  }
};

const loadSettings = async () => {
  const response = await window.ProcessMaker.apiClient.get(`devlink/local-bundles/${bundleId}/setting/${modalTitle.value}`);
  const settingsResponse = await window.ProcessMaker.apiClient.get(`devlink/local-bundles/all-settings/${modalTitle.value}`);

  // Check if response.data.config is not empty before parsing
  const configData = response.data.config ? JSON.parse(response.data.config) : { id: [] };
  selectedIds.value = configData.id || [];

  // Check if settings are enabled if their key is in selectedIds
  settings.value = settingsResponse.data.map((setting) => ({
    ...setting,
    enabled: selectedIds.value.includes(setting.key),
  }));

  // Update the state of allSelected
  allSelected.value = settings.value.every(setting => setting.enabled);
};

const toggleSetting = (key) => {
  if (selectedIds.value.includes(key)) {
    selectedIds.value = selectedIds.value.filter(id => id !== key);
  } else {
    selectedIds.value.push(key);
  }
  allSelected.value = settings.value.every(setting => setting.enabled);
};

const toggleAll = () => {
  settings.value.forEach(setting => {
    setting.enabled = allSelected.value;
    if (allSelected.value && !selectedIds.value.includes(setting.key)) {
      selectedIds.value.push(setting.key);
    } else if (!allSelected.value) {
      selectedIds.value = selectedIds.value.filter(id => id !== setting.key);
    }
  });
};

defineExpose({
  show,
  hide,
});
</script>
<style lang="scss" scoped>
  @import "styles/components/modal";

  .settings-listing-card {
  border-radius: 8px;
  margin: 24px;
}
</style>
