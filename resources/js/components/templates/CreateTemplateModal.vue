<template>
  <div>
    <modal
      id="createTemplate"
      :title="title"
      :subtitle="descriptionText"
      @update="onUpdate"
      @saveTemplate="saveTemplate"
      @close="close"
      @updateTemplate="updateTemplate"
      @saveNewTemplate="saveTemplate"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
      :size="modalSize ? modalSize : 'md'"
      :headerClass="headerClass"
      :footerClass="footerClass"
    >
      <required></required>
      <p class="mb-3" v-if="showWarning"><i class="fas fa-exclamation-triangle text-warning"></i> {{ assetExistsError }}</p>
      
      <create-screen-template-form 
        v-if="assetType === 'screen'" 
        :screenType="screenType"
        :permission="permission"
        @input="updateTemplateData"
      />
      
      <create-template-form v-else
        @input="updateTemplateData"
      />
    </modal>
  </div>
</template>

<script>
import Required from "../shared/Required.vue";
import Modal from "../shared/Modal.vue";
import FormErrorsMixin from "../shared/FormErrorsMixin";
import CategorySelect from "../../processes/categories/components/CategorySelect.vue";
import CreateScreenTemplateForm from "../../processes/screen-templates/components/CreateScreenTemplateForm.vue";
import CreateTemplateForm from "./CreateTemplateForm.vue";

