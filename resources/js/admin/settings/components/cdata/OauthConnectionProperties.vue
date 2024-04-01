<template>
  <div>
    <b-form-group
      required
      :label="$t('Client ID')"
      :description="
        formDescription(
          'The client ID assigned when you register your application.',
          'client_id',
          errors
        )
      "
      :invalid-feedback="errorMessage('client_id', errors)"
      :state="errorState('client_id', errors)"
    >
      <b-form-input
        v-model="config.client_id"
        required
        autofocus
        autocomplete="off"
        :state="errorState('client_id', errors)"
        name="client_id"
        data-cy="client_id"
      />
    </b-form-group>

    <b-form-group
      required
      :label="$t('Client Secret')"
      :description="
        formDescription(
          'The client secret assigned when you register your application.',
          'client_secret',
          errors
        )
      "
      :invalid-feedback="errorMessage('client_secret', errors)"
      :state="errorState('client_secret', errors)"
    >
      <b-input-group>
        <b-form-input
          v-model="config.client_secret"
          required
          autofocus
          autocomplete="off"
          trim
          :type="inputType"
          :state="errorState('client_secret', errors)"
          name="client_secret"
          data-cy="client_secret"
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

    <b-form-group
      required
      :label="$t('Redirect URL')"
      :description="
        formDescription(
          'This value must match the callback URL you specify in your app settings.',
          'callback_url',
          errors
        )
      "
      :invalid-feedback="errorMessage('callback_url', errors)"
      :state="errorState('callback_url', errors)"
    >
      <b-input-group>
        <b-form-input
          v-model="config.callback_url"
          autofocus
          readonly
          autocomplete="off"
          :state="errorState('callback_url', errors)"
          name="callback_url"
          data-cy="callback_url"
        />
        <b-input-group-append>
          <b-button
            :aria-label="$t('Copy')"
            variant="secondary"
            @click="onCopy"
          >
            <i class="fas fa-copy" />
          </b-button>
        </b-input-group-append>
      </b-input-group>
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
      config: {
        AuthScheme: "OAuth",
        client_id: "",
        client_secret: "",
        callback_url: "",
      },
      errors: {},
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
    onCopy() {
      if (navigator.clipboard) {
        navigator.clipboard
          .writeText(this.config.callback_url)
          .then(() => {
            const message = this.$t(`Copied: ${this.config.callback_url}`);
            ProcessMaker.alert(message, "success");
          });
      } else {
        ProcessMaker.alert(this.$t("Clipboard functionality not available"), "danger");
      }
    },
    togglePassword() {
      this.inputType = this.inputType === "password" ? "text" : "password";
    },
  },
};
</script>
