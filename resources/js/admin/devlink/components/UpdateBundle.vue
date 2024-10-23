<script setup>
import { ref, onMounted, computed, getCurrentInstance, defineExpose } from 'vue';
import InstallProgress from './InstallProgress.vue';

const vue = getCurrentInstance().proxy;
const confirmUpdateVersion = ref(null);
const selected = ref(null);
const selectedOption = ref('update');
const showInstallModal = ref(false);

const show = (bundle) => {
  selected.value = bundle;
  confirmUpdateVersion.value.show();
}

defineExpose({
  show
});

const updateBundleText = computed(() => {
  return vue.$t('Select how you would like to update the bundle <strong>{{ selectedBundleName }}</strong>.', { selectedBundleName: selected.value?.name });
});

const executeUpdate = (updateType) => {
  showInstallModal.value = true;
  ProcessMaker.apiClient
    .post(`/devlink/${selected.value.dev_link_id}/remote-bundles/${selected.value.remote_id}/install`, {
      updateType,
    })
    .then((response) => {
      // Handle the response as needed
    });
};

</script>

<template>
  <div>
    <b-modal
      ref="confirmUpdateVersion"
      centered
      size="lg"
      content-class="modal-style"
      :title="$t('Update Bundle')"
      :ok-title="$t('Continue')"
      :cancel-title="$t('Cancel')"
      @ok="executeUpdate(selectedOption)"
    >
      <div>
        <p v-html="updateBundleText"></p>

        <b-form-group>
          <b-form-radio-group
            v-model="selectedOption"
            name="bundleUpdateOptions"
            stacked
          >
            <b-form-radio value="update">
              {{ $t('Update Bundle Assets') }}
              <p class="text-muted">{{ $t('Existing assets on this instance will be updated.') }}</p>
            </b-form-radio>

            <b-form-radio value="copy">
              {{ $t('Copy Bundle Assets') }}
              <p class="text-muted">{{ $t('Create new copies of bundle assets.') }}</p>
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
      </div>
    </b-modal>

    <b-modal id="install-progress" size="lg" v-model="showInstallModal" :title="$t('Installation Progress')" hide-footer>
      <install-progress />
    </b-modal>
  </div>
</template>