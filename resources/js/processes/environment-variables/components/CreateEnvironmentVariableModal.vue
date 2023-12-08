<template>
  <div>
    <b-button
      v-b-modal.createEnvironmentVariable
      :aria-label="$t('Create Environment Variable')"
      class="mb-3 mb-md-0 ml-md-2"
    >
      <i class="fas fa-plus" /> {{ $t('Environment Variable') }}
    </b-button>
    <modal
      id="createEnvironmentVariable"
      :title="$t('Create Environment Variable')"
      :ok-disabled="disabled"
      @ok.prevent="onSubmit"
      @hidden="onClose"
    >
      <required />
      <b-form-group
        required
        :label="$t('Name')"
        :description="formDescription('The environment variable name must be unique.', 'name', errors)"
        :invalid-feedback="errorMessage('name', errors)"
        :state="errorState('name', errors)"
      >
        <b-form-input
          v-model="name"
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
          v-model="description"
          required
          autocomplete="off"
          rows="3"
          :state="errorState('description', errors)"
          name="description"
        />
      </b-form-group>
      <b-form-group
        :label="$t('Value')"
        :invalid-feedback="errorMessage('value', errors)"
        :state="errorState('value', errors)"
      >
        <b-form-textarea
          v-model="value"
          autocomplete="off"
          rows="10"
          :state="errorState('value', errors)"
          name="value"
        />
      </b-form-group>
    </modal>
  </div>
</template>

<script>
import { FormErrorsMixin, Modal, Required } from "../../../components/shared";

export default {
  components: { Modal, Required },
  mixins: [FormErrorsMixin],
  data() {
    return {
      errors: {},
      name: "",
      description: "",
      value: "",
      disabled: false,
    };
  },
  methods: {
    onClose() {
      this.name = "";
      this.description = "";
      this.value = "";
      this.errors = {};
    },
    onSubmit() {
      this.errors = {};
      // single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      ProcessMaker.apiClient.post("environment_variables", {
        name: this.name,
        description: this.description,
        value: this.value,
      })
        .then((response) => {
          ProcessMaker.alert(this.$t("The environment variable was created."), "success");
          window.location = "/designer/environment-variables";
        })
        .catch((error) => {
          this.disabled = false;
          if (error.response.status === 422) {
            this.errors = error.response.data.errors;
          }
        });
    },
  },
};
</script>