export default {
  components: { Modal, Required, CategorySelect, CreateScreenTemplateForm, CreateTemplateForm },
  mixins: [FormErrorsMixin],
  props: ["assetName", "assetType", "assetId", "currentUserId", "modalSize", "screenType", "permission", "headerClass", "footerClass"],
  data() {
    return {
      // errors: {},
      // name: "",
      // description: "",
      // process_category_id: "",
      // version: null,
      // addError: {},
      showModal: false,
      disabled: true,
      showWarning: false,
      // saveAssetsMode: "saveAllAssets",
      existingAssetId: null,
      existingAssetName: "",
      templateData: {},
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
        return this.$t('Create Template');
      },
      assetExistsError() {
          const capFirst = this.assetType[0].toUpperCase();
          const reset =  this.assetType.slice(1);
          const asset = capFirst + reset;
          return asset + ' Template with the same name already exists';
      },
      descriptionText() {
        return this.$t('This will create a re-usable template based on the {{assetName}} {{assetType}}', {assetName: this.assetName, assetType: this.assetType});
      }
    },
    watch: {
      // templateData:{
      //   deep:true,
      //   handler() {
          
      //   }
      // }
      // description() {
      //   this.validateDescription();
      // },
      // name(newValue, oldValue) {
      //   this.validateName(newValue, oldValue);
      // }
    },  
    methods: {
      show() {
        this.customModalButtons[1].hidden === true ? this.toggleButtons() : false;
        this.$bvModal.show('createTemplate');
      },
      close() {
        this.$bvModal.hide('createTemplate');
        // this.clear();
        // this.errors = {};
      },
      // clear() {
      //   this.name = "";
      //   this.description = "";
      //   this.process_category_id = "";
      //   this.showWarning = false;
      //   this.saveMode = "copy";
      //   this.saveAssetsMode = "saveAllAssets";
      // },
      onUpdate() {
        this.$emit('update-template');
        this.close();
      },
      saveTemplate() {    
        let formData = new FormData();
        formData.append("asset_id", this.assetId);

        // Iterate over the templateData properties and append them to formData
        for (let key in this.templateData) {
          if (this.templateData.hasOwnProperty(key)) {
            formData.append(key, this.templateData[key]);
          }
        } 

        
        // formData.append("name", this.name);
        // formData.append("description", this.description);
        // formData.append("version", this.version);
        // formData.append("user_id", this.currentUserId);
        // formData.append("saveAssetsMode", this.saveAssetsMode);
        // formData.append("process_category_id", this.process_category_id);
        this.customModalButtons[1].disabled = true;
        ProcessMaker.apiClient.post("template/" + this.assetType + "/" + this.assetId, formData)
        .then(response => {
          ProcessMaker.alert(this.$t("Template successfully created"), "success");
          this.close();
        }).catch(error => {
          this.errors = error.response.data;
          this.customModalButtons[1].disabled = false;
          if (this.errors.hasOwnProperty('errors')) {
            this.errors = this.errors.errors;
          } else if (_.includes(this.errors.name, 'The template name must be unique.')) {
            this.showWarning = true;
            this.toggleButtons();
            this.existingAssetId = error.response.data.id;
            this.existingAssetName = error.response.data.templateName;
          } else {
            const message = error.response.data.error;
            ProcessMaker.alert(this.$t(message), "danger");
          }
        });
      },  
      updateTemplate() {   
        let putData = {
          name: this.name,
          description: this.description,
          version: this.version,
          user_id: this.currentUserId,
          mode: this.saveAssetsMode,
          process_id: this.assetId,
          process_category_id: this.process_category_id,
        };
        ProcessMaker.apiClient.put("template/" + this.assetType + "/" + this.existingAssetId, putData)
        .then(response => {
          ProcessMaker.alert( this.$t("Template successfully updated"),"success");
          this.close();
        }).catch(error => {
          this.errors = error.response.data;
          if (this.errors.hasOwnProperty('errors')) {
            this.errors = this.errors.errors;
          } else if (_.includes(this.errors.name, 'The template name must be unique.')) {
            this.showWarning = true;
            this.toggleButtons();
            this.existingAssetId = error.response.data.id;
            this.existingAssetName = error.response.data.assetName;
          }
        });
      },
      toggleButtons() {
        this.customModalButtons[1].hidden = !this.customModalButtons[1].hidden;
        this.customModalButtons[2].hidden = !this.customModalButtons[2].hidden;
        this.customModalButtons[3].hidden = !this.customModalButtons[3].hidden;
      },
      validateFormData(errors) {
        console.log("validate", errors);
        // if (errors) {
        //   this.customModalButtons[1].disabled = true;
        //   this.customModalButtons[3].disabled = true;
        // }
        if (!_.isEmpty(this.templateData.name) && !_.isEmpty(this.templateData.description)) {
          this.customModalButtons[1].disabled = false;
          if (this.showWarning) {
            if (this.templateData.name !== this.existingAssetName) {
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
            this.customModalButtons[3].disabled = false;  
          } else {
            this.customModalButtons[2].disabled = false;
            this.customModalButtons[3].disabled = true;
          }
        }
        // if (this.templateData.name.length > 255) {
        //   this.errors.name = ['Name must be less than 255 characters.'];
        //   this.customModalButtons[1].disabled = true;
        //   this.customModalButtons[3].disabled = true;
        // } else {
        //   this.errors.name = null;
        // }
      },
      // validateDescription() {
      //   if (!_.isEmpty(this.description) && !_.isEmpty(this.name)) {
      //     this.customModalButtons[1].disabled = false;
      //     if (this.showWarning) {
      //       if (this.name !== this.existingAssetName) {
      //         this.customModalButtons[2].disabled = true;
      //         this.customModalButtons[3].disabled = false;  
      //       } else {
      //         this.customModalButtons[2].disabled = false;
      //         this.customModalButtons[3].disabled = true;
      //       }
      //     }
      //   } else {
      //     this.customModalButtons[1].disabled = true;
      //     if (this.showWarning) {
      //       this.customModalButtons[2].disabled = true;
      //       if (this.showWarning) {
      //         this.customModalButtons[2].disabled = true;
      //         this.customModalButtons[3].disabled = false;  
      //       } else {
      //         this.customModalButtons[2].disabled = false;
      //         this.customModalButtons[3].disabled = true;
      //       }
      //     }
      //   }
      // },
      // validateName(newName, oldName) {
      //   if (!_.isEmpty(this.name) && !_.isEmpty(this.description)) {
      //     this.customModalButtons[1].disabled = false;         
      //     if (this.showWarning) {
      //       if (newName !== oldName && newName !== this.existingAssetName) {
      //         this.customModalButtons[2].disabled = true;
      //         this.customModalButtons[3].disabled = false;
      //       } else {
      //         this.customModalButtons[2].disabled = false;
      //         this.customModalButtons[3].disabled = true;
      //       }
      //     }
      //   } else {
      //     this.customModalButtons[1].disabled = true;
  
      //     if (this.showWarning) {
      //       this.customModalButtons[2].disabled = true;
      //       this.customModalButtons[3].disabled = true;
      //     }
      //   }
      //   if (this.name.length > 255) {
      //     this.errors.name = ['Name must be less than 255 characters.'];
      //     this.customModalButtons[1].disabled = true;
      //     this.customModalButtons[3].disabled = true;
      //   }else {
      //     this.errors.name = null;
      //   }
      // },
      updateTemplateData(data, errors) {
        // this.name = data.name;
        // this.description = data.description;
        this.templateData = data;
        this.validateFormData(errors);
      }
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
