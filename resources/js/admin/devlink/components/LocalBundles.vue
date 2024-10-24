<script setup>
import { ref, onMounted, getCurrentInstance, computed } from 'vue';
import debounce from 'lodash/debounce';
import Origin from './Origin.vue';
import VersionCheck from './VersionCheck.vue';
import EllipsisMenu from '../../../components/shared/EllipsisMenu.vue';
import BundleModal, { show as showBundleModal, hide as hideBundleModal } from './BundleModal.vue';
import InstallProgress from './InstallProgress.vue';
import { useRouter, useRoute } from 'vue-router/composables';

const vue = getCurrentInstance().proxy;
const router = useRouter();
const route = useRoute();
const bundles = ref([]);
const editModal = ref(null);
const confirmDeleteModal = ref(null);
const confirmIncreaseVersion = ref(null);
const confirmUpdateVersion = ref(null);
const selectedOption = ref('update');
const showInstallModal = ref(false);
const filter = ref("");
const bundleModal = ref(null);

const actions = [
  { value: "increase-item", content: "Increase Version", conditional: "if(not(dev_link_id), true, false)" },
  { value: "update-item", content: "Update Version", conditional: "if(dev_link_id, true, false)" },
  { value: "edit-item", content: "Edit", conditional: "if(not(dev_link_id) , true, false)" },
  { value: "delete-item", content: "Delete" },
];
const customButton = {
  icon: "fas fa-ellipsis-v",
  content: "",
};

onMounted(() => {
  load();
})

const load = () => {
  ProcessMaker.apiClient
    .get(`/devlink/local-bundles?filter=${filter.value}`)
    .then((result) => {
      bundles.value = result.data.data;
    });
};

const fields = [
  {
    key: 'name',
    label: vue.$t('Name')
  },
  {
    key: 'origin',
    label: vue.$t('Origin'),
  },
  {
    key: 'published',
    label: vue.$t('Published'),
  },
  {
    key: 'asset_count',
    label: vue.$t('Assets'),
  },
  {
    key: 'version',
    label: vue.$t('Version'),
  },
  {
    key: 'menu',
    label: ''
  },
];

const bundleAttributes = {
  id: null,
  name: '',
  published: false,
};

const selected = ref(bundleAttributes);

const reset = () => {
  selected.value = { ...bundleAttributes };
};

const createNewBundle = () => {
  reset();
  if (bundleModal.value) {
    bundleModal.value.show();
  }
}

const onNavigate = (action, data, index) => {
  switch (action.value) {
    case "edit-item":
      edit(data);
      break;
    case "increase-item":
      increaseVersionBundle(data);
      break;
    case "update-item":
      updateVersionBundle(data);
      break;
    case "delete-item":
      deleteBundle(data);
      break;
  }
};

const create = () => {
  ProcessMaker.apiClient
    .post('/devlink/local-bundles', selected.value)
    .then((result) => {
      load();
    });
};

const edit = (bundle) => {
  selected.value = { ...bundle };
  if (bundleModal.value) {
    bundleModal.value.show();
  }
};

const update = () => {
  if (selected.value.id === null) {
    create();
    return;
  }
  ProcessMaker.apiClient
    .put(`/devlink/local-bundles/${selected.value.id}`, selected.value)
    .then((result) => {
      load();
    });
};

const updatePublished = (bundle) => {
  selected.value = bundle;
  update();
};

const deleteBundle = (bundle) => {
  selected.value = bundle;
  confirmDeleteModal.value.show();
};

const updateVersionBundle = (bundle) => {
  selected.value = bundle;
  confirmUpdateVersion.value.show();
};

const increaseVersionBundle = (bundle) => {
  selected.value = bundle;
  confirmIncreaseVersion.value.show();
};

const executeIncrease = () => {
  ProcessMaker.apiClient
    .post(`devlink/local-bundles/${selected.value.id}/increase-version`)
    .then((result) => {
      confirmIncreaseVersion.value.hide();
      load();
    });
};

const executeDelete = () => {
  ProcessMaker.apiClient
    .delete(`/devlink/local-bundles/${selected.value.id}`)
    .then((result) => {
      confirmDeleteModal.value.hide();
      load();
    });
};

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

// Debounced function
const debouncedLoad = debounce(load, 300);

