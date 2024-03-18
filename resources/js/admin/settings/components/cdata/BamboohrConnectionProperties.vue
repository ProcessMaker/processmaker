<template>
  <div>
    <!-- API key input field -->
    <b-form-group
      required
      :label="$t('API Key')"
      :description="formDescription('Your BambooHR API Key.', 'APIKey', errors)"
      :invalid-feedback="errorMessage('APIKey', errors)"
      :state="errorState('APIKey', errors)"
    >
      <b-input-group>
        <b-form-input
          v-model="config.APIKey"
          required
          autofocus
          autocomplete="off"
          trim
          :type="inputType"
          :state="errorState('APIKey', errors)"
          name="APIKey"
          data-cy="api-key"
        />
        <b-input-group-append>
          <b-button
            :aria-label="$t('Toggle Show Password')"
            variant="secondary"
            @click="togglePassword"
          >
            <i
              class="fas"
              :class="icon"
            />
          </b-button>
        </b-input-group-append>
      </b-input-group>
    </b-form-group>

    <!-- Domain input field -->
    <b-form-group
      required
      :label="$t('Domain')"
      :description="
        formDescription('The Domain for the BambooHR account.', 'Domain', errors)
      "
      :invalid-feedback="errorMessage('Domain', errors)"
      :state="errorState('Domain', errors)"
    >
      <b-form-input
        v-model="config.Domain"
        required
        autofocus
        autocomplete="off"
        :state="errorState('Domain', errors)"
        name="Domain"
        data-cy="domain"
      />
    </b-form-group>
  </div>
</template>
<script>
// eslint-disable-next-line import/no-unresolved
import { FormErrorsMixin } from "SharedComponents";

export default {
  mixins: [FormErrorsMixin],
  props: {
    formData: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      errors: {},
      config: {
        APIKey: "",
        Domain: "",
      },
      inputType: "password",
    };
  },
  computed: {
    icon() {
      return this.inputType === "password" ? "fa-eye-slash" : "fa-eye";
    },
  },
  watch: {
    config: {
      deep: true,
      handler() {
        this.$emit("updateFormData", this.config);
      },
    },
  },
  mounted() {
    this.config = {
      ...this.config,
      ...this.formData,
    };
    this.$emit("updateFormData", this.config);
  },
  methods: {
    togglePassword() {
      this.inputType = this.inputType === "password" ? "text" : "password";
    },
  },
};
</script>
