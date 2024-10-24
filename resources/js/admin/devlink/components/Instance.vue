<script setup>
import { ref, onMounted, getCurrentInstance } from 'vue';
import debounce from 'lodash/debounce';
import { useRouter, useRoute } from 'vue-router/composables';
import InstanceTabs from './InstanceTabs.vue';
import InstallProgress from './InstallProgress.vue';

const vue = getCurrentInstance().proxy;
const router = useRouter();
const route = useRoute();

const bundles = ref([]);
const filter = ref("");
const warnings = ref([]);
const showInstallModal = ref(false);
const confirmUpdateVersion = ref(null);
const selectedOption = ref('update');
const bundleAttributes = {
  id: null,
  name: '',
  published: false,
};
const selected = ref(bundleAttributes);
const fields = [
  {
    key: 'name',
    label: vue.$t('Name')
  },
  {
    key: 'version',
    label: vue.$t('Version')
  },
  {
    key: 'created_at',
    label: vue.$t('Creation Date')
  },
  {
    key: 'updated_at',
    label: vue.$t('Last Modified')
  },
  {
    key: 'menu',
    label: ''
  },
];

onMounted(() => {
  load();
});

const showModal = () => {
  $("#warningsModal").modal("show");
};

const closeModal = () => {
  $("#warningsModal").modal("hide");
};

const updateVersionBundle = (bundle) => {
  selected.value = bundle;
  confirmUpdateVersion.value.show();
};

const load = () => {
  ProcessMaker.apiClient
    .get(`/devlink/${route.params.id}/remote-bundles?filter=${filter.value}`)
    .then((result) => {
      bundles.value = result.data.data;
    });
};

// Debounced function
const debouncedLoad = debounce(load, 300);

// Function called on change
const handleFilterChange = () => {
  debouncedLoad();
};

const install = (bundle) => {
  vue.$bvModal.msgBoxConfirm(vue.$t('Are you sure you want to install this bundle?'), {
    okTitle: vue.$t('Ok'),
    cancelTitle: vue.$t('Cancel')
  }).then((confirm) => {
    if (confirm) {
      showInstallModal.value = true;
      ProcessMaker.apiClient
        .post(`/devlink/${route.params.id}/remote-bundles/${bundle.id}/install`)
        .then((response) => {
          // Handle the response as needed
        });
    }
  });
};
const executeUpdate = (updateType) => {
  showInstallModal.value = true;
  ProcessMaker.apiClient
    .post(`/devlink/${route.params.id}/remote-bundles/${selected.value.id}/install`, {
      updateType,
    })
    .then((response) => {
      // Handle the response as needed
    });
};

</script>

<template>
  <div>
    <instance-tabs ><template #bundles>
    <div class="top-options row">
      <div class="col">
        <input v-model="filter" class="form-control search-input" @input="handleFilterChange">
      </div>
    </div>
    <div class="card instance-card">
      <b-table
        :items="bundles"
        :fields="fields"
        class="instance-table"
      >
        <template #cell(menu)="data">
          <div class="btn-menu-container">
            <button
              class="btn install-bundle-btn"
              @click.prevent="updateVersionBundle(data.item)"
            >
              <i class="fp-cloud-download-outline"></i>
            </button>
          </div>
        </template>
      </b-table>
    </div>
    <div class="modal fade" id="warningsModal" tabindex="-1" role="dialog" aria-labelledby="warningsModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-body">
            <h5>Warnings</h5>
            <ul>
              <li
                v-for="(warning, index) in warnings"
                :key="index"
              >
                {{ warning }}
              </li>
            </ul>
          </div>
          <div class="modal-footer">
            <button type="button" @click="closeModal()" class="btn btn-secondary" data-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
    <b-modal
      ref="confirmUpdateVersion"
      centered
      size="lg"
      content-class="modal-style"
      :title="$t('Update Bundle Version')"
      :ok-title="$t('Continue')"
      :cancel-title="$t('Cancel')"
      @ok="executeUpdate(selectedOption)"
    >
      <div>
        <p class="mb-4">Select how you want to update the bundle <strong>Testing Processes and Assets</strong></p>

        <b-form-group>
          <b-form-radio-group v-model="selectedOption" name="bundleUpdateOptions">
            <b-form-radio
              class="mb-4"
              value="update"
            >
              {{ $t('Quick Update') }}
              <p class="text-muted">{{ $t('The current bundle will be replaced completely for the new version immediately.') }}</p>
            </b-form-radio>

            <b-form-radio value="copy">
              {{ $t('Copy Changes') }}
              <p class="text-muted">{{ $t('Copy and update bundle.') }}</p>
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
      </div>
    </b-modal>
    <b-modal id="install-progress" size="lg" v-model="showInstallModal" :title="$t('Installation Progress')" hide-footer>
      <install-progress />
    </b-modal>
    </template></instance-tabs>
  </div>
</template>

<style lang="scss" scoped>
.top-options {
  padding-bottom: 16px;

  .search-input {
    background: url(/img/search-icon.svg) no-repeat left;
    background-position: 7px 8px;
    background-size: 15px;
    border-radius: 8px;
  }
}

@import "styles/components/table";

.instance-card {
  border-radius: 8px;
  min-height: calc(-355px + 100vh);
}
.install-bundle-btn {
  border-radius: 8px;
  border: 1px solid rgba(0, 0, 0, 0.125);
}
.btn-menu-container {
  display: flex;
  justify-content: center;
}
</style>
