   <template>
    <b-modal ref="bundleModal" :title="modalTitle" @ok="onOk" :ok-title="'Ok'" :cancel-title="'Cancel'">
      <b-form-group :label="'Name'">
        <b-form-input v-model="bundle.name"></b-form-input>
      </b-form-group>
      <b-form-group v-if="!canEdit(bundle)" :label="'Published'">
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
        return props.bundle.id ? 'Edit Bundle' : 'Create New Bundle';
      });
  
      const canEdit = (bundle) => {
        return bundle.dev_link === null;
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
        canEdit,
        onOk,
        show,
        hide,
      };
    },
  };
  </script>
  