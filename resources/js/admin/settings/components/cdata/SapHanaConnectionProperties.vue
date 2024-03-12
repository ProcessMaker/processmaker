<template>
  <div>
    <!-- Server input field -->
    <b-form-group
      required
      :label="$t('Server')"
      :description="
        formDescription(
          'The name of the server running SAP HANA database.',
          'server',
          errors
        )
      "
      :invalid-feedback="errorMessage('server', errors)"
      :state="errorState('server', errors)"
    >
      <b-form-input
        v-model="config.server"
        type="text"
        required
        trim
        name="server"
        data-cy="server"
        :state="errorState('server', errors)"
      />
    </b-form-group>

    <!-- Port input field -->
    <b-form-group
      required
      :label="$t('Port')"
      :description="formDescription('The port of the SAP HANA database.', 'port', errors)"
      :invalid-feedback="errorMessage('port', errors)"
      :state="errorState('port', errors)"
    >
      <b-form-input
        v-model="config.port"
        required
        name="port"
        data-cy="port"
        :state="errorState('port', errors)"
      />
    </b-form-group>

    <!-- Database input field -->
    <b-form-group
      required
      :label="$t('Database')"
      :description="
        formDescription('The name of the SAP HANA database.', 'database', errors)
      "
      :invalid-feedback="errorMessage('database', errors)"
      :state="errorState('database', errors)"
    >
      <b-form-input
        v-model="config.database"
        required
        name="database"
        data-cy="database"
        :state="errorState('database', errors)"
      />
    </b-form-group>

    <!-- UseSSL switch field -->
    <b-form-group
      :label="$t('Use SSL')"
      :description="
        formDescription('This field sets whether SSL is enabled.', 'UseSSL', errors)
      "
      :invalid-feedback="errorMessage('UseSSL', errors)"
      :state="errorState('UseSSL', errors)"
    >
      <b-form-checkbox
        v-model="config.UseSSL"
        name="use_ssl"
        data-cy="use_ssl"
        switch
        :state="errorState('UseSSL', errors)"
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
        server: "",
        port: "",
        database: "",
        UseSSL: true,
      },
    };
  },
  computed: {},
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
  methods: {},
};
</script>
