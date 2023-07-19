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

            <b-form-group
              required
              :label="$t('Icon')"
              :description="formDescription('Choose an icon for this PM Block.', 'icon', errors)"
            >
              <icon-selector
                v-model="meta"
                name="icon"
                @error="fileError"
                @input="clearFileError"
              />
              <small v-if="fileUploadError === true" class="text-danger">
                {{ $t('The custom icon file is too large. File size must be less than 2KB.') }}
              </small>
            </b-form-group>

            <b-form-group
              v-if="!meta.isImported"
              required
              :label="$t('Author')"
              :description="formDescription('Enter the name of the PM Block author.', 'author', errors)"
              :invalid-feedback="errorMessage('author', errors)"
              :state="errorState('author', errors)"
            >
              <b-form-input
                required
                autofocus
                v-model="meta.author"
                autocomplete="off"
                :state="errorState('author', errors)"
                name="author"
              ></b-form-input>
            </b-form-group>

            <b-form-group
              v-else
              :label="$t('Author')"
              :description="formDescription('Enter the name of the PM Block author.', 'author', errors)"
              :invalid-feedback="errorMessage('author', errors)"
              :state="errorState('author', errors)"
            >
              <b-form-input
                disabled
                autofocus
                v-model="meta.author"
                autocomplete="off"
                :state="errorState('author', errors)"
                name="author"
              ></b-form-input>
            </b-form-group>

            <b-form-group
              :label="$t('Version')"
              :description="formDescription('Enter the version of this PM Block.', 'version', errors)"
              :invalid-feedback="errorMessage('version', errors)"
              :state="errorState('version', errors)"
            >
              <b-form-input
                v-if="!meta.isImported"
                autofocus
                v-model="meta.version"
                autocomplete="off"
                :state="errorState('version', errors)"
                name="version"
              ></b-form-input>
              <b-form-input
                v-else
                disabled
                autofocus
                v-model="meta.version"
                autocomplete="off"
                :state="errorState('version', errors)"
                name="version"
              ></b-form-input>
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
import { FormErrorsMixin, Modal, Required, IconSelector } from "SharedComponents";
import CategorySelect from "../../processes/categories/components/CategorySelect.vue";

export default {
  components: { Modal, Required, CategorySelect, IconSelector },
  mixins: [FormErrorsMixin],
  props: ["assetName", "assetType", "assetId", "currentUserId"],
  data() {
    return {
      errors: {},
      name: "",
      description: "",
      pm_block_category_id: "",
      meta: {
        icon: "cube",
        file: null,
        author: "",
        version: "",
        isImported: false,
      },
      addError: {},
      fileUploadError: false,
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
      descriptionText() {
        return this.$t('This will create a PM Block based on the {{assetName}} Process', {assetName: this.assetName});
      }
    },
    watch: {
      description() {
        this.validateDescription();
      },
      name(newValue, oldValue) {
        this.validateName(newValue, oldValue);
      },
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
        this.meta.icon = "cube";
        this.meta.file = "";
        this.meta.author = "";
        this.meta.version = "";
      },
      savePmBlock() {
        let formData = new FormData();
        formData.append("asset_id", this.assetId);
        formData.append("name", this.name);
        formData.append("description", this.description);
        formData.append("user_id", this.currentUserId);
        formData.append("pm_block_category_id", this.pm_block_category_id);
        formData.append("meta", JSON.stringify(this.meta));
        ProcessMaker.apiClient.post("pm-blocks", formData)
        .then(response => {
          ProcessMaker.alert(this.$t("PM Block successfully created"), "success");
          window.setTimeout(() => {
            window.location.href = `/designer/pm-blocks/${response.data.id}/edit/`;
          }, 1000)
          this.close();
        }).catch(error => {
          this.errors = error.response?.data;
          if (this.errors.hasOwnProperty('errors')) {
            this.errors = this.errors?.errors;
          } else {
            const message = error.response?.data?.error;
            ProcessMaker.alert(this.$t(message), "danger");
          }
        });
      },  
      toggleButtons() {
        this.customModalButtons[1].hidden = !this.customModalButtons[1].hidden;
      },
      validateDescription() {
        if (!_.isEmpty(this.description) && !_.isEmpty(this.name)) {
          this.customModalButtons[1].disabled = false;
        } else {
          this.customModalButtons[1].disabled = true;
        }
        if (this.description.length > 255) {
          this.errors.description = ['Description must be less than 255 characters.'];
          this.customModalButtons[1].disabled = true;
        } else {
          this.errors.description = null;
        }
      },
      validateName(newName, oldName) {
        if (!_.isEmpty(this.name) && !_.isEmpty(this.description)) {
          this.customModalButtons[1].disabled = false;         
        }  
        if (this.name.length > 255) {
          this.errors.name = ['Name must be less than 255 characters.'];
          this.customModalButtons[1].disabled = true;
        } else {
          this.errors.name = null;
        }
      },
      fileError() {
        this.fileUploadError = true;
      },
      clearFileError() {
        this.fileUploadError = false;
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
