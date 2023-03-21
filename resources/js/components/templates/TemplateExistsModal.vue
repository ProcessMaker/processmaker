<template>
  <div>
    <modal
      id="templateExists"
      :title="title"
      @update="onUpdate"
      @saveNew="saveNew"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
      size="lg"
    >
      <template>
        <b-row align-v="start" class="pt-3">
          <b-col class="overflow-modal">
            <b-row align-v="start" class="pl-0">
              <b-col class="col-1 p-0 pr-1 text-right">
                <i class="fas fa-exclamation-triangle text-warning"></i>
              </b-col>
              <b-col class="p-0 pl-1">
                <h5 class="mb-3 fw-semibold">
                  {{ $t('Caution: Template Already Exists') }}
                  <div><small class="helper text-muted">{{ $t('This environment contains a template with the same name.') }}</small></div>
                </h5>
              </b-col>
            </b-row>
            <p>{{ $t('You do not have permissions to update the existing template in this environment.') }}</p>
          </b-col>
        </b-row>
        <b-row align-v="start">
          <b-col>
            <ul class="descriptions pl-3 ml-1">
              <li class="mb-1"><span class="fw-semibold">{{ $t('Save As New') }}</span>{{ $t(' will create a new template in this environment.') }}</li>
              <li class="mb-1"><span class="fw-semibold">{{$t('Update') }}</span>{{ $t(' will overwrite any templates tied to the current process. This may cause unintended side effects.') }}</li>
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
    props: ['existingAssets', 'assetData','userHasEditPermissions'],
    data: function() {
      return {
        showModal: false,
        disabled: true,
        customModalButtons: [
            {'content': 'Cancel', 'action': 'hide()', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
            {'content': 'Update', 'action': 'update', 'variant': 'secondary', 'disabled': false, 'hidden': true},
            {'content': 'Save as New', 'action': 'saveNew', 'variant': 'primary', 'disabled': false, 'hidden': false},
        ],
      }
    },
    computed: {
      title() {
        return this.$t('Create Template from: {{item}}', {item: 'SOMETHING'});
      }
    },
    // watch: {
      
    // },
    beforeMount() {
        if (this.userHasEditPermissions) {
            this.customModalButtons[1].hidden = false;
        } else {
            this.customModalButtons[1].hidden = true;
        }
    },
    methods: {
      show() {
        this.$bvModal.show('templateExists');
      },
      close() {
        this.$bvModal.hide('templateExists');
      },
      onUpdate() {
        this.$emit('update-template');
        this.close();
      },
      saveNew() {
        this.$emit('save-new');
        this.close();
      },
      warningTitle(assetType) {
        return this.$t('Caution: {{type}} Already Exists', {type: assetType});
      },
      helperText(asset) {
        let text = this.$t('This environment contains a {{ item }} with the same name', {
          item: asset.type.toLowerCase()
        });

        text += ': ' + asset.existingName + '.';

        if (asset.matchedBy !== 'uuid') {
          text += ' ' + this.$t('We found it by its {{ matchedBy }}.', {
            matchedBy: asset.matchedBy
          });
        }

        if (asset.existingName !== asset.importingName) {
          text += ' ' + this.$t('Its name is {{ name }} in the file you are importing.', {
            name: asset.importingName
          });
        }

        return text;
      },
    }
  };
</script>

<style scoped>
  .descriptions {
    list-style: inherit;
  }
  .overflow-modal {
    max-height: 30vh;
    overflow-y: auto;
  }
</style>
