<template>
  <div>
    <b-form-group
      :label="$t('Use Sandbox')"
      :description="
        formDescription(
          'Set to true if you are using sandbox account.',
          'use_sandbox',
          errors
        )
      "
      :invalid-feedback="errorMessage('use_sandbox', errors)"
      :state="errorState('use_sandbox', errors)"
    >
      <b-form-checkbox
        v-model="config.use_sandbox"
        name="use_sandbox"
        data-cy="use_sandbox"
        switch
        :state="errorState('use_sandbox', errors)"
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
        use_sandbox: true,
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
    this.config = {
      ...this.config,
      ...this.formData,
    };

    // Emit the updateFormData event after assigning values.
    this.$emit("updateFormData", this.config);
  },
};
</script>
