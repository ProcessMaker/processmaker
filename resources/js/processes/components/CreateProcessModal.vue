<template>
  <div>
    <modal
      id="createProcess"
      :title="$t('Create Process')"
      :ok-disabled="disabled"
      @ok.prevent="onSubmit"
      @hidden="onClose"
      @shown="onShown()"
    >
      <template v-if="countCategories">
        <required />
        <b-form-group
          required
          :label="$t('Name')"
          :description="formDescription('The process name must be unique', 'name', addError)"
          :invalid-feedback="errorMessage('name', addError)"
          :state="errorState('name', addError)"
        >
          <b-form-input
            v-model="name"
            autofocus
            autocomplete="off"
            :state="errorState('name', addError)"
            name="name"
            required
          />
        </b-form-group>
        <b-form-group
          required
          :label="$t('Description')"
          :invalid-feedback="errorMessage('description', addError)"
          :state="errorState('description', addError)"
        >
          <b-form-textarea
            v-model="description"
            required
            autocomplete="off"
            rows="3"
            :state="errorState('description', addError)"
            name="description"
          />
        </b-form-group>
        <category-select
          v-model="process_category_id"
          :label="$t('Category')"
          api-get="process_categories"
          api-list="process_categories"
          :errors="addError?.process_category_id"
          name="category"
        />
        <project-select
          v-if="isProjectsInstalled"
          v-model="projects"
          :required="isProjectSelectionRequired"
          :label="$t('Project')"
          api-get="projects"
          api-list="projects"
          :errors="addError?.projects"
          name="project"
          :project-id="projectId"
        />
        <b-form-group
          :label="$t('Process Manager')"
        >
          <select-user
            v-model="manager"
            :multiple="false"
            name="process_manager_id"
          />
        </b-form-group>
        <b-form-group
          v-if="!selectedTemplate && !generativeProcessData"
          :label="$t('Upload BPMN File (optional)')"
          :invalid-feedback="errorMessage('bpmn', addError)"
          :state="errorState('bpmn', addError)"
        >
          <b-form-file
            ref="customFile"
            :browse-text="$t('Browse')"
            accept=".bpmn,.xml"
            :placeholder="selectedFile"
            :state="errorState('bpmn', addError)"
            @change="onFileChange"
          />
        </b-form-group>
      </template>
      <template v-else>
        <div>{{ $t('Categories are required to create a process') }}</div>
        <a
          href="/designer/processes/categories"
          class="btn btn-primary container mt-2"
        >
          {{ $t('Add Category') }}
        </a>
      </template>
    </modal>
  </div>
</template>

<script>
import Required from "../../components/shared/Required.vue";
import Modal from "../../components/shared/Modal.vue";
import FormErrorsMixin from "../../components/shared/FormErrorsMixin";
import AssetRedirectMixin from "../../components/shared/AssetRedirectMixin";
import ProjectSelect from "../../components/shared/ProjectSelect.vue";

