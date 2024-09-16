<script setup>
import { ref, onMounted } from 'vue';
import Status from './Status.vue';
import { useRouter, useRoute } from 'vue-router/composables';

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

</script>

<template>
  <div>
    <b-button variant="primary" v-b-modal.create>New Instance</b-button>

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
  
    <b-table :items="devlinks" :fields="fields">
      <template #cell(name)="data">
        <a href="#" @click.prevent="router.push({ name: 'instance', params: { id: data.item.id } })">{{ data.item.name }}</a>
      </template>
      <template #cell(status)="data">
        <Status :id="data.item.id" />
      </template>
      <template #cell(menu)="data">
        <a href="#" @click.prevent="editDevLink(data.item)">Edit</a>
        |
        <a href="#" @click.prevent="deleteDevLink(data.item)">Delete</a>
      </template>
    </b-table>
  </div>
</template>

<style lang="scss" scoped>
tr:hover {
  cursor: pointer;
}
</style>