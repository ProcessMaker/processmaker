<template>
  <div>
    <modal 
      id="importProccess" 
      :title="$t('Import Process')" 
      :ok-title="$t('Import As New')"
      :ok-disabled="disabled" 
      @ok.prevent="importFileAsNew" 
      @hidden="onClose"
      @update="onUpdate"
      @import="importAsNew"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
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
    props: ['userHasEditPermissions'],
    data: function() {
      return {
        showModal: false,
        disabled: true,
        customModalButtons: [
            {'content': 'Cancel', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
            {'content': 'Update', 'action': 'update', 'variant': 'secondary', 'disabled': false, 'hidden': true},
            {'content': 'Import as New', 'action': 'import', 'variant': 'primary', 'disabled': false, 'hidden': false},
        ],
      }
    },
    computed: {
      
    },
    watch: {
      
    },
    beforeMount() {
        if (this.userHasEditPermissions) {
            this.customModalButtons[1].hidden = false
        } else {
            this.customModalButtons[1].hidden = true;
        }
    },
    methods: {
      show() {
        this.$bvModal.show('importProccess');
      },
      onClose () {
       console.log('MODAL CLOSED');
      },
      onUpdate() {
        console.log('UPDATE PROCESS AS NEW');
      },
      importAsNew() {
        console.log('IMPORT NEW PROCESS');
      }
    }
  };
</script>
