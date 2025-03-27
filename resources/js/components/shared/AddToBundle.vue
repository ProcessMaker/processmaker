<script setup>
import { getCurrentInstance, onMounted, ref, defineProps, onBeforeUnmount } from 'vue';
import BackendSelect from './BackendSelect.vue';

const props = defineProps({
  assetType: {
    type: String,
    required: true
  },
  setting: {
    type: Boolean,
    default: false
  },
  settingType: {
    type: String,
    default: null
  }
});

const vue = getCurrentInstance().proxy;
const modal = ref(null);
const selected = ref(null);
const assetId = ref(null);
const error = ref(null);
const assetName = ref(null);
onMounted(() => {
  vue.$root.$on('add-to-bundle', (data) => {
    selected.value = null;
    error.value = null;
    assetId.value = data.id;
    assetName.value = data.title || data.name;
    vue.$nextTick(() => {
      modal.value.show();
    });
  });
});

onBeforeUnmount(() => {
  vue.$root.$off('add-to-bundle');
});

const save = (event) => {
  event.preventDefault();
  if (selected.value?.id) {
    if (props.setting) {
      window.ProcessMaker.apiClient.post(`devlink/local-bundles/${selected.value.id}/add-settings`, {
        setting: props.assetType,
        config: assetId.value,
        type: props.settingType || null
      })
        .then(() => {
          window.ProcessMaker.alert(vue.$t('Added successfully to bundle'), 'success');
        });
    } else {
      const asset = {
        'type': props.assetType,
        'id': assetId.value
      };
      window.ProcessMaker.apiClient.post(`devlink/local-bundles/${selected.value.id}/add-assets`, asset).then(() => {
        modal.value.hide();
        window.ProcessMaker.alert(vue.$t('Added successfully to bundle'), 'success');
      }).catch(e => {
        error.value = e.response?.data?.error?.message || e.message;
      })
    }
  }
  if (Array.isArray(selected.value)) {
    let bundles = [];
    selected.value.forEach((item) => {
      bundles.push(item.id);
    });
    if (props.setting) {
      const setting = {
        'setting': props.assetType,
        'config': assetId.value,
        'bundles': bundles,
        'type': props.settingType || null
      };
      window.ProcessMaker.apiClient.post(`devlink/local-bundles/add-setting-to-bundles`, setting).then(() => {
        modal.value.hide();
        window.ProcessMaker.alert(vue.$t('Added successfully to bundle'), 'success');
      }).catch(e => {
        error.value = e.response?.data?.error?.message || e.message;
      });
    } else {
      const asset = {
        'type': props.assetType,
        'id': assetId.value,
        'bundles': bundles
      };
      window.ProcessMaker.apiClient.post(`devlink/local-bundles/add-asset-to-bundles`, asset).then(() => {
        modal.value.hide();
        window.ProcessMaker.alert(vue.$t('Added successfully to bundle'), 'success');
      }).catch(e => {
        error.value = e.response?.data?.error?.message || e.message;
      });
    }
  }
};

</script>

<template>
  <b-modal ref="modal" @ok="save"
           :ok-title="vue.$t('Save')" 
           cancel-variant="light" 
           modal-class="add-to-bundle-modal">
    <template #modal-title>
      <b>{{ vue.$t('Add asset to bundle') }}</b>
    </template>
    <b-form-group
      :invalid-feedback="error"
      :state="!error"
      >
      <p>
        {{ vue.$t('The asset') }}
        <b>{{ assetName }}</b>
        {{ vue.$t('can be added to one or various bundles.') }}
      </p>
      <b>{{ vue.$t('Bundles') }}</b>
      <BackendSelect
        url="devlink/local-bundles?editable=true"
        value-field="id"
        text-field="name"
        v-model="selected"
        ></BackendSelect>
      </b-form-group>
  </b-modal>
</template>

<style>
.add-to-bundle-modal .modal-content {
  width: 600px;
  padding: 15px;
}
.add-to-bundle-modal .modal-header {
  border-bottom: none;
}
.add-to-bundle-modal .modal-footer {
  border-top: none;
}
</style>
