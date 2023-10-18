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
        autofocus
        autocomplete="off"
        :state="errorState('uri', errors)"
        name="uri"
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
    this.config.connection_type = this.formData?.connection_type;
    this.config.uri = this.formData?.uri;
  },
};
</script>
