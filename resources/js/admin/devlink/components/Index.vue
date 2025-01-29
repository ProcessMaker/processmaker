<script setup>
import { ref, onMounted, computed, getCurrentInstance } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import debounce from 'lodash/debounce';
import Status from './Status.vue';
import EllipsisMenu from '../../../components/shared/EllipsisMenu.vue';
import DeleteModal from './DeleteModal.vue';
import CreateDevLinkModal from './CreateDevLinkModal.vue';
import { store } from '../common';

const vue = getCurrentInstance().proxy;
const router = useRouter();
const route = useRoute();
const devlinks = ref([]);
const editModal = ref(null);
const deleteModal = ref(null);
const createDevLinkModal = ref(null);
const deleteWarningTitle = ref(vue.$t("Delete Confirmation"));
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
const status = ref('');

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

const create = (name, url) => {
  status.value = '';
  ProcessMaker.apiClient
    .post('/devlink', {
      name: name,
      url: url
    })
    .then((result) => {
      const newUrl = result.data.url;
      const newId = result.data.id;
      const redirectUri = result.data.redirect_uri;
      const params = {
        devlink_id: newId,
        redirect_uri: redirectUri,
      };
      const fullUrl = `${newUrl}/admin/devlink/oauth-client?${new URLSearchParams(params).toString()}`;

      ProcessMaker.apiClient
        .get(`devlink/${newId}/ping`)
        .then((response) => {
          status.value = 'success';
          window.location.href = fullUrl;
        })
        .catch((e) => {
          if (e.response.status === 401) {
            status.value = 'success';
            window.location.href = fullUrl;
          } else {
            status.value = 'error';
          }
        });
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

const deleteWarning = computed(() => {
  const name = selected.value?.name;
  return vue.$t('Are you sure you want to delete <strong>{{name}}</strong>? The action is irreversible.', { name });
});

const deleteDevLink = (devlink) => {
  selected.value = { ...devlink };
  deleteModal.value.show();
};

const destroyDevLink = async () => {
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

const showCreateModal = () => {
  status.value = '';
  createDevLinkModal.value.show();
};

const handleNewUrlUpdate = (newValue) => {
  newUrl.value = newValue;
};

const urlIsValid = computed(() => {
  return /^(https?:\/\/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})(:\d{1,5})?$/.test(newUrl.value);
});

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
          @click="showCreateModal"
          class="new-button"
        >
          <i class="fas fa-plus-circle" style="padding-right: 8px;"></i>{{ $t('Add Instance') }}
        </b-button>
      </div>
    </div>

    <!-- <b-modal
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
    </b-modal> -->
    <CreateDevLinkModal
      ref="createDevLinkModal"
      :newName="newName"
      :newUrl="newUrl"
      :urlIsValid="urlIsValid"
      :status="status"
      @clear="clear"
      @create="create"
      @update:newUrl="handleNewUrlUpdate"
    />

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

    <DeleteModal ref="deleteModal" :title="deleteWarningTitle" :message="deleteWarning" @delete="destroyDevLink" />

    <div class="card linked-instances-card">
      <b-table
        hover
        @row-clicked="select"
        :items="devlinks"
        :fields="fields"
        class="clickable"
      >
        <template #cell(name)="data">
          {{ data.item.name }}
        </template>
        <template #cell(status)="data">
          <Status :devlink="data.item" />
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
      <div v-if="devlinks.length === 0" class="div-message d-flex flex-column justify-content-center align-items-center">
        <div class="div-message-title">{{ $t("No linked instances of ProcessMaker") }}</div>
        <div>{{ $t("Use the button Add Instance") }}</div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
tr:hover {
  cursor: pointer;
}
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

.linked-instances-card {
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
