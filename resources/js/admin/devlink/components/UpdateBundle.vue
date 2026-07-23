<script setup>
import { ref, computed, getCurrentInstance, defineEmits, defineExpose } from 'vue';
import InstallProgress from './InstallProgress.vue';

const vue = getCurrentInstance().proxy;
const confirmUpdateVersion = ref(null);
const selected = ref(null);
const selectedOption = ref('update');
const showInstallModal = ref(false);
const installationInProgress = ref(false);
const installationAttempt = ref(0);
const requestError = ref("");
const reinstall = ref(false);
const title = computed(() => {
  if (reinstall.value) {
    return 'Reinstall Bundle';
  }
  return 'Update Bundle';
});

const emit = defineEmits(['installation-complete']);

const show = (bundle, shouldReinstall = false, type = 'update') => {
  selected.value = bundle;
  reinstall.value = shouldReinstall;
  selectedOption.value = type;
  confirmUpdateVersion.value.show();
}

defineExpose({
  show
});

const updateBundleText = computed(() => {
  if (reinstall.value) {
    if (selectedOption.value === 'update') {
      return vue.$t('Are you sure you want to update <strong>{{ selectedBundleName }}</strong>? This will overwrite any changes that have been made to the assets.', { selectedBundleName: selected.value?.name });
    } else if (selectedOption.value === 'copy')  {
      return vue.$t('Are you sure you want to add a copy of <strong>{{ selectedBundleName }}</strong>? This will create a new copy of the bundle assets.', { selectedBundleName: selected.value?.name });
    }
  }
  return vue.$t('Select how you would like to update the bundle <strong>{{ selectedBundleName }}</strong>.', { selectedBundleName: selected.value?.name });
});

const executeUpdate = (updateType) => {
  if (installationInProgress.value) {
    return;
  }

  installationInProgress.value = true;
  installationAttempt.value += 1;
  requestError.value = "";
  showInstallModal.value = true;
  let url;
  if (reinstall.value) {
    url = `devlink/local-bundles/${selected.value.id}/reinstall`
  } else {
    url = `/devlink/${selected.value.dev_link_id}/remote-bundles/${selected.value.remote_id}/install`
  }

  ProcessMaker.apiClient
    .post(url, {
      updateType,
    })
    .catch((error) => {
      const message = error?.response?.data?.message || error?.message;
      requestError.value = typeof message === "string"
        ? message
        : vue.$t("Unable to start installation.");
    });
};

const handleInstallationComplete = () => {
  installationInProgress.value = false;
  showInstallModal.value = false;
  requestError.value = "";
  emit('installation-complete');
};

</script>

<template>
  <div>
    <b-modal
      ref="confirmUpdateVersion"
      centered
      size="lg"
      content-class="modal-style"
      :title="$t(title)"
      :ok-title="$t('Continue')"
      :cancel-title="$t('Cancel')"
      @ok="executeUpdate(selectedOption)"
    >
      <div>
        <p v-html="updateBundleText"></p>

        <b-form-group v-if="!reinstall">
          <b-form-radio-group
            v-model="selectedOption"
            name="bundleUpdateOptions"
            stacked
          >
            <b-form-radio value="update">
              {{ $t('Update Bundle Assets') }}
              <p class="text-muted">{{ $t('Existing assets on this instance will be updated. Warning: this will overwrite any changes you made to the assets on this instance.') }}</p>
            </b-form-radio>

            <b-form-radio value="copy">
              {{ $t('Copy Bundle Assets') }}
              <p class="text-muted">{{ $t('Create new copies of bundle assets.') }}</p>
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
      </div>
    </b-modal>

    <b-modal
      id="install-progress"
      v-model="showInstallModal"
      size="lg"
      :title="$t('Installation Progress')"
      hide-footer
      hide-header-close
      no-close-on-backdrop
      no-close-on-esc
    >
      <install-progress
        :key="installationAttempt"
        :request-error="requestError"
        @installation-complete="handleInstallationComplete"
      />
    </b-modal>
  </div>
</template>