// Function called on change
const handleFilterChange = () => {
  debouncedLoad();
};

const canEdit = (bundle) => {
  return bundle.dev_link === null;
}

const goToBundleAssets = (bundle) => {
  router.push({ name: 'bundle-assets', params: { id: bundle.id } });
}

const deleteWaring = computed(() => {
  const name = selected?.value.name;
  return vue.$t('Are you sure you want to delete {{name}}?', { name });
})
</script>

<template>
  <div>
    <div class="top-options row">
      <div class="col">
        <input v-model="filter" class="form-control search-input" @input="handleFilterChange">
      </div>
      <div class="col-2">
        <b-button
          variant="primary"
          @click="createNewBundle"
          class="new-button"
        >
          <i class="fas fa-plus-circle" style="padding-right: 8px;"></i>
          {{ $t('Create Bundle') }}
        </b-button>
      </div>
    </div>
    <b-modal
      ref="confirmDeleteModal"
      centered
      content-class="modal-style"
      title="Delete Bundle"
      @ok="executeDelete"
    >
      <p>{{ deleteWaring }}'</p>
    </b-modal>

    <BundleModal ref="bundleModal" :bundle="selected" @update="update" />

    <b-modal
      ref="confirmIncreaseVersion"
      centered
      content-class="modal-style"
      title="Increase Version"
      @ok="executeIncrease"
    >
      <p>Are you sure you increase the version of {{ selected?.name }}?</p>
    </b-modal>

    <b-modal
      ref="confirmUpdateVersion"
      centered
      size="lg"
      content-class="modal-style"
      :title="$t('Update Bundle Version')"
      :ok-title="$t('Continue')"
      :cancel-title="$t('Cancel')"
      @ok="executeUpdate(selectedOption)"
    >
      <div>
        <p class="mb-4">Select how you want to update the bundle <strong>Testing Processes and Assets</strong></p>

        <b-form-group>
          <b-form-radio-group v-model="selectedOption" name="bundleUpdateOptions">
            <b-form-radio
              class="mb-4"
              value="update"
            >
              {{ $t('Quick Update') }}
              <p class="text-muted">{{ $t('The current bundle will be replaced completely for the new version immediately.') }}</p>
            </b-form-radio>

            <b-form-radio value="copy">
              {{ $t('Copy Changes') }}
              <p class="text-muted">{{ $t('Copy and update bundle.') }}</p>
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
      </div>
    </b-modal>

    <b-modal id="install-progress" size="lg" v-model="showInstallModal" :title="$t('Installation Progress')" hide-footer>
      <install-progress />
    </b-modal>
    <div class="card local-bundles-card">
      <b-table
        hover
        class="clickable"
        :items="bundles"
        :fields="fields"
        @row-clicked="goToBundleAssets"
      >
        <template #cell(name)="data">
          {{ data.item.name }}
          <i v-if="!canEdit(data.item)" class="ml-2 fa fa-lock"></i>
        </template>
        <template #cell(published)="data">
          <b-form-checkbox v-if="canEdit(data.item)" v-model="data.item.published" switch @change="updatePublished(data.item)">
          </b-form-checkbox>
        </template>
        <template #cell(origin)="data">
          <Origin :dev-link="data.item.dev_link"></Origin>
        </template>
        <template #cell(version)="data">
          {{ data.item.version }} <VersionCheck :dev-link="data.item"></VersionCheck>
        </template>
        <template #cell(menu)="data">
          <EllipsisMenu
            class="ellipsis-devlink"
            :actions="actions"
            :data="data.item"
            :custom-button="customButton"
            @navigate="onNavigate"
          />
        </template>
      </b-table>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.top-options {
  padding-bottom: 16px;

  .search-input {
    background: url(/img/search-icon.svg) no-repeat left;
    background-position: 7px 8px;
    background-size: 15px;
    border-radius: 8px;
  }

  .new-button {
    width: 100%;
    text-transform: none;
    font-weight: 500;
    font-size: 14px;
  }
}

@import "styles/components/table";

.local-bundles-card {
  border-radius: 8px;
  min-height: calc(-355px + 100vh);
}
.btn-menu {
  border: 1px solid rgba(0, 0, 0, 0.125);
  background-color: transparent;
}
.btn-menu-container {
  display: flex;
  justify-content: flex-end;
}
</style>
