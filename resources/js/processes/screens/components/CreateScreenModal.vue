<template>
  <div>
    <b-button
      v-if="!callFromAiModeler"
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
      :title="$t('Create Screen')"
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
            :options="types"
            :state="errorState('type', errors)"
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
        />
        <project-select
          v-if="isProjectsInstalled"
          :label="$t('Project')"
          api-get="projects"
          api-list="projects"
          v-model="formData.projects"
          :errors="errors.projects"
        ></project-select>
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
import { FormErrorsMixin, Modal, Required, ProjectSelect } from "SharedComponents";

const channel = new BroadcastChannel("assetCreation");

export default {
  components: {
    Modal,
    Required,
    ProjectSelect,
  },
  mixins: [FormErrorsMixin],
  props: ["countCategories", "types", "isProjectsInstalled", "callFromAiModeler"],
  data() {
    return {
      formData: {},
      errors: {
        title: null,
        type: null,
        description: null,
        category: null,
      },
      disabled: false,
    };
  },
  mounted() {
    this.resetFormData();
    this.resetErrors();
  },
  destroyed() {
    channel.close();
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
    /**
     * Check if the search params contains create=true which means is coming from the Modeler as a Quick Asset Creation
     * @returns {boolean}
     */
    isQuickCreate() {
      const searchParams = new URLSearchParams(window.location.search);
      return searchParams?.get("create") === "true";
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
          } else {
            if (this.isQuickCreate()) {
              channel.postMessage({
                assetType: "screen",
                id: data.id,
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
