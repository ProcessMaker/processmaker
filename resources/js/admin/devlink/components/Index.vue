<script setup>
import { ref, onMounted } from 'vue';
import Status from './Status.vue';
import { useRouter, useRoute } from 'vue-router/composables';
import { store } from '../common';

const router = useRouter();
const route = useRoute();
const devlinks = ref([]);
const confirmDeleteModal = ref(null);
const editModal = ref(null);

const fields = [
  {
    key: 'id',
    label: 'ID'
  },
  {
    key: 'name',
    label: 'Name'
  },
  {
    key: 'url',
    label: 'URL'
  },
  {
    key: 'status',
    label: 'Status'
  },
  {
    key: 'menu',
    label: ''
  },
];

onMounted(() => {
  load();
});

const newName = ref('');
const newUrl = ref('');

const load = () => {
  ProcessMaker.apiClient
    .get('/devlink')
    .then((result) => {
      devlinks.value = result.data;
    });
};

const clear = () => {
  newName.value = '';
  newUrl.value = '';
}

const create = () => {
  ProcessMaker.apiClient
    .post('/devlink', {
      name: newName.value,
      url: newUrl.value
    })
    .then((result) => {
      const newUrl = result.data.url;
      const newId = result.data.id;
      const redirectUri = result.data.redirect_uri;
      const params = {
        devlink_id: newId,
        redirect_uri: redirectUri,
      };
      window.location.href = `${newUrl}/admin/devlink/oauth-client?${new URLSearchParams(params).toString()}`;
    });
};

const selected = ref(null);
const editDevLink = (devlink) => {
  selected.value = { ...devlink }; // copy the object
  editModal.value.show();
};

const updateDevLink = () => {
  ProcessMaker.apiClient
    .put(`/devlink/${selected.value.id}`, {
      name: selected.value.name
    })
    .then((result) => {
      editModal.value.hide();
      load();
    });
};

const deleteDevLink = (devlink) => {
  selected.value = devlink;
  confirmDeleteModal.value.show();
};

const executeDelete = () => {
  ProcessMaker.apiClient
    .delete(`/devlink/${selected.value.id}`)
    .then((result) => {
      confirmDeleteModal.value.hide();
      load();
    });
}

const select = (devlink) => {
  store.selectedInstance = devlink;
  router.push({ name: 'instance', params: { id: devlink.id } });
}

</script>

<template>
  <div>
    <div class="top-options">
      <b-button 
        variant="primary" 
        v-b-modal.create
        class="new-button"
      >
        <i class="fas fa-plus-circle" style="padding-right: 8px;"></i>Add Instance
    </b-button>
    </div>
    <b-modal ref="confirmDeleteModal" title="Delete DevLink" @ok="executeDelete">
      <p>Are you sure you want to delete {{ selected?.name }}?</p>
    </b-modal>

    <b-modal id="create" title="Create new devlink" @hidden="clear" @ok="create">
      <b-form-group label="Name">
        <b-form-input v-model="newName"></b-form-input>
      </b-form-group>
      <b-form-group label="Instance URL">
        <b-form-input v-model="newUrl"></b-form-input>
      </b-form-group>
    </b-modal>

    <b-modal ref="editModal" title="Edit DevLink" @ok="updateDevLink">
      <template v-if="selected">
        <b-form-group label="Name">
          <b-form-input v-model="selected.name"></b-form-input>
        </b-form-group>
      </template>
    </b-modal>
    <div class="card linked-instances-card">
      <b-table 
        :items="devlinks" 
        :fields="fields"
        class="linked-instances-table"
      >
        <template #cell(name)="data">
          <a href="#" @click.prevent="select(data.item)">{{ data.item.name }}</a>
        </template>
        <template #cell(status)="data">
          <Status :id="data.item.id" />
        </template>
        <template #cell(menu)="data">
          <div class="btn-group" role="group" aria-label="Basic example">
            <button 
              type="button" 
              class="btn btn-menu" 
              @click.prevent="editDevLink(data.item)"
            >
              <img src="/img/pencil-fill.svg">
            </button>
            <button 
              type="button" 
              class="btn btn-menu" 
              @click.prevent="deleteDevLink(data.item)"
            >
              <img src="/img/trash-fill.svg">
            </button>
          </div>
        </template>
      </b-table>
    </div>
  </div>
</template>

<style>
tr:hover {
  cursor: pointer;
}
.top-options {
  display: flex;
  justify-content: flex-end;
  padding-bottom: 16px;
}
.linked-instances-table {
  border-bottom: 1px solid #e9edf1;
}
.linked-instances-table th {
  border-top: none;
  background-color: #FBFBFC;
  border-right: 1px solid rgba(0, 0, 0, 0.125);
  color: #4E5663;
  font-weight: 600;
  font-size: 14px;
}
.linked-instances-table thead th:last-child {
  border-right: none !important;
  border-top-right-radius: 8px;
}
.linked-instances-table thead th:first-child {
  border-top-left-radius: 8px;
}
.linked-instances-card {
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
</style>