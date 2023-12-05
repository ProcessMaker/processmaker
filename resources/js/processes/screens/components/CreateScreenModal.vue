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
      @hidden="onClose"
      @ok.prevent="onSubmit"
    >
      <template v-if="countCategories">
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
        <b-form-group
          :invalid-feedback="errorMessage('type', errors)"
          :label="$t('Type')"
          :state="errorState('type', errors)"
          required
        >
          <b-form-select
            v-model="formData.type"
            :options="screenTypes"
            :state="errorState('type', errors)"
            :disabled="copyAssetMode"
            name="type"
            required
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
    </modal>
  </div>
</template>

<script>
import FormErrorsMixin from "../../../components/shared/FormErrorsMixin";
import Modal from "../../../components/shared/Modal.vue";
import Required from "../../../components/shared/Required.vue";
import ProjectSelect from "../../../components/shared/ProjectSelect.vue";
import {
  isQuickCreate as isQuickCreateFunc,
  screenSelectId,
} from "../../../utils/isQuickCreate";
import { filterScreenType } from "../../../utils/filterScreenType";

const channel = new BroadcastChannel("assetCreation");

export default {
  components: {
    Modal,
    Required,
    ProjectSelect,
  },
  mixins: [FormErrorsMixin],
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
      return this.$t("Create Screen");
    },
  },
  mounted() {
    this.resetFormData();
    this.resetErrors();
    if (this.isQuickCreate === true) {
      this.screenTypes = filterScreenType() ?? this.types;
      // in any case the screenType if the only one, default to the first value
      if (Object.keys(this.screenTypes).length === 1) this.formData.type = Object.keys(this.screenTypes)[0];
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
        type: "",
        description: null,
        projects: [],
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
    },
    close() {
      this.$bvModal.hide("createScreen");
      this.disabled = false;
      this.$emit("reload");
    },
    onSubmit() {
      this.resetErrors();
      // single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      ProcessMaker.apiClient
        .post("screens", this.formData)
        .then(({ data }) => {
          ProcessMaker.alert(this.$t("The screen was created."), "success");

          const url = `/designer/screen-builder/${data.id}/edit`;

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
        })
        .catch((error) => {
          this.disabled = false;
          if (error?.response?.status && error?.response?.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
  },
};
</script>
