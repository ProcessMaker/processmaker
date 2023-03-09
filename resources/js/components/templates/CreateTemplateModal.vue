<template>
    <div>
      <modal
        id="createTemplate"
        :title="title" 
        @update="onUpdate"
        @saveTemplate="saveTemplate"
        @close="close"
        @updateTemplate="updateTemplate"
        @saveNewTemplate="saveNewTemplate"
        :setCustomButtons="true"
        :customButtons="customModalButtons"
        size="md"
      >
        <template>
          <b-row align-v="start">
            <b-col>
                <p>{{ $t(`This will create a re-usuable template based on the ${this.assetName} ${this.assetType}`) }}</p>
                <required></required>
                <p class="mb-3" v-if="showWarning"><i class="fas fa-exclamation-triangle text-warning"></i> {{ assetExistsError }}</p>
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

                <b-form-group>
                    <b-form-radio v-model="saveMode" 
                        name="save-mode-options" 
                        value="copy">{{ $t('Save all assets') }}
                    </b-form-radio>

                    <b-form-radio v-model="saveMode" 
                        name="save-mode-options" 
                        value="discard">{{ $t(`Save ${assetType} modal only`) }}
                    </b-form-radio>
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
          saveMode: 'copy',
          customModalButtons: [
              {'content': 'Cancel', 'action': 'close', 'variant': 'outline-secondary', 'disabled': false, 'hidden': false},
              {'content': 'Publish', 'action': 'saveTemplate', 'variant': 'primary', 'disabled': false, 'hidden': false},
              {'content': 'Update', 'action': 'updateTemplate', 'variant': 'secondary', 'disabled': false, 'hidden': true},
              {'content': 'Save as New', 'action': 'saveNewTemplate', 'variant': 'primary', 'disabled': false, 'hidden': true},
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
          this.saveMode = 'copy';
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
            formData.append("mode", this.saveMode);
            formData.append("template_category_id", null);
            ProcessMaker.apiClient.post("template/" + this.assetType + '/' + this.assetId, formData)
            .then(response => {
              ProcessMaker.alert( this.$t("Template successfully created"),"success");
              this.close();
            }).catch(error => {
                const name = error.response.data.name[0];
                if (name) {
                    this.showWarning = true;
                    this.showHiddenButtons();
                    this.errors = error.response.data;
                } else {
                    ProcessMaker.alert(error,"danger");
                }
            }); 
            //this.close();
        },
        updateTemplate() {    
          console.log("UPDATE TEMPLATE");
          // let formData = new FormData();
          // formData.append("asset_id", this.assetId);
          // formData.append("name", this.name);
          // formData.append("description", this.description);
          // formData.append("user_id", this.currentUserId);
          // formData.append("mode", this.saveMode);
          // formData.append("template_category_id", null);
          // ProcessMaker.apiClient.post("template/" + this.assetType + '/' + this.assetId, formData)
          // .then(response => {
          //   ProcessMaker.alert( this.$t("Template successfully created"),"success");
          //   this.close();
          // }).catch(error => {
          //     const message = error.response.data.message;
          //     if (message === this.assetExistsError) {
          //         this.showWarning = true;
          //         this.showHiddenButtons();
          //         // this.errors = {'name':  };
          //         // this.$emit('templateExists', formData);
          //         // this.close();
          //     } else {
          //         ProcessMaker.alert(message,"danger");
          //     }
          // }); 
          // //this.close();
        },
        saveNewTemplate() {
          console.log('SAVE NEW TEMPLATE');
        },
        showHiddenButtons() {
          this.customModalButtons[1].hidden = true;
          this.customModalButtons[2].hidden = false;
          this.customModalButtons[3].hidden = false;
        }
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
  