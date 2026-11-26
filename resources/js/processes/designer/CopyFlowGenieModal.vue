<template>
  <div>
    <modal
      id="copyFlowGenieDesigner"
      size="md"
      :title="modalTitle"
      :set-custom-buttons="true"
      :custom-buttons="customModalButtons"
      :required-in-footer="false"
      @duplicateAsset="onDuplicate"
      @close="onClose"
      @hidden="onClose"
    >
      <Required />
      <b-form-group
        required
        :label="$t('Name')"
        :invalid-feedback="errorMessage('name', errors)"
        :state="errorState('name', errors)"
        :description="errorState('name', errors) === false ? '' : $t('The Genie name must be unique.')"
      >
        <b-form-input
          v-model="formData.name"
          required
          autofocus
          autocomplete="off"
          :state="errorState('name', errors)"
          name="name"
        />
      </b-form-group>
      <b-form-group
        required
        :label="$t('Description')"
        :invalid-feedback="errorMessage('description', errors)"
        :state="errorState('description', errors)"
      >
        <b-form-textarea
          v-model="formData.description"
          autocomplete="off"
          :state="errorState('description', errors)"
          name="description"
        />
      </b-form-group>
      <category-select
        v-model="formData.flow_genie_category_id"
        :label="$t('Category')"
        api-get="package-ai/flow_genie_categories"
        api-list="package-ai/flow_genie_categories"
        name="category"
        :errors="errors.flow_genie_category_id"
      />
      <project-select
        :label="$t('Project')"
        api-get="projects"
        api-list="projects"
        v-model="formData.projects"
        :errors="errors.projects"
      />
    </modal>
  </div>
</template>

<script>
import { FormErrorsMixin, Required, Modal, CategorySelect, ProjectSelect } from "SharedComponents";

export default {
  components: {
    Modal,
    Required,
    CategorySelect,
    ProjectSelect,
  },
  mixins: [FormErrorsMixin],
  props: {
    assetType: {
      type: String,
      default: "Flow Genie",
    },
    assetName: {
      type: String,
      default: "",
    },
    assetData: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      autoValidate: false,
      errors: {},
      customModalButtons: [
        {
          content: this.$t("Cancel"),
          action: "close",
          variant: "outline-secondary",
          disabled: false,
          hidden: false,
        },
        {
          content: this.$t("Duplicate"),
          action: "duplicateAsset",
          variant: "primary",
          disabled: false,
          hidden: false,
        },
      ],
      formData: {
        id: null,
        name: null,
        description: null,
        projects: "",
        flow_genie_category_id: null,
        config: null,
      },
    };
  },
  computed: {
    modalTitle() {
      return `${this.$t("Copy of")} ${this.assetType}: ${this.assetName}`;
    },
  },
  watch: {
    formData: {
      handler() {
        if (this.autoValidate) {
          this.validateData();
        }
      },
      deep: true,
    },
  },
  methods: {
    show() {
      this.initializeFormData();
      this.$bvModal.show("copyFlowGenieDesigner");
    },
    initializeFormData() {
      this.formData = {
        id: this.assetData.id,
        name: `${this.assetName || this.assetData.name || ""} ${this.$t("Copy")}`.trim(),
        description: this.assetData.description || "",
        flow_genie_category_id: this.assetData.flow_genie_category_id,
        config: this.assetData.config,
        projects: this.parseProjects(this.assetData.projects),
      };
    },
    parseProjects(projects) {
      if (!projects) {
        return "";
      }
      let parsed = projects;
      if (typeof projects === "string") {
        try {
          parsed = JSON.parse(projects);
        } catch (e) {
          return projects;
        }
      }
      if (Array.isArray(parsed)) {
        return parsed
          .map((project) => (typeof project === "object" ? project.id : project))
          .filter((id) => id !== null && id !== undefined && id !== "")
          .join(",");
      }
      return "";
    },
    resetFormData() {
      this.formData = {
        id: null,
        name: null,
        description: null,
        projects: "",
        flow_genie_category_id: null,
        config: null,
      };
    },
    resetErrors() {
      this.errors = {
        name: null,
        description: null,
        flow_genie_category_id: null,
        projects: null,
      };
    },
    onClose() {
      this.$bvModal.hide("copyFlowGenieDesigner");
      this.resetFormData();
      this.resetErrors();
      this.autoValidate = false;
      this.customModalButtons[1].disabled = false;
    },
    onDuplicate() {
      this.autoValidate = true;
      this.validateData();
      if (!_.isEmpty(this.errors.name) || !_.isEmpty(this.errors.description)) {
        return;
      }

      const sourceId = this.formData.id || this.assetData.id;
      window.ProcessMaker.apiClient
        .put(`package-ai/flow_genies/${sourceId}/duplicate`, this.formData)
        .then(() => {
          ProcessMaker.alert(this.$t("The Genie was duplicated."), "success");
          this.onClose();
          this.$emit("reload");
        })
        .catch((error) => {
          if (error.response?.status === 422) {
            this.errors = error.response.data.errors;
            Object.keys(this.errors).forEach((key) => {
              this.errors[key] = this.errors[key].map((text) => {
                const replaced = text.replace("Authtype", this.$t("Authentication Method"));
                return this.$t(replaced);
              });
            });
          }
        });
    },
    validateData() {
      const { name, description } = this.formData;

      this.resetErrors();

      if (_.isEmpty(name)) {
        this.errors.name = ["The name is a required field."];
      } else if (name.length > 255) {
        this.errors.name = ["Name must be less than 255 characters."];
      }

      if (_.isEmpty(description)) {
        this.errors.description = ["The description is a required field."];
      }

      this.customModalButtons[1].disabled = !(
        _.isEmpty(this.errors.name) && _.isEmpty(this.errors.description)
      );
    },
  },
};
</script>
