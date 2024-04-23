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
        :types="types"
        :responseErrors="errors"
        :screenType="screenType"
        :permission="permission"
        @input="updateTemplateData"
      />
      
      <create-template-form v-else
        @input="updateTemplateData"
        :responseErrors="errors"
        :assetType="assetType"
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
  props: ["assetName", "assetType", "assetId", "currentUserId", "modalSize", "screenType", "permission", "headerClass", "footerClass", "types"],
  data() {
    return {
      errors: {},
      showModal: false,
      disabled: true,
      showWarning: false,
      existingAssetId: null,
      existingAssetName: "",
      existingAssetOwnerId: null,
      templateData: {},
      customModalButtons: [
        {"content": "Cancel", "action": "close", "variant": "outline-secondary", "disabled": false, "hidden": false},
        {"content": "Save", "action": "saveTemplate", "variant": "primary", "disabled": true, "hidden": false},
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
      },
    }, 
    methods: {
      show() {
        this.customModalButtons[1].hidden === true ? this.toggleButtons() : false;
        this.$bvModal.show('createTemplate');
      },
      close() {
        this.$bvModal.hide('createTemplate');
        this.showWarning = false;
      },
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
            this.existingAssetId = error.response.data.id;
            this.existingAssetName = error.response.data.templateName;
            this.existingAssetOwnerId = error.response.data.owner_id;
            this.toggleButtons();
          } else {
            const message = error.response.data.error;
            ProcessMaker.alert(this.$t(message), "danger");
          }
        });
      },  
      updateTemplate() {   
        this.templateData.existingAssetId = this.existingAssetId;
        this.templateData.asset_id = this.assetId;

        ProcessMaker.apiClient.put("template/" + this.assetType + "/" + this.existingAssetId + "/update", this.templateData)
        .then(response => {
          ProcessMaker.alert( this.$t("Template successfully updated"),"success");
          this.close();
        }).catch(error => {
          this.errors = error.response.data;
          if (this.errors.hasOwnProperty('errors')) {
            this.errors = this.errors.errors;
          } else if (_.includes(this.errors.name, 'The template name must be unique.')) {
            this.showWarning = true;
            this.existingAssetId = error.response.data.id;
            this.existingAssetName = error.response.data.assetName;
            this.existingAssetOwner = error.response.data?.owner_id;
            this.toggleButtons();
          }
        });
      },
      toggleButtons() {
        if (this.assetType === 'process' || this.assetType === 'screen' && this.existingAssetOwnerId === this.currentUserId) {
          this.customModalButtons[2].hidden = !this.customModalButtons[2].hidden;
        }

        this.customModalButtons[1].hidden = !this.customModalButtons[1].hidden;
        this.customModalButtons[3].hidden = !this.customModalButtons[3].hidden;
      },
      validateFormData(errors) {
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
      },
      updateTemplateData(data, errors) {
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
