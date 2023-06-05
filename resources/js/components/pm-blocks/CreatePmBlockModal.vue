<template>
  <div>
    <modal
      id="createPmBlock"
      :title="title" 
      @savePmBlock="savePmBlock"
      @close="close"
      :setCustomButtons="true"
      :customButtons="customModalButtons"
      size="md"
    >
      <template>
        <b-row align-v="start">
          <b-col>
            <required></required>
            <div v-html="descriptionText" class="my-3"></div>
            <p class="mb-3" v-if="showWarning"><i class="fas fa-exclamation-triangle text-warning"></i> {{ assetExistsError }}</p>
            <b-form-group
              required
              :label="$t('PM Block Name')"
              :description="formDescription('The PM Block name must be unique.', 'name', errors)"
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
                v-model="pm_block_category_id"
                :label="$t('Category')"
                api-get="pm-blocks-categories"
                api-list="pm-blocks-categories"
                name="category"
                :errors="addError.pm_block_category_id"
              />

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
      pm_block_category_id: "",
      addError: {},
      showModal: false,
      disabled: true,
      showWarning: false,
      existingAssetId: null,
      existingAssetName: "",
      customModalButtons: [
        {"content": "Cancel", "action": "close", "variant": "outline-secondary", "disabled": false, "hidden": false},
        {"content": "Publish", "action": "savePmBlock", "variant": "primary", "disabled": true, "hidden": false},
      ],
    }
  },
    computed: {
      title() {
        return this.$t('Publish PM Block');
      },
      assetExistsError() {
          const capFirst = this.assetType[0].toUpperCase();
          const reset =  this.assetType.slice(1);
          const asset = capFirst + reset;
          return asset + ' PM Block with the same name already exists';
      },
      descriptionText() {
        return this.$t('This will create a PM Block based on the {{assetName}} {{assetType}}', {assetName: this.assetName, assetType: this.assetType});
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
        this.$bvModal.show('createPmBlock');
      },
      close() {
        this.$bvModal.hide('createPmBlock');
        this.clear();
        this.errors = {};
      },
      clear() {
        this.name = "";
        this.description = "";
        this.pm_block_category_id = "";
        this.showWarning = false;
      },
      savePmBlock() {
        let formData = new FormData();
        formData.append("process_id", this.assetId);
        formData.append("name", this.name);
        formData.append("description", this.description);
        formData.append("user_id", this.currentUserId);
        formData.append("pm_block_category_id", this.pm_block_category_id);
        console.log(formData);
        ProcessMaker.apiClient.post("pm-blocks/store", formData)
        .then(response => {
          ProcessMaker.alert(this.$t("PM Block successfully created"), "success");
          this.close();
        }).catch(error => {
          this.errors = error.response.data;
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
            if (this.showWarning) {
              this.customModalButtons[2].disabled = true;
              this.customModalButtons[3].disabled = false;  
            } else {
              this.customModalButtons[2].disabled = false;
              this.customModalButtons[3].disabled = true;
            }
          }
        }
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
