<script setup>
import { ref, onMounted } from 'vue';
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
    <b-button variant="primary" @click="createNewBundle">Create New Bundle</b-button>

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
      <template #cell(menu)="data">
        <a href="#" @click.prevent="edit(data.item)">Edit</a>
        |
        <a href="#" @click.prevent="deleteBundle(data.item)">Delete</a>
      </template>
    </b-table>
  </div>
</template>