<template>
  <b-modal
    ref="bundleModal"
    centered
    :title="modalTitle"
    @ok="onOk"
    :ok-title="modalOkButton"
    :cancel-title="'Cancel'"
  >
    <b-form-group :label="'Name'">
      <b-form-input v-model="bundle.name"></b-form-input>
    </b-form-group>
    <b-form-group :label="'Description'">
      <b-form-textarea
        v-model="bundle.description"
        autocomplete="off"
        rows="3"
        data-cy="devlink_description"
      />
    </b-form-group>
    <b-form-group v-if="canEdit(bundle)" :label="'Published'">
      <b-form-checkbox v-model="bundle.published"></b-form-checkbox>
    </b-form-group>
  </b-modal>
</template>
  
  <script>
  import { ref, computed, defineProps, defineEmits } from 'vue';
  
  export default {
    props: {
      bundle: Object,
    },
    emits: ['update'],
    setup(props, { emit }) {
      const bundleModal = ref(null);
      
      const modalTitle = computed(() => {
        return props.bundle.id ? 'Edit Bundle' : 'New Bundle';
      });

      const modalOkButton = computed(() => {
        return props.bundle.id ? 'Edit' : 'Create';
      });
  
      const canEdit = (bundle) => {
        return bundle.dev_link_id === null;
      };
  
      const onOk = () => {
        emit('update', props.bundle);
      };
  
      const show = () => {
        if (bundleModal.value) {
          bundleModal.value.show();
        }
      };
  
      const hide = () => {
        if (bundleModal.value) {
          bundleModal.value.hide();
        }
      };
  
      return {
        bundleModal,
        modalTitle,
        modalOkButton,
        canEdit,
        onOk,
        show,
        hide,
      };
    },
  };
  </script>
<style lang="scss" scoped>
  @import "styles/components/modal";
</style>
