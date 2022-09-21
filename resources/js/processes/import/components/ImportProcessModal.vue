<template>
  <div>
    <modal 
      id="importProccess" 
      :title="title" 
      @hidden="onClose"
      @update="onUpdate"
      @importNew="importNew"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
    >
      <template> 
        <h5>
            <i class="fas fa-exclamation-triangle text-warning"></i> 
            {{$t('Caution: Process Already Exists')}}
            <div><small class="helper text-muted">{{ $t('This environment contains a process with the same ID.') }}</small></div>
        </h5>
        <p v-if="!userHasEditPermissions">{{ $t('You do not have permissions to update the existing process in this environment') }}</p>
        <ul>
            <li>{{ $t('Import As New will create a new process in this environment.') }}</li>
            <li v-if="userHasEditPermissions">{{ $t('Update will overwrite any assets tied to the current process. This may cause unintended side effects.') }}</li>
        </ul>
      </template>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal } from "SharedComponents";

  export default {
    components: { Modal },
    mixins: [ FormErrorsMixin ],
    props: ['processName', 'userHasEditPermissions'],
    data: function() {
      return {
        showModal: false,
        disabled: true,
        customModalButtons: [
            {'content': 'Cancel', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
            {'content': 'Update', 'action': 'update', 'variant': 'secondary', 'disabled': false, 'hidden': true},
            {'content': 'Import as New', 'action': 'importNew', 'variant': 'primary', 'disabled': false, 'hidden': false},
        ],
      }
    },
    computed: {
      title() {
        return this.$t('Import Process: {{item}}', {item: this.processName});
      }
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
      importNew() {
        this.$emit('import-new');
      }
    }
  };
</script>
