<template>
  <div>
    <b-form-group
      :label="$t('Connection Type')"
      :description="
        formDescription(
          'Specifies the service, server, or protocol for storing and retrieving Microsoft Excel files.',
          'connection_type',
          errors
        )
      "
      :invalid-feedback="errorMessage('connection_type', errors)"
      :state="errorState('connection_type', errors)"
    >
      <b-form-input
        v-model="config.connection_type"
        autofocus
        autocomplete="off"
        :state="errorState('connection_type', errors)"
        name="connection_type"
      />
    </b-form-group>

    <b-form-group
      :label="$t('URI')"
      :description="
        formDescription(
          'The Uniform Resource Identifier (URI) for the Excel resource location.',
          'uri',
          errors
        )
      "
      :invalid-feedback="errorMessage('uri', errors)"
      :state="errorState('uri', errors)"
    >
      <b-form-input
        v-model="config.uri"
        autocomplete="off"
        :state="errorState('uri', errors)"
        name="uri"
      />
    </b-form-group>

    <b-form-group
      v-if="config.connection_type.toLowerCase() === 'dropbox'"
      :label="$t('Access Token')"
      :description="
        formDescription(
          'The access token can be used to access your account via the API.',
          'o_auth_access_token',
          errors
        )
      "
      :invalid-feedback="errorMessage('o_auth_access_token', errors)"
      :state="errorState('o_auth_access_token', errors)"
    >
      <b-form-input
        v-model="config.o_auth_access_token"
        autocomplete="off"
        name="o_auth_access_token"
        :state="errorState('o_auth_access_token', errors)"
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
      config: {
        connection_type: "",
        uri: "",
        o_auth_access_token: "",
      },
      errors: {},
    };
  },
  watch: {
    config: {
      handler() {
        this.$emit("updateFormData", this.config);
      },
      deep: true,
    },
  },
  mounted() {
    this.config.connection_type = this.formData?.connection_type ?? "";
    this.config.uri = this.formData?.uri ?? "";
    this.config.o_auth_access_token = this.formData?.o_auth_access_token ?? "";
  },
};
</script>
