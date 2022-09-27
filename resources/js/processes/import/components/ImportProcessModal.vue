<template>
  <div>
    <modal 
      id="importProccess" 
      :title="title" 
      @update="onUpdate"
      @importNew="importNew"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
      size="lg"
    >
      <template>
        <b-row align-v="start">
          <b-col class="col-1 p-0 pr-1 text-right">
            <i class="fas fa-exclamation-triangle text-warning"></i>
          </b-col>
          <b-col class="p-0 pl-1">
            <h5 class="mb-3 fw-semibold">
              {{$t('Caution: Process Already Exists')}}
              <div><small class="helper text-muted">{{ $t('This environment contains a process with the same ID.') }}</small></div>
            </h5>
            <p v-if="!userHasEditPermissions">{{ $t('You do not have permissions to update the existing process in this environment') }}</p>
            <ul class="pl-3 ml-1">
              <li class="mb-1"><span class="fw-semibold">{{ $t('Import As New') }}</span>{{ $t(' will create a new process in this environment.') }}</li>
              <li v-if="userHasEditPermissions" class="mb-1"><span class="fw-semibold">{{$t('Update') }}</span>{{ $t(' will overwrite any assets tied to the current process. This may cause unintended side effects.') }}</li>
            </ul>
          </b-col>
        </b-row>
        
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
      close() {
        this.$bvModal.hide('importProccess');
      },
      onUpdate() {
        this.$emit('update-process', false);
        this.close();
      },
      importNew() {
        this.$emit('import-new');
        this.close();
      }
    }
  };
</script>
