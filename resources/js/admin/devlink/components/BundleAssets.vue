<script setup>
import { ref, onMounted, getCurrentInstance, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router/composables';
import BundleModal, { show as showBundleModal, hide as hideBundleModal } from './BundleModal.vue';
import Header from './Header.vue';

const vue = getCurrentInstance().proxy;
const route = useRoute();
const router = useRouter();
const bundleId = route.params.id;
const bundle = ref({});
const loading = ref(true);
const fields = [
  { key: 'name', label: vue.$t('Name') },
  { key: 'type', label: vue.$t('Type') },
  { key: 'updated_at', label: vue.$t('Last Modified') },
  { key: 'created_at', label: vue.$t('Created') },
  { key: 'menu', label: '' },
];
const bundleModal = ref(null);
const bundleAttributes = {
  id: null,
  name: '',
  published: false,
};

const selectedBundle = ref(bundleAttributes);

const openBundleModalForEdit = (bundle) => {
  selectedBundle.value = { ...bundle };
  if (bundleModal.value) {
    bundleModal.value.show();
  }
};

const update = () => {
  if (selectedBundle.value.id === null) {
    return;
  }
  ProcessMaker.apiClient
    .put(`/devlink/local-bundles/${selectedBundle.value.id}`, selectedBundle.value)
    .then((result) => {
      loadAssets();
    });
};

const updateBundle = (bundle) => {
  selectedBundle.value = bundle;
  update();
};

const computedFields = computed(() => {
  if (bundle.value.dev_link_id === null) {
    return fields;
  }
  // remove the menu field
  return fields.filter(field => field.key !== 'menu');
});

const loadAssets = async () => {
  loading.value = true;
  const response = await window.ProcessMaker.apiClient.get(`/api/1.0/devlink/local-bundles/${bundleId}`);
  bundle.value = response.data;
  loading.value = false;
}

onMounted(async () => {
  await loadAssets();
});

const remove = async (asset) => {
  const confirm = await vue.$bvModal.msgBoxConfirm(vue.$t('Are you sure you want to remote this asset from the bundle?'), {
    okTitle: vue.$t('Ok'),
    cancelTitle: vue.$t('Cancel'),
  });
  if (!confirm) {
    return;
  }
  await window.ProcessMaker.apiClient.delete(`/api/1.0/devlink/local-bundles/assets/${asset.id}`);
  await loadAssets();
};
</script>

<template>
  <div v-if="loading">
    {{ $t("Loading...") }}
  </div>
  <div v-else>
    <div class="row">
      <div class="col">
        <Header back="local-bundles">
          {{ bundle.name }} {{ $t("Assets") }}
        </Header>
      </div>
      <div class="col">
        <div class="header">
          <div class="header-right">
            <b-button
              v-if="bundle.dev_link_id !== null"
              class="btn text-secondary icon-button"
              variant="light"
              :aria-label="$t('Edit Bundle')"
              v-b-tooltip.hover
              :title="$t('Edit Bundle')"
              @click.prevent="openBundleModalForEdit(bundle)"
            >
              <i class="fas fa-edit" />
            </b-button>

            <b-button
              variant="primary"
              class="install-btn">
              <i class="fas fa-plus-circle" style="padding-right: 8px;"></i>{{ $t('Reinstall this Bundle') }}
            </b-button>
          </div>
        </div>
  </div>
</div>   
    <div v-if="bundle.assets.length" class="card instance-card">
      <b-table
        :items="bundle.assets"
        :fields="computedFields"
      >
        <template #cell(name)="data">
          <a :href="data.item.url" target="_blank">{{ data.item.name }}</a>
        </template>

        <template #cell(menu)="data">
          <button
            class="btn install-asset-btn"
            @click.prevent="remove(data.item)"
          >
            <i class="fa fa-trash"></i>
          </button>
        </template>
      </b-table>
    </div>
    <div v-else>
      No assets found in this bundle.
    </div>
    <BundleModal ref="bundleModal" :bundle="selectedBundle" @update="updateBundle"/>
  </div>
</template>

<style scoped>
.header {
  display: flex;
  justify-content: end;
  align-items: center;
  padding: 10px 0px;
  background-color: white;
  margin-right: 10px;
}

.header-left h2 {
  margin: 0;
  font-size: 1.5rem;
}

.header-right {
  display: flex;
  gap: 10px;
}

.edit-btn {
  background: transparent;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
}

.install-btn {
  text-transform: none;
  font-weight: 500;
  font-size: 14px;
}

.install-btn i {
  margin-right: 8px;
  font-size: 16px;
}

.install-btn:hover {
  background-color: #0056b3;
}

.assets-btn {
  background-color: white;
  color: #007bff;
  border: 1px solid #007bff;
  padding: 8px 16px;
  border-radius: 5px;
  cursor: pointer;
}

.assets-btn:hover {
  background-color: #f1f1f1;
}
</style>
<style lang="scss" scoped>
@import "styles/components/table";
h3 {
  font-size: 1.4em;
}
.instance-card {
  border-radius: 8px;
  min-height: calc(-355px + 100vh);
}
</style>
