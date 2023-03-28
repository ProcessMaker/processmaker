<template>
  <div>
    <modal
      id="createTemplate"
      :title="title" 
      @update="onUpdate"
      @saveTemplate="saveTemplate"
      @close="close"
      @updateTemplate="updateTemplate"
      @saveNewTemplate="saveTemplate"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
      size="md"
    >
      <template>
        <b-row align-v="start">
          <b-col>
            <required></required>
            <p>{{ $t(`This will create a re-usable template based on the ${this.assetName} ${this.assetType}`) }}</p>
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

              <category-select
                v-model="process_category_id"
                :label="$t('Category')"
                api-get="process_categories"
                api-list="process_categories"
                name="category"
                :errors="addError.process_category_id"
              />

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
import CategorySelect from "../../processes/categories/components/CategorySelect.vue";

export default {
  components: { Modal, Required, CategorySelect },
  mixins: [FormErrorsMixin],
  props: ["assetName", "assetType", "assetId", "currentUserId"],
  data() {
    return {
      errors: {},
      name: "",
      description: "",
      process_category_id: "",
      addError: {},
      showModal: false,
      disabled: true,
      showWarning: false,
      saveMode: "copy",
      existingAssetId: null,
      existingAssetName: "",
      customModalButtons: [
        {"content": "Cancel", "action": "close", "variant": "outline-secondary", "disabled": false, "hidden": false},
        {"content": "Publish", "action": "saveTemplate", "variant": "primary", "disabled": true, "hidden": false},
        {"content": "Update", "action": "updateTemplate", "variant": "secondary", "disabled": false, "hidden": true},
        {"content": "Save as New", "action": "saveNewTemplate", "variant": "primary", "disabled": true, "hidden": true},
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
    watch: {
      description() {
        this.validateDescription();
      },
      name(newValue, oldValue) {
        this.validateName(newValue, oldValue);
      }
    },  
    methods: {
      show() {
        this.customModalButtons[1].hidden === true ? this.toggleButtons() : false;
        this.$bvModal.show('createTemplate');
      },
      close() {
        this.$bvModal.hide('createTemplate');
        this.clear();
        this.errors = {};
      },
      clear() {
        this.name = "";
        this.description = "";
        this.process_category_id = "";
        this.showWarning = false;
        this.saveMode = "copy";
        // this.name = '';
        // this.description = '';
        // this.showWarning = false;
        // this.saveMode = 'copy';
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
      formData.append("process_category_id", this.process_category_id);
      ProcessMaker.apiClient.post("template/" + this.assetType + "/" + this.assetId, formData)
        .then(response => {
          ProcessMaker.alert(this.$t("Template successfully created"), "success");
          this.close();
        }).catch(error => {
          const name = error.response.data.name[0];
          if (name) {
            this.showWarning = true;
            this.toggleButtons();
            this.errors = error.response.data;
            this.existingAssetId = error.response.data.id;
            this.existingAssetName = error.response.data.templateName;
          } else {
            ProcessMaker.alert(error, "danger");
          }
        });
      //this.close();
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
          //     const name = error.response.data.name[0];
          //     if (name) {
          //         this.showWarning = true;
          //         this.toggleButtons();
          //         this.errors = error.response.data;
          //         this.existingAssetId = error.response.data.id;
          //         this.existingAssetName = error.response.data.templateName;
          //     } else {
          //         ProcessMaker.alert(error,"danger");
          //     }
          // }); 
          // //this.close();
      },  
      updateTemplate() {   
        let putData = {
        name: this.name,
        description: this.description,
        user_id: this.currentUserId,
        mode: this.saveMode,
        process_id: this.assetId,
        process_category_id: this.process_category_id,
      };
      ProcessMaker.apiClient.put("template/" + this.assetType + "/" + this.existingAssetId, putData)
        .then(response => {
          ProcessMaker.alert( this.$t("Template successfully updated"),"success");
          this.close();
        }).catch(error => {
          const message = error.response.data.message;
          if (message === this.assetExistsError) {
            this.showWarning = true;
            this.toggleButtons();
            this.errors = error.response.data;
            this.existingAssetId = error.response.data.id;
            this.existingAssetName = error.response.data.assetName;
          } else {
            ProcessMaker.alert(message,"danger");
          }
        });
        // let putData = {
        //   name: this.name,
        //   description: this.description,
        //   user_id: this.currentUserId,
        //   mode: this.saveMode,
        //   process_id: this.assetId,
        //   template_category_id: null,
        // }; 
        // ProcessMaker.apiClient.put("template/" + this.assetType + '/' + this.existingAssetId, putData)
        // .then(response => {
        //   ProcessMaker.alert( this.$t("Template successfully updated"),"success");
        //   this.close();
        // }).catch(error => {
        //     const message = error.response.data.message;
        //     if (message === this.assetExistsError) {
        //         this.showWarning = true;
        //         this.toggleButtons();
        //         this.errors = error.response.data;
        //         this.existingAssetId = error.response.data.id;
        //         this.existingAssetName = error.response.data.assetName;
        //     } else {
        //         ProcessMaker.alert(message,"danger");
        //     }
        // }); 
      },
      toggleButtons() {
        this.customModalButtons[1].hidden = !this.customModalButtons[1].hidden;
        this.customModalButtons[2].hidden = !this.customModalButtons[2].hidden;
        this.customModalButtons[3].hidden = !this.customModalButtons[3].hidden;
      },
      validateDescription() {
        if (!_.isEmpty(this.description) && !_.isEmpty(this.name)) {
        this.customModalButtons[1].disabled = false;
        if (this.showWarning) {
          if (this.name !== this.existingAssetName) {
            this.customModalButtons[2].disabled = true;
            this.customModalButtons[3].disabled = false;  
          } else {
            this.customModalButtons[2].disabled = false;
            this.customModalButtons[3].disabled = true;
          }
        }
      } else {
        this.customModalButtons[1].disabled = true;
        if (this.showWarning) {
          this.customModalButtons[2].disabled = true;
          this.customModalButtons[3].disabled = true;
        }
      }

        // if (!_.isEmpty(this.description) && !_.isEmpty(this.name)) {
        //   this.customModalButtons[1].disabled = false;
        //   if (this.showWarning) {
        //     if (this.name !== this.existingAssetName) {
        //       this.customModalButtons[2].disabled = true;
        //       this.customModalButtons[3].disabled = false;  
        //     } else {
        //       this.customModalButtons[2].disabled = false;
        //       this.customModalButtons[3].disabled = true;
        //     }
            
        //   }
        // } else {
        //   this.customModalButtons[1].disabled = true;
        //   if (this.showWarning) {
        //     this.customModalButtons[2].disabled = true;
        //     this.customModalButtons[3].disabled = true;
        //   }
        // }
      },
      validateName(newName, oldName) {
        if (!_.isEmpty(this.name) && !_.isEmpty(this.description)) {
          this.customModalButtons[1].disabled = false;         
          if (this.showWarning) {
            if (newName !== oldName && newName !== this.existingAssetName) {
              this.customModalButtons[2].disabled = true;
              this.customModalButtons[3].disabled = false;
            } else {
              this.customModalButtons[2].disabled = false;
              this.customModalButtons[3].disabled = true;
            }
          }
        } else {
          this.customModalButtons[1].disabled = true;
  
          if (this.showWarning) {
            this.customModalButtons[2].disabled = true;
            this.customModalButtons[3].disabled = true;
          }
        }
        if (this.name.length > 255) {
          this.errors.name = ['Name must be less than 255 characters.'];
          this.customModalButtons[1].disabled = true;
          this.customModalButtons[3].disabled = true;
        }else {
          this.errors.name = null;
        }
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
