<script setup>
import { ref, onMounted, getCurrentInstance, computed, reactive, set } from 'vue';
import debounce from 'lodash/debounce';
import Origin from './Origin.vue';
import VersionCheck from './VersionCheck.vue';
import EllipsisMenu from '../../../components/shared/EllipsisMenu.vue';
import BundleModal from './BundleModal.vue';
import DeleteModal from './DeleteModal.vue';
import { useRouter, useRoute } from 'vue-router/composables';
import UpdateBundle from './UpdateBundle.vue';

const vue = getCurrentInstance().proxy;
const router = useRouter();
const route = useRoute();
const bundles = ref([]);
const editModal = ref(null);
const confirmDeleteModal = ref(null);
const confirmPublishNewVersion = ref(null);
const confirmUpdateVersion = ref(null);
const filter = ref("");
const bundleModal = ref(null);
const updateBundle = ref(null);
const deleteWarningTitle = ref(vue.$t("Delete Confirmation"));
const updatesAvailable = reactive({});
const refreshKey = ref(0);

const actions = [
  { value: "open-item", content: "Open" },
  { value: "increase-item", content: "Publish New Version", conditional: "if(not(dev_link_id), true, false)" },
  { value: "update-item", content: "Update Bundle", conditional: "if(update_available, true, false)" },
  { value: "reinstall-item", content: "Reinstall Bundle", conditional: "if(dev_link_id, true, false)" },
  { value: "edit-item", content: "Edit", conditional: "if(not(dev_link_id) , true, false)" },
  { value: "delete-item", content: "Delete" },
]
const updateBundleAction = {
  value: "increase-item", content: "Publish New Version", conditional: "if(not(dev_link_id), true, false)"
};
const customButton = {
  icon: "fas fa-ellipsis-v",
  content: "",
};

const setUpdateAvailable = (bundle, updateAvailable) => {
  set(updatesAvailable, bundle.id, updateAvailable);
};

onMounted(() => {
  load();
})

const load = () => {
  ProcessMaker.apiClient
    .get(`/devlink/local-bundles?filter=${filter.value}`)
    .then((result) => {
      bundles.value = result.data.data;
      refreshKey.value++;
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
  description: '',
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
    case "open-item":
      goToBundleAssets(data);
      break;
    case "edit-item":
      edit(data);
      break;
    case "increase-item":
      increaseVersionBundle(data);
      break;
    case "update-item":
      updateVersionBundle(data);
      break;
    case "reinstall-item":
      updateVersionBundle(data, true);
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

const updateVersionBundle = (bundle, reinstall = false) => {
  selected.value = bundle;
  updateBundle.value.show(bundle, reinstall);
};

const increaseVersionBundle = (bundle) => {
  selected.value = bundle;
  confirmPublishNewVersion.value.show();
};

const executeIncrease = () => {
  ProcessMaker.apiClient
    .post(`devlink/local-bundles/${selected.value.id}/increase-version`)
    .then((result) => {
      confirmPublishNewVersion.value.hide();
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
  router.push({ name: 'bundle-detail', params: { id: bundle.id } });
}

const deleteWarning = computed(() => {
  const name = selected?.value.name;
  return vue.$t('Are you sure you want to delete <strong>{{name}}</strong>? The action is irreversible.', { name });
});

const confirmPublishNewVersionText = computed(() => {
  return vue.$t('Are you sure you increase the version of <strong>{{ selectedBundleName }}</strong>?', { selectedBundleName: selected.value?.name });
});

const handleInstallationComplete = () => {
  load();
};

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

    <DeleteModal ref="confirmDeleteModal" :message="deleteWarning" :title="deleteWarningTitle" @delete="executeDelete" />

    <BundleModal ref="bundleModal" :bundle="selected" @update="update" />

    <b-modal
      ref="confirmPublishNewVersion"
      centered
      content-class="modal-style"
      title="Publish New Version"
      @ok="executeIncrease"
    >
      <p v-html="confirmPublishNewVersionText"></p>
    </b-modal>

    <UpdateBundle
      ref="updateBundle"
      @installation-complete="handleInstallationComplete"
    ></UpdateBundle>

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
          {{ data.item.version }} <VersionCheck 
            :key="`version-check-${data.item.id}-${refreshKey}`" 
            @updateAvailable="setUpdateAvailable(data.item, $event)" 
            :dev-link="data.item">
          </VersionCheck>
        </template>
        <template #cell(menu)="data">
          <EllipsisMenu
            class="ellipsis-devlink"
            :actions="actions"
            :data="{ ...data.item, update_available: updatesAvailable[data.item.id] ?? false }"
            :custom-button="customButton"
            @navigate="onNavigate"
          />
        </template>
      </b-table>
      <div v-if="bundles.length === 0" class="div-message d-flex flex-column justify-content-center align-items-center">
        <div class="div-message-title">{{ $t("No bundles of assets to display") }}</div>
        <div>{{ $t("Create a bundle to easily share assets and settings between ProcessMaker instances.") }}</div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
.top-options {
  padding-bottom: 16px;

  .search-input {
    padding-left: 30px;
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
@import "styles/components/modal";
::v-deep .delete-modal-style .modal-header .delete-icon {
  width: 48px;
  height: 48px;
  background-color: #FEE6E5;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  color: #EC5962;
  font-size: 26px;
}
::v-deep .delete-modal-style .modal-body-text {
  font-size: 16px;
  font-weight: 500;
}
::v-deep .delete-modal-style .modal-footer {
  background-color: #FBFBFC;
  border-top: 1px solid #E9ECF1;
  border-bottom-left-radius: 16px;
  border-bottom-right-radius: 16px;
}
::v-deep .delete-modal-style .modal-footer .btn-primary {
  border: none;
  background-color: #EC5962;
  color: #FFFFFF;
}
::v-deep .delete-modal-style .modal-footer .btn-secondary {
  border: 1px solid #D7DDE5;
  background-color: #FFFFFF;
  color: #20242A;
}

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
.div-message {
  position: absolute;
  top: 50px;
  bottom: 0px;
  left: 0px;
  right: 0px;
}
.div-message-title {
  font-size: larger;
  padding-bottom: 5px;
}
</style>
