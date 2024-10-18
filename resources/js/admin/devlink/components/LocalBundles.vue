<script setup>
import { ref, onMounted, getCurrentInstance, computed } from 'vue';
import debounce from 'lodash/debounce';
import Origin from './Origin.vue';
import VersionCheck from './VersionCheck.vue';
import EllipsisMenu from '../../../components/shared/EllipsisMenu.vue';
import { useRouter, useRoute } from 'vue-router/composables';

const vue = getCurrentInstance().proxy;
const router = useRouter();
const route = useRoute();
const bundles = ref([]);
const editModal = ref(null);
const confirmDeleteModal = ref(null);
const confirmIncreaseVersion = ref(null);
const confirmUpdateVersion = ref(null);
const filter = ref("");
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
  editModal.value.show();
};

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
  editModal.value.show();
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

const executeUpdate = () => {
  return null;
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
  console.log("Name is " + name);
  return vue.$t('Are you sure you want to delete {{name}}?', { name });
})
</script>

<template>
  <div>
    <div class="top-options">
      <input v-model="filter" class="form-control col-10 search-input" @input="handleFilterChange">
      <b-button
        variant="primary"
        @click="createNewBundle"
        class="new-button"
      >
      <i class="fas fa-plus-circle" style="padding-right: 8px;"></i>
      {{ $t('Create Bundle') }}
      </b-button>
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
      content-class="modal-style"
      :title="$t('Update Bundle Version')"
      :ok-title="$t('Continue')"
      :cancel-title="$t('Cancel')"
      @ok="executeUpdate"
    >
      <div>
        <p>Select how you want to update the bundle <strong>Testing Processes and Assets</strong></p>

        <b-form-group>
          <b-form-radio-group v-model="selectedOption" name="bundleUpdateOptions">
            <b-form-radio value="quick-update">
              Quick Update
              <p class="text-muted">The current bundle will be replaced completely for the new version immediately.</p>
            </b-form-radio>

            <b-form-radio value="review-changes">
              Review changes in bundle
              <p class="text-muted">Check and compare the changes before updating the bundle.</p>
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
      </div>
    </b-modal>

    <b-modal
      ref="editModal"
      centered
      content-class="modal-style"
      :title="selected.id ? $t('Edit Bundle') : $t('Create New Bundle')"
      @ok="update"
      :ok-title="$t('Ok')"
      :cancel-title="$t('Cancel')"
    >
      <b-form-group :label="$t('Name')">
        <b-form-input v-model="selected.name"></b-form-input>
      </b-form-group>
      <b-form-group v-if="canEdit(selected)" :label="$t('Published')">
        <b-form-checkbox v-model="selected.published"></b-form-checkbox>
      </b-form-group>
    </b-modal>
    <div class="card local-bundles-card">
      <b-table
        hover
        @row-clicked="goToBundleAssets"
        :items="bundles"
        :fields="fields"
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
  display: flex;
  justify-content: space-between;
  padding-bottom: 16px;
}
.search-input {
  padding-left: 30px;
  background: url(/img/search-icon.svg) no-repeat left;
  background-position: 7px 8px;
  background-size: 15px;
  border-radius: 8px;
}
.ellipsis-devlink {
  border-radius: 10px;
  border: 1px solid #D7DDE5;
}
::v-deep .ellipsis-devlink .btn {
  border-radius: 10px;
}
::v-deep .modal-style {
  border-radius: 8px;
}
::v-deep .modal-header {
  border-bottom: none;
}
::v-deep .modal-footer {
  border-top: none;
}
::v-deep .modal-title {
  font-size: 24px;
  font-weight: 500;
  color: #20242A;
}
::v-deep .modal-body {
  font-size: 14px;
  font-weight: 400;
  color: #20242A;
}
::v-deep .table {
  border-bottom: 1px solid #e9edf1;
}
::v-deep .table > thead > tr > th {
  border-top: none;
  background-color: #FBFBFC;
  border-right: 1px solid rgba(0, 0, 0, 0.125);
  color: #4E5663;
  font-weight: 600;
  font-size: 14px;
}
::v-deep .table > tbody > tr > td {
  color: #4E5663;
  font-size: 14px;
  font-weight: 400;
}
::v-deep .table > tbody > tr {
  cursor: pointer;
}
::v-deep .table > thead > tr > th:last-child {
  border-right: none !important;
  border-top-right-radius: 8px;
}
::v-deep .table > thead > tr > th:first-child {
  border-top-left-radius: 8px;
}
.local-bundles-card {
  border-radius: 8px;
  min-height: calc(-355px + 100vh);
}
.new-button {
  text-transform: none;
  font-weight: 500;
  font-size: 14px;
}
</style>
