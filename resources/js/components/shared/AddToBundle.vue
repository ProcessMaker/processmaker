<script setup>
import { getCurrentInstance, onMounted, ref, defineProps } from 'vue';
import BackendSelect from './BackendSelect.vue';

const props = defineProps({
  assetType: {
    type: String,
    required: true
  }
});

const vue = getCurrentInstance().proxy;
const modal = ref(null);
const selected = ref(null);
const assetId = ref(null);
const error = ref(null);

vue.$root.$on('add-to-bundle', (data) => {
  selected.value = null;
  error.value = null;
  assetId.value = data.id;
  modal.value.show();
});

const save = (event) => {
  event.preventDefault();
  if (selected.value?.id) {
    const asset = {
      'type': props.assetType,
      'id': assetId.value
    };
    window.ProcessMaker.apiClient.post(`devlink/local-bundles/${selected.value.id}/add-assets`, asset).then(() => {
      modal.value.hide();
      window.ProcessMaker.alert(vue.$t('Asset added to bundle'), 'success');
    }).catch(e => {
      error.value = e.response?.data?.error?.message || e.message;
    })
  }
  if (Array.isArray(selected.value)) {
    let bundles = [];
    selected.value.forEach((item) => {
      bundles.push(item.id);
    });
    const asset = {
      'type': props.assetType,
      'id': assetId.value,
      'bundles': bundles
    };
    window.ProcessMaker.apiClient.post(`devlink/local-bundles/add-asset-to-bundles`, asset).then(() => {
      modal.value.hide();
      window.ProcessMaker.alert(vue.$t('Asset added to bundle'), 'success');
    }).catch(e => {
      error.value = e.response?.data?.error?.message || e.message;
    });
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
        <b>{{ vue.$t('Marketing screens') }}</b>
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