<template>
  <div>
    <b-button
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
import { FormErrorsMixin, Modal, Required } from "SharedComponents";

const channel = new BroadcastChannel("assetCreation");

export default {
  components: {
    Modal,
    Required,
  },
  mixins: [FormErrorsMixin],
  props: ["countCategories", "types"],
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
          channel.postMessage({
            assetType: "screen",
            id: data.id,
          });
          window.location = `/designer/screen-builder/${data.id}/edit`;
        })
        .catch((error) => {
          this.disabled = false;
          if (error.response.status && error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
  },
};
</script>
