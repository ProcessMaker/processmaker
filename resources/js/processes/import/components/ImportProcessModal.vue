<template>
  <div>
    <modal 
      id="importProccess" 
      :title="$t('Import Process')" 
      :ok-title="$t('Import As New')"
      :ok-disabled="disabled" 
      @ok.prevent="importFileAsNew" 
      @hidden="onClose"
    >
      <template> 
        <h5>Caution: Process Already Exists</h5>
      </template>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal } from "SharedComponents";

  export default {
    components: { Modal },
    mixins: [ FormErrorsMixin ],
    props: ['userPermissions'],
    data: function() {
      return {
        showModal: false,
        disabled: true,
      }
    },
    computed: {
      
    },
    watch: {
      
    },
    methods: {
      show() {
        this.$bvModal.show('importProccess');
      },
      onClose () {
       console.log('MODAL CLOSED');
      },
    }
  };
</script>
