<template>
  <div>
    <b-button
      v-if="!hideAddBtn && !callFromAiModeler"
      ref="createScreenModalBtn"
      v-b-modal.createScreen
      :aria-label="$t('Create Screen')"
      class="mb-3 mb-md-0 ml-md-2"
    >
      <i class="fas fa-plus" /> {{ $t("Screen") }}
    </b-button>
    <modal
      id="createScreen"
      :ok-disabled="disabled"
      :title="modalSetUp"
      :subtitle="subtitle"
      :hide-footer="true"
      size="xl"
      @hidden="onClose"
      @ok.prevent="onSubmit"
    >
      <b-row>
        <b-col
          cols="7"
          class="type-style-col"
        >
          <div v-if="!showTemplatePreview">
            <screen-type-dropdown
              v-model="formData.type"
              :copy-asset-mode="copyAssetMode"
              :screen-types="screenTypes"
              :hide-description="false"
              data-cy="screen-type-dropdown"
            />
            <div class="template-type-label pt-4">
              <p>{{ templateTypeLabel }}</p>
            </div>
            <screen-template-options
              data-cy="screen-template-options"
              :selected-screen-type="formData.type ? formData.type : 'FORM'"
              @show-template-preview="showPreview"
              @selected-template="handleSelectedTemplate"
              @selected-default-template="handleSelectedDefaultTemplate"
              @default-template-type-changed="handleDefaultTemplateType"
            />
          </div>
          <preview-template
            v-if="showTemplatePreview"
            :template="selectedTemplate"
            @hide-template-preview="hidePreview"
            @template-options-selected="handleSelectedTemplateOptions"
          />
        </b-col>
        <b-col
          cols="5"
          class="form-style-col d-flex flex-column pb-4"
        >
          <template v-if="countCategories">
            <div>
              <required />
              <b-form-group
                :description="
                  formDescription('The screen name must be unique.', 'title', errors)
                "
                :invalid-feedback="errorMessage('title', errors)"
                :label="$t('Name')"
                :state="errorState('title', errors)"
                required
              >
                <b-form-input
                  v-model="formData.title"
                  :state="errorState('title', errors)"
                  autocomplete="off"
                  autofocus
                  name="title"
                  required
                />
              </b-form-group>
              <b-form-group
                :invalid-feedback="errorMessage('description', errors)"
                :label="$t('Description')"
                :state="errorState('description', errors)"
                required
              >
                <b-form-textarea
                  v-model="formData.description"
                  :state="errorState('description', errors)"
                  autocomplete="off"
                  name="description"
                  required
                  rows="3"
                />
              </b-form-group>
              <category-select
                v-model="formData.screen_category_id"
                :errors="errors.screen_category_id"
                :label="$t('Category')"
                api-get="screen_categories"
                api-list="screen_categories"
                name="category"
              />
              <project-select
                v-if="isProjectsInstalled"
                v-model="formData.projects"
                :errors="errors.projects"
                :project-id="projectId"
                :label="$t('Project')"
                :required="isProjectSelectionRequired"
                api-get="projects"
                api-list="projects"
              />
            </div>
            <div class="w-100 m-0 d-flex mt-auto">
              <button
                type="button"
                class="btn btn-outline-secondary ml-auto"
                @click="close"
              >
                {{ $t('Cancel') }}
              </button>
              <a
                class="btn btn-secondary ml-3"
                @click="onSubmit"
              >
                {{ $t('Save') }}
              </a>
            </div>
          </template>
          <template v-else>
            <div>{{ $t("Categories are required to create a screen") }}</div>
            <a
              class="btn btn-primary container mt-2"
              href="/designer/screens/categories"
            >
              {{ $t("Add Category") }}
            </a>
          </template>
        </b-col>
      </b-row>
    </modal>
  </div>
</template>

<script>
import FormErrorsMixin from "../../../components/shared/FormErrorsMixin";
import Modal from "../../../components/shared/Modal.vue";
import Required from "../../../components/shared/Required.vue";
import ProjectSelect from "../../../components/shared/ProjectSelect.vue";
import ScreenTypeDropdown from "./ScreenTypeDropdown.vue";
import ScreenTemplateOptions from "./ScreenTemplateOptions.vue";
import {
  isQuickCreate as isQuickCreateFunc,
  screenSelectId,
} from "../../../utils/isQuickCreate";
import { filterScreenType } from "../../../utils/filterScreenType";
import AssetRedirectMixin from "../../../components/shared/AssetRedirectMixin";
import PreviewTemplate from "../../../components/templates/PreviewTemplate.vue";

const channel = new BroadcastChannel("assetCreation");