export default {
  components: { Modal, Required, ProjectSelect },
  mixins: [ FormErrorsMixin, AssetRedirectMixin ],
  props: [
    "countCategories",
    "blankTemplate",
    "selectedTemplate",
    "templateData",
    "generativeProcessData",
    "isProjectsInstalled",
    "categoryType",
    "callFromAiModeler",
    "isProjectSelectionRequired",
    "projectId",
    "isAiGenerated"
  ],
  data: function() {
    return {
      showModal: false,
      name: "",
      selectedFile: "",
      categoryOptions: "",
      description: "",
      process_category_id: "",
      category_type_id: "",
      projects: [],
      projectCategories: false,
      template_version: null,
      addError: {},
      status: "",
      bpmn: "",
      disabled: false,
      customModalButtons: [
          {"content": "Cancel", "action": "hide()", "variant": "outline-secondary", "disabled": false, "hidden": false},
          {"content": "Create", "action": "createTemplate", "variant": "primary", "disabled": false, "hidden": false},
      ],
      manager: "",
    };
  },
  watch: {
    selectedTemplate() {
      if (this.selectedTemplate) {
        this.name = this.templateData.name;
        this.description = this.templateData.description;
        if (this.categoryType) {
          this.category_type_id = this.templateData.category_id;
        } else {
          this.process_category_id = this.templateData.category_id;
        }
        this.template_version = this.templateData.version;
      }
    },
    manager() {
      if (!this.manager) {
        this.manager = "";
      }
    },
  },
  mounted() {
    const searchParams = new URLSearchParams(window.location.search);
    if (searchParams.size > 0 && searchParams.get("create") === "true") {
      this.show();
    }
  },
  methods: {
    onShown() {
      if (this.generativeProcessData && !this.generativeProcessData.choices?.length) {
        this.name = this.generativeProcessData.process_title;
        this.description = this.generativeProcessData.process_description;
      }
      if (this.generativeProcessData && this.generativeProcessData.choices?.length > 0) {
        const currentChoice = this.generativeProcessData.choices[this.generativeProcessData.currentChoice];
        this.name = currentChoice.process_title;
        this.description = currentChoice.process_description;
      }
    },
    show() {
      this.$bvModal.show("createProcess");
    },
    browse() {
      this.$refs.customFile.click();
    },
    onFileChange(e) {
      const files = e.target.files || e.dataTransfer.files;

      if (!files.length) {
        return;
      }

      [this.file] = files;
      this.selectedFile = this.file.name;
    },
    onClose() {
      this.name = "";
      this.description = "";
      this.process_category_id = "";
      this.category_type_id = "";
      this.projects = [];
      this.status = "";
      this.addError = {};
      this.selectedFile = "";
      this.file = null;
      this.manager = "";
      this.$emit("resetModal");
    },
    onSubmit() {
      this.errors = {
        name: null,
        description: null,
        process_category_id: null,
        status: null,
      };
      if (this.process_category_id === "") {
        this.addError = { process_category_id: ["{{__('The category field is required.')}}"] };
        return;
      }
      // single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;

      const formData = new FormData();
      formData.append("name", this.name);
      formData.append("description", this.description);
      formData.append("process_category_id", this.process_category_id);
      formData.append("projects", this.projects);
      formData.append("manager_id", this.manager.id);
      if (this.file) {
        formData.append("file", this.file);
      }
      if (this.selectedTemplate) {
        formData.append("version", this.template_version);
        this.handleCreateFromTemplate(this.templateData.id, formData);
      } else {
        if (this.generativeProcessData && !this.generativeProcessData.choices?.length) {
          formData.append("bpmn", this.generativeProcessData.bpmn);
        }
        if (this.generativeProcessData && this.generativeProcessData.choices?.length > 0) {
          formData.append("bpmn", this.generativeProcessData.choices[this.generativeProcessData.currentChoice].bpmn);
        }
        this.handleCreateBlank(formData);
      }
    },
    handleCreateFromTemplate(id, formData) {
      ProcessMaker.apiClient.post(
        `template/create/process/${id}`,
        formData,
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
              name: this.templateData.name,
              responseId: response.data.id,
              request: JSON.stringify(response.data.request),
              redirectTo: null,
            };
            localStorage.setItem("templateAssetsState", JSON.stringify(stateData));
            // Redirect to the assets page.
            window.location = "/template/assets";
          } else {
            ProcessMaker.alert(this.$t("The process was created."), "success");
            window.location = `/modeler/${response.data.processId}`;
          }
        })
        .catch((error) => {
          this.disabled = false;
          this.addError = error.response.data.errors;
        });
    },
    handleCreateBlank(formData) {
      // Add default alternative
      formData.append("alternative", "A");

      ProcessMaker.apiClient.post(
        "/processes",
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        },
      )
        .then(response => {
          if (this.generativeProcessData) {
            this.$emit("clear-ai-history");
          }

          ProcessMaker.alert(this.$t("The process was created."), "success");

          if (this.callFromAiModeler) {
            const url = `/package-ai/processes/create/${response.data.id}`;
            this.$emit("process-created-from-modeler", url, response.data.id, response.data.name);
          } else if (this.projectId) {
            const url = new URL(`/modeler/${response.data.id}`, window.location.origin);
            this.appendProjectIdToURL(url, this.projectId);
            this.handleRedirection(url, response.data);
          } else {
            window.location =
              this.isAiGenerated
                ? "/modeler/" + response.data.id + "?isAiGenerated=true"
                : "/modeler/" + response.data.id;
          }
        })
        .catch((error) => {
          this.disabled = false;
          this.addError = error.response.data.errors;
        });
    },
    handleRedirection(url, data) {
      if (this.callFromAiModeler) {
        const redirectUrl = `/package-ai/processes/create/${data.id}`;
        this.$emit("process-created-from-modeler", redirectUrl, data.id, data.name);
      } else {
        window.location.href = url;
      }
    },
  },
};
</script>

<style scoped>
  .search-column {
    border-right: 1px solid #b6bfc6;
  }
</style>
