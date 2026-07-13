<template>
  <b-modal
    ref="bundleSettingsModal"
    centered
    size="lg"
    :title="modalTitle"
    ok-title="Save"
    :cancel-title="'Cancel'"
    :ok-disabled="!editable || loading"
    @ok="onOk"
  >
    <p>
      {{ $t(modalDescription) }}
    </p>
    <div class="card settings-listing-card">
      <b-table
        :items="settings"
        :fields="computedFields"
        :busy="loading"
        responsive="sm"
        show-empty
        :empty-text="$t(emptyText)"
        class="asset-listing-table"
      >
        <template #head(toggle)>
          <b-form-checkbox
            v-model="allSelected"
            :disabled="!editable || loading || settings.length === 0"
            switch
            @change="toggleAll"
          />
        </template>
        <template #cell(toggle)="data">
          <b-form-checkbox
            v-model="data.item.enabled"
            :disabled="!editable || loading"
            switch
            @change="toggleSetting(data.item.key, $event)"
          />
        </template>
        <template #table-busy>
          <div class="text-center my-3">
            <b-spinner
              small
              class="mr-2"
            />
            {{ $t("Loading") }}
          </div>
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
import { ref, computed } from "vue";
import { useRoute } from "vue-router/composables";

const emit = defineEmits(["settings-saved"]);

const props = defineProps({
  editable: {
    type: Boolean,
    default: false,
  },
});

const bundleSettingsModal = ref(null);
const route = useRoute();
const bundleId = route.params.id;
const settings = ref([]);
const modalTitle = ref("");
const settingKey = ref("");
const selectedIds = ref([]);
const allSelected = ref(false);
const loading = ref(false);
const bundleSettingExists = ref(false);
const previewAvailable = ref(true);

const computedFields = computed(() => [
  {
    key: "name",
    label: "Name",
    formatter: (value, key, item) => {
      if (settingKey.value === "ui_settings") {
        return item.key;
      }
      // Concatena el nombre con el grupo si existe
      return item.group ? `${value} (${item.group})` : value;
    },
  },
  { key: "toggle", label: "", class: "text-center" },
]);

const platformConfigurationLabels = {
  ui_dashboards: "Dashboards",
  ui_menus: "Menus",
};
const isPlatformConfiguration = computed(() => (
  Object.prototype.hasOwnProperty.call(platformConfigurationLabels, settingKey.value)
));

const modalDescription = computed(() => {
  if (isPlatformConfiguration.value && !props.editable) {
    return `These ${modalTitle.value.toLowerCase()} are included in the installed bundle.`;
  }

  if (settingKey.value === "ui_dashboards") {
    return [
      "Select the dashboards to include in this bundle.",
      "Selecting all includes every dashboard available when the bundle is exported.",
    ].join(" ");
  }
  if (settingKey.value === "ui_menus") {
    return [
      "Select the menus to include in this bundle.",
      "Selecting all includes every menu available when the bundle is exported.",
    ].join(" ");
  }

  return [
    "These settings will be saved as they are now in the platform.",
    "Future changes to the platform's settings won't affect them, as this is a snapshot of the current configuration.",
  ].join(" ");
});

const emptyText = computed(() => {
  if (isPlatformConfiguration.value && !props.editable && !previewAvailable.value) {
    return "Bundle content preview is unavailable for this version";
  }
  if (settingKey.value === "ui_dashboards" && !props.editable) {
    return "No dashboards included";
  }
  if (settingKey.value === "ui_menus" && !props.editable) {
    return "No menus included";
  }
  if (settingKey.value === "ui_dashboards") {
    return "No dashboards available";
  }
  if (settingKey.value === "ui_menus") {
    return "No menus available";
  }

  return "No settings available";
});

const hide = () => {
  if (bundleSettingsModal.value) {
    bundleSettingsModal.value.hide();
  }
};