export default {
  components: {
    Modal,
    Required,
    ProjectSelect,
    ScreenTypeDropdown,
    ScreenTemplateOptions,
    PreviewTemplate,
  },
  mixins: [FormErrorsMixin, AssetRedirectMixin],
  props: [
    "countCategories",
    "types",
    "isProjectsInstalled",
    "hideAddBtn",
    "copyAssetMode",
    "projectAsset",
    "assetName",
    "callFromAiModeler",
    "isProjectSelectionRequired",
    "projectId",
    "assetData",
  ],
  data() {
    return {
      formData: {},
      errors: {
        title: null,
        type: null,
        description: null,
        category: null,
      },
      screenTypes: this.types,
      disabled: false,
      isQuickCreate: isQuickCreateFunc(),
      screenSelectId: screenSelectId(),
      showTemplatePreview: false,
      selectedTemplate: null,
    };
  },
  computed: {
    modalSetUp() {
      if (this.copyAssetMode) {
        this.formData = this.assetData;
        this.formData.title = `${this.assetName} ${this.$t("Copy")}`;
        return this.$t("Copy of Asset");
      }
      this.formData.title = "";
      return this.$t("New Screen");
    },
    subtitle() {
      return this.$t("Select the screen type and style.");
    },
    templateTypeLabel() {
      return this.$t("Styles for the Screen Type").toUpperCase();
    },
    hasTemplateId() {
      return this.formData.templateId !== null && this.formData.templateId !== undefined; 
    },
    hasDefaultTemplateId() {
      return this.formData.defaultTemplateId !== null;
    },
    otherTemplateSelected() {
      return this.formData.selectedTemplate;
    }
  },
  mounted() {
    this.resetFormData();
    this.resetErrors();
    if (this.isQuickCreate === true) {
      this.screenTypes = filterScreenType() ?? this.types;
      // in any case the screenType if the only one, default to the first value
      const [defaultScreenType] = Object.keys(this.screenTypes);
      if (Object.keys(this.screenTypes).length === 1) {
        this.formData.type = defaultScreenType;
      }
    }
    if (this.callFromAiModeler === true) {
      this.screenTypes = this.types;
    }
  },
  methods: {
    show() {
      this.$bvModal.show("createScreen");
    },
    resetFormData() {
      this.formData = {
        title: null,
        type: null,
        description: null,
        projects: [],
        templateId: null,
        templateOptions: JSON.stringify(['CSS', 'Layout', 'Fields']),
      };
    },
    resetErrors() {
      this.errors = {
        title: null,
        type: null,
        description: null,
      };
    },
    onClose() {
      this.resetFormData();
      this.resetErrors();
      this.showTemplatePreview = false;
      this.selectedTemplate = null;
    },
    close() {
      this.$bvModal.hide("createScreen");
      this.disabled = false;
      this.onClose();
      this.$emit("reload");
    },
    onSubmit() {
      this.resetErrors();
      // single click
      if (this.disabled) {
        return;
      }
      if (this.copyAssetMode) {
        this.formData.asset_type = null;
      }
      this.disabled = true;
      if (this.otherTemplateSelected && this.hasTemplateId || this.hasDefaultTemplateId && !this.otherTemplateSelected || this.hasTemplateId) {
        this.handleCreateFromTemplate();
      } else {
        this.handleCreateFromBlank();
      }
    },
    handleCreateFromBlank() {
      ProcessMaker.apiClient
        .post("screens", this.formData)
        .then(({ data }) => {
          this.handleSuccessMessageAndRedirect(data);
        })
        .catch((error) => {
          this.disabled = false;
          if (error?.response?.status && error?.response?.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
    handleCreateFromTemplate() {
      ProcessMaker.apiClient.post(
        `template/create/screen/${this.formData.templateId}`,
        this.formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        },
      )
        .then((response) => {
          if (response.data.existingAssets) {
            // Use local storage to pass the data to the assets page.
            const stateData = {
              assets: JSON.stringify(response.data.existingAssets),
              name: this.template.name,
              responseId: response.data.id,
              request: JSON.stringify(response.data.request),
              redirectTo: null,
            };
            localStorage.setItem("templateAssetsState", JSON.stringify(stateData));
            // Redirect to the assets page.
            window.location = "/template/assets";
          } else {
            this.handleSuccessMessageAndRedirect(response.data);
          }
        })
        .catch((error) => {
          this.disabled = false;
          this.addError = error.response?.data.errors;
        });
    },
    handleSuccessMessageAndRedirect(data) {
      ProcessMaker.alert(this.$t("The screen was created."), "success");

      const url = new URL(`/designer/screen-builder/${data.id}/edit`, window.location.origin);
      this.appendProjectIdToURL(url, this.projectId);
      this.handleRedirection(url, data);
    },
    handleRedirection(url, data) {
      if (this.callFromAiModeler) {
        this.$emit("screen-created-from-modeler", url, data.id, data.title);
      } else if (this.copyAssetMode) {
        this.close();
      } else {
        if (this.isQuickCreate === true) {
          channel.postMessage({
            assetType: "screen",
            asset: data,
            screenSelectId: this.screenSelectId,
          });
        }
        window.location = url;
      }
    },
    showPreview(data) {
      this.showTemplatePreview = true;
      this.selectedTemplate = data;
      this.formData.templateId = data.template.id;
    },
    hidePreview() {
      this.showTemplatePreview = false;
      this.selectedTemplate = null;
      this.formData.templateId = null;
    },
    handleSelectedTemplate(templateId) {
      this.formData.templateId =  templateId;
      this.formData.selectedTemplate = true;
      this.formData.templateOptions = JSON.stringify(['CSS', 'Layout', 'Fields']);
    },
    handleSelectedTemplateOptions(options) {
      this.formData.templateOptions = JSON.stringify(options);
    },
    handleSelectedDefaultTemplate(templateId) {
      this.formData.defaultTemplateId = templateId;
    },
    handleDefaultTemplateType(type) {
      const isPublic = type === "Public Templates" ? 1 : 0;
      this.formData.is_public = isPublic;
    },

  },
};
</script>

<style scoped>
.type-style-col {
  background-color: #F6F9FB;
}

.form-style-col {
  background-color: #FFFFFF;
}

.template-type-label {
  font-size: 14px;
  color: #6c757d;
  font-weight: 700;
}
</style>
