<template>
    <div>
      <modal
        id="createTemplate"
        :title="title" 
        @update="onUpdate"
        @saveTemplate="saveTemplate"
        @close="close"
        :setCustomButtons="true"
        :customButtons="customModalButtons"
        size="md"
      >
        <template>
          <b-row align-v="start">
            <b-col>
                <p>{{ $t(`This will create a re-usuable template based on the ${this.assetName} ${this.assetType}`) }}</p>
                <required></required>
                <p class="mb-3" v-if="showWarning"><i class="fas fa-exclamation-triangle text-warning"></i> {{ $t('There is already a template with this name') }}</p>
                <b-form-group
                    required
                    :label="$t('Template Name')"
                    :description="formDescription('The template name must be unique.', 'name', errors)"
                    :invalid-feedback="errorMessage('name', errors)"
                    :state="errorState('name', errors)"
                >
                    <b-form-input
                    required
                    autofocus
                    v-model="name"
                    autocomplete="off"
                    :state="errorState('name', errors)"
                    name="name"
                    ></b-form-input>
                </b-form-group>

                <b-form-group
                    required
                    :label="$t('Description')"
                    :invalid-feedback="errorMessage('description', errors)"
                    :state="errorState('description', errors)"
                >
                    <b-form-textarea
                    required
                    v-model="description"
                    autocomplete="off"
                    rows="3"
                    :state="errorState('description', errors)"
                    name="description"
                    ></b-form-textarea>
                </b-form-group>
            </b-col>
          </b-row>
        </template>
      </modal>
    </div>
  </template>
  
  <script>
    import { FormErrorsMixin, Modal, Required } from "SharedComponents";
  
    export default {
      components: { Modal, Required },
      mixins: [ FormErrorsMixin ],
      props: ['existingAssets', 'assetName','userHasEditPermissions', 'assetType', 'assetId', 'currentUserId'],
      data: function() {
        return {
          errors: {},
          name: '',
          description: '',
          showModal: false,
          disabled: true,
          showWarning: false,
          mode: 'copy',
          customModalButtons: [
              {'content': 'Cancel', 'action': 'close', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
              {'content': 'Publish', 'action': 'saveTemplate', 'variant': 'secondary', 'disabled': false, 'hidden': false},
          ],
        }
      },
      computed: {
        title() {
          return this.$t('Publish Template');
        },
        assetExistsError() {
            const capFirst = this.assetType[0].toUpperCase();
            const reset =  this.assetType.slice(1);
            const asset = capFirst + reset;
            return asset + ' Template with the same name already exists';
        }
      },
      methods: {
        show() {
          this.$bvModal.show('createTemplate');
        },
        close() {
          this.$bvModal.hide('createTemplate');
          this.clear();
          this.errors = {};
        },
        clear() {
          this.name = '';
          this.description = '';
          this.showWarning = false;
        },
        onUpdate() {
          this.$emit('update-template');
          this.close();
        },
        saveTemplate() {    
            let formData = new FormData();
            formData.append("asset_id", this.assetId);
            formData.append("name", this.name);
            formData.append("description", this.description);
            formData.append("user_id", this.currentUserId);
            formData.append("mode", this.mode);
            formData.append("template_category_id", null);
            ProcessMaker.apiClient.post("template/" + this.assetType + '/' + this.assetId, formData)
            .then(response => {
              ProcessMaker.alert( this.$t("Template successfully created"),"success");
              this.close();
            }).catch(error => {
                const message = error.response.data.message;
                if (message === this.assetExistsError) {
                    this.showWarning = true;
                   // this.errors = {'name':  };
                   // this.$emit('templateExists', formData);
                   // this.close();
                } else {
                    ProcessMaker.alert(message,"danger");
                }
            }); 
            //this.close();
        },
      },
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
  