const loadSettingPreview = async () => {
  const response = await window.ProcessMaker.apiClient.get(
    `devlink/local-bundles/${bundleId}/setting-preview/${settingKey.value}`,
  );
  const preview = response.data;

  previewAvailable.value = preview.available;
  bundleSettingExists.value = preview.selection !== "none";
  settings.value = (preview.items || []).map((setting) => ({
    ...setting,
    enabled: true,
  }));
  selectedIds.value = settings.value.map((setting) => setting.key);
  allSelected.value = preview.selection === "all" && settings.value.length > 0;
};

const loadSettings = async () => {
  loading.value = true;

  try {
    if (isPlatformConfiguration.value && !props.editable) {
      await loadSettingPreview();
      return;
    }

    const [response, settingsResponse] = await Promise.all([
      window.ProcessMaker.apiClient.get(`devlink/local-bundles/${bundleId}/setting/${settingKey.value}`),
      window.ProcessMaker.apiClient.get(`devlink/local-bundles/all-settings/${settingKey.value}`),
    ]);

    const bundleSetting = response.data && response.data.id ? response.data : null;
    bundleSettingExists.value = !!bundleSetting;
    const availableSettings = settingsResponse.data || [];

    if (isPlatformConfiguration.value && bundleSetting && bundleSetting.config === null) {
      selectedIds.value = availableSettings.map((setting) => setting.key);
    } else {
      let configData = { id: [] };
      if (bundleSetting?.config) {
        configData = typeof bundleSetting.config === "string"
          ? JSON.parse(bundleSetting.config)
          : bundleSetting.config;
      }
      selectedIds.value = configData.id || [];
    }

    settings.value = availableSettings.map((setting) => ({
      ...setting,
      enabled: selectedIds.value.includes(setting.key),
    }));
    selectedIds.value = settings.value
      .filter((setting) => setting.enabled)
      .map((setting) => setting.key);
    allSelected.value = settings.value.length > 0
      && selectedIds.value.length === settings.value.length;
  } finally {
    loading.value = false;
  }
};

const onOk = async () => {
  if (!props.editable) {
    hide();
    return;
  }

  if (selectedIds.value.length === 0 && !bundleSettingExists.value) {
    hide();
    return;
  }

  const config = isPlatformConfiguration.value
    && allSelected.value
    && settings.value.length > 0
    ? null
    : JSON.stringify({ id: selectedIds.value });

  await window.ProcessMaker.apiClient.post(`devlink/local-bundles/${bundleId}/add-settings`, {
    setting: settingKey.value,
    config,
    type: null,
    replaceIds: true,
  });
  const successMessage = isPlatformConfiguration.value
    ? `${modalTitle.value} saved`
    : "Settings saved";
  window.ProcessMaker.alert(successMessage, "success");
  emit("settings-saved");
  hide();
};

const show = (config) => {
  settingKey.value = config.key;
  modalTitle.value = platformConfigurationLabels[config.key]
    || (config.key === "ui_settings" ? "UI Settings" : config.key);
  settings.value = [];
  selectedIds.value = [];
  allSelected.value = false;
  bundleSettingExists.value = false;
  previewAvailable.value = true;
  if (bundleSettingsModal.value) {
    bundleSettingsModal.value.show();
    loadSettings();
  }
};

const refreshUi = async () => {
  await window.ProcessMaker.apiClient.post("devlink/local-bundles/setting/refresh-ui");
  window.ProcessMaker.alert("UI refreshed", "success");
  emit("settings-saved");
};

const toggleSetting = (key, enabled) => {
  if (enabled && !selectedIds.value.includes(key)) {
    selectedIds.value.push(key);
  } else if (!enabled) {
    selectedIds.value = selectedIds.value.filter((id) => id !== key);
  }
  allSelected.value = settings.value.length > 0
    && selectedIds.value.length === settings.value.length;
};

const toggleAll = (enabled) => {
  settings.value = settings.value.map((setting) => ({
    ...setting,
    enabled,
  }));
  selectedIds.value = enabled ? settings.value.map((setting) => setting.key) : [];
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
