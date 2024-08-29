<script setup>
import { ref, onMounted } from 'vue';
import Status from './Status.vue';

let devlinks = ref([]);

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
  'status'
];

onMounted(() => {
  ProcessMaker.apiClient
    .get('/devlink')
    .then((result) => {
      console.log("Got result", result);
      devlinks.value = result.data;
    });
});

const newName = ref('test');
const newUrl = ref('http://processmaker-b.test');

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
      const params = {
        devlink_id: newId,
        redirect_url: window.location.href,
      };
      window.location.href = `${newUrl}/admin/devlink/oauth-client?${new URLSearchParams(params).toString()}`;
    });
};

</script>

<template>
  <div>
    <b-button variant="primary" v-b-modal.create>Create</b-button>
    <b-modal id="create" title="Create new devlink" @hidden="clear" @ok="create">
      <b-form-group label="Name">
        <b-form-input v-model="newName"></b-form-input>
      </b-form-group>
      <b-form-group label="Instance URL">
        <b-form-input v-model="newUrl"></b-form-input>
      </b-form-group>
    </b-modal>
    <b-table :items="devlinks" :fields="fields">
      <template #cell(status)="data">
        <Status :id="data.item.id" />
      </template>
    </b-table>
  </div>
</template>