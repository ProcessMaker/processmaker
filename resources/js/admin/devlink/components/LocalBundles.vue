<script setup>
import { ref, onMounted } from 'vue';
import Origin from './Origin.vue';
import { useRouter, useRoute } from 'vue-router/composables';

const router = useRouter();
const route = useRoute();
const bundles = ref([]);
const editModal = ref(null);
const confirmDeleteModal = ref(null);

onMounted(() => {
  load();
})

const load = () => {
  ProcessMaker.apiClient
    .get(`/devlink/local-bundles`)
    .then((result) => {
      bundles.value = result.data;
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
  locked: false,
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
}
</script>

<template>
  <div>
    <div class="top-options">
      <b-button 
        variant="primary" 
        @click="createNewBundle"
        class="new-button"
      >
      <i class="fas fa-plus-circle" style="padding-right: 8px;"></i>Create Bundle
      </b-button>
    </div>
    <b-modal ref="confirmDeleteModal" title="Delete Bundle" @ok="executeDelete">
      <p>Are you sure you want to delete {{ selected?.name }}?</p>
    </b-modal>

    <b-modal ref="editModal" :title="selected.id ? 'Edit Bundle' : 'Create New Bundle'" @ok="update">
      <b-form-group label="Name">
        <b-form-input v-model="selected.name"></b-form-input>
      </b-form-group>
      <b-form-group label="Published">
        <b-form-checkbox v-model="selected.published"></b-form-checkbox>
      </b-form-group>
      <b-form-group label="Locked">
        <b-form-checkbox v-model="selected.locked"></b-form-checkbox>
      </b-form-group>
    </b-modal>
    <div class="card local-bundles-card">
      <b-table
        :items="bundles"
        :fields="fields"
      >
        <template #cell(name)="data">
          {{ data.item.name }}
          <i v-if="data.item.locked" class="ml-2 fa fa-lock"></i>
        </template>
        <template #cell(published)="data">
          <b-form-checkbox v-model="data.item.published" switch @change="updatePublished(data.item)">
          </b-form-checkbox>
        </template>
        <template #cell(origin)="data">
          <Origin :dev-link="data.item.dev_link"></Origin>
        </template>
        <template #cell(menu)="data">
          <div class="btn-menu-container">
            <div class="btn-group" role="group" aria-label="Basic example">
              <button 
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
  justify-content: flex-end;
  padding-bottom: 16px;
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
