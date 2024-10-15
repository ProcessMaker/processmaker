<script setup>
import { ref, onMounted } from 'vue';
import debounce from 'lodash/debounce';
import Origin from './Origin.vue';
import VersionCheck from './VersionCheck.vue';
import { useRouter, useRoute } from 'vue-router/composables';

const router = useRouter();
const route = useRoute();
const bundles = ref([]);
const editModal = ref(null);
const confirmDeleteModal = ref(null);
const filter = ref("");

onMounted(() => {
  load();
})

const load = () => {
  ProcessMaker.apiClient
    .get(`/devlink/local-bundles?filter=${filter.value}`)
    .then((result) => {
      bundles.value = result.data.data;
    });
}

const fields = [
  {
    key: 'name',
    label: 'Name'
  },
  {
    key: 'origin',
    label: 'Origin'
  },
  {
    key: 'published',
    label: 'Published',
  },
  {
    key: 'asset_count',
    label: 'Assets'
  },
  {
    key: 'version',
    label: 'Version'
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
}

const createNewBundle = () => {
  reset();
  editModal.value.show();
}

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
      <i class="fas fa-plus-circle" style="padding-right: 8px;"></i>Create Bundle
      </b-button>
    </div>
    <b-modal
      ref="confirmDeleteModal"
      centered
      content-class="modal-style"
      title="Delete Bundle"
      @ok="executeDelete"
    >
      <p>Are you sure you want to delete {{ selected?.name }}?</p>
    </b-modal>

    <b-modal
      ref="editModal"
      centered
      content-class="modal-style"
      :title="selected.id ? 'Edit Bundle' : 'Create New Bundle'"
      @ok="update"
    >
      <b-form-group label="Name">
        <b-form-input v-model="selected.name"></b-form-input>
      </b-form-group>
      <b-form-group v-if="canEdit(selected)" label="Published">
        <b-form-checkbox v-model="selected.published"></b-form-checkbox>
      </b-form-group>
    </b-modal>
    <div class="card local-bundles-card">
      <b-table
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
          <div class="btn-menu-container">
            <div class="btn-group" role="group" aria-label="Basic example">
              <button
                v-if="canEdit(data.item)"
                type="button"
                class="btn btn-menu"
                @click.prevent="deleteBundle(data.item)"
              >
                <i class="fas fa-cloud-upload-alt" />
              </button>
              <button
                v-if="!canEdit(data.item)"
                type="button"
                class="btn btn-menu"
                @click.prevent="edit(data.item)"
              >
                <i class="fas fa-cloud-download-alt" />
              </button>
              <button
                v-if="canEdit(data.item)"
                type="button"
                class="btn btn-menu"
                @click.prevent="edit(data.item)"
              >
                <img src="/img/pencil-fill.svg">
              </button>
              <button
                type="button"
                class="btn btn-menu"
                @click.prevent="deleteBundle(data.item)"
              >
                <img src="/img/trash-fill.svg">
              </button>
            </div>
          </div>
        </template>
      </b-table>
    </div>
  </div>
</template>

<style lang="scss" scoped>
tr:hover {
  cursor: pointer;
}
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
.btn-menu {
  border: 1px solid rgba(0, 0, 0, 0.125);
  background-color: transparent;
  color: #6A7888;
}
.new-button {
  text-transform: none;
  font-weight: 500;
  font-size: 14px;
}
.btn-menu-container {
  display: flex;
  justify-content: flex-end;
}
</style>
