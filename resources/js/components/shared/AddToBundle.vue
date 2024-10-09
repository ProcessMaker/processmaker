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
      error.value = e.response?.data?.message || e.message;
    })
  }
};

</script>

<template>
  <b-modal ref="modal" @ok="save" :title="vue.$t('Add to Bundle')">
    <b-form-group
      :invalid-feedback="error"
      :state="!error"
      >
      <BackendSelect
        url="devlink/local-bundles"
        value-field="id"
        text-field="name"
        v-model="selected"
        ></BackendSelect>
      </b-form-group>
  </b-modal>
</template>