<script setup>
import { ref, onMounted, computed, getCurrentInstance } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import debounce from 'lodash/debounce';
import Status from './Status.vue';
import EllipsisMenu from '../../../components/shared/EllipsisMenu.vue';
import { store } from '../common';

const vue = getCurrentInstance().proxy;
const router = useRouter();
const route = useRoute();
const devlinks = ref([]);
const editModal = ref(null);
const filter = ref("");
const actions = [
  { value: "edit-item", content: "Edit" },
  { value: "delete-item", content: "Delete" },
];
const customButton = {
  icon: "fas fa-ellipsis-v",
  content: "",
};

const fields = [
  {
    key: 'id',
    label: vue.$t('ID'),
  },
  {
    key: 'name',
    label: vue.$t('Name')
  },
  {
    key: 'url',
    label: vue.$t('URL')
  },
  {
    key: 'status',
    label: vue.$t('Status'),
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
    .get(`/devlink?filter=${filter.value}`)
    .then((result) => {
      devlinks.value = result.data.data;
    });
};

const onNavigate = (action, data, index) => {
  switch (action.value) {
    case "edit-item":
      editDevLink(data);
      break;
    case "delete-item":
      deleteDevLink(data);
      break;
  }
};

const clear = () => {
  newName.value = '';
  newUrl.value = '';
}

const create = (e) => {
  if (!urlIsValid.value) {
    e.preventDefault();
    return;
  }

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

const deleteDevLink = async (devlink) => {
  selected.value = devlink;
  const confirm = await vue.$bvModal.msgBoxConfirm(
    vue.$t('Are you sure you want to delete {{name}}?', {
      name: selected.value.name
    }), {
    okTitle: vue.$t('Ok'),
    cancelTitle: vue.$t('Cancel')
  });

  if (!confirm) {
    return;
  }

  await ProcessMaker.apiClient.delete(`/devlink/${selected.value.id}`);
  load();
};

const select = (devlink) => {
  store.selectedInstance = devlink;
  router.push({ name: 'instance', params: { id: devlink.id } });
};

// Debounced function
const debouncedLoad = debounce(load, 300);

// Function called on change
const handleFilterChange = () => {
  debouncedLoad();
};

const urlIsValid = computed(() => {
  return /^(https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})$/.test(newUrl.value);
});

</script>

<template>
  <div>
    <div class="top-options">
      <input v-model="filter" class="form-control col-10 search-input" @input="handleFilterChange">
      <b-button
        variant="primary"
        v-b-modal.create
        class="new-button"
      >
        <i class="fas fa-plus-circle" style="padding-right: 8px;"></i>{{ $t('Add Instance') }}
      </b-button>
    </div>
    <b-modal
      ref="confirmDeleteModal"
      centered
      title="Delete DevLink"
      @ok="executeDelete"
    >
      <p>Are you sure you want to delete {{ selected?.name }}?</p>
    </b-modal>

    <b-modal
      id="create"
      centered
      :title="$t('Create new DevLink')"
      @hidden="clear"
      @ok="create"
      :ok-title="$t('Create')"
      :cancel-title="$t('Cancel')"
    >
      <b-form-group :label="$t('Name')">
        <b-form-input v-model="newName"></b-form-input>
      </b-form-group>
      <b-form-group
        :label="$t('Instance URL')"
        :invalid-feedback="$t('Invalid URL')"
        :state="urlIsValid"
      >
        <b-form-input v-model="newUrl"></b-form-input>
      </b-form-group>
    </b-modal>

    <b-modal
      ref="editModal"
      centered
      :title="$t('Edit DevLink')"
      @ok="updateDevLink"
      :ok-title="$t('Ok')"
      :cancel-title="$t('Cancel')"
    >
      <template v-if="selected">
        <b-form-group :label="$t('Name')">
          <b-form-input v-model="selected.name"></b-form-input>
        </b-form-group>
      </template>
    </b-modal>
    <div class="card linked-instances-card">
      <b-table
        hover
        @row-clicked="select"
        :items="devlinks"
        :fields="fields"
      >
        <template #cell(name)="data">
          {{ data.item.name }}
        </template>
        <template #cell(status)="data">
          <Status :id="data.item.id" />
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
.ellipsis-devlink {
  border-radius: 10px;
  border: 1px solid #D7DDE5;
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
::v-deep .ellipsis-devlink .btn {
  border-radius: 10px;
}
::v-deep .table {
  border-bottom: 1px solid #e9edf1;
}
::v-deep .table tr {
  cursor: pointer;
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
.linked-instances-card {
  border-radius: 8px;
  min-height: calc(-355px + 100vh);
}
.new-button {
  text-transform: none;
  font-weight: 500;
  font-size: 14px;
}
</style>
