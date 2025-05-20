<template>
  <b-modal
    ref="bundleSettingsModal"
    centered
    size="lg"
    :title="modalTitle"
    @ok="onOk"
    ok-title="Save"
    :cancel-title="'Cancel'"
    :ok-disabled="!editable"
  >
    <p>
      {{ $t("These settings will be saved as they are now in the platform. Future changes to the platform's settings won't affect them, as this is a snapshot of the current configuration.") }}
    </p>
    <div class="card settings-listing-card">
      <b-table
        :items="settings"
        :fields="computedFields"
        responsive="sm"
        class="asset-listing-table"
      >
        <template #head(toggle)>
          <b-form-checkbox
            v-model="allSelected"
            :disabled="!editable"
            @change="toggleAll"
            switch
          />
        </template>
        <template #cell(toggle)="data">
          <b-form-checkbox
            v-model="data.item.enabled"
            :disabled="!editable"
            @change="toggleSetting(data.item.key)"
            switch
          />
        </template>
      </b-table>
    </div>
    <button
      v-if="settingKey === 'ui_settings' && !editable"
      class="btn btn-primary"
      @click="refreshUi"
    >
      {{ $t("Refresh UI") }}
    </button>
  </b-modal>
</template>
<script setup>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router/composables';

const emit = defineEmits(['settings-saved']);

const props = defineProps({
  editable: {
    type: Boolean,
    default: false,
  },
});

const computedFields = computed(() => [
  { 
    key: 'name', 
    label: 'Name',
    formatter: (value, key, item) => {
      if (settingKey.value === 'ui_settings') {
        return item.key;
      }
      // Concatena el nombre con el grupo si existe
      return item.group ? `${value} (${item.group})` : value;
    }
  },
  { key: 'toggle', label: '', class: 'text-center' },
]);

const bundleSettingsModal = ref(null);
const route = useRoute();
const bundleId = route.params.id;
const settings = ref([]);
const modalTitle = ref('');
const settingKey = ref('');
const configs = ref({});
const selectedIds = ref([]);
const allSelected = ref(false);

const onOk = async () => {
  configs.value = {
    id: selectedIds.value,
  };
  await window.ProcessMaker.apiClient.post(`devlink/local-bundles/${bundleId}/add-settings`, {
    setting: settingKey.value,
    config: JSON.stringify(configs.value),
    type: null,
    replaceIds: true
  });
  window.ProcessMaker.alert('Settings saved', 'success');
  emit('settings-saved');
  hide();
};

const show = (config) => {
  modalTitle.value = config.key === 'ui_settings' ? 'UI Settings' : config.key;
  settingKey.value = config.key;
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
  const response = await window.ProcessMaker.apiClient.get(`devlink/local-bundles/${bundleId}/setting/${settingKey.value}`);
  const settingsResponse = await window.ProcessMaker.apiClient.get(`devlink/local-bundles/all-settings/${settingKey.value}`);
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

const refreshUi = async () => {
  await window.ProcessMaker.apiClient.post(`devlink/local-bundles/setting/refresh-ui`);
  window.ProcessMaker.alert('UI refreshed', 'success');
  emit('settings-saved');
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